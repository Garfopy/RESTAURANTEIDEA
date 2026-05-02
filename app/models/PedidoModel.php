<?php
class PedidoModel extends BaseModel
{
    protected string $table = 'pedidos';

    public function generarFolio(): string
    {
        $year  = date('Y');
        $row   = $this->queryOne(
            "SELECT MAX(CAST(SUBSTRING_INDEX(folio, '-', -1) AS UNSIGNED)) AS ultimo
               FROM pedidos WHERE folio LIKE 'CHB-$year-%'"
        );
        $next = ($row && $row['ultimo']) ? (int)$row['ultimo'] + 1 : 1;
        return sprintf('CHB-%s-%04d', $year, $next);
    }

    public function crearConDetalle(array $pedido, array $detalles, array $porSucursal): int
    {
        $this->db->beginTransaction();
        try {
            $pedido['folio'] = $this->generarFolio();
            $pedidoId = $this->insert($pedido);

            foreach ($detalles as $d) {
                $d['pedido_id'] = $pedidoId;
                $stmtD = $this->db->prepare(
                    'INSERT INTO pedido_detalle (pedido_id, producto_id, cantidad, precio_unitario, subtotal)
                     VALUES (?, ?, ?, ?, ?)'
                );
                $stmtD->execute([$pedidoId, $d['producto_id'], $d['cantidad'], $d['precio_unitario'], $d['subtotal']]);
            }

            foreach ($porSucursal as $ps) {
                $stmtS = $this->db->prepare(
                    'INSERT INTO pedido_sucursal (pedido_id, sucursal_id, producto_id, cantidad, precio_unitario, subtotal)
                     VALUES (?, ?, ?, ?, ?, ?)'
                );
                $stmtS->execute([$pedidoId, $ps['sucursal_id'], $ps['producto_id'], $ps['cantidad'], $ps['precio_unitario'], $ps['subtotal']]);
            }

            $this->db->commit();
            return $pedidoId;
        } catch (Exception $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    public function getByEmpresa(int $empresaId, array $filtros = [], int $page = 1): array
    {
        $where  = ['p.empresa_id = ?'];
        $params = [$empresaId];

        if (!empty($filtros['estado'])) {
            $where[]  = 'p.estado = ?';
            $params[] = $filtros['estado'];
        }
        if (!empty($filtros['fecha_desde'])) {
            $where[]  = 'p.fecha_pedido >= ?';
            $params[] = $filtros['fecha_desde'];
        }
        if (!empty($filtros['fecha_hasta'])) {
            $where[]  = 'p.fecha_pedido <= ?';
            $params[] = $filtros['fecha_hasta'] . ' 23:59:59';
        }

        $sqlWhere = 'WHERE ' . implode(' AND ', $where);
        $sql = "SELECT p.*, e.razon_social AS empresa_nombre
                  FROM pedidos p
                  JOIN empresas e ON e.id = p.empresa_id
                  $sqlWhere
              ORDER BY p.created_at DESC";
        return $this->paginate($sql, $params, $page);
    }

    public function getAll(array $filtros = [], int $page = 1): array
    {
        $where  = ['1=1'];
        $params = [];

        if (!empty($filtros['estado'])) {
            $where[]  = 'p.estado = ?';
            $params[] = $filtros['estado'];
        }
        if (!empty($filtros['empresa_id'])) {
            $where[]  = 'p.empresa_id = ?';
            $params[] = $filtros['empresa_id'];
        }
        if (!empty($filtros['busqueda'])) {
            $where[]  = '(p.folio LIKE ? OR e.razon_social LIKE ?)';
            $like     = '%' . $filtros['busqueda'] . '%';
            $params   = array_merge($params, [$like, $like]);
        }

        $sqlWhere = 'WHERE ' . implode(' AND ', $where);
        $sql = "SELECT p.*, e.razon_social AS empresa_nombre, u.nombre AS usuario_nombre
                  FROM pedidos p
                  JOIN empresas e ON e.id = p.empresa_id
                  JOIN usuarios u ON u.id = p.usuario_id
                  $sqlWhere
              ORDER BY p.created_at DESC";
        return $this->paginate($sql, $params, $page);
    }

    public function getDetalle(int $id): ?array
    {
        $pedido = $this->queryOne(
            'SELECT p.*, e.razon_social, e.rfc, e.telefono AS empresa_tel,
                    u.nombre AS comprador_nombre
               FROM pedidos p
               JOIN empresas e ON e.id = p.empresa_id
               JOIN usuarios u ON u.id = p.usuario_id
              WHERE p.id = ?',
            [$id]
        );
        if (!$pedido) return null;

        $pedido['detalle'] = $this->query(
            'SELECT pd.*, pr.nombre AS producto_nombre, pr.imagen
               FROM pedido_detalle pd
               JOIN productos pr ON pr.id = pd.producto_id
              WHERE pd.pedido_id = ?',
            [$id]
        );

        $pedido['por_sucursal'] = $this->query(
            'SELECT ps.*, s.nombre AS sucursal_nombre, s.direccion AS sucursal_dir,
                    pr.nombre AS producto_nombre
               FROM pedido_sucursal ps
               JOIN sucursales s ON s.id = ps.sucursal_id
               JOIN productos pr ON pr.id = ps.producto_id
              WHERE ps.pedido_id = ?
           ORDER BY s.nombre, pr.nombre',
            [$id]
        );

        return $pedido;
    }

    public function cambiarEstado(int $id, string $estado): bool
    {
        return $this->execute('UPDATE pedidos SET estado = ? WHERE id = ?', [$estado, $id]);
    }

    public function getEstadisticasDashboard(?int $empresaId = null): array
    {
        $where  = $empresaId ? 'WHERE empresa_id = ?' : '';
        $params = $empresaId ? [$empresaId] : [];

        $stats = $this->queryOne(
            "SELECT COUNT(*) AS total,
                    SUM(total) AS ventas_total,
                    SUM(estado='pendiente') AS pendientes,
                    SUM(estado='en_ruta') AS en_ruta,
                    SUM(estado='entregado') AS entregados,
                    SUM(IF(MONTH(fecha_pedido)=MONTH(NOW()) AND YEAR(fecha_pedido)=YEAR(NOW()),1,0)) AS mes_actual
               FROM pedidos $where",
            $params
        );

        $ventasPorDia = $this->query(
            "SELECT DATE(fecha_pedido) AS dia, SUM(total) AS total, COUNT(*) AS cantidad
               FROM pedidos
              WHERE fecha_pedido >= DATE_SUB(NOW(), INTERVAL 7 DAY)
              " . ($empresaId ? 'AND empresa_id = ?' : '') . "
              GROUP BY dia ORDER BY dia",
            $empresaId ? [$empresaId] : []
        );

        return ['stats' => $stats, 'ventas_por_dia' => $ventasPorDia];
    }

    public function getVentasPorCategoria(int $dias = 30): array
    {
        return $this->query(
            "SELECT cat.nombre, SUM(pd.cantidad) AS kg, SUM(pd.subtotal) AS ventas
               FROM pedido_detalle pd
               JOIN pedidos p ON p.id = pd.pedido_id
               JOIN productos pr ON pr.id = pd.producto_id
               JOIN categorias cat ON cat.id = pr.categoria_id
              WHERE p.fecha_pedido >= DATE_SUB(NOW(), INTERVAL ? DAY)
              GROUP BY cat.id ORDER BY kg DESC",
            [$dias]
        );
    }
}
