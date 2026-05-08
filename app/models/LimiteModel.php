<?php
class LimiteModel extends BaseModel
{
    protected string $table = 'limites_compra';

    public function getByEmpresa(int $empresaId): array
    {
        return $this->query(
            "SELECT lc.*,
                    s.nombre AS sucursal_nombre,
                    u.nombre AS comprador_nombre, u.apellido_paterno AS comprador_apellido,
                    p.nombre AS producto_nombre, p.unidad
               FROM limites_compra lc
          LEFT JOIN sucursales s ON s.id = lc.sucursal_id
          LEFT JOIN usuarios u ON u.id = s.comprador_id
          LEFT JOIN productos p ON p.id = lc.producto_id
              WHERE lc.empresa_id = ?
           ORDER BY u.nombre ASC, s.nombre ASC, p.nombre ASC",
            [$empresaId]
        );
    }

    public function crear(array $data): int
    {
        return $this->insert([
            'empresa_id'   => $data['empresa_id'],
            'sucursal_id'  => $data['sucursal_id']  ?: null,
            'producto_id'  => $data['producto_id']  ?: null,
            'limite_kg'    => $data['limite_kg']    ?: null,
            'limite_monto' => $data['limite_monto'] ?: null,
            'periodo'      => $data['periodo'],
            'activo'       => 1,
            'created_by'   => $data['created_by'],
        ]);
    }

    public function actualizar(int $id, array $data): void
    {
        $this->execute(
            "UPDATE limites_compra
                SET sucursal_id=?, producto_id=?, limite_kg=?, limite_monto=?, periodo=?
              WHERE id=? AND empresa_id=?",
            [
                $data['sucursal_id']  ?: null,
                $data['producto_id']  ?: null,
                $data['limite_kg']    ?: null,
                $data['limite_monto'] ?: null,
                $data['periodo'],
                $id,
                $data['empresa_id'],
            ]
        );
    }

    public function toggleActivo(int $id, int $empresaId): void
    {
        $this->execute(
            'UPDATE limites_compra SET activo = NOT activo WHERE id=? AND empresa_id=?',
            [$id, $empresaId]
        );
    }

    public function eliminar(int $id, int $empresaId): void
    {
        $this->execute(
            'DELETE FROM limites_compra WHERE id=? AND empresa_id=?',
            [$id, $empresaId]
        );
    }

    public function findConDetalle(int $id): ?array
    {
        return $this->queryOne(
            'SELECT * FROM limites_compra WHERE id=?',
            [$id]
        );
    }
}
