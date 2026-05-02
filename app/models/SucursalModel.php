<?php
class SucursalModel extends BaseModel
{
    protected string $table = 'sucursales';

    public function getByEmpresa(int $empresaId): array
    {
        return $this->query(
            'SELECT * FROM sucursales WHERE empresa_id = ? ORDER BY nombre',
            [$empresaId]
        );
    }

    public function getActivasByEmpresa(int $empresaId): array
    {
        return $this->query(
            'SELECT * FROM sucursales WHERE empresa_id = ? AND activo = 1 ORDER BY nombre',
            [$empresaId]
        );
    }

    public function getAll(int $page = 1): array
    {
        $sql = 'SELECT s.*, e.razon_social AS empresa_nombre
                  FROM sucursales s
                  JOIN empresas e ON e.id = s.empresa_id
              ORDER BY e.razon_social, s.nombre';
        return $this->paginate($sql, [], $page);
    }
}
