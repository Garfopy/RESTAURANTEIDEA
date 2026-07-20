<?php
class RestSocialModeracionModel extends BaseModel
{
    protected string $table = 'social_reports';
    private static array $schemaCache = [];

    private function tableExists(string $table): bool
    {
        $key = 'table:' . $table;
        if (array_key_exists($key, self::$schemaCache)) {
            return self::$schemaCache[$key];
        }

        $row = $this->queryOne(
            "SELECT COUNT(*) AS c
             FROM information_schema.tables
             WHERE table_schema = DATABASE() AND table_name = ?",
            [$table]
        );
        self::$schemaCache[$key] = $row && (int)$row['c'] > 0;
        return self::$schemaCache[$key];
    }

    private function columnExists(string $table, string $column): bool
    {
        $key = 'column:' . $table . '.' . $column;
        if (array_key_exists($key, self::$schemaCache)) {
            return self::$schemaCache[$key];
        }

        $row = $this->queryOne(
            "SELECT COUNT(*) AS c
             FROM information_schema.columns
             WHERE table_schema = DATABASE() AND table_name = ? AND column_name = ?",
            [$table, $column]
        );
        self::$schemaCache[$key] = $row && (int)$row['c'] > 0;
        return self::$schemaCache[$key];
    }

    private function firstExistingColumn(string $table, array $columns): ?string
    {
        foreach ($columns as $column) {
            if ($this->columnExists($table, $column)) {
                return $column;
            }
        }

        return null;
    }

    private function userNameExpr(string $alias): string
    {
        $name = $this->firstExistingColumn('mobile_usuarios', ['nombre', 'nombre_completo', 'name', 'full_name']);
        $email = $this->firstExistingColumn('mobile_usuarios', ['email', 'correo']);

        if ($name && $email) {
            return "COALESCE(NULLIF({$alias}.{$name}, ''), NULLIF({$alias}.{$email}, ''), CONCAT('Usuario #', {$alias}.id))";
        }

        if ($name) {
            return "COALESCE(NULLIF({$alias}.{$name}, ''), CONCAT('Usuario #', {$alias}.id))";
        }

        if ($email) {
            return "COALESCE(NULLIF({$alias}.{$email}, ''), CONCAT('Usuario #', {$alias}.id))";
        }

        return "CONCAT('Usuario #', {$alias}.id)";
    }

    private function userMetaExpr(string $alias): string
    {
        $mesa = $this->firstExistingColumn('mobile_usuarios', ['mesa', 'mesa_nombre', 'table_name']);
        $email = $this->firstExistingColumn('mobile_usuarios', ['email', 'correo']);

        if ($mesa && $email) {
            return "COALESCE(NULLIF({$alias}.{$mesa}, ''), NULLIF({$alias}.{$email}, ''))";
        }

        if ($mesa) {
            return "NULLIF({$alias}.{$mesa}, '')";
        }

        if ($email) {
            return "NULLIF({$alias}.{$email}, '')";
        }

        return 'NULL';
    }

    private function restaurantScopeSql(string $reporterAlias = 'reporter', string $reportedAlias = 'reported'): string
    {
        $restaurant = $this->firstExistingColumn('mobile_usuarios', [
            'current_restaurante_id',
            'restaurante_id',
            'restaurant_id',
            'rest_id',
        ]);

        if (!$restaurant) {
            return '? = ?';
        }

        return "({$reporterAlias}.{$restaurant} = ? OR {$reportedAlias}.{$restaurant} = ?)";
    }

    private function emptyPayload(): array
    {
        return [
            'available' => false,
            'kpis' => [
                'reportes_abiertos' => 0,
                'reportes_mes' => 0,
                'bloqueos_mes' => 0,
                'usuarios_bloqueados' => 0,
            ],
            'reportes' => [],
            'bloqueos' => [],
            'usuarios_observados' => [],
        ];
    }

    public function resumenDashboard(int $restauranteId, int $limit = 5): array
    {
        if (!$this->tableExists('social_reports') && !$this->tableExists('social_blocks')) {
            return $this->emptyPayload();
        }

        $payload = $this->emptyPayload();
        $payload['available'] = true;

        if ($this->tableExists('social_reports') && $this->tableExists('mobile_usuarios')) {
            $payload['kpis']['reportes_abiertos'] = $this->countReports($restauranteId, true);
            $payload['kpis']['reportes_mes'] = $this->countReports($restauranteId, false);
            $payload['reportes'] = $this->recentReports($restauranteId, $limit);
        }

        if ($this->tableExists('social_blocks') && $this->tableExists('mobile_usuarios')) {
            $payload['kpis']['bloqueos_mes'] = $this->countBlocks($restauranteId);
            $payload['kpis']['usuarios_bloqueados'] = $this->countBlockedUsers($restauranteId);
            $payload['bloqueos'] = $this->recentBlocks($restauranteId, $limit);
            $payload['usuarios_observados'] = $this->topBlockedUsers($restauranteId, 3);
        }

        return $payload;
    }

    private function reportStatusPendingSql(string $alias = 'sr'): string
    {
        if (!$this->columnExists('social_reports', 'status')) {
            return '1 = 1';
        }

        return "LOWER(COALESCE({$alias}.status, 'open')) NOT IN (
            'reviewed',
            'revisado',
            'resolved',
            'resuelto',
            'dismissed',
            'descartado',
            'closed',
            'cerrado',
            'banned',
            'auto_banned'
        )";
    }

    private function userIsActiveExpr(string $alias = 'u'): string
    {
        $active = $this->firstExistingColumn('mobile_usuarios', ['activo', 'active', 'is_active']);
        return $active ? "COALESCE({$alias}.{$active}, 0)" : '1';
    }

    private function userSocialActiveExpr(string $alias = 'u'): string
    {
        $active = $this->firstExistingColumn('mobile_usuarios', ['is_social_active', 'social_active']);
        return $active ? "COALESCE({$alias}.{$active}, 0)" : '1';
    }

    private function userEmailExpr(string $alias = 'u'): string
    {
        $email = $this->firstExistingColumn('mobile_usuarios', ['email', 'correo']);
        return $email ? "{$alias}.{$email}" : 'NULL';
    }

    private function canManage(): bool
    {
        return $this->tableExists('social_reports')
            && $this->tableExists('mobile_usuarios')
            && $this->columnExists('social_reports', 'reported_user_id');
    }

    public function gestionReportes(int $restauranteId): array
    {
        if (!$this->canManage()) {
            return [
                'available' => false,
                'kpis' => ['pendientes' => 0, 'auto_baneables' => 0, 'desactivados' => 0],
                'usuarios' => [],
                'reportes' => [],
            ];
        }

        $bloqueosExpr = $this->tableExists('social_blocks')
            ? "(SELECT COUNT(*) FROM social_blocks sb WHERE sb.blocked_user_id = u.id)"
            : '0';

        $usuarios = $this->query(
            "SELECT u.id,
                    {$this->userNameExpr('u')} AS nombre,
                    {$this->userEmailExpr('u')} AS email,
                    {$this->userMetaExpr('u')} AS meta,
                    {$this->userIsActiveExpr('u')} AS activo,
                    {$this->userSocialActiveExpr('u')} AS social_activo,
                    agg.total_reportes,
                    agg.reportes_pendientes,
                    agg.ultimo_reporte,
                    {$bloqueosExpr} AS bloqueos_recibidos
             FROM (
                SELECT sr.reported_user_id,
                       COUNT(sr.id) AS total_reportes,
                       SUM(CASE WHEN {$this->reportStatusPendingSql('sr')} THEN 1 ELSE 0 END) AS reportes_pendientes,
                       MAX(sr.created_at) AS ultimo_reporte
                FROM social_reports sr
                LEFT JOIN mobile_usuarios reporter ON reporter.id = sr.reporter_user_id
                LEFT JOIN mobile_usuarios reported ON reported.id = sr.reported_user_id
                WHERE " . $this->restaurantScopeSql() . "
                GROUP BY sr.reported_user_id
             ) agg
             JOIN mobile_usuarios u ON u.id = agg.reported_user_id
             ORDER BY reportes_pendientes DESC, total_reportes DESC, ultimo_reporte DESC",
            [$restauranteId, $restauranteId]
        );

        $reportes = $this->query(
            "SELECT sr.id,
                    sr.reporter_user_id,
                    sr.reported_user_id,
                    sr.reason,
                    sr.details,
                    sr.status,
                    sr.created_at,
                    {$this->userNameExpr('reporter')} AS reporter_nombre,
                    {$this->userMetaExpr('reporter')} AS reporter_meta,
                    {$this->userNameExpr('reported')} AS reported_nombre,
                    {$this->userMetaExpr('reported')} AS reported_meta,
                    {$this->userIsActiveExpr('reported')} AS reported_activo
             FROM social_reports sr
             LEFT JOIN mobile_usuarios reporter ON reporter.id = sr.reporter_user_id
             LEFT JOIN mobile_usuarios reported ON reported.id = sr.reported_user_id
             WHERE " . $this->restaurantScopeSql() . "
             ORDER BY CASE WHEN {$this->reportStatusPendingSql('sr')} THEN 0 ELSE 1 END,
                      sr.created_at DESC
             LIMIT 100",
            [$restauranteId, $restauranteId]
        );

        $pendientes = 0;
        $autoBaneables = 0;
        $desactivados = 0;
        foreach ($usuarios as $u) {
            $pending = (int)($u['reportes_pendientes'] ?? 0);
            $active = (int)($u['activo'] ?? 0) === 1;
            $pendientes += $pending;
            if ($pending >= 3 && $active) {
                $autoBaneables++;
            }
            if (!$active) {
                $desactivados++;
            }
        }

        return [
            'available' => true,
            'kpis' => [
                'pendientes' => $pendientes,
                'auto_baneables' => $autoBaneables,
                'desactivados' => $desactivados,
            ],
            'usuarios' => $usuarios,
            'reportes' => $reportes,
        ];
    }

    public function autoBanPorReportes(int $restauranteId, int $threshold = 3, ?int $adminId = null): int
    {
        if (!$this->canManage()) {
            return 0;
        }

        $activeCol = $this->firstExistingColumn('mobile_usuarios', ['activo', 'active', 'is_active']);
        $socialActiveCol = $this->firstExistingColumn('mobile_usuarios', ['is_social_active', 'social_active']);
        if (!$activeCol && !$socialActiveCol) {
            return 0;
        }

        $candidates = $this->query(
            "SELECT sr.reported_user_id AS usuario_id, COUNT(*) AS total
             FROM social_reports sr
             LEFT JOIN mobile_usuarios reporter ON reporter.id = sr.reporter_user_id
             LEFT JOIN mobile_usuarios reported ON reported.id = sr.reported_user_id
             WHERE " . $this->restaurantScopeSql() . "
               AND {$this->reportStatusPendingSql('sr')}
             GROUP BY sr.reported_user_id
             HAVING total >= ?",
            [$restauranteId, $restauranteId, $threshold]
        );

        $desactivados = 0;
        foreach ($candidates as $candidate) {
            $userId = (int)($candidate['usuario_id'] ?? 0);
            if ($userId <= 0 || $this->desactivarUsuario($userId, $restauranteId, $adminId, true) === false) {
                continue;
            }
            $desactivados++;
        }

        return $desactivados;
    }

    public function desactivarUsuario(int $userId, int $restauranteId, ?int $adminId = null, bool $auto = false): bool
    {
        if (!$this->canManage() || !$this->usuarioEnRestaurante($userId, $restauranteId)) {
            return false;
        }

        $sets = [];
        $activeCol = $this->firstExistingColumn('mobile_usuarios', ['activo', 'active', 'is_active']);
        $socialActiveCol = $this->firstExistingColumn('mobile_usuarios', ['is_social_active', 'social_active']);
        if ($activeCol) {
            $sets[] = "`{$activeCol}` = 0";
        }
        if ($socialActiveCol && $socialActiveCol !== $activeCol) {
            $sets[] = "`{$socialActiveCol}` = 0";
        }
        if (!$sets) {
            return false;
        }

        $this->execute("UPDATE mobile_usuarios SET " . implode(', ', $sets) . " WHERE id = ?", [$userId]);
        $this->marcarReportesUsuario($userId, $restauranteId, $auto ? 'auto_banned' : 'banned', $adminId);
        return true;
    }

    public function reactivarUsuario(int $userId, int $restauranteId): bool
    {
        if (!$this->canManage() || !$this->usuarioEnRestaurante($userId, $restauranteId)) {
            return false;
        }

        $sets = [];
        $activeCol = $this->firstExistingColumn('mobile_usuarios', ['activo', 'active', 'is_active']);
        $socialActiveCol = $this->firstExistingColumn('mobile_usuarios', ['is_social_active', 'social_active']);
        if ($activeCol) {
            $sets[] = "`{$activeCol}` = 1";
        }
        if ($socialActiveCol && $socialActiveCol !== $activeCol) {
            $sets[] = "`{$socialActiveCol}` = 1";
        }
        if (!$sets) {
            return false;
        }

        return $this->execute("UPDATE mobile_usuarios SET " . implode(', ', $sets) . " WHERE id = ?", [$userId]);
    }

    public function cambiarEstadoReporte(int $reportId, int $restauranteId, string $status, ?int $adminId = null): bool
    {
        if (!$this->canManage() || !$this->reporteEnRestaurante($reportId, $restauranteId)) {
            return false;
        }

        $allowed = ['reviewed', 'dismissed', 'open'];
        if (!in_array($status, $allowed, true)) {
            return false;
        }

        $sets = ['status = ?'];
        $params = [$status];
        if ($this->columnExists('social_reports', 'reviewed_at')) {
            $sets[] = $status === 'open' ? 'reviewed_at = NULL' : 'reviewed_at = NOW()';
        }
        if ($this->columnExists('social_reports', 'reviewed_by')) {
            $sets[] = $status === 'open' ? 'reviewed_by = NULL' : 'reviewed_by = ?';
            if ($status !== 'open') {
                $params[] = $adminId;
            }
        }
        $params[] = $reportId;

        return $this->execute("UPDATE social_reports SET " . implode(', ', $sets) . " WHERE id = ?", $params);
    }

    private function marcarReportesUsuario(int $userId, int $restauranteId, string $status, ?int $adminId): void
    {
        if (!$this->tableExists('social_reports') || !$this->columnExists('social_reports', 'status')) {
            return;
        }

        $sets = ['sr.status = ?'];
        $params = [$status, $restauranteId, $restauranteId, $userId];
        if ($this->columnExists('social_reports', 'reviewed_at')) {
            $sets[] = 'sr.reviewed_at = NOW()';
        }
        if ($this->columnExists('social_reports', 'reviewed_by')) {
            $sets[] = 'sr.reviewed_by = ?';
            $params = [$status, $adminId, $restauranteId, $restauranteId, $userId];
        }

        $this->execute(
            "UPDATE social_reports sr
             LEFT JOIN mobile_usuarios reporter ON reporter.id = sr.reporter_user_id
             LEFT JOIN mobile_usuarios reported ON reported.id = sr.reported_user_id
             SET " . implode(', ', $sets) . "
             WHERE " . $this->restaurantScopeSql() . "
               AND sr.reported_user_id = ?",
            $params
        );
    }

    private function usuarioEnRestaurante(int $userId, int $restauranteId): bool
    {
        if (!$this->tableExists('mobile_usuarios')) {
            return false;
        }

        $restaurantCol = $this->firstExistingColumn('mobile_usuarios', [
            'current_restaurante_id',
            'restaurante_id',
            'restaurant_id',
            'rest_id',
        ]);
        if (!$restaurantCol) {
            return true;
        }

        $row = $this->queryOne(
            "SELECT id FROM mobile_usuarios WHERE id = ? AND {$restaurantCol} = ? LIMIT 1",
            [$userId, $restauranteId]
        );
        return $row !== null;
    }

    private function reporteEnRestaurante(int $reportId, int $restauranteId): bool
    {
        $row = $this->queryOne(
            "SELECT sr.id
             FROM social_reports sr
             LEFT JOIN mobile_usuarios reporter ON reporter.id = sr.reporter_user_id
             LEFT JOIN mobile_usuarios reported ON reported.id = sr.reported_user_id
             WHERE sr.id = ? AND " . $this->restaurantScopeSql() . "
             LIMIT 1",
            [$reportId, $restauranteId, $restauranteId]
        );
        return $row !== null;
    }

    private function countReports(int $restauranteId, bool $onlyOpen): int
    {
        $where = [$this->restaurantScopeSql()];
        $params = [$restauranteId, $restauranteId];

        if ($onlyOpen) {
            $where[] = "LOWER(COALESCE(sr.status, 'open')) IN ('open', 'abierto', 'pendiente', 'pending')";
        } elseif ($this->columnExists('social_reports', 'created_at')) {
            $where[] = 'sr.created_at >= DATE_FORMAT(CURDATE(), "%Y-%m-01")';
        }

        $row = $this->queryOne(
            "SELECT COUNT(*) AS c
             FROM social_reports sr
             LEFT JOIN mobile_usuarios reporter ON reporter.id = sr.reporter_user_id
             LEFT JOIN mobile_usuarios reported ON reported.id = sr.reported_user_id
             WHERE " . implode(' AND ', $where),
            $params
        );

        return (int)($row['c'] ?? 0);
    }

    private function countBlocks(int $restauranteId): int
    {
        $where = [$this->restaurantScopeSql('blocker', 'blocked')];
        $params = [$restauranteId, $restauranteId];

        if ($this->columnExists('social_blocks', 'created_at')) {
            $where[] = 'sb.created_at >= DATE_FORMAT(CURDATE(), "%Y-%m-01")';
        }

        $row = $this->queryOne(
            "SELECT COUNT(*) AS c
             FROM social_blocks sb
             LEFT JOIN mobile_usuarios blocker ON blocker.id = sb.blocker_user_id
             LEFT JOIN mobile_usuarios blocked ON blocked.id = sb.blocked_user_id
             WHERE " . implode(' AND ', $where),
            $params
        );

        return (int)($row['c'] ?? 0);
    }

    private function countBlockedUsers(int $restauranteId): int
    {
        $row = $this->queryOne(
            "SELECT COUNT(DISTINCT sb.blocked_user_id) AS c
             FROM social_blocks sb
             LEFT JOIN mobile_usuarios blocker ON blocker.id = sb.blocker_user_id
             LEFT JOIN mobile_usuarios blocked ON blocked.id = sb.blocked_user_id
             WHERE " . $this->restaurantScopeSql('blocker', 'blocked'),
            [$restauranteId, $restauranteId]
        );

        return (int)($row['c'] ?? 0);
    }

    private function recentReports(int $restauranteId, int $limit): array
    {
        $limit = max(1, min(10, $limit));

        return $this->query(
            "SELECT sr.id,
                    sr.reason,
                    sr.details,
                    sr.status,
                    sr.created_at,
                    {$this->userNameExpr('reporter')} AS reporter_nombre,
                    {$this->userMetaExpr('reporter')} AS reporter_meta,
                    {$this->userNameExpr('reported')} AS reported_nombre,
                    {$this->userMetaExpr('reported')} AS reported_meta
             FROM social_reports sr
             LEFT JOIN mobile_usuarios reporter ON reporter.id = sr.reporter_user_id
             LEFT JOIN mobile_usuarios reported ON reported.id = sr.reported_user_id
             WHERE " . $this->restaurantScopeSql() . "
             ORDER BY CASE WHEN LOWER(COALESCE(sr.status, 'open')) IN ('open', 'abierto', 'pendiente', 'pending') THEN 0 ELSE 1 END,
                      sr.created_at DESC
             LIMIT {$limit}",
            [$restauranteId, $restauranteId]
        );
    }

    private function recentBlocks(int $restauranteId, int $limit): array
    {
        $limit = max(1, min(10, $limit));

        return $this->query(
            "SELECT sb.id,
                    sb.reason,
                    sb.created_at,
                    {$this->userNameExpr('blocker')} AS blocker_nombre,
                    {$this->userMetaExpr('blocker')} AS blocker_meta,
                    {$this->userNameExpr('blocked')} AS blocked_nombre,
                    {$this->userMetaExpr('blocked')} AS blocked_meta
             FROM social_blocks sb
             LEFT JOIN mobile_usuarios blocker ON blocker.id = sb.blocker_user_id
             LEFT JOIN mobile_usuarios blocked ON blocked.id = sb.blocked_user_id
             WHERE " . $this->restaurantScopeSql('blocker', 'blocked') . "
             ORDER BY sb.created_at DESC
             LIMIT {$limit}",
            [$restauranteId, $restauranteId]
        );
    }

    private function topBlockedUsers(int $restauranteId, int $limit): array
    {
        $limit = max(1, min(10, $limit));

        return $this->query(
            "SELECT sb.blocked_user_id AS usuario_id,
                    {$this->userNameExpr('blocked')} AS nombre,
                    {$this->userMetaExpr('blocked')} AS meta,
                    COUNT(*) AS total_bloqueos,
                    MAX(sb.created_at) AS ultimo_bloqueo
             FROM social_blocks sb
             LEFT JOIN mobile_usuarios blocker ON blocker.id = sb.blocker_user_id
             LEFT JOIN mobile_usuarios blocked ON blocked.id = sb.blocked_user_id
             WHERE " . $this->restaurantScopeSql('blocker', 'blocked') . "
             GROUP BY sb.blocked_user_id, nombre, meta
             ORDER BY total_bloqueos DESC, ultimo_bloqueo DESC
             LIMIT {$limit}",
            [$restauranteId, $restauranteId]
        );
    }
}
