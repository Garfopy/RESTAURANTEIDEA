<?php
class RestReservaModel extends BaseModel
{
    protected string $table = 'rest_reservaciones';
    private const RESERVA_BLOQUEO_ANTES_SEGUNDOS = 7200;
    private const RESERVA_BLOQUEO_DESPUES_SEGUNDOS = 9000;
    private static array $columnCache = [];

    private function hasColumn(string $column): bool
    {
        if (array_key_exists($column, self::$columnCache)) {
            return self::$columnCache[$column];
        }

        if (!preg_match('/^[a-zA-Z0-9_]+$/', $column)) {
            return self::$columnCache[$column] = false;
        }

        try {
            $stmt = $this->db->prepare("SHOW COLUMNS FROM `{$this->table}` LIKE ?");
            $stmt->execute([$column]);
            self::$columnCache[$column] = (bool)$stmt->fetch(PDO::FETCH_ASSOC);
        } catch (\Throwable $e) {
            self::$columnCache[$column] = false;
        }

        return self::$columnCache[$column];
    }

    public function aplicarCanalReserva(array $data, string $canal): array
    {
        if ($this->hasColumn('canal_reserva')) {
            $data['canal_reserva'] = in_array($canal, ['web', 'movil'], true) ? $canal : 'web';
        }

        return $data;
    }

    public function getResumenCanal(int $restauranteId): array
    {
        if ($this->hasColumn('canal_reserva')) {
            $row = $this->queryOne(
                "SELECT
                    SUM(CASE WHEN canal_reserva = 'movil' THEN 1 ELSE 0 END) AS movil,
                    SUM(CASE WHEN canal_reserva = 'web' OR canal_reserva IS NULL OR canal_reserva = '' THEN 1 ELSE 0 END) AS web,
                    COUNT(*) AS total
                 FROM rest_reservaciones
                 WHERE restaurante_id = ?",
                [$restauranteId]
            ) ?: [];
        } else {
            $row = $this->queryOne(
                "SELECT COUNT(*) AS total
                 FROM rest_reservaciones
                 WHERE restaurante_id = ?",
                [$restauranteId]
            ) ?: [];
            $row['web'] = (int)($row['total'] ?? 0);
            $row['movil'] = 0;
        }

        $web = (int)($row['web'] ?? 0);
        $movil = (int)($row['movil'] ?? 0);
        $total = (int)($row['total'] ?? ($web + $movil));
        $movilPct = $total > 0 ? round(($movil / $total) * 100, 1) : 0.0;

        return [
            'web' => $web,
            'movil' => $movil,
            'total' => $total,
            'web_pct' => $total > 0 ? round(($web / $total) * 100, 1) : 0.0,
            'movil_pct' => $movilPct,
            'chart_pct' => $total > 0 ? round(($movil / $total) * 100, 4) : 0.0,
        ];
    }

    public function getByRestaurante(int $restauranteId, int $page = 1, ?string $estado = null, ?string $fechaDesde = null, ?string $fechaHasta = null): array
    {
        $allowed = ['pendiente', 'confirmada', 'cancelada', 'completada'];
        $params  = [$restauranteId];
        $where   = [];

        if ($estado && in_array($estado, $allowed, true)) {
            $where[]  = 'r.estado = ?';
            $params[] = $estado;
        }

        if ($fechaDesde) {
            $where[]  = 'r.fecha >= ?';
            $params[] = $fechaDesde;
        }

        if ($fechaHasta) {
            $where[]  = 'r.fecha <= ?';
            $params[] = $fechaHasta;
        }

        $whereClause = $where ? 'AND ' . implode(' AND ', $where) : '';

        $sql = "SELECT r.id, r.restaurante_id, r.mesa_id, r.comensal_id, r.mesero_id,
                       r.nombre, r.telefono, r.email, r.fecha, r.hora, r.personas,
                       r.estado, r.origen, r.notas, r.created_at, r.updated_at,
                       m.nombre AS mesa_nombre,
                       u.nombre AS mesero_nombre
                FROM rest_reservaciones r
                LEFT JOIN rest_mesas m ON m.id = r.mesa_id
                LEFT JOIN usuarios u   ON u.id = r.mesero_id
                WHERE r.restaurante_id = ? $whereClause
                ORDER BY r.fecha DESC, r.hora DESC";
        return $this->paginate($sql, $params, $page);
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

    public function getHoyPorZonas(int $restauranteId, array $zonaIds): array
    {
        if (empty($zonaIds)) {
            return $this->query(
                "SELECT r.*, r.fecha, m.nombre AS mesa_nombre
                 FROM rest_reservaciones r
                 LEFT JOIN rest_mesas m ON m.id = r.mesa_id
                 WHERE r.restaurante_id = ?
                   AND r.fecha BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 1 DAY)
                   AND r.estado IN ('pendiente','confirmada')
                 ORDER BY r.fecha ASC, r.hora ASC",
                [$restauranteId]
            );
        }
        $placeholders = implode(',', array_fill(0, count($zonaIds), '?'));
        return $this->query(
            "SELECT r.*, r.fecha, m.nombre AS mesa_nombre
             FROM rest_reservaciones r
             JOIN rest_mesas m ON m.id = r.mesa_id
             WHERE r.restaurante_id = ?
               AND r.fecha BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 1 DAY)
               AND r.estado IN ('pendiente','confirmada')
               AND m.zona_id IN ($placeholders)
             ORDER BY r.fecha ASC, r.hora ASC",
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
                   AND TIME_TO_SEC(TIMEDIFF(?, hora)) >= -?
                   AND TIME_TO_SEC(TIMEDIFF(?, hora)) < ?";
        $params = [
            $mesaId,
            $fecha,
            $hora,
            self::RESERVA_BLOQUEO_ANTES_SEGUNDOS,
            $hora,
            self::RESERVA_BLOQUEO_DESPUES_SEGUNDOS,
        ];
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
               AND TIME_TO_SEC(TIMEDIFF(?, hora)) >= -?
               AND TIME_TO_SEC(TIMEDIFF(?, hora)) < ?"
        );
        $stmtOcup->execute([
            $restauranteId,
            $fecha,
            $hora,
            self::RESERVA_BLOQUEO_ANTES_SEGUNDOS,
            $hora,
            self::RESERVA_BLOQUEO_DESPUES_SEGUNDOS,
        ]);
        $ocupadas = (int)$stmtOcup->fetchColumn();

        return $ocupadas >= $totalMesas;
    }

    /**
     * Mesas activas del restaurante con capacidad >= $personas
     * y sin conflicto (±2h) con otra reservación pendiente/confirmada
     * en la fecha/hora indicada. Ordenadas por capacidad asc para
     * sugerir la mesa más ajustada al grupo.
     */
    public function mesasDisponiblesParaCapacidad(
        int $restauranteId,
        string $fecha,
        string $hora,
        int $personas
    ): array {
        return $this->query(
            "SELECT m.id, m.nombre, m.capacidad, z.nombre AS zona_nombre
             FROM rest_mesas m
             LEFT JOIN rest_zonas z ON z.id = m.zona_id
             WHERE m.restaurante_id = ?
               AND m.activo = 1
               AND m.capacidad >= ?
               AND NOT EXISTS (
                 SELECT 1 FROM rest_reservaciones r
                 WHERE r.mesa_id = m.id
                   AND r.fecha   = ?
                   AND r.estado IN ('pendiente','confirmada')
                    AND TIME_TO_SEC(TIMEDIFF(?, r.hora)) >= -?
                    AND TIME_TO_SEC(TIMEDIFF(?, r.hora)) < ?
                )
              ORDER BY m.capacidad ASC, m.nombre ASC",
            [
                $restauranteId,
                $personas,
                $fecha,
                $hora,
                self::RESERVA_BLOQUEO_ANTES_SEGUNDOS,
                $hora,
                self::RESERVA_BLOQUEO_DESPUES_SEGUNDOS,
            ]
        );
    }

    /**
     * Reservaciones confirmadas/pendientes para mañana cuyo recordatorio
     * aún no ha sido enviado. Usado por el cron de recordatorios 24h.
     */
    public function getParaRecordatorio(): array
    {
        return $this->query(
            "SELECT r.*, rest.nombre AS rest_nombre, rest.slug AS rest_slug,
                    rest.telefono AS rest_telefono, rest.direccion AS rest_direccion,
                    rest.color_primario, m.nombre AS mesa_nombre
             FROM rest_reservaciones r
             JOIN rest_restaurantes rest ON rest.id = r.restaurante_id
             LEFT JOIN rest_mesas m      ON m.id   = r.mesa_id
             WHERE r.fecha = DATE_ADD(CURDATE(), INTERVAL 1 DAY)
               AND r.estado IN ('pendiente','confirmada')
               AND r.recordatorio_enviado = 0
               AND r.email IS NOT NULL AND r.email <> ''",
            []
        );
    }

    public function getConfirmacionesPendientes(int $limit = 50): array
    {
        $limit = max(1, min(200, $limit));
        return $this->query(
            "SELECT r.*, rest.nombre AS rest_nombre, rest.slug AS rest_slug,
                    rest.telefono AS rest_telefono, rest.direccion AS rest_direccion,
                    rest.color_primario, m.nombre AS mesa_nombre
             FROM rest_reservaciones r
             JOIN rest_restaurantes rest ON rest.id = r.restaurante_id
             LEFT JOIN rest_mesas m      ON m.id   = r.mesa_id
             WHERE r.fecha >= CURDATE()
               AND r.estado = 'confirmada'
               AND r.confirmacion_enviada = 0
               AND r.email IS NOT NULL AND r.email <> ''
             ORDER BY r.created_at ASC
             LIMIT {$limit}",
            []
        );
    }

    public function marcarConfirmacionEnviada(int $id): void
    {
        $this->execute(
            "UPDATE rest_reservaciones SET confirmacion_enviada = 1 WHERE id = ?",
            [$id]
        );
    }

    public function marcarRecordatorioEnviado(int $id): void
    {
        $this->execute(
            "UPDATE rest_reservaciones SET recordatorio_enviado = 1 WHERE id = ?",
            [$id]
        );
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

    /**
     * Busca una reservación por su ID validando que pertenezca al restaurante
     * y que el teléfono coincida (para cancelación pública sin login).
     */
    public function getParaCancelar(int $id, int $restauranteId, string $telefono): ?array
    {
        return $this->queryOne(
            "SELECT r.*, m.nombre AS mesa_nombre
             FROM rest_reservaciones r
             LEFT JOIN rest_mesas m ON m.id = r.mesa_id
             WHERE r.id = ? AND r.restaurante_id = ? AND r.telefono = ?
               AND r.estado IN ('pendiente','confirmada')",
            [$id, $restauranteId, $telefono]
        );
    }
}
