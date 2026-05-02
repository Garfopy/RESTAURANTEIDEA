<?php
class RecurrenteModel extends BaseModel
{
    protected string $table = 'pedidos_recurrentes';

    public function getByEmpresa(int $empresaId): array
    {
        return $this->query(
            'SELECT * FROM pedidos_recurrentes WHERE empresa_id = ? ORDER BY nombre',
            [$empresaId]
        );
    }

    public function getConDetalle(int $id): ?array
    {
        $rec = $this->find($id);
        if (!$rec) return null;

        $rec['items'] = $this->query(
            'SELECT prd.*, pr.nombre AS producto_nombre, pr.imagen, pr.precio_base,
                    s.nombre AS sucursal_nombre
               FROM plantilla_recurrente_detalle prd
               JOIN productos pr ON pr.id = prd.producto_id
               JOIN sucursales s  ON s.id  = prd.sucursal_id
              WHERE prd.recurrente_id = ?
           ORDER BY s.nombre, pr.nombre',
            [$id]
        );
        return $rec;
    }

    public function guardarDetalle(int $recId, array $items): bool
    {
        $this->execute('DELETE FROM plantilla_recurrente_detalle WHERE recurrente_id = ?', [$recId]);
        foreach ($items as $item) {
            $this->execute(
                'INSERT INTO plantilla_recurrente_detalle (recurrente_id, sucursal_id, producto_id, cantidad)
                 VALUES (?, ?, ?, ?)',
                [$recId, $item['sucursal_id'], $item['producto_id'], $item['cantidad']]
            );
        }
        return true;
    }

    public function togglePausado(int $id, int $pausado): bool
    {
        return $this->execute(
            'UPDATE pedidos_recurrentes SET pausado = ? WHERE id = ?',
            [$pausado, $id]
        );
    }
}
