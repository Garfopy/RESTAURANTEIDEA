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

    // ── Panel Admin ───────────────────────────────────────────────────────────

    public function getCategorias(): array
    {
        return $this->query('SELECT * FROM categorias ORDER BY nombre');
    }

    public function listadoAdmin(array $filtros = [], int $page = 1): array
    {
        $where  = ['1=1'];
        $params = [];

        if (!empty($filtros['categoria_id'])) {
            $where[]  = 'p.categoria_id = ?';
            $params[] = $filtros['categoria_id'];
        }
        if (!empty($filtros['buscar'])) {
            $where[]  = 'p.nombre LIKE ?';
            $params[] = '%' . $filtros['buscar'] . '%';
        }
        if (!empty($filtros['stock_bajo'])) {
            $where[] = 'COALESCE(inv.stock, 0) <= COALESCE(inv.umbral_minimo, 0)';
        }

        $sqlWhere = 'WHERE ' . implode(' AND ', $where);
        $sql = "SELECT p.*, c.nombre AS categoria_nombre,
                       COALESCE(inv.stock, 0) AS stock,
                       COALESCE(inv.umbral_minimo, 10) AS umbral_minimo
                  FROM productos p
                  JOIN categorias c ON c.id = p.categoria_id
             LEFT JOIN inventario inv ON inv.producto_id = p.id
                  $sqlWhere
              ORDER BY p.activo DESC, c.nombre, p.nombre";

        return $this->paginate($sql, $params, $page);
    }

    public function listadoInventario(array $filtros = [], int $page = 1): array
    {
        $where  = ['p.activo = 1'];
        $params = [];

        if (!empty($filtros['empresa_id'])) {
            $where[]  = 'p.empresa_id = ?';
            $params[] = (int)$filtros['empresa_id'];
        }
        if (!empty($filtros['buscar'])) {
            $where[]  = 'p.nombre LIKE ?';
            $params[] = '%' . $filtros['buscar'] . '%';
        }
        if (!empty($filtros['stock_bajo'])) {
            $where[] = 'COALESCE(inv.stock, 0) <= COALESCE(inv.umbral_minimo, 0)';
        }

        $sqlWhere = 'WHERE ' . implode(' AND ', $where);
        $sql = "SELECT p.id, p.nombre, p.presentacion, p.imagen, c.nombre AS categoria_nombre,
                       COALESCE(inv.stock, 0) AS stock_actual,
                       COALESCE(inv.umbral_minimo, 10) AS umbral_minimo,
                       inv.id AS inventario_id
                  FROM productos p
                  JOIN categorias c ON c.id = p.categoria_id
             LEFT JOIN inventario inv ON inv.producto_id = p.id
                  $sqlWhere
              ORDER BY (COALESCE(inv.stock, 0) <= COALESCE(inv.umbral_minimo, 0)) DESC, c.nombre, p.nombre";

        return $this->paginate($sql, $params, $page);
    }

    public function ajustarStock(int $productoId, string $tipo, float $cantidad): void
    {
        $existe = $this->queryOne('SELECT id, stock FROM inventario WHERE producto_id = ?', [$productoId]);

        if (!$existe) {
            $stockNuevo = $tipo === 'salida' ? -$cantidad : $cantidad;
            $this->execute(
                'INSERT INTO inventario (producto_id, stock, umbral_minimo) VALUES (?, ?, 10)',
                [$productoId, max(0, $stockNuevo)]
            );
            return;
        }

        $stockActual = (float)$existe['stock'];
        $stockNuevo  = match ($tipo) {
            'entrada' => $stockActual + $cantidad,
            'salida'  => max(0, $stockActual - $cantidad),
            default   => $cantidad, // ajuste directo
        };

        $this->execute(
            'UPDATE inventario SET stock = ? WHERE producto_id = ?',
            [$stockNuevo, $productoId]
        );
    }

    public function actualizarEscalonados(int $productoId, array $cantMin, array $cantMax, array $precios): void
    {
        $this->execute('DELETE FROM precios_escalonados WHERE producto_id = ?', [$productoId]);

        foreach ($cantMin as $i => $min) {
            if ($min === '' || !isset($precios[$i]) || $precios[$i] === '') continue;
            $max = ($cantMax[$i] ?? '') !== '' ? (float)$cantMax[$i] : null;
            $this->execute(
                'INSERT INTO precios_escalonados (producto_id, cantidad_min, cantidad_max, precio) VALUES (?, ?, ?, ?)',
                [$productoId, (float)$min, $max, (float)$precios[$i]]
            );
        }
    }

    public function inicializarInventario(int $productoId, int $stock, int $umbral): void
    {
        $existe = $this->queryOne('SELECT id FROM inventario WHERE producto_id = ?', [$productoId]);
        if (!$existe) {
            $this->execute(
                'INSERT INTO inventario (producto_id, stock, umbral_minimo) VALUES (?, ?, ?)',
                [$productoId, $stock, $umbral]
            );
        }
    }

    public function actualizarInventario(int $productoId, int $umbral): void
    {
        $this->execute(
            'UPDATE inventario SET umbral_minimo = ? WHERE producto_id = ?',
            [$umbral, $productoId]
        );
    }
}
