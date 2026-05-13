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
                    "INSERT INTO rest_pedido_items (pedido_id, platillo_id, cantidad, precio_unit, subtotal, notas)
                     VALUES (?,?,?,?,?,?)",
                    [$pedidoId, $item['platillo_id'], $item['cantidad'], $item['precio_unit'], $item['subtotal'], $item['notas'] ?? null]
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
        return $this->query(
            "SELECT p.id, p.folio, p.created_at, p.notas AS pedido_notas,
                    m.nombre AS mesa_nombre,
                    pi.id AS item_id, pi.cantidad, pi.notas AS item_notas, pi.estado AS item_estado,
                    pl.nombre AS platillo_nombre, pl.tiempo_preparacion_min
             FROM rest_pedidos p
             JOIN rest_pedido_items pi ON pi.pedido_id = p.id
             JOIN rest_platillos pl ON pl.id = pi.platillo_id
             LEFT JOIN rest_mesas m ON m.id = p.mesa_id
             WHERE p.restaurante_id = ? AND pi.estado IN ('pendiente','en_preparacion')
             ORDER BY p.created_at ASC, pi.id ASC",
            [$restauranteId]
        );
    }

    public function cambiarEstadoPedido(int $pedidoId, string $estado): bool
    {
        return $this->execute(
            "UPDATE rest_pedidos SET estado = ? WHERE id = ?",
            [$estado, $pedidoId]
        );
    }

    public function cambiarEstadoItem(int $itemId, string $estado): bool
    {
        return $this->execute(
            "UPDATE rest_pedido_items SET estado = ? WHERE id = ?",
            [$estado, $itemId]
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
