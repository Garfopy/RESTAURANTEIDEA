<?php
class ProductoModel extends BaseModel
{
    protected string $table = 'productos';

    public function listadoConPrecio(array $filtros = [], int $page = 1): array
    {
        $where  = ['p.activo = 1'];
        $params = [];

        if (!empty($filtros['categoria_id'])) {
            $where[]  = 'p.categoria_id = ?';
            $params[] = $filtros['categoria_id'];
        }
        if (!empty($filtros['buscar'])) {
            $where[]  = 'p.nombre LIKE ?';
            $params[] = '%' . $filtros['buscar'] . '%';
        }

        $sqlWhere = 'WHERE ' . implode(' AND ', $where);
        $sql = "SELECT p.*, c.nombre AS categoria_nombre,
                       inv.stock, inv.umbral_minimo
                  FROM productos p
                  JOIN categorias c ON c.id = p.categoria_id
             LEFT JOIN inventario inv ON inv.producto_id = p.id
                  $sqlWhere
              ORDER BY c.nombre, p.nombre";

        return $this->paginate($sql, $params, $page);
    }

    public function getPrecioParaCantidad(int $productoId, float $cantidad): float
    {
        $row = $this->queryOne(
            'SELECT precio FROM precios_escalonados
              WHERE producto_id = ?
                AND cantidad_min <= ?
                AND (cantidad_max IS NULL OR cantidad_max >= ?)
              ORDER BY cantidad_min DESC
              LIMIT 1',
            [$productoId, $cantidad, $cantidad]
        );
        if ($row) return (float)$row['precio'];

        // Fallback al precio_base
        $prod = $this->find($productoId);
        return $prod ? (float)$prod['precio_base'] : 0;
    }

    public function getEscalonados(int $productoId): array
    {
        return $this->query(
            'SELECT * FROM precios_escalonados WHERE producto_id = ? ORDER BY cantidad_min',
            [$productoId]
        );
    }

    public function conDetalle(int $id): ?array
    {
        $prod = $this->queryOne(
            'SELECT p.*, c.nombre AS categoria_nombre, inv.stock, inv.umbral_minimo
               FROM productos p
               JOIN categorias c ON c.id = p.categoria_id
          LEFT JOIN inventario inv ON inv.producto_id = p.id
              WHERE p.id = ?',
            [$id]
        );
        if (!$prod) return null;
        $prod['escalonados'] = $this->getEscalonados($id);
        return $prod;
    }
}
