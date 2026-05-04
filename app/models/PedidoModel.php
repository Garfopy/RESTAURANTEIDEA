<?php
class PedidoModel extends BaseModel
{
    protected string $table = 'pedidos';

    public function generarFolio(): string
    {
        $anio = date('Y');
        $row  = $this->queryOne(
            "SELECT MAX(CAST(SUBSTRING_INDEX(folio, '-', -1) AS UNSIGNED)) AS ultimo
               FROM pedidos WHERE folio LIKE ?",
            ["CHB-{$anio}-%"]
        );
        $num = (int)($row['ultimo'] ?? 0) + 1;
        return sprintf('CHB-%s-%04d', $anio, $num);
    }

    /**
     * Crea pedido + detalle + pedido_sucursal en una transacción.
     *
     * $pedidoData: campos directos para la tabla pedidos (sin folio, subtotal, total).
     * $items: [['producto_id'=>, 'cantidad'=>, 'precio_unit'=>, 'subtotal'=>], ...]
     * $sucursalesIds: [sucursal_id, ...] — sucursales involucradas
     */
    public function crear(array $pedidoData, array $items, array $sucursalesIds): int
    {
        $this->db->beginTransaction();
        try {
            $subtotal = array_sum(array_column($items, 'subtotal'));
            $pedidoData['folio']    = $this->generarFolio();
            $pedidoData['subtotal'] = $subtotal;
            $pedidoData['total']    = $subtotal;

            $pedidoId = $this->insert($pedidoData);

            foreach ($items as $item) {
                $this->execute(
                    'INSERT INTO pedido_detalle (pedido_id, producto_id, cantidad, precio_unit, subtotal)
                     VALUES (?, ?, ?, ?, ?)',
                    [$pedidoId, $item['producto_id'], $item['cantidad'], $item['precio_unit'], $item['subtotal']]
                );
            }

            foreach (array_unique($sucursalesIds) as $sucursalId) {
                $this->execute(
                    'INSERT INTO pedido_sucursal (pedido_id, sucursal_id) VALUES (?, ?)',
                    [$pedidoId, $sucursalId]
                );
            }

            $this->db->commit();
            return $pedidoId;
        } catch (\Throwable $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    public function listadoEmpresa(int $empresaId, array $filtros = [], int $page = 1): array
    {
        $where  = ['p.empresa_id = ?'];
        $params = [$empresaId];

        if (!empty($filtros['estado'])) {
            $where[]  = 'p.estado = ?';
            $params[] = $filtros['estado'];
        }
        if (!empty($filtros['buscar'])) {
            $where[]  = '(p.folio LIKE ? OR u.nombre LIKE ? OR u.apellido_paterno LIKE ?)';
            $t = '%' . $filtros['buscar'] . '%';
            array_push($params, $t, $t, $t);
        }

        $sql = 'SELECT p.*, u.nombre AS comprador_nombre, u.apellido_paterno AS comprador_apellido
                  FROM pedidos p
                  JOIN usuarios u ON u.id = p.comprador_id
                 WHERE ' . implode(' AND ', $where) . '
              ORDER BY p.created_at DESC';

        return $this->paginate($sql, $params, $page);
    }

    public function pendientesAprobacion(int $empresaId): array
    {
        return $this->query(
            "SELECT p.*, u.nombre AS comprador_nombre, u.apellido_paterno AS comprador_apellido
               FROM pedidos p
               JOIN usuarios u ON u.id = p.comprador_id
              WHERE p.empresa_id = ? AND p.requiere_aprobacion = 1 AND p.estado = 'pendiente'
              ORDER BY p.created_at DESC",
            [$empresaId]
        );
    }

    public function conDetalle(int $id): ?array
    {
        $pedido = $this->queryOne(
            "SELECT p.*,
                    u.nombre AS comprador_nombre, u.apellido_paterno AS comprador_apellido,
                    ap.nombre AS aprobador_nombre
               FROM pedidos p
               JOIN usuarios u ON u.id = p.comprador_id
          LEFT JOIN usuarios ap ON ap.id = p.aprobado_por
              WHERE p.id = ?",
            [$id]
        );
        if (!$pedido) return null;

        $pedido['items'] = $this->query(
            'SELECT pd.*, pr.nombre AS producto_nombre, pr.presentacion
               FROM pedido_detalle pd
               JOIN productos pr ON pr.id = pd.producto_id
              WHERE pd.pedido_id = ?',
            [$id]
        );

        $pedido['sucursales'] = $this->query(
            'SELECT ps.*, s.nombre AS sucursal_nombre, s.direccion
               FROM pedido_sucursal ps
               JOIN sucursales s ON s.id = ps.sucursal_id
              WHERE ps.pedido_id = ?',
            [$id]
        );

        return $pedido;
    }

    public function aprobar(int $id, int $aprobadoPor): bool
    {
        return $this->execute(
            "UPDATE pedidos
                SET estado = 'confirmado', aprobado_por = ?, aprobado_at = NOW()
              WHERE id = ? AND estado = 'pendiente' AND requiere_aprobacion = 1",
            [$aprobadoPor, $id]
        );
    }

    public function rechazar(int $id, int $rechazadoPor, string $motivo): bool
    {
        return $this->execute(
            "UPDATE pedidos
                SET estado = 'cancelado', aprobado_por = ?, aprobado_at = NOW(),
                    notas = CONCAT(COALESCE(notas,''), IF(notas IS NULL OR notas='','','\n'), 'Rechazado: ', ?)
              WHERE id = ? AND estado = 'pendiente'",
            [$rechazadoPor, $motivo, $id]
        );
    }

    public function getTrackingActivo(int $pedidoId): ?array
    {
        return $this->queryOne(
            "SELECT rd.lat_actual, rd.lng_actual, rd.eta_minutos, rd.estado,
                    s.nombre AS sucursal_nombre, s.lat AS sucursal_lat, s.lng AS sucursal_lng,
                    u.nombre AS repartidor_nombre, p.estado AS pedido_estado
               FROM ruta_detalle rd
               JOIN rutas r        ON r.id = rd.ruta_id
               JOIN sucursales s   ON s.id = rd.sucursal_id
               JOIN usuarios u     ON u.id = r.repartidor_id
               JOIN pedidos p      ON p.id = rd.pedido_id
              WHERE rd.pedido_id = ? AND rd.tracking_activo = 1
              LIMIT 1",
            [$pedidoId]
        );
    }

    public function verificarPertenece(int $id, int $empresaId): bool
    {
        return $this->queryOne(
            'SELECT id FROM pedidos WHERE id = ? AND empresa_id = ?',
            [$id, $empresaId]
        ) !== null;
    }

    public function cancelar(int $id, int $usuarioId): bool
    {
        return $this->execute(
            "UPDATE pedidos
                SET estado = 'cancelado'
              WHERE id = ? AND comprador_id = ? AND estado IN ('pendiente')",
            [$id, $usuarioId]
        );
    }
}
