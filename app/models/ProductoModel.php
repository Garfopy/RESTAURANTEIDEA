<?php
class ProductoModel extends BaseModel
{
    protected string $table = 'productos';

    public function getCatalogo(?string $categoria = null, ?string $busqueda = null): array
    {
        $where  = ['p.activo = 1'];
        $params = [];

        if ($categoria && $categoria !== 'todos') {
            $where[]  = 'c.slug = ?';
            $params[] = $categoria;
        }
        if ($busqueda) {
            $where[]  = '(p.nombre LIKE ? OR p.descripcion LIKE ?)';
            $like     = "%$busqueda%";
            $params   = array_merge($params, [$like, $like]);
        }

        $sqlWhere = 'WHERE ' . implode(' AND ', $where);
        return $this->query(
            "SELECT p.*, c.nombre AS categoria_nombre, c.slug AS categoria_slug,
                    (SELECT MIN(pe.precio_por_unidad) FROM precios_escalonados pe WHERE pe.producto_id = p.id AND pe.activo=1) AS precio_minimo,
                    (SELECT MAX(pe.precio_por_unidad) FROM precios_escalonados pe WHERE pe.producto_id = p.id AND pe.activo=1) AS precio_maximo
               FROM productos p
               JOIN categorias c ON c.id = p.categoria_id
               $sqlWhere
           ORDER BY c.nombre, p.nombre",
            $params
        );
    }

    public function getConPreciosEscalonados(int $id): ?array
    {
        $producto = $this->queryOne(
            'SELECT p.*, c.nombre AS categoria_nombre FROM productos p
               JOIN categorias c ON c.id = p.categoria_id
              WHERE p.id = ?',
            [$id]
        );
        if (!$producto) return null;

        $producto['precios'] = $this->query(
            'SELECT * FROM precios_escalonados WHERE producto_id = ? AND activo = 1 ORDER BY rango_min',
            [$id]
        );
        return $producto;
    }

    public function getPrecioParaCantidad(int $productoId, float $cantidad): float
    {
        $row = $this->queryOne(
            'SELECT precio_por_unidad FROM precios_escalonados
              WHERE producto_id = ? AND activo = 1
                AND rango_min <= ?
                AND (rango_max IS NULL OR rango_max >= ?)
           ORDER BY rango_min DESC LIMIT 1',
            [$productoId, $cantidad, $cantidad]
        );

        if ($row) return (float) $row['precio_por_unidad'];

        // Fallback to base price
        $p = $this->queryOne('SELECT precio_base FROM productos WHERE id = ?', [$productoId]);
        return $p ? (float) $p['precio_base'] : 0.0;
    }

    public function getAllAdmin(int $page = 1, array $filtros = []): array
    {
        $where  = ['p.activo >= 0'];
        $params = [];

        if (!empty($filtros['categoria_id'])) {
            $where[]  = 'p.categoria_id = ?';
            $params[] = $filtros['categoria_id'];
        }
        if (!empty($filtros['busqueda'])) {
            $where[]  = '(p.nombre LIKE ? OR p.descripcion LIKE ?)';
            $like     = '%' . $filtros['busqueda'] . '%';
            $params   = array_merge($params, [$like, $like]);
        }
        if (isset($filtros['activo']) && $filtros['activo'] !== '') {
            $where[0] = 'p.activo = ?';
            array_unshift($params, $filtros['activo']);
        }

        $sqlWhere = 'WHERE ' . implode(' AND ', $where);
        $sql = "SELECT p.*, c.nombre AS categoria_nombre,
                       inv.disponible AS stock_disponible
                  FROM productos p
                  JOIN categorias c ON c.id = p.categoria_id
             LEFT JOIN inventario inv ON inv.producto_id = p.id
                  $sqlWhere
              ORDER BY p.created_at DESC";
        return $this->paginate($sql, $params, $page);
    }

    public function getTopVendidos(int $limit = 5): array
    {
        return $this->query(
            "SELECT p.nombre, c.nombre AS categoria,
                    SUM(pd.cantidad) AS kg_vendidos,
                    SUM(pd.subtotal) AS ventas_total
               FROM pedido_detalle pd
               JOIN productos p ON p.id = pd.producto_id
               JOIN categorias c ON c.id = p.categoria_id
              GROUP BY pd.producto_id
              ORDER BY kg_vendidos DESC
              LIMIT ?",
            [$limit]
        );
    }
}
