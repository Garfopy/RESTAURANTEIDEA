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
