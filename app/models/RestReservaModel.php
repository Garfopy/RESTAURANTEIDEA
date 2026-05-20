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
                   AND ABS(TIME_TO_SEC(TIMEDIFF(hora, ?))) < 7200";
        $params = [$mesaId, $fecha, $hora];
        if ($excludeId) {
            $sql    .= " AND id != ?";
            $params[] = $excludeId;
        }
        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        return (int)$stmt->fetchColumn() > 0;
    }

    /**
     * Devuelve true si todas las mesas activas del restaurante ya tienen
     * reservación pendiente/confirmada en esa fecha dentro de ±2 horas.
     * Se usa para el form público donde no se elige mesa específica.
     */
    public function estaLleno(int $restauranteId, string $fecha, string $hora): bool
    {
        $db = Database::getInstance();

        // Total de mesas activas
        $stmtTotal = $db->prepare(
            "SELECT COUNT(*) FROM rest_mesas WHERE restaurante_id = ? AND activo = 1"
        );
        $stmtTotal->execute([$restauranteId]);
        $totalMesas = (int)$stmtTotal->fetchColumn();

        if ($totalMesas === 0) return false; // sin mesas configuradas → aceptar

        // Mesas ya ocupadas en ese horario
        $stmtOcup = $db->prepare(
            "SELECT COUNT(DISTINCT mesa_id) FROM rest_reservaciones
             WHERE restaurante_id = ? AND fecha = ?
               AND mesa_id IS NOT NULL
               AND estado IN ('pendiente','confirmada')
               AND ABS(TIME_TO_SEC(TIMEDIFF(hora, ?))) < 7200"
        );
        $stmtOcup->execute([$restauranteId, $fecha, $hora]);
        $ocupadas = (int)$stmtOcup->fetchColumn();

        return $ocupadas >= $totalMesas;
    }

    public function asignar(int $id, ?int $mesaId, ?int $meseroId): void
    {
        $this->execute(
            "UPDATE rest_reservaciones SET mesa_id = ?, mesero_id = ? WHERE id = ?",
            [$mesaId, $meseroId, $id]
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
