<?php
class InventarioModel extends BaseModel
{
    protected string $table = 'inventario';

    public function getAll(int $page = 1, ?string $busqueda = null): array
    {
        $where  = ['1=1'];
        $params = [];

        if ($busqueda) {
            $where[]  = 'p.nombre LIKE ?';
            $params[] = "%$busqueda%";
        }

        $sqlWhere = 'WHERE ' . implode(' AND ', $where);
        $sql = "SELECT i.*, p.nombre AS producto_nombre, p.imagen,
                       c.nombre AS categoria_nombre,
                       CASE
                         WHEN i.disponible <= i.minimo_alerta THEN 'critico'
                         WHEN i.disponible <= i.minimo_alerta * 1.5 THEN 'bajo'
                         ELSE 'optimo'
                       END AS estado_stock
                  FROM inventario i
                  JOIN productos p ON p.id = i.producto_id
                  JOIN categorias c ON c.id = p.categoria_id
                  $sqlWhere
              ORDER BY p.nombre";
        return $this->paginate($sql, $params, $page);
    }

    public function getByProducto(int $productoId): ?array
    {
        return $this->queryOne(
            'SELECT * FROM inventario WHERE producto_id = ?',
            [$productoId]
        );
    }

    public function actualizarStock(int $productoId, float $cantidad): bool
    {
        return $this->execute(
            'UPDATE inventario SET disponible = disponible - ? WHERE producto_id = ?',
            [$cantidad, $productoId]
        );
    }

    public function getAlertas(): array
    {
        return $this->query(
            'SELECT i.*, p.nombre AS producto_nombre
               FROM inventario i
               JOIN productos p ON p.id = i.producto_id
              WHERE i.disponible <= i.minimo_alerta
           ORDER BY i.disponible ASC'
        );
    }

    public function getResumen(): array
    {
        return $this->queryOne(
            "SELECT
               COUNT(*) AS total_productos,
               SUM(disponible) AS kg_disponibles,
               SUM(disponible <= minimo_alerta) AS stock_critico,
               SUM(disponible > minimo_alerta AND disponible <= minimo_alerta * 1.5) AS stock_bajo
             FROM inventario"
        ) ?? [];
    }
}
