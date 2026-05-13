<?php
class RestReservaModel extends BaseModel
{
    protected string $table = 'rest_reservaciones';

    public function getByRestaurante(int $restauranteId, int $page = 1, ?string $estado = null): array
    {
        $where = $estado ? "AND estado = '$estado'" : '';
        $sql = "SELECT r.*, m.nombre AS mesa_nombre
                FROM rest_reservaciones r
                LEFT JOIN rest_mesas m ON m.id = r.mesa_id
                WHERE r.restaurante_id = ? $where
                ORDER BY r.fecha DESC, r.hora DESC";
        return $this->paginate($sql, [$restauranteId], $page);
    }

    public function getProximas(int $restauranteId, int $dias = 7): array
    {
        return $this->query(
            "SELECT r.*, m.nombre AS mesa_nombre
             FROM rest_reservaciones r
             LEFT JOIN rest_mesas m ON m.id = r.mesa_id
             WHERE r.restaurante_id = ? AND r.fecha BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL ? DAY)
               AND r.estado IN ('pendiente','confirmada')
             ORDER BY r.fecha ASC, r.hora ASC",
            [$restauranteId, $dias]
        );
    }

    public function cambiarEstado(int $id, string $estado): bool
    {
        return $this->execute(
            "UPDATE rest_reservaciones SET estado = ? WHERE id = ?",
            [$estado, $id]
        );
    }
}
