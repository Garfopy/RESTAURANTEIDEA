<?php
class RestInventarioModel extends BaseModel
{
    protected string $table = 'rest_ingredientes';

    public function getByRestaurante(int $restauranteId, bool $soloActivos = false): array
    {
        $where = $soloActivos ? 'AND activo = 1' : '';
        return $this->query(
            "SELECT * FROM rest_ingredientes WHERE restaurante_id = ? $where ORDER BY nombre",
            [$restauranteId]
        );
    }

    public function alertasStockBajo(int $restauranteId): array
    {
        return $this->query(
            "SELECT * FROM rest_ingredientes
             WHERE restaurante_id = ? AND activo = 1 AND stock <= stock_minimo
             ORDER BY (stock_minimo - stock) DESC",
            [$restauranteId]
        );
    }

    public function registrarMovimiento(array $data): void
    {
        $this->execute(
            "INSERT INTO rest_movimientos_inventario
             (restaurante_id, ingrediente_id, tipo, cantidad, stock_antes, stock_despues, motivo, referencia, usuario_id)
             VALUES (?,?,?,?,?,?,?,?,?)",
            [
                $data['restaurante_id'],
                $data['ingrediente_id'],
                $data['tipo'],
                $data['cantidad'],
                $data['stock_antes'],
                $data['stock_despues'],
                $data['motivo'] ?? null,
                $data['referencia'] ?? null,
                $data['usuario_id'] ?? null,
            ]
        );
    }

    public function ajustarStock(int $ingredienteId, float $delta, string $tipo, string $motivo, ?string $ref, int $restauranteId, ?int $usuarioId): void
    {
        $ing = $this->find($ingredienteId);
        if (!$ing) return;

        $stockAntes = (float) $ing['stock'];
        $stockDespues = max(0, $stockAntes + $delta);

        $this->execute(
            "UPDATE rest_ingredientes SET stock = ? WHERE id = ?",
            [$stockDespues, $ingredienteId]
        );

        $this->registrarMovimiento([
            'restaurante_id' => $restauranteId,
            'ingrediente_id' => $ingredienteId,
            'tipo'           => $tipo,
            'cantidad'       => abs($delta),
            'stock_antes'    => $stockAntes,
            'stock_despues'  => $stockDespues,
            'motivo'         => $motivo,
            'referencia'     => $ref,
            'usuario_id'     => $usuarioId,
        ]);
    }

    public function descontarPorOrden(int $pedidoId, int $restauranteId, ?int $usuarioId = null): void
    {
        $items = $this->query(
            "SELECT pi.cantidad AS cantidad_pedida, ri.ingrediente_id, ri.cantidad AS cant_receta, ri.unidad,
                    rec.porciones_base
             FROM rest_pedido_items pi
             JOIN rest_platillos pl ON pl.id = pi.platillo_id
             JOIN rest_recetas rec ON rec.platillo_id = pl.id
             JOIN rest_receta_ingredientes ri ON ri.receta_id = rec.id
             WHERE pi.pedido_id = ?",
            [$pedidoId]
        );

        foreach ($items as $item) {
            $descuento = ($item['cant_receta'] / max(1, $item['porciones_base'])) * $item['cantidad_pedida'];
            $this->ajustarStock(
                (int) $item['ingrediente_id'],
                -$descuento,
                'salida',
                'Consumo pedido restaurante',
                'rest_pedido:' . $pedidoId,
                $restauranteId,
                $usuarioId
            );
        }
    }

    public function getMovimientos(int $restauranteId, int $page = 1): array
    {
        $sql = "SELECT mv.*, i.nombre AS ingrediente_nombre, u.nombre AS usuario_nombre
                FROM rest_movimientos_inventario mv
                JOIN rest_ingredientes i ON i.id = mv.ingrediente_id
                LEFT JOIN usuarios u ON u.id = mv.usuario_id
                WHERE mv.restaurante_id = ?
                ORDER BY mv.created_at DESC";
        return $this->paginate($sql, [$restauranteId], $page);
    }
}
