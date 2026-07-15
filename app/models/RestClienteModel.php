<?php
class RestClienteModel extends BaseModel
{
    protected string $table = 'rest_comensales';
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

    private function textExpr(string $alias, string $column): string
    {
        return "CONVERT({$alias}.{$column} USING utf8mb4) COLLATE utf8mb4_unicode_ci";
    }

    private function phoneExpr(string $alias, string $column): string
    {
        $value = $this->textExpr($alias, $column);
        return "NULLIF(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE({$value}, ' ', ''), '-', ''), '(', ''), ')', ''), '+', ''), '')";
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

    private function mobileColumns(): array
    {
        return [
            'name' => $this->firstExistingColumn('mobile_usuarios', ['nombre', 'nombre_completo', 'name', 'full_name']),
            'email' => $this->firstExistingColumn('mobile_usuarios', ['email', 'correo']),
            'phone' => $this->firstExistingColumn('mobile_usuarios', ['telefono', 'celular', 'phone', 'mobile', 'whatsapp']),
            'created_at' => $this->firstExistingColumn('mobile_usuarios', ['created_at', 'fecha_registro', 'registrado_at']),
            'active' => $this->firstExistingColumn('mobile_usuarios', ['activo', 'active', 'is_active']),
            'role' => $this->firstExistingColumn('mobile_usuarios', ['rol', 'role']),
            'restaurant' => $this->firstExistingColumn('mobile_usuarios', ['current_restaurante_id', 'restaurante_id', 'restaurant_id', 'rest_id']),
        ];
    }

    private function mobileColumnSql(string $alias, ?string $column): string
    {
        return $column ? "{$alias}.{$column}" : 'NULL';
    }

    private function mobileActiveWhere(string $alias, ?string $activeColumn): string
    {
        return $activeColumn ? " AND {$alias}.{$activeColumn} = 1" : '';
    }

    private function mobileRoleWhere(string $alias, ?string $roleColumn): string
    {
        return $roleColumn ? " AND {$alias}.{$roleColumn} = 'user'" : '';
    }

    private function mobileMatchConditions(string $mobileAlias, string $comensalAlias, ?string $emailColumn, ?string $phoneColumn): array
    {
        $conditions = [];

        if ($this->columnExists('rest_comensales', 'mobile_usuario_id')) {
            $conditions[] = "({$comensalAlias}.mobile_usuario_id IS NOT NULL AND {$comensalAlias}.mobile_usuario_id = {$mobileAlias}.id)";
        }

        if ($emailColumn) {
            $mobileEmailExpr = "LOWER(TRIM({$this->textExpr($mobileAlias, $emailColumn)}))";
            $comensalEmailExpr = "LOWER(TRIM({$this->textExpr($comensalAlias, 'email')}))";
            $conditions[] = "({$comensalAlias}.email IS NOT NULL AND {$comensalAlias}.email <> '' AND {$mobileEmailExpr} = {$comensalEmailExpr})";
        }

        if ($phoneColumn) {
            $conditions[] = "({$comensalAlias}.telefono IS NOT NULL AND {$comensalAlias}.telefono <> '' AND {$this->phoneExpr($mobileAlias, $phoneColumn)} = {$this->phoneExpr($comensalAlias, 'telefono')})";
        }

        return $conditions;
    }

    private function mobileUserColumn(string $table): ?string
    {
        if (!$this->tableExists($table)) {
            return null;
        }

        return $this->firstExistingColumn($table, [
            'mobile_usuario_id',
            'mobile_user_id',
            'app_cliente_id',
            'app_usuario_id',
            'usuario_mobile_id',
            'amare_usuario_id',
        ]);
    }

    private function pedidoFechaExpr(string $alias = 'p'): string
    {
        $columns = [];
        foreach (['pagado_at', 'cerrado_at', 'actualizado_at', 'created_at'] as $column) {
            if ($this->columnExists('rest_pedidos', $column)) {
                $columns[] = "{$alias}.{$column}";
            }
        }

        if (!$columns) {
            return 'NULL';
        }

        return count($columns) === 1 ? $columns[0] : 'COALESCE(' . implode(', ', $columns) . ')';
    }

    private function mobileGastoTotalSql(string $mobileAlias = 'mu', ?int $restauranteId = null): string
    {
        $restauranteId = $restauranteId ? (int)$restauranteId : null;
        $visitaMobileColumn = $this->mobileUserColumn('rest_visitas');
        if ($visitaMobileColumn) {
            $visitaRestFilter = $restauranteId ? " AND vt.restaurante_id = {$restauranteId}" : '';
            $pedidoVisitaRestFilter = $restauranteId ? " AND vp.restaurante_id = {$restauranteId}" : '';
            return "(
                COALESCE((
                    SELECT SUM(t.total)
                    FROM rest_visitas vt
                    JOIN rest_tickets t ON t.visita_id = vt.id
                    WHERE vt.{$visitaMobileColumn} = {$mobileAlias}.id
                      {$visitaRestFilter}
                      AND t.estado = 'pagado'
                ), 0)
                +
                COALESCE((
                    SELECT SUM(COALESCE(NULLIF(p.total, 0), p.subtotal))
                    FROM rest_visitas vp
                    JOIN rest_pedidos p ON p.visita_id = vp.id
                    LEFT JOIN rest_tickets tp
                      ON tp.visita_id = vp.id
                     AND tp.estado = 'pagado'
                    WHERE vp.{$visitaMobileColumn} = {$mobileAlias}.id
                      {$pedidoVisitaRestFilter}
                      AND p.estado <> 'cancelado'
                      AND tp.id IS NULL
                ), 0)
            )";
        }

        $parts = [];
        $ticketMobileColumn = $this->mobileUserColumn('rest_tickets');
        if ($ticketMobileColumn) {
            $ticketRestFilter = $restauranteId ? " AND t.restaurante_id = {$restauranteId}" : '';
            $parts[] = "COALESCE((
                SELECT SUM(t.total)
                FROM rest_tickets t
                WHERE t.{$ticketMobileColumn} = {$mobileAlias}.id
                  {$ticketRestFilter}
                  AND t.estado = 'pagado'
            ), 0)";
        }

        $pedidoMobileColumn = $this->mobileUserColumn('rest_pedidos');
        if ($pedidoMobileColumn) {
            $pedidoRestFilter = $restauranteId ? " AND p.restaurante_id = {$restauranteId}" : '';
            $parts[] = "COALESCE((
                SELECT SUM(COALESCE(NULLIF(p.total, 0), p.subtotal))
                FROM rest_pedidos p
                LEFT JOIN rest_tickets tp
                  ON tp.visita_id = p.visita_id
                 AND tp.estado = 'pagado'
                WHERE p.{$pedidoMobileColumn} = {$mobileAlias}.id
                  {$pedidoRestFilter}
                  AND p.estado <> 'cancelado'
                  AND tp.id IS NULL
            ), 0)";
        }

        return $parts ? '(' . implode(' + ', $parts) . ')' : '0.00';
    }

    private function mobileVisitasCountSql(string $mobileAlias = 'mu', ?int $restauranteId = null): string
    {
        $restauranteId = $restauranteId ? (int)$restauranteId : null;
        $visitaMobileColumn = $this->mobileUserColumn('rest_visitas');
        if ($visitaMobileColumn) {
            $restFilter = $restauranteId ? " AND v.restaurante_id = {$restauranteId}" : '';
            return "COALESCE((
                SELECT COUNT(DISTINCT v.id)
                FROM rest_visitas v
                WHERE v.{$visitaMobileColumn} = {$mobileAlias}.id
                  {$restFilter}
            ), 0)";
        }

        $pedidoMobileColumn = $this->mobileUserColumn('rest_pedidos');
        if ($pedidoMobileColumn) {
            $restFilter = $restauranteId ? " AND p.restaurante_id = {$restauranteId}" : '';
            return "COALESCE((
                SELECT COUNT(DISTINCT COALESCE(p.visita_id, p.id))
                FROM rest_pedidos p
                WHERE p.{$pedidoMobileColumn} = {$mobileAlias}.id
                  {$restFilter}
                  AND p.estado <> 'cancelado'
            ), 0)";
        }

        $ticketMobileColumn = $this->mobileUserColumn('rest_tickets');
        if ($ticketMobileColumn) {
            $restFilter = $restauranteId ? " AND t.restaurante_id = {$restauranteId}" : '';
            return "COALESCE((
                SELECT COUNT(DISTINCT COALESCE(t.visita_id, t.id))
                FROM rest_tickets t
                WHERE t.{$ticketMobileColumn} = {$mobileAlias}.id
                  {$restFilter}
                  AND t.estado = 'pagado'
            ), 0)";
        }

        return '0';
    }

    private function mobileUltimaActividadSql(string $mobileAlias = 'mu', ?int $restauranteId = null): string
    {
        $restauranteId = $restauranteId ? (int)$restauranteId : null;
        $visitaMobileColumn = $this->mobileUserColumn('rest_visitas');
        if ($visitaMobileColumn) {
            $restFilter = $restauranteId ? " AND v.restaurante_id = {$restauranteId}" : '';
            return "(
                SELECT MAX(v.created_at)
                FROM rest_visitas v
                WHERE v.{$visitaMobileColumn} = {$mobileAlias}.id
                  {$restFilter}
            )";
        }

        $pedidoMobileColumn = $this->mobileUserColumn('rest_pedidos');
        if ($pedidoMobileColumn) {
            $restFilter = $restauranteId ? " AND p.restaurante_id = {$restauranteId}" : '';
            $pedidoFechaExpr = $this->pedidoFechaExpr('p');
            return "(
                SELECT MAX({$pedidoFechaExpr})
                FROM rest_pedidos p
                WHERE p.{$pedidoMobileColumn} = {$mobileAlias}.id
                  {$restFilter}
                  AND p.estado <> 'cancelado'
            )";
        }

        $ticketMobileColumn = $this->mobileUserColumn('rest_tickets');
        if ($ticketMobileColumn) {
            $restFilter = $restauranteId ? " AND t.restaurante_id = {$restauranteId}" : '';
            return "(
                SELECT MAX(COALESCE(t.pagado_at, t.created_at))
                FROM rest_tickets t
                WHERE t.{$ticketMobileColumn} = {$mobileAlias}.id
                  {$restFilter}
                  AND t.estado = 'pagado'
            )";
        }

        return 'NULL';
    }

    private function mobileJoinSql(): array
    {
        if (!$this->tableExists('mobile_usuarios')) {
            return [
                'select' => ', NULL AS mobile_usuario_id, NULL AS mobile_nombre, NULL AS mobile_email, NULL AS mobile_telefono',
                'join' => '',
            ];
        }

        $conditions = [];
        $selects = ['MIN(mu.id) AS mobile_usuario_id'];
        $mobileColumns = $this->mobileColumns();
        $conditions = $this->mobileMatchConditions('mu', 'c', $mobileColumns['email'], $mobileColumns['phone']);
        $mobileConstraints = $this->mobileActiveWhere('mu', $mobileColumns['active'])
            . $this->mobileRoleWhere('mu', $mobileColumns['role']);

        if ($mobileColumns['name']) {
            $selects[] = "MIN(mu.{$mobileColumns['name']}) AS mobile_nombre";
        } else {
            $selects[] = 'NULL AS mobile_nombre';
        }

        if ($mobileColumns['email']) {
            $selects[] = "MIN(mu.{$mobileColumns['email']}) AS mobile_email";
        } else {
            $selects[] = 'NULL AS mobile_email';
        }

        if ($mobileColumns['phone']) {
            $selects[] = "MIN(mu.{$mobileColumns['phone']}) AS mobile_telefono";
        } else {
            $selects[] = 'NULL AS mobile_telefono';
        }

        if (!$conditions) {
            return [
                'select' => ', NULL AS mobile_usuario_id, NULL AS mobile_nombre, NULL AS mobile_email, NULL AS mobile_telefono',
                'join' => '',
            ];
        }

        return [
            'select' => ', ' . implode(', ', $selects),
            'join' => 'LEFT JOIN mobile_usuarios mu ON ((' . implode(' OR ', $conditions) . ')' . $mobileConstraints . ')',
        ];
    }

    private function gastoTotalSql(string $comensalAlias = 'c'): string
    {
        return "(
            COALESCE((
                SELECT SUM(t.total)
                FROM rest_visitas vt
                JOIN rest_tickets t ON t.visita_id = vt.id
                WHERE vt.comensal_id = {$comensalAlias}.id
                  AND t.estado = 'pagado'
            ), 0)
            +
            COALESCE((
                SELECT SUM(COALESCE(NULLIF(p.total, 0), p.subtotal))
                FROM rest_visitas vp
                JOIN rest_pedidos p ON p.visita_id = vp.id
                LEFT JOIN rest_tickets tp
                  ON tp.visita_id = vp.id
                 AND tp.estado = 'pagado'
                WHERE vp.comensal_id = {$comensalAlias}.id
                  AND p.estado <> 'cancelado'
                  AND tp.id IS NULL
            ), 0)
        )";
    }

    public function getByRestaurante(int $restauranteId, int $page = 1, string $tipo = 'todos'): array
    {
        $tipo = in_array($tipo, ['todos', 'web', 'mobile'], true) ? $tipo : 'todos';
        $mobile = $this->mobileJoinSql();
        $gastoTotalSql = $this->gastoTotalSql('c');
        $pedidoMobileColumn = $this->mobileUserColumn('rest_pedidos');
        $mobileMetricsJoin = '';
        $mobileGastoExpr = '0.00';
        $mobileVisitasExpr = '0';
        $mobileUltimaExpr = 'NULL';
        $params = [];

        if ($pedidoMobileColumn && $mobile['join'] !== '') {
            $restauranteIdSql = (int)$restauranteId;
            $pedidoFechaExpr = $this->pedidoFechaExpr('p');
            $mobileMetricsJoin = "
                LEFT JOIN (
                    SELECT p.{$pedidoMobileColumn} AS mobile_usuario_id,
                           SUM(COALESCE(NULLIF(p.total, 0), p.subtotal)) AS gasto_total,
                           COUNT(DISTINCT COALESCE(p.visita_id, p.id)) AS num_visitas,
                           MAX({$pedidoFechaExpr}) AS ultima_visita
                    FROM rest_pedidos p
                    WHERE p.restaurante_id = {$restauranteIdSql}
                      AND p.estado <> 'cancelado'
                      AND p.{$pedidoMobileColumn} IS NOT NULL
                    GROUP BY p.{$pedidoMobileColumn}
                ) pm ON pm.mobile_usuario_id = mu.id";
            $mobileGastoExpr = 'COALESCE(MAX(pm.gasto_total), 0)';
            $mobileVisitasExpr = 'COALESCE(MAX(pm.num_visitas), 0)';
            $mobileUltimaExpr = 'MAX(pm.ultima_visita)';
        }

        $parts = [];

        if ($tipo !== 'mobile') {
            $webSql = "SELECT c.id,
                           c.restaurante_id,
                           c.nombre,
                           c.telefono,
                           c.email,
                           c.total_visitas,
                           c.total_gastado,
                           c.ultima_visita,
                           c.created_at,
                           ({$gastoTotalSql} + {$mobileGastoExpr}) AS gasto_total,
                           (COALESCE(COUNT(DISTINCT v.id), 0) + {$mobileVisitasExpr}) AS num_visitas,
                           COALESCE(GREATEST(MAX(v.created_at), {$mobileUltimaExpr}), MAX(v.created_at), {$mobileUltimaExpr}) AS ultima_visita_real,
                           c.id                                AS detalle_id,
                           'comensal'                          AS origen,
                           " . ltrim($mobile['select'], ', ') . "
                    FROM rest_comensales c
                    LEFT JOIN rest_visitas v ON v.comensal_id = c.id
                    {$mobile['join']}
                    {$mobileMetricsJoin}
                    WHERE c.restaurante_id = ?
                    GROUP BY c.id";

            $parts[] = $webSql;
            $params[] = $restauranteId;
        }

        if ($tipo !== 'web' && $this->tableExists('mobile_usuarios')) {
            $mobileColumns = $this->mobileColumns();
            $matchConditions = $this->mobileMatchConditions('mu', 'cm', $mobileColumns['email'], $mobileColumns['phone']);
            $matchSql = $matchConditions ? implode(' OR ', $matchConditions) : '0 = 1';
            $createdAtSql = $this->mobileColumnSql('mu', $mobileColumns['created_at']);
            $mobileGastoTotalSql = $this->mobileGastoTotalSql('mu', $restauranteId);
            $mobileVisitasCountSql = $this->mobileVisitasCountSql('mu', $restauranteId);
            $mobileUltimaActividadSql = $this->mobileUltimaActividadSql('mu', $restauranteId);
            $scopeConditions = [];

            $params[] = $restauranteId;
            $params[] = $restauranteId;

            if ($mobileColumns['restaurant']) {
                $scopeConditions[] = "mu.{$mobileColumns['restaurant']} = ?";
                $params[] = $restauranteId;
            }

            if ($pedidoMobileColumn) {
                $scopeConditions[] = "EXISTS (
                    SELECT 1
                    FROM rest_pedidos ps
                    WHERE ps.{$pedidoMobileColumn} = mu.id
                      AND ps.restaurante_id = ?
                )";
                $params[] = $restauranteId;
            }

            $restaurantWhere = $scopeConditions ? ' AND (' . implode(' OR ', $scopeConditions) . ')' : '';
            $mobileDedupWhere = $tipo === 'mobile' ? '1 = 1' : 'cm.id IS NULL';

            $parts[] = "
                SELECT -CAST(mu.id AS SIGNED)            AS id,
                       ?                                 AS restaurante_id,
                       NULL                              AS nombre,
                       NULL                              AS telefono,
                       NULL                              AS email,
                       0                                 AS total_visitas,
                       0.00                              AS total_gastado,
                       NULL                              AS ultima_visita,
                       {$createdAtSql}                   AS created_at,
                       {$mobileGastoTotalSql}            AS gasto_total,
                       {$mobileVisitasCountSql}          AS num_visitas,
                       {$mobileUltimaActividadSql}       AS ultima_visita_real,
                       -CAST(mu.id AS SIGNED)            AS detalle_id,
                       'mobile'                          AS origen,
                       mu.id                             AS mobile_usuario_id,
                       {$this->mobileColumnSql('mu', $mobileColumns['name'])} AS mobile_nombre,
                       {$this->mobileColumnSql('mu', $mobileColumns['email'])} AS mobile_email,
                       {$this->mobileColumnSql('mu', $mobileColumns['phone'])} AS mobile_telefono
                FROM mobile_usuarios mu
                LEFT JOIN rest_comensales cm
                  ON cm.restaurante_id = ?
                 AND ({$matchSql})
                WHERE {$mobileDedupWhere}
                  {$this->mobileActiveWhere('mu', $mobileColumns['active'])}
                  {$this->mobileRoleWhere('mu', $mobileColumns['role'])}
                  {$restaurantWhere}";
        }

        if (!$parts) {
            $sql = "SELECT NULL AS id,
                           NULL AS restaurante_id,
                           NULL AS nombre,
                           NULL AS telefono,
                           NULL AS email,
                           0 AS total_visitas,
                           0.00 AS total_gastado,
                           NULL AS ultima_visita,
                           NULL AS created_at,
                           0.00 AS gasto_total,
                           0 AS num_visitas,
                           NULL AS ultima_visita_real,
                           NULL AS detalle_id,
                           'mobile' AS origen,
                           NULL AS mobile_usuario_id,
                           NULL AS mobile_nombre,
                           NULL AS mobile_email,
                           NULL AS mobile_telefono
                    WHERE 1 = 0";
        } else {
            $sql = implode(' UNION ALL ', $parts);
        }
        $sql .= " ORDER BY ultima_visita_real DESC, created_at DESC, id DESC";
        return $this->paginate($sql, $params, $page);
    }

    public function buscarOCrear(int $restauranteId, ?string $nombre, ?string $telefono, ?string $email): int
    {
        $email = $email ? mb_strtolower(trim($email)) : null;

        if ($email) {
            $existing = $this->queryOne(
                "SELECT id, nombre FROM rest_comensales WHERE restaurante_id = ? AND email = ?",
                [$restauranteId, $email]
            );
            if ($existing) {
                if ($nombre && trim($nombre) !== '' && $nombre !== $existing['nombre']) {
                    $this->execute(
                        "UPDATE rest_comensales SET nombre = ? WHERE id = ?",
                        [$nombre, (int)$existing['id']]
                    );
                }
                return (int) $existing['id'];
            }
        } elseif ($telefono) {
            $existing = $this->queryOne(
                "SELECT id FROM rest_comensales WHERE restaurante_id = ? AND telefono = ?",
                [$restauranteId, $telefono]
            );
            if ($existing) return (int) $existing['id'];
        }

        $this->execute(
            "INSERT INTO rest_comensales (restaurante_id, nombre, telefono, email) VALUES (?,?,?,?)",
            [$restauranteId, $nombre, $telefono, $email]
        );
        return (int) $this->db->lastInsertId();
    }

    public function getDetalle(int $comensalId): ?array
    {
        $mobile = $this->mobileJoinSql();
        $gastoTotalSql = $this->gastoTotalSql('c');
        $pedidoMobileColumn = $this->mobileUserColumn('rest_pedidos');
        $mobileMetricsJoin = '';
        $mobileGastoExpr = '0.00';
        $mobileVisitasExpr = '0';
        $mobileUltimaExpr = 'NULL';

        if ($pedidoMobileColumn && $mobile['join'] !== '') {
            $pedidoFechaExpr = $this->pedidoFechaExpr('p');
            $mobileMetricsJoin = "
                LEFT JOIN (
                    SELECT p.{$pedidoMobileColumn} AS mobile_usuario_id,
                           p.restaurante_id,
                           SUM(COALESCE(NULLIF(p.total, 0), p.subtotal)) AS gasto_total,
                           COUNT(DISTINCT COALESCE(p.visita_id, p.id)) AS num_visitas,
                           MAX({$pedidoFechaExpr}) AS ultima_visita
                    FROM rest_pedidos p
                    WHERE p.estado <> 'cancelado'
                      AND p.{$pedidoMobileColumn} IS NOT NULL
                    GROUP BY p.{$pedidoMobileColumn}, p.restaurante_id
                ) pm ON pm.mobile_usuario_id = mu.id AND pm.restaurante_id = c.restaurante_id";
            $mobileGastoExpr = 'COALESCE(MAX(pm.gasto_total), 0)';
            $mobileVisitasExpr = 'COALESCE(MAX(pm.num_visitas), 0)';
            $mobileUltimaExpr = 'MAX(pm.ultima_visita)';
        }

        return $this->queryOne(
            "SELECT c.*,
                    (COALESCE(COUNT(DISTINCT v.id), 0) + {$mobileVisitasExpr}) AS total_visitas,
                    ({$gastoTotalSql} + {$mobileGastoExpr}) AS gasto_total,
                    COALESCE(GREATEST(MAX(v.created_at), {$mobileUltimaExpr}), MAX(v.created_at), {$mobileUltimaExpr}) AS ultima_visita
                    {$mobile['select']}
             FROM rest_comensales c
             LEFT JOIN rest_visitas v ON v.comensal_id = c.id
             {$mobile['join']}
             {$mobileMetricsJoin}
             WHERE c.id = ?
             GROUP BY c.id",
            [$comensalId]
        );
    }

    public function getDetalleMobile(int $mobileUsuarioId, int $restauranteId): ?array
    {
        if (!$this->tableExists('mobile_usuarios')) {
            return null;
        }

        $mobileColumns = $this->mobileColumns();
        $pedidoMobileColumn = $this->mobileUserColumn('rest_pedidos');
        $restauranteIdSql = (int)$restauranteId;
        $scopeConditions = [];

        if ($mobileColumns['restaurant']) {
            $scopeConditions[] = "mu.{$mobileColumns['restaurant']} = {$restauranteIdSql}";
        }

        if ($pedidoMobileColumn) {
            $scopeConditions[] = "EXISTS (
                SELECT 1
                FROM rest_pedidos ps
                WHERE ps.{$pedidoMobileColumn} = mu.id
                  AND ps.restaurante_id = {$restauranteIdSql}
            )";
        }

        $scopeWhere = $scopeConditions ? ' AND (' . implode(' OR ', $scopeConditions) . ')' : '';
        $gastoTotalSql = $this->mobileGastoTotalSql('mu', $restauranteId);
        $visitasSql = $this->mobileVisitasCountSql('mu', $restauranteId);
        $ultimaSql = $this->mobileUltimaActividadSql('mu', $restauranteId);
        $createdAtSql = $this->mobileColumnSql('mu', $mobileColumns['created_at']);

        return $this->queryOne(
            "SELECT -CAST(mu.id AS SIGNED) AS id,
                    {$restauranteIdSql} AS restaurante_id,
                    NULL AS nombre,
                    NULL AS telefono,
                    NULL AS email,
                    0 AS total_gastado,
                    {$gastoTotalSql} AS gasto_total,
                    {$visitasSql} AS total_visitas,
                    {$ultimaSql} AS ultima_visita,
                    {$createdAtSql} AS created_at,
                    NULL AS detalle_id,
                    'mobile' AS origen,
                    mu.id AS mobile_usuario_id,
                    {$this->mobileColumnSql('mu', $mobileColumns['name'])} AS mobile_nombre,
                    {$this->mobileColumnSql('mu', $mobileColumns['email'])} AS mobile_email,
                    {$this->mobileColumnSql('mu', $mobileColumns['phone'])} AS mobile_telefono
             FROM mobile_usuarios mu
             WHERE mu.id = ?
               {$this->mobileActiveWhere('mu', $mobileColumns['active'])}
               {$this->mobileRoleWhere('mu', $mobileColumns['role'])}
               {$scopeWhere}
             LIMIT 1",
            [$mobileUsuarioId]
        );
    }

    public function registrarVisita(int $comensalId, float $gasto): void
    {
        $this->execute(
            "UPDATE rest_comensales
             SET total_visitas = total_visitas + 1,
                 total_gastado = total_gastado + ?,
                 ultima_visita = NOW()
             WHERE id = ?",
            [$gasto, $comensalId]
        );
    }

    public function topPorConsumo(int $restauranteId, int $limit = 20): array
    {
        $mobile = $this->mobileJoinSql();
        $gastoTotalSql = $this->gastoTotalSql('c');
        return $this->query(
            "SELECT c.*, {$gastoTotalSql} AS gasto_total
                    {$mobile['select']}
             FROM rest_comensales c
             {$mobile['join']}
             WHERE c.restaurante_id = ?
             GROUP BY c.id
             ORDER BY gasto_total DESC
             LIMIT $limit",
            [$restauranteId]
        );
    }

    public function topPorVisitas(int $restauranteId, int $limit = 20): array
    {
        $mobile = $this->mobileJoinSql();
        $gastoTotalSql = $this->gastoTotalSql('c');
        return $this->query(
            "SELECT c.*,
                    {$gastoTotalSql} AS gasto_total,
                    COALESCE(COUNT(DISTINCT v.id), 0) AS total_visitas
                    {$mobile['select']}
             FROM rest_comensales c
             LEFT JOIN rest_visitas v ON v.comensal_id = c.id
             {$mobile['join']}
             WHERE c.restaurante_id = ?
             GROUP BY c.id
             ORDER BY total_visitas DESC, gasto_total DESC
             LIMIT $limit",
            [$restauranteId]
        );
    }

    public function getHistorialVisitas(int $comensalId): array
    {
        return $this->query(
            "SELECT v.*,
                    m.nombre AS mesa_nombre,
                    t.total AS ticket_total,
                    t.estado AS ticket_estado,
                    t.metodo_pago,
                    COALESCE((
                        SELECT SUM(COALESCE(NULLIF(p.total, 0), p.subtotal))
                        FROM rest_pedidos p
                        WHERE p.visita_id = v.id
                          AND p.estado <> 'cancelado'
                    ), 0) AS pedido_total
             FROM rest_visitas v
             LEFT JOIN rest_mesas m ON m.id = v.mesa_id
             LEFT JOIN rest_tickets t ON t.visita_id = v.id AND t.estado = 'pagado'
             WHERE v.comensal_id = ?
             ORDER BY v.created_at DESC",
            [$comensalId]
        );
    }

    public function getHistorialMobile(int $mobileUsuarioId, int $restauranteId): array
    {
        $pedidoMobileColumn = $this->mobileUserColumn('rest_pedidos');
        if (!$pedidoMobileColumn) {
            return [];
        }
        $pedidoFechaExpr = $this->pedidoFechaExpr('p');
        $itemsSelect = 'NULL AS items_resumen';
        $itemsJoin = '';

        if ($this->tableExists('rest_pedido_items')) {
            $platilloJoin = $this->tableExists('rest_platillos')
                ? 'LEFT JOIN rest_platillos pl ON pl.id = i.platillo_id'
                : '';
            $platilloNombre = $this->tableExists('rest_platillos')
                ? "COALESCE(pl.nombre, CONCAT('Platillo #', i.platillo_id))"
                : "CONCAT('Platillo #', i.platillo_id)";
            $itemsSelect = 'items.items_resumen';
            $itemsJoin = "
             LEFT JOIN (
                SELECT i.pedido_id,
                       GROUP_CONCAT(
                         CONCAT(i.cantidad, ' x ', {$platilloNombre})
                         ORDER BY i.id
                         SEPARATOR ' - '
                       ) AS items_resumen
                FROM rest_pedido_items i
                {$platilloJoin}
                GROUP BY i.pedido_id
             ) items ON items.pedido_id = p.id";
        }

        return $this->query(
            "SELECT p.id,
                    p.created_at,
                    p.folio,
                    p.estado,
                    m.nombre AS mesa_nombre,
                    NULL AS ticket_total,
                    p.estado AS ticket_estado,
                    p.metodo_pago,
                    COALESCE(NULLIF(p.total, 0), p.subtotal) AS pedido_total,
                    {$itemsSelect},
                    'mobile' AS historial_origen
             FROM rest_pedidos p
             LEFT JOIN rest_mesas m ON m.id = p.mesa_id
             {$itemsJoin}
             WHERE p.restaurante_id = ?
               AND p.{$pedidoMobileColumn} = ?
               AND p.estado <> 'cancelado'
             ORDER BY {$pedidoFechaExpr} DESC",
            [$restauranteId, $mobileUsuarioId]
        );
    }

    private function getProductosFavoritosPedidos(string $whereSql, array $params, string $extraJoin = '', int $limit = 5): array
    {
        if (!$this->tableExists('rest_pedidos') || !$this->tableExists('rest_pedido_items')) {
            return [];
        }

        $limit = max(1, min(12, $limit));
        $hasPlatillos = $this->tableExists('rest_platillos');
        $platilloJoin = $hasPlatillos ? 'LEFT JOIN rest_platillos pl ON pl.id = i.platillo_id' : '';
        $platilloNombre = $hasPlatillos
            ? "COALESCE(pl.nombre, CONCAT('Platillo #', i.platillo_id))"
            : "CONCAT('Platillo #', i.platillo_id)";
        $platilloImagen = ($hasPlatillos && $this->columnExists('rest_platillos', 'imagen'))
            ? 'MAX(pl.imagen)'
            : 'NULL';
        $platilloGroup = $hasPlatillos ? 'i.platillo_id, pl.nombre' : 'i.platillo_id';
        $pedidoFechaExpr = $this->pedidoFechaExpr('p');

        return $this->query(
            "SELECT i.platillo_id,
                    {$platilloNombre} AS nombre,
                    {$platilloImagen} AS imagen,
                    SUM(COALESCE(i.cantidad, 0)) AS cantidad_total,
                    COUNT(DISTINCT p.id) AS veces,
                    SUM(COALESCE(NULLIF(i.subtotal, 0), COALESCE(i.precio_unit, 0) * COALESCE(i.cantidad, 0))) AS total_gastado,
                    MAX({$pedidoFechaExpr}) AS ultima_compra
             FROM rest_pedidos p
             {$extraJoin}
             JOIN rest_pedido_items i ON i.pedido_id = p.id
             {$platilloJoin}
             {$whereSql}
             GROUP BY {$platilloGroup}
             ORDER BY cantidad_total DESC, veces DESC, total_gastado DESC
             LIMIT {$limit}",
            $params
        );
    }

    public function getProductosFavoritosMobile(int $mobileUsuarioId, int $restauranteId, int $limit = 5): array
    {
        $pedidoMobileColumn = $this->mobileUserColumn('rest_pedidos');
        if (!$pedidoMobileColumn) {
            return [];
        }

        return $this->getProductosFavoritosPedidos(
            "WHERE p.restaurante_id = ?
               AND p.{$pedidoMobileColumn} = ?
               AND p.estado <> 'cancelado'",
            [$restauranteId, $mobileUsuarioId],
            '',
            $limit
        );
    }

    public function getProductosFavoritosComensal(int $comensalId, int $restauranteId, ?int $mobileUsuarioId = null, int $limit = 5): array
    {
        $conditions = ['v.comensal_id = ?'];
        $params = [$restauranteId, $comensalId];
        $pedidoMobileColumn = $this->mobileUserColumn('rest_pedidos');

        if ($mobileUsuarioId && $pedidoMobileColumn) {
            $conditions[] = "p.{$pedidoMobileColumn} = ?";
            $params[] = $mobileUsuarioId;
        }

        return $this->getProductosFavoritosPedidos(
            "WHERE p.restaurante_id = ?
               AND p.estado <> 'cancelado'
               AND (" . implode(' OR ', $conditions) . ")",
            $params,
            'LEFT JOIN rest_visitas v ON v.id = p.visita_id',
            $limit
        );
    }

    public function sugerirPromocion(array $productos, array $comensal): array
    {
        $visitas = (int)($comensal['total_visitas'] ?? $comensal['num_visitas'] ?? 0);

        if (empty($productos)) {
            return [
                'titulo' => 'Promocion de bienvenida',
                'descripcion' => 'Aun no hay suficientes productos para detectar un favorito claro.',
                'mecanica' => 'Ofrece una bebida o postre de cortesia en su proxima visita para incentivar una segunda compra.',
            ];
        }

        $favorito = $productos[0];
        $nombre = (string)($favorito['nombre'] ?? 'su producto favorito');
        $cantidad = (int)($favorito['cantidad_total'] ?? 0);

        if ($visitas >= 8 || $cantidad >= 8) {
            return [
                'titulo' => 'Recompensa a cliente frecuente',
                'descripcion' => "Este cliente compra mucho {$nombre}. Conviene premiar recurrencia, no solo atraerlo.",
                'mecanica' => "10% de descuento en {$nombre} o upgrade gratis al comprarlo en su proxima visita.",
            ];
        }

        if (isset($productos[1])) {
            $segundo = (string)($productos[1]['nombre'] ?? 'otro favorito');
            return [
                'titulo' => 'Combo personalizado',
                'descripcion' => "{$nombre} y {$segundo} aparecen entre sus compras mas frecuentes.",
                'mecanica' => "Arma un combo con {$nombre} + {$segundo} con precio especial por tiempo limitado.",
            ];
        }

        return [
            'titulo' => 'Impulso al favorito',
            'descripcion' => "{$nombre} ya tiene senales de preferencia en su historial.",
            'mecanica' => "Agrega un extra o bebida con descuento al comprar {$nombre}.",
        ];
    }

    public function definirPromocionApp(array $productos, array $comensal, int $restauranteId, string $motivo = 'manual'): array
    {
        $sugerencia = $this->sugerirPromocion($productos, $comensal);
        $mobileUsuarioId = (int)($comensal['mobile_usuario_id'] ?? 0);
        $favorito = $productos[0]['nombre'] ?? null;
        $favoritoProductoId = (int)($productos[0]['platillo_id'] ?? 0);
        $favoritoImagen = $this->normalizarImagenPromocion((string)($productos[0]['imagen'] ?? ''));
        $valor = 10.0;
        $tipo = 'porcentaje';
        $hoy = date('Y-m-d');
        $vence = date('Y-m-d', strtotime('+30 days'));
        $prefijo = $motivo === 'reactivacion' ? 'VUELVE' : 'AMARE';
        $code = strtoupper($prefijo . '-' . $mobileUsuarioId . '-' . date('md'));
        $vigencia = date('d/m/Y', strtotime($vence));
        $tituloApp = $favorito ? 'Tu favorito con 10% off' : 'Un detalle especial para ti';
        $descripcion = $favorito
            ? "Sabemos que disfrutas {$favorito}. Te dejamos 10% de descuento para tu proxima visita. Valido hasta {$vigencia}."
            : "Tenemos una promocion especial para tu proxima visita a Amare. Recibe 10% de descuento. Valido hasta {$vigencia}.";

        if ($motivo === 'reactivacion') {
            $tituloApp = 'Te extranamos en Amare';
            $descripcion = 'Vuelve a Amare y recibe 10% de descuento en tu proxima visita. '
                . ($favorito ? 'Ideal para disfrutar de nuevo: ' . $favorito . '. ' : '')
                . 'Valido hasta ' . $vigencia . '.';
        }

        return [
            'restaurante_id' => $restauranteId,
            'usuario_id' => $mobileUsuarioId,
            'mobile_usuario_id' => $mobileUsuarioId,
            'titulo' => $tituloApp,
            'descripcion' => $descripcion,
            'code' => $code,
            'tipo' => $tipo,
            'valor_descuento' => $valor,
            'fecha_inicio' => $hoy,
            'fecha_fin' => $vence,
            'expires_at' => $vence . ' 23:59:59',
            'activo' => 1,
            'motivo' => $motivo,
            'producto_favorito' => $favorito,
            'producto_id' => $favoritoProductoId ?: null,
            'platillo_id' => $favoritoProductoId ?: null,
            'producto_imagen' => $favoritoImagen,
        ];
    }

    private function normalizarImagenPromocion(string $imagen): string
    {
        $imagen = trim($imagen);
        $fallback = defined('BASE_URL')
            ? rtrim(BASE_URL, '/') . '/public/img/amare4.jpeg'
            : 'public/img/amare4.jpeg';

        if ($imagen === '') {
            return $fallback;
        }

        if (preg_match('#^https?://#i', $imagen)) {
            return $imagen;
        }

        if (str_starts_with($imagen, '/')) {
            return defined('BASE_URL') ? rtrim(BASE_URL, '/') . $imagen : $imagen;
        }

        return defined('BASE_URL')
            ? rtrim(BASE_URL, '/') . '/' . ltrim($imagen, '/')
            : $imagen;
    }

    public function guardarPromocionAppLocal(array $payload): int
    {
        $mobileId = (int)($payload['mobile_usuario_id'] ?? $payload['usuario_id'] ?? 0);
        if ($mobileId <= 0) {
            throw new InvalidArgumentException('La promocion no tiene usuario movil valido.');
        }

        if ($this->tableExists('mobile_promociones')) {
            $this->ensureMobilePromocionesProductoColumn();
            $imagen = (string)($payload['producto_imagen'] ?? '');
            $imagen = $imagen !== '' ? $imagen : $this->normalizarImagenPromocion('');
            $productoId = (int)($payload['producto_id'] ?? $payload['platillo_id'] ?? 0);

            $this->execute(
                "INSERT INTO mobile_promociones
                    (usuario_id, producto_id, titulo, descripcion, imagen, deep_link, code, activo, expires_at, created_at)
                 VALUES (?, ?, ?, ?, ?, ?, ?, 1, ?, NOW())",
                [
                    $mobileId,
                    $productoId > 0 ? $productoId : null,
                    (string)$payload['titulo'],
                    (string)$payload['descripcion'],
                    $imagen,
                    'amare://promociones/' . rawurlencode((string)$payload['code']),
                    (string)$payload['code'],
                    (string)$payload['expires_at'],
                ]
            );

            return (int)$this->db->lastInsertId();
        }

        if ($this->tableExists('rest_promociones')) {
            $columns = [
                'restaurante_id' => (int)$payload['restaurante_id'],
                'usuario_id' => $mobileId,
                'titulo' => (string)$payload['titulo'],
                'descripcion' => (string)$payload['descripcion'],
                'tipo' => (string)$payload['tipo'],
                'valor_descuento' => (float)$payload['valor_descuento'],
                'fecha_inicio' => (string)$payload['fecha_inicio'],
                'fecha_fin' => (string)$payload['fecha_fin'],
                'activo' => 1,
            ];

            foreach (['code', 'expires_at', 'imagen', 'producto_id', 'platillo_id'] as $column) {
                if ($this->columnExists('rest_promociones', $column)) {
                    if ($column === 'imagen') {
                        $columns[$column] = (string)($payload['producto_imagen'] ?? '');
                    } elseif (in_array($column, ['producto_id', 'platillo_id'], true)) {
                        $productoId = (int)($payload['producto_id'] ?? $payload['platillo_id'] ?? 0);
                        $columns[$column] = $productoId > 0 ? $productoId : null;
                    } else {
                        $columns[$column] = (string)$payload[$column];
                    }
                }
            }

            $names = array_keys($columns);
            $sqlColumns = implode(', ', array_map(fn($column) => "`{$column}`", $names));
            $placeholders = implode(', ', array_fill(0, count($names), '?'));
            $this->execute(
                "INSERT INTO rest_promociones ({$sqlColumns}) VALUES ({$placeholders})",
                array_values($columns)
            );

            return (int)$this->db->lastInsertId();
        }

        throw new RuntimeException('No existe tabla local para guardar promociones de app.');
    }

    private function ensureMobilePromocionesProductoColumn(): void
    {
        if (!$this->tableExists('mobile_promociones') || $this->columnExists('mobile_promociones', 'producto_id')) {
            return;
        }

        $this->execute(
            "ALTER TABLE mobile_promociones
             ADD COLUMN producto_id INT(10) UNSIGNED DEFAULT NULL AFTER usuario_id"
        );
        self::$schemaCache['column:mobile_promociones.producto_id'] = true;

        try {
            $this->execute("ALTER TABLE mobile_promociones ADD KEY idx_producto_id (producto_id)");
        } catch (\Throwable $e) {
            error_log('[mobile_promociones] No se pudo crear indice producto_id: ' . $e->getMessage());
        }
    }

    public function getPromocionesMobileActivas(int $mobileUsuarioId): array
    {
        if ($mobileUsuarioId <= 0 || !$this->tableExists('mobile_promociones')) {
            return [];
        }

        $productoSelect = $this->columnExists('mobile_promociones', 'producto_id')
            ? 'producto_id, producto_id AS platillo_id,'
            : 'NULL AS producto_id, NULL AS platillo_id,';
        $ruleSelects = [
            $this->columnExists('mobile_promociones', 'tipo_descuento') ? 'tipo_descuento' : "'porcentaje' AS tipo_descuento",
            $this->columnExists('mobile_promociones', 'valor_descuento') ? 'valor_descuento' : '0 AS valor_descuento',
            $this->columnExists('mobile_promociones', 'scope_tipo') ? 'scope_tipo' : "'all' AS scope_tipo",
            $this->columnExists('mobile_promociones', 'scope_ids') ? 'scope_ids' : 'NULL AS scope_ids',
            $this->columnExists('mobile_promociones', 'buy_qty') ? 'buy_qty' : 'NULL AS buy_qty',
            $this->columnExists('mobile_promociones', 'pay_qty') ? 'pay_qty' : 'NULL AS pay_qty',
            $this->columnExists('mobile_promociones', 'min_subtotal') ? 'min_subtotal' : '0 AS min_subtotal',
            $this->columnExists('mobile_promociones', 'max_uses') ? 'max_uses' : 'NULL AS max_uses',
            $this->columnExists('mobile_promociones', 'combinable') ? 'combinable' : '0 AS combinable',
        ];
        $ruleSelect = implode(",\n                    ", $ruleSelects) . ',';

        return $this->query(
            "SELECT id,
                    usuario_id,
                    {$productoSelect}
                    {$ruleSelect}
                    titulo,
                    descripcion,
                    imagen,
                    deep_link,
                    code,
                    code AS texto_copiar,
                    activo,
                    expires_at,
                    created_at
             FROM mobile_promociones
             WHERE usuario_id = ?
               AND activo = 1
               AND (expires_at IS NULL OR expires_at >= NOW())
             ORDER BY created_at DESC, id DESC",
            [$mobileUsuarioId]
        );
    }

    public function ensurePromocionEnviosTable(): void
    {
        $this->execute(
            "CREATE TABLE IF NOT EXISTS rest_promocion_envios (
                id INT UNSIGNED NOT NULL AUTO_INCREMENT,
                restaurante_id INT UNSIGNED NOT NULL,
                mobile_usuario_id INT UNSIGNED NOT NULL,
                comensal_id INT UNSIGNED DEFAULT NULL,
                promocion_remota_id INT UNSIGNED DEFAULT NULL,
                code VARCHAR(80) NOT NULL,
                motivo VARCHAR(40) NOT NULL DEFAULT 'manual',
                periodo CHAR(7) NOT NULL,
                payload_json TEXT NULL,
                enviado_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (id),
                UNIQUE KEY uniq_envio_periodo (restaurante_id, mobile_usuario_id, motivo, periodo),
                KEY idx_envio_mobile (mobile_usuario_id),
                KEY idx_envio_restaurante (restaurante_id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
        );
    }

    public function promocionYaEnviada(int $restauranteId, int $mobileUsuarioId, string $motivo, ?string $periodo = null): bool
    {
        $this->ensurePromocionEnviosTable();
        $periodo = $periodo ?: date('Y-m');
        $row = $this->queryOne(
            "SELECT id
             FROM rest_promocion_envios
             WHERE restaurante_id = ?
               AND mobile_usuario_id = ?
               AND motivo = ?
               AND periodo = ?
             LIMIT 1",
            [$restauranteId, $mobileUsuarioId, $motivo, $periodo]
        );
        return (bool)$row;
    }

    public function registrarPromocionEnviada(int $restauranteId, int $mobileUsuarioId, string $motivo, array $payload, ?int $remoteId = null, ?int $comensalId = null): void
    {
        $this->ensurePromocionEnviosTable();
        $periodo = date('Y-m');
        $json = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        $this->execute(
            "INSERT INTO rest_promocion_envios
                (restaurante_id, mobile_usuario_id, comensal_id, promocion_remota_id, code, motivo, periodo, payload_json)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?)
             ON DUPLICATE KEY UPDATE
                promocion_remota_id = VALUES(promocion_remota_id),
                code = VALUES(code),
                payload_json = VALUES(payload_json),
                enviado_at = CURRENT_TIMESTAMP",
            [
                $restauranteId,
                $mobileUsuarioId,
                $comensalId,
                $remoteId,
                (string)($payload['code'] ?? ''),
                $motivo,
                $periodo,
                $json,
            ]
        );
    }

    public function getClientesMobileParaReactivacion(int $restauranteId, int $limit = 10): array
    {
        if (!$this->tableExists('mobile_usuarios')) {
            return [];
        }

        $pedidoMobileColumn = $this->mobileUserColumn('rest_pedidos');
        if (!$pedidoMobileColumn) {
            return [];
        }

        $this->ensurePromocionEnviosTable();
        $mobileColumns = $this->mobileColumns();
        $restauranteIdSql = (int)$restauranteId;
        $limit = max(1, min(50, $limit));
        $ultimaSql = $this->mobileUltimaActividadSql('mu', $restauranteId);
        $gastoSql = $this->mobileGastoTotalSql('mu', $restauranteId);
        $visitasSql = $this->mobileVisitasCountSql('mu', $restauranteId);
        $scopeConditions = [];

        if ($mobileColumns['restaurant']) {
            $scopeConditions[] = "mu.{$mobileColumns['restaurant']} = {$restauranteIdSql}";
        }

        $scopeConditions[] = "EXISTS (
            SELECT 1
            FROM rest_pedidos ps
            WHERE ps.{$pedidoMobileColumn} = mu.id
              AND ps.restaurante_id = {$restauranteIdSql}
        )";

        $scopeWhere = ' AND (' . implode(' OR ', $scopeConditions) . ')';
        $periodo = date('Y-m');

        return $this->query(
            "SELECT -CAST(mu.id AS SIGNED) AS id,
                    {$restauranteIdSql} AS restaurante_id,
                    NULL AS nombre,
                    NULL AS telefono,
                    NULL AS email,
                    {$gastoSql} AS gasto_total,
                    {$visitasSql} AS total_visitas,
                    {$ultimaSql} AS ultima_visita,
                    'mobile' AS origen,
                    mu.id AS mobile_usuario_id,
                    {$this->mobileColumnSql('mu', $mobileColumns['name'])} AS mobile_nombre,
                    {$this->mobileColumnSql('mu', $mobileColumns['email'])} AS mobile_email,
                    {$this->mobileColumnSql('mu', $mobileColumns['phone'])} AS mobile_telefono
             FROM mobile_usuarios mu
             WHERE {$ultimaSql} IS NOT NULL
               AND {$ultimaSql} <= DATE_SUB(NOW(), INTERVAL 2 MONTH)
               {$this->mobileActiveWhere('mu', $mobileColumns['active'])}
               {$this->mobileRoleWhere('mu', $mobileColumns['role'])}
               {$scopeWhere}
               AND NOT EXISTS (
                 SELECT 1
                 FROM rest_promocion_envios pe
                 WHERE pe.restaurante_id = {$restauranteIdSql}
                   AND pe.mobile_usuario_id = mu.id
                   AND pe.motivo = 'reactivacion'
                   AND pe.periodo = ?
               )
             ORDER BY {$ultimaSql} ASC
             LIMIT {$limit}",
            [$periodo]
        );
    }
}
