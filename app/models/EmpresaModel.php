<?php
class EmpresaModel extends BaseModel
{
    protected string $table = 'empresas';

    public function getAll(int $page = 1, array $filtros = []): array
    {
        $where  = [];
        $params = [];

        if (!empty($filtros['busqueda'])) {
            $where[]  = '(e.razon_social LIKE ? OR e.rfc LIKE ? OR e.email LIKE ?)';
            $like     = '%' . $filtros['busqueda'] . '%';
            $params   = array_merge($params, [$like, $like, $like]);
        }
        if (isset($filtros['activo']) && $filtros['activo'] !== '') {
            $where[]  = 'e.activo = ?';
            $params[] = $filtros['activo'];
        }

        $sqlWhere = $where ? 'WHERE ' . implode(' AND ', $where) : '';
        $sql = "SELECT e.*,
                       u.nombre AS vendedor_nombre,
                       (SELECT COUNT(*) FROM sucursales s WHERE s.empresa_id = e.id AND s.activo=1) AS total_sucursales,
                       (SELECT COUNT(*) FROM pedidos p WHERE p.empresa_id = e.id) AS total_pedidos
                  FROM empresas e
             LEFT JOIN usuarios u ON u.id = e.vendedor_asignado
                  $sqlWhere
              ORDER BY e.created_at DESC";
        return $this->paginate($sql, $params, $page);
    }

    public function getConSucursales(int $id): ?array
    {
        $empresa = $this->find($id);
        if (!$empresa) return null;
        $empresa['sucursales'] = $this->query(
            'SELECT * FROM sucursales WHERE empresa_id = ? ORDER BY nombre',
            [$id]
        );
        return $empresa;
    }

    public function toggleCredito(int $id, int $estado): bool
    {
        return $this->execute('UPDATE empresas SET credito_activo = ? WHERE id = ?', [$estado, $id]);
    }

    public function getEstadisticas(): array
    {
        return $this->queryOne(
            'SELECT
               COUNT(*) AS total,
               SUM(activo = 1) AS activos,
               SUM(activo = 0) AS inactivos,
               SUM(credito_activo = 1) AS con_credito
             FROM empresas'
        ) ?? [];
    }
}
