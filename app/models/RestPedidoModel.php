<?php
class RestPedidoModel extends BaseModel
{
    protected string $table = 'rest_pedidos';

    public function generarFolio(int $restauranteId): string
    {
        $count = $this->queryOne(
            "SELECT COUNT(*) AS c FROM rest_pedidos WHERE restaurante_id = ?",
            [$restauranteId]
        );
        return 'P-' . str_pad((int)($count['c'] ?? 0) + 1, 5, '0', STR_PAD_LEFT);
    }

    public function crear(array $data, array $items): int
    {
        $this->db->beginTransaction();
        try {
            $folio = $this->generarFolio($data['restaurante_id']);
            $subtotal = array_sum(array_column($items, 'subtotal'));

            $this->execute(
                "INSERT INTO rest_pedidos (restaurante_id, mesa_id, visita_id, mesero_id, folio, notas, subtotal, total)
                 VALUES (?,?,?,?,?,?,?,?)",
                [
                    $data['restaurante_id'],
                    $data['mesa_id'] ?? null,
                    $data['visita_id'] ?? null,
                    $data['mesero_id'] ?? null,
                    $folio,
                    $data['notas'] ?? null,
                    $subtotal,
                    $subtotal,
                ]
            );
            $pedidoId = (int) $this->db->lastInsertId();

            foreach ($items as $item) {
                $this->execute(
                    "INSERT INTO rest_pedido_items (pedido_id, platillo_id, cantidad, precio_unit, subtotal, notas, exclusiones, extras)
                     VALUES (?,?,?,?,?,?,?,?)",
                    [$pedidoId, $item['platillo_id'], $item['cantidad'], $item['precio_unit'], $item['subtotal'], $item['notas'] ?? null, $item['exclusiones'] ?? null, $item['extras'] ?? null]
                );
            }

            $this->db->commit();
            return $pedidoId;
        } catch (\Throwable $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    public function getConItems(int $pedidoId): ?array
    {
        $pedido = $this->queryOne(
            "SELECT p.*, m.nombre AS mesa_nombre, u.nombre AS mesero_nombre
             FROM rest_pedidos p
             LEFT JOIN rest_mesas m ON m.id = p.mesa_id
             LEFT JOIN usuarios u ON u.id = p.mesero_id
             WHERE p.id = ?",
            [$pedidoId]
        );
        if (!$pedido) return null;
        $pedido['items'] = $this->query(
            "SELECT pi.*, pl.nombre AS platillo_nombre
             FROM rest_pedido_items pi
             JOIN rest_platillos pl ON pl.id = pi.platillo_id
             WHERE pi.pedido_id = ?",
            [$pedidoId]
        );
        return $pedido;
    }

    public function getActivosPorMesa(int $mesaId): array
    {
        return $this->query(
            "SELECT p.*, m.nombre AS mesa_nombre
             FROM rest_pedidos p
             LEFT JOIN rest_mesas m ON m.id = p.mesa_id
             WHERE p.mesa_id = ? AND p.estado NOT IN ('entregado','cancelado')
             ORDER BY p.created_at DESC",
            [$mesaId]
        );
    }

    public function getKitchenQueue(int $restauranteId): array
    {
        // Formato ingredientes_raw (separador ||, campos |):
        //   codigo | nombre | tipo | cantidad | unidad | notas | es_informativo
        //   - tipo: materia_prima | guarnicion | bebida | otro
        //   - es_informativo=1 → no descuenta stock, sólo muestra al chef
        return $this->query(
            "SELECT p.id, p.folio, p.created_at, p.notas AS pedido_notas,
                    TIMESTAMPDIFF(MINUTE, p.created_at, NOW()) AS minutos_espera,
                    m.nombre AS mesa_nombre,
                    pi.id AS item_id, pi.platillo_id, pi.cantidad, pi.notas AS item_notas, pi.estado AS item_estado,
                    pi.exclusiones,
                    pl.nombre AS platillo_nombre, pl.tiempo_preparacion_min,
                    COALESCE(pl.codigo,'') AS platillo_codigo,
                    (SELECT r.notas
                       FROM rest_recetas r
                      WHERE r.platillo_id = pi.platillo_id LIMIT 1) AS instrucciones_armado,
                    (SELECT GROUP_CONCAT(
                                CONCAT_WS('|',
                                    COALESCE(ing.codigo, CONCAT('#', ing.id)),
                                    ing.nombre,
                                    COALESCE(ing.tipo, 'otro'),
                                    ri.cantidad,
                                    ri.unidad,
                                    COALESCE(ri.notas,''),
                                    COALESCE(ri.es_informativo, 0)
                                )
                                ORDER BY
                                    FIELD(COALESCE(ing.tipo,'otro'),'materia_prima','guarnicion','bebida','otro'),
                                    COALESCE(ing.codigo, ing.nombre)
                                SEPARATOR '||'
                            )
                     FROM rest_recetas r
                     JOIN rest_receta_ingredientes ri ON ri.receta_id = r.id
                     JOIN rest_ingredientes ing ON ing.id = ri.ingrediente_id
                     WHERE r.platillo_id = pi.platillo_id) AS ingredientes_raw
             FROM rest_pedidos p
             JOIN rest_pedido_items pi ON pi.pedido_id = p.id
             JOIN rest_platillos pl ON pl.id = pi.platillo_id
             LEFT JOIN rest_mesas m ON m.id = p.mesa_id
             WHERE p.restaurante_id = ?
               AND p.estado NOT IN ('cancelado', 'entregado')
               AND pi.estado IN ('pendiente','en_preparacion')
             ORDER BY p.created_at ASC, pi.id ASC",
            [$restauranteId]
        );
    }

    public function cancelarItemsActivos(int $pedidoId): void
    {
        $this->execute(
            "UPDATE rest_pedido_items SET estado = 'cancelado' WHERE pedido_id = ? AND estado IN ('pendiente', 'en_preparacion', 'listo')",
            [$pedidoId]
        );
    }

    public function cambiarEstadoPedido(int $pedidoId, string $estado): bool
    {
        // Capturar estado actual + restaurante_id para detectar transición a 'entregado'.
        $row = $this->queryOne(
            "SELECT estado, restaurante_id FROM rest_pedidos WHERE id = ?",
            [$pedidoId]
        );
        $estadoPrevio = $row['estado'] ?? null;

        $ok = $this->execute(
            "UPDATE rest_pedidos SET estado = ? WHERE id = ?",
            [$estado, $pedidoId]
        );

        // Descontar stock SOLO al transitar a 'entregado' (idempotente en el modelo).
        if ($ok && $estado === 'entregado' && $estadoPrevio !== 'entregado' && $row) {
            try {
                (new RestInventarioModel())->descontarPorOrden(
                    $pedidoId,
                    (int)$row['restaurante_id']
                );
            } catch (\Throwable $e) {
                error_log('[RestauranteStock] entrega pedido=' . $pedidoId . ' ' . $e->getMessage());
            }
        }

        return $ok;
    }

    public function cambiarEstadoItem(int $itemId, string $estado): bool
    {
        return $this->execute(
            "UPDATE rest_pedido_items SET estado = ? WHERE id = ?",
            [$estado, $itemId]
        );
    }

    // Marca como entregados todos los pedidos e items aún no entregados/cancelados
    // de una visita. Se llama al confirmar el pago: si se cobró ya se entregó.
    public function marcarVisitaEntregada(int $visitaId): void
    {
        $pedidos = $this->query(
            "SELECT id FROM rest_pedidos WHERE visita_id = ? AND estado NOT IN ('entregado','cancelado')",
            [$visitaId]
        );
        foreach ($pedidos as $p) {
            $this->cambiarEstadoPedido((int)$p['id'], 'entregado');
        }
        $this->execute(
            "UPDATE rest_pedido_items pi
             JOIN rest_pedidos p ON p.id = pi.pedido_id
             SET pi.estado = 'entregado'
             WHERE p.visita_id = ? AND pi.estado NOT IN ('entregado','cancelado')",
            [$visitaId]
        );
    }

    public function getByVisita(int $visitaId): array
    {
        return $this->query(
            "SELECT p.*, m.nombre AS mesa_nombre
             FROM rest_pedidos p
             LEFT JOIN rest_mesas m ON m.id = p.mesa_id
             WHERE p.visita_id = ? AND p.estado != 'cancelado'
             ORDER BY p.created_at ASC",
            [$visitaId]
        );
    }

    public function listar(int $restauranteId, int $page = 1, string $estado = ''): array
    {
        $where = $estado ? "AND p.estado = '$estado'" : '';
        $sql = "SELECT p.*, m.nombre AS mesa_nombre, u.nombre AS mesero_nombre
                FROM rest_pedidos p
                LEFT JOIN rest_mesas m ON m.id = p.mesa_id
                LEFT JOIN usuarios u ON u.id = p.mesero_id
                WHERE p.restaurante_id = ? $where
                ORDER BY p.created_at DESC";
        return $this->paginate($sql, [$restauranteId], $page);
    }
}
