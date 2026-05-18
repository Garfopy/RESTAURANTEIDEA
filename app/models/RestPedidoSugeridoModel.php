<?php
/**
 * RestPedidoSugeridoModel
 *
 * Gestiona los pedidos de reabastecimiento sugeridos por el sistema
 * de forecast. Al aprobar, crea un pedido real en la tabla `pedidos`
 * de CarniHub para que la empresa proveedora lo reciba.
 */
class RestPedidoSugeridoModel extends BaseModel
{
    protected string $table = 'rest_pedidos_sugeridos';

    // ── Consultas ─────────────────────────────────────────────────

    public function getByRestaurante(int $restauranteId, string $estado = ''): array
    {
        $params = [$restauranteId];
        $where  = '';
        if ($estado !== '') {
            $where    = 'AND ps.estado = ?';
            $params[] = $estado;
        }

        return $this->query(
            "SELECT ps.*, e.razon_social AS empresa_nombre, e.email AS empresa_email,
                    u.nombre AS usuario_nombre
             FROM rest_pedidos_sugeridos ps
             JOIN empresas e ON e.id = ps.empresa_id
             LEFT JOIN usuarios u ON u.id = ps.usuario_id
             WHERE ps.restaurante_id = ? $where
             ORDER BY ps.created_at DESC",
            $params
        );
    }

    public function getItems(int $pedidoSugeridoId): array
    {
        return $this->query(
            "SELECT psi.*, i.nombre AS ingrediente_nombre, i.unidad_principal,
                    p.nombre AS producto_nombre, p.presentacion AS producto_unidad,
                    e.razon_social AS empresa_nombre
             FROM rest_pedido_sugerido_items psi
             JOIN rest_ingredientes i ON i.id = psi.ingrediente_id
             JOIN productos p ON p.id = psi.carnihub_producto_id
             JOIN empresas e ON e.id = (SELECT empresa_id FROM productos WHERE id = psi.carnihub_producto_id LIMIT 1)
             WHERE psi.pedido_sugerido_id = ?
             ORDER BY i.nombre",
            [$pedidoSugeridoId]
        );
    }

    public function findConItems(int $id): ?array
    {
        $pedido = $this->find($id);
        if (!$pedido) return null;
        $pedido['items'] = $this->getItems($id);
        return $pedido;
    }

    public function countPendientes(int $restauranteId): int
    {
        $row = $this->queryOne(
            "SELECT COUNT(*) AS n FROM rest_pedidos_sugeridos
             WHERE restaurante_id = ? AND estado IN ('sugerido','aprobado')",
            [$restauranteId]
        );
        return (int)($row['n'] ?? 0);
    }

    // ── Crear ─────────────────────────────────────────────────────

    /**
     * Crea un pedido sugerido con sus items en una transacción.
     *
     * $data keys: restaurante_id, empresa_id, notas, usuario_id
     * $items: [['ingrediente_id', 'carnihub_producto_id', 'cantidad_sugerida',
     *           'unidad', 'precio_unit_estimado', 'subtotal_estimado'], ...]
     */
    public function crear(array $data, array $items): int
    {
        $this->db->beginTransaction();
        try {
            $total = array_sum(array_column($items, 'subtotal_estimado'));

            $id = $this->insert([
                'restaurante_id'  => $data['restaurante_id'],
                'empresa_id'      => $data['empresa_id'],
                'estado'          => 'sugerido',
                'total_estimado'  => $total,
                'notas'           => $data['notas'] ?? null,
                'usuario_id'      => $data['usuario_id'] ?? null,
            ]);

            foreach ($items as $item) {
                $this->execute(
                    "INSERT INTO rest_pedido_sugerido_items
                     (pedido_sugerido_id, ingrediente_id, carnihub_producto_id,
                      cantidad_sugerida, cantidad_aprobada, unidad,
                      precio_unit_estimado, subtotal_estimado)
                     VALUES (?,?,?,?,?,?,?,?)",
                    [
                        $id,
                        (int)$item['ingrediente_id'],
                        (int)$item['carnihub_producto_id'],
                        (float)$item['cantidad_sugerida'],
                        (float)$item['cantidad_sugerida'],   // aprobada = sugerida por defecto
                        $item['unidad'],
                        (float)$item['precio_unit_estimado'],
                        (float)$item['subtotal_estimado'],
                    ]
                );
            }

            $this->db->commit();
            return $id;
        } catch (\Throwable $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    // ── Estado ────────────────────────────────────────────────────

    public function cambiarEstado(int $id, string $estado, ?int $usuarioId = null): void
    {
        $sets   = ['estado = ?'];
        $params = [$estado];

        if ($estado === 'aprobado') {
            $sets[]   = 'aprobado_at = NOW()';
            $sets[]   = 'usuario_id = ?';
            $params[] = $usuarioId;
        }

        $params[] = $id;
        $this->execute(
            'UPDATE rest_pedidos_sugeridos SET ' . implode(', ', $sets) . ' WHERE id = ?',
            $params
        );
    }

    /**
     * Actualiza las cantidades aprobadas (ajustadas manualmente) y recalcula total.
     * $cantidades: [item_id => cantidad_aprobada, ...]
     */
    public function actualizarCantidades(int $pedidoId, array $cantidades): void
    {
        $this->db->beginTransaction();
        try {
            foreach ($cantidades as $itemId => $cantidad) {
                $item = $this->queryOne(
                    'SELECT precio_unit_estimado FROM rest_pedido_sugerido_items WHERE id = ? AND pedido_sugerido_id = ?',
                    [(int)$itemId, $pedidoId]
                );
                if (!$item) continue;
                $subtotal = round((float)$cantidad * (float)$item['precio_unit_estimado'], 2);
                $this->execute(
                    'UPDATE rest_pedido_sugerido_items SET cantidad_aprobada = ?, subtotal_estimado = ? WHERE id = ?',
                    [(float)$cantidad, $subtotal, (int)$itemId]
                );
            }
            // Recalcular total del pedido
            $row = $this->queryOne(
                'SELECT SUM(subtotal_estimado) AS total FROM rest_pedido_sugerido_items WHERE pedido_sugerido_id = ?',
                [$pedidoId]
            );
            $this->execute(
                'UPDATE rest_pedidos_sugeridos SET total_estimado = ? WHERE id = ?',
                [(float)($row['total'] ?? 0), $pedidoId]
            );
            $this->db->commit();
        } catch (\Throwable $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    // ── Convertir a pedido CarniHub ───────────────────────────────

    /**
     * Convierte el pedido sugerido aprobado en un pedido real de CarniHub.
     * Crea registro en tabla `pedidos` y `pedido_detalle`.
     * Actualiza estado a 'convertido' y guarda el ID del pedido creado.
     *
     * @param int $compradorId  Usuario comprador que genera el pedido
     */
    public function convertirACarnihub(int $id, int $compradorId): int
    {
        $pedido = $this->findConItems($id);
        if (!$pedido || $pedido['estado'] !== 'aprobado') {
            throw new \RuntimeException('El pedido sugerido no existe o no está aprobado.');
        }
        if (empty($pedido['items'])) {
            throw new \RuntimeException('El pedido sugerido no tiene items.');
        }

        $this->db->beginTransaction();
        try {
            // Generar folio tipo "CHB-YYYY-NNNN"
            $anio = date('Y');
            $row  = $this->queryOne(
                "SELECT MAX(CAST(SUBSTRING_INDEX(folio, '-', -1) AS UNSIGNED)) AS ultimo
                 FROM pedidos WHERE folio LIKE ?",
                ["CHB-{$anio}-%"]
            );
            $num   = (int)($row['ultimo'] ?? 0) + 1;
            $folio = sprintf('CHB-%s-%04d', $anio, $num);

            // Calcular total real con cantidades aprobadas
            $subtotal = 0.0;
            $lineas   = [];
            foreach ($pedido['items'] as $item) {
                $cant   = (float)$item['cantidad_aprobada'];
                $precio = (float)$item['precio_unit_estimado'];
                $sub    = round($cant * $precio, 2);
                $subtotal += $sub;
                $lineas[] = [
                    'producto_id' => (int)$item['carnihub_producto_id'],
                    'cantidad'    => $cant,
                    'precio_unit' => $precio,
                    'subtotal'    => $sub,
                ];
            }

            // Insertar en pedidos (tabla B2B de CarniHub)
            $this->execute(
                "INSERT INTO pedidos
                 (folio, empresa_id, usuario_id, fecha_pedido, total, estado, notas)
                 VALUES (?, ?, ?, NOW(), ?, 'pendiente', ?)",
                [
                    $folio,
                    (int)$pedido['empresa_id'],
                    $compradorId,
                    $subtotal,
                    'Generado automáticamente por sistema de forecast — Restaurante ID: ' . $pedido['restaurante_id'],
                ]
            );
            $carnihubPedidoId = (int)$this->db->lastInsertId();

            // Insertar líneas en pedido_detalle
            foreach ($lineas as $linea) {
                $this->execute(
                    "INSERT INTO pedido_detalle (pedido_id, producto_id, cantidad, precio_unitario, subtotal)
                     VALUES (?, ?, ?, ?, ?)",
                    [$carnihubPedidoId, $linea['producto_id'], $linea['cantidad'], $linea['precio_unit'], $linea['subtotal']]
                );
            }

            // Marcar sugerido como convertido
            $this->execute(
                "UPDATE rest_pedidos_sugeridos
                 SET estado = 'convertido', pedido_carnihub_id = ?, aprobado_at = COALESCE(aprobado_at, NOW())
                 WHERE id = ?",
                [$carnihubPedidoId, $id]
            );

            $this->db->commit();
            return $carnihubPedidoId;
        } catch (\Throwable $e) {
            $this->db->rollBack();
            throw $e;
        }
    }
}
