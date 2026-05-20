<?php
class RestReservaModel extends BaseModel
{
    protected string $table = 'rest_reservaciones';

    public function getByRestaurante(int $restauranteId, int $page = 1, ?string $estado = null): array
    {
        $where = $estado ? "AND r.estado = '" . addslashes($estado) . "'" : '';
        $sql = "SELECT r.*, m.nombre AS mesa_nombre,
                       u.nombre AS mesero_nombre
                FROM rest_reservaciones r
                LEFT JOIN rest_mesas m ON m.id = r.mesa_id
                LEFT JOIN usuarios u   ON u.id = r.mesero_id
                WHERE r.restaurante_id = ? $where
                ORDER BY r.fecha DESC, r.hora DESC";
        return $this->paginate($sql, [$restauranteId], $page);
    }

    public function getProximas(int $restauranteId, int $dias = 7): array
    {
        return $this->query(
            "SELECT r.*, m.nombre AS mesa_nombre,
                    u.nombre AS mesero_nombre
             FROM rest_reservaciones r
             LEFT JOIN rest_mesas m ON m.id = r.mesa_id
             LEFT JOIN usuarios u   ON u.id = r.mesero_id
             WHERE r.restaurante_id = ? AND r.fecha BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL ? DAY)
               AND r.estado IN ('pendiente','confirmada')
             ORDER BY r.fecha ASC, r.hora ASC",
            [$restauranteId, $dias]
        );
    }

    public function getHoyPorZonas(int $restauranteId, array $zonaIds): array
    {
        if (empty($zonaIds)) {
            return $this->query(
                "SELECT r.*, m.nombre AS mesa_nombre
                 FROM rest_reservaciones r
                 LEFT JOIN rest_mesas m ON m.id = r.mesa_id
                 WHERE r.restaurante_id = ? AND r.fecha = CURDATE()
                   AND r.estado IN ('pendiente','confirmada')
                 ORDER BY r.hora ASC",
                [$restauranteId]
            );
        }
        $placeholders = implode(',', array_fill(0, count($zonaIds), '?'));
        return $this->query(
            "SELECT r.*, m.nombre AS mesa_nombre
             FROM rest_reservaciones r
             JOIN rest_mesas m ON m.id = r.mesa_id
             WHERE r.restaurante_id = ? AND r.fecha = CURDATE()
               AND r.estado IN ('pendiente','confirmada')
               AND m.zona_id IN ($placeholders)
             ORDER BY r.hora ASC",
            array_merge([$restauranteId], $zonaIds)
        );
    }

    /**
     * Busca el usuario_id del mesero activo de turno hoy cuya zona coincide
     * con la zona de la mesa indicada. Devuelve null si no hay asignación.
     */
    public function meseroAsignadoPorMesa(int $mesaId, int $restauranteId): ?int
    {
        $db   = Database::getInstance();
        $stmt = $db->prepare(
            "SELECT mt.usuario_id
             FROM rest_mesas m
             JOIN rest_mesero_turno mt
               ON mt.zona_id = m.zona_id
              AND mt.restaurante_id = m.restaurante_id
              AND mt.turno_fecha = CURDATE()
              AND mt.activo = 1
             WHERE m.id = ? AND m.restaurante_id = ?
             LIMIT 1"
        );
        $stmt->execute([$mesaId, $restauranteId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ? (int)$row['usuario_id'] : null;
    }

    /**
     * Devuelve true si ya existe otra reservación pendiente/confirmada
     * en esa mesa y fecha dentro de una ventana de ±2 horas.
     */
    public function hayConflicto(int $mesaId, string $fecha, string $hora, ?int $excludeId = null): bool
    {
        $db   = Database::getInstance();
        $sql  = "SELECT COUNT(*) FROM rest_reservaciones
                 WHERE mesa_id = ? AND fecha = ?
                   AND estado IN ('pendiente','confirmada')
                   AND ABS(TIMESTAMPDIFF(MINUTE, hora, ?)) < 120";
        $params = [$mesaId, $fecha, $hora];
        if ($excludeId) {
            $sql    .= " AND id != ?";
            $params[] = $excludeId;
        }
        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        return (int)$stmt->fetchColumn() > 0;
    }

    public function cambiarEstado(int $id, string $estado): bool
    {
        return $this->execute(
            "UPDATE rest_reservaciones SET estado = ? WHERE id = ?",
            [$estado, $id]
        );
    }
}
