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
            'SELECT prd.*, pr.nombre AS producto_nombre, pr.imagen, pr.precio_base
               FROM plantilla_recurrente_detalle prd
               JOIN productos pr ON pr.id = prd.producto_id
              WHERE prd.recurrente_id = ?
           ORDER BY pr.nombre',
            [$id]
        );
        return $rec;
    }

    public function guardarDetalle(int $recId, array $items): bool
    {
        $this->execute('DELETE FROM plantilla_recurrente_detalle WHERE recurrente_id = ?', [$recId]);
        foreach ($items as $item) {
            $this->execute(
                'INSERT INTO plantilla_recurrente_detalle (recurrente_id, producto_id, cantidad)
                 VALUES (?, ?, ?)',
                [$recId, $item['producto_id'], $item['cantidad']]
            );
        }
        return true;
    }

    public function togglePausado(int $id, int $pausado): bool
    {
        return (bool)$this->execute(
            'UPDATE pedidos_recurrentes SET pausado = ? WHERE id = ?',
            [$pausado, $id]
        );
    }

    public function getActivos(int $empresaId): array
    {
        return $this->query(
            'SELECT * FROM pedidos_recurrentes WHERE empresa_id = ? AND pausado = 0 ORDER BY nombre',
            [$empresaId]
        );
    }
}
