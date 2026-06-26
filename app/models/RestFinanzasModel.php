<?php
class RestFinanzasModel extends BaseModel
{
    protected string $table = 'rest_tickets';
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

    public function kpisDashboard(int $restauranteId, string $desde, string $hasta): array
    {
        $params = [$restauranteId, $desde, $hasta];

        $ingresos = (float) $this->queryOne(
            "SELECT COALESCE(SUM(total),0) AS v FROM rest_tickets
             WHERE restaurante_id=? AND estado='pagado' AND DATE(pagado_at) BETWEEN ? AND ?",
            $params
        )['v'];

        $gastos = (float) $this->queryOne(
            "SELECT COALESCE(SUM(monto),0) AS v FROM rest_gastos
             WHERE restaurante_id=? AND fecha BETWEEN ? AND ?",
            $params
        )['v'];

        $retiros = (float) $this->queryOne(
            "SELECT COALESCE(SUM(monto),0) AS v FROM rest_retiros
             WHERE restaurante_id=? AND DATE(created_at) BETWEEN ? AND ?",
            $params
        )['v'];

        $propinas = (float) $this->queryOne(
            "SELECT COALESCE(SUM(propina),0) AS v FROM rest_tickets
             WHERE restaurante_id=? AND estado='pagado' AND DATE(pagado_at) BETWEEN ? AND ?",
            $params
        )['v'];

        $totalTickets = (int) $this->queryOne(
            "SELECT COUNT(*) AS c FROM rest_tickets
             WHERE restaurante_id=? AND estado='pagado' AND DATE(pagado_at) BETWEEN ? AND ?",
            $params
        )['c'];

        $ticketPromedio = $totalTickets > 0 ? round($ingresos / $totalTickets, 2) : 0.0;

        $pendiente = (float) $this->queryOne(
            "SELECT COALESCE(SUM(total),0) AS v FROM rest_tickets
             WHERE restaurante_id=? AND estado='pendiente' AND DATE(created_at) BETWEEN ? AND ?",
            $params
        )['v'];

        $utilidad  = $ingresos - $gastos - $retiros;
        $margen    = $ingresos > 0 ? round(($utilidad / $ingresos) * 100, 2) : 0;

        return compact('ingresos','gastos','retiros','propinas','utilidad','margen','totalTickets','ticketPromedio','pendiente');
    }

    public function amareDashboardKpis(int $restauranteId, string $desde, string $hasta): array
    {
        $saldo = 0.0;
        $recargas = 0.0;
        $walletUsado = 0.0;
        $descuentos = 0.0;
        $puntosDados = 0;
        $puntosRedimidos = 0;

        if ($this->tableExists('amare_wallets')) {
            $saldo = (float)($this->queryOne(
                "SELECT COALESCE(SUM(balance_mxn),0) AS v FROM amare_wallets"
            )['v'] ?? 0);
        } elseif ($this->tableExists('mobile_usuarios') && $this->columnExists('mobile_usuarios', 'amare_saldo')) {
            $saldo = (float)($this->queryOne(
                "SELECT COALESCE(SUM(amare_saldo),0) AS v FROM mobile_usuarios WHERE activo = 1"
            )['v'] ?? 0);
        }

        if ($this->tableExists('amare_wallet_topups')) {
            $recargas = (float)($this->queryOne(
                "SELECT COALESCE(SUM(COALESCE(amount_received, requested_amount)),0) AS v
                 FROM amare_wallet_topups
                 WHERE status = 'confirmed' AND DATE(COALESCE(confirmed_at, created_at)) BETWEEN ? AND ?",
                [$desde, $hasta]
            )['v'] ?? 0);
        } elseif ($this->tableExists('amare_wallet_transactions')) {
            $recargas = (float)($this->queryOne(
                "SELECT COALESCE(SUM(amount_mxn),0) AS v
                 FROM amare_wallet_transactions
                 WHERE type IN ('wallet_topup','demo_credit')
                   AND amount_mxn > 0
                   AND DATE(created_at) BETWEEN ? AND ?",
                [$desde, $hasta]
            )['v'] ?? 0);
        }

        if ($this->tableExists('amare_wallet_transactions') && $this->tableExists('rest_pedidos')) {
            $walletUsado = (float)($this->queryOne(
                "SELECT COALESCE(SUM(ABS(wt.amount_mxn)),0) AS v
                 FROM amare_wallet_transactions wt
                 JOIN rest_pedidos p ON p.id = wt.reference_id
                 WHERE p.restaurante_id = ?
                   AND wt.reference_type = 'order'
                   AND wt.type = 'wallet_payment'
                   AND wt.amount_mxn < 0
                   AND DATE(wt.created_at) BETWEEN ? AND ?",
                [$restauranteId, $desde, $hasta]
            )['v'] ?? 0);

            $puntosDados = (int)($this->queryOne(
                "SELECT COALESCE(SUM(GREATEST(wt.points_delta, 0)),0) AS v
                 FROM amare_wallet_transactions wt
                 JOIN rest_pedidos p ON p.id = wt.reference_id
                 WHERE p.restaurante_id = ?
                   AND wt.reference_type = 'order'
                   AND DATE(wt.created_at) BETWEEN ? AND ?",
                [$restauranteId, $desde, $hasta]
            )['v'] ?? 0);

            $puntosRedimidos = (int)($this->queryOne(
                "SELECT COALESCE(SUM(ABS(LEAST(wt.points_delta, 0))),0) AS v
                 FROM amare_wallet_transactions wt
                 JOIN rest_pedidos p ON p.id = wt.reference_id
                 WHERE p.restaurante_id = ?
                   AND wt.reference_type = 'order'
                   AND DATE(wt.created_at) BETWEEN ? AND ?",
                [$restauranteId, $desde, $hasta]
            )['v'] ?? 0);
        }

        if ($this->tableExists('rest_pedidos')) {
            $pedidoFechaExpr = $this->columnExists('rest_pedidos', 'pagado_at') ? 'COALESCE(pagado_at, created_at)' : 'created_at';

            if ($this->columnExists('rest_pedidos', 'amare_discount_mxn')) {
                $descuentos += (float)($this->queryOne(
                    "SELECT COALESCE(SUM(amare_discount_mxn),0) AS v
                     FROM rest_pedidos
                     WHERE restaurante_id = ? AND DATE({$pedidoFechaExpr}) BETWEEN ? AND ?",
                    [$restauranteId, $desde, $hasta]
                )['v'] ?? 0);
            }

            if ($walletUsado <= 0 && $this->columnExists('rest_pedidos', 'amare_wallet_used_mxn')) {
                $walletUsado = (float)($this->queryOne(
                    "SELECT COALESCE(SUM(amare_wallet_used_mxn),0) AS v
                     FROM rest_pedidos
                     WHERE restaurante_id = ? AND DATE({$pedidoFechaExpr}) BETWEEN ? AND ?",
                    [$restauranteId, $desde, $hasta]
                )['v'] ?? 0);
            }

            if ($puntosDados <= 0 && $this->columnExists('rest_pedidos', 'amare_points_earned')) {
                $puntosDados = (int)($this->queryOne(
                    "SELECT COALESCE(SUM(amare_points_earned),0) AS v
                     FROM rest_pedidos
                     WHERE restaurante_id = ? AND DATE({$pedidoFechaExpr}) BETWEEN ? AND ?",
                    [$restauranteId, $desde, $hasta]
                )['v'] ?? 0);
            }

            if ($puntosRedimidos <= 0 && $this->columnExists('rest_pedidos', 'amare_points_redeemed')) {
                $puntosRedimidos = (int)($this->queryOne(
                    "SELECT COALESCE(SUM(amare_points_redeemed),0) AS v
                     FROM rest_pedidos
                     WHERE restaurante_id = ? AND DATE({$pedidoFechaExpr}) BETWEEN ? AND ?",
                    [$restauranteId, $desde, $hasta]
                )['v'] ?? 0);
            }
        }

        return [
            'saldo' => $saldo,
            'recargas' => $recargas,
            'walletUsado' => $walletUsado,
            'descuentos' => $descuentos,
            'puntosDados' => $puntosDados,
            'puntosRedimidos' => $puntosRedimidos,
            'perdidaAmare' => $descuentos + $puntosRedimidos,
        ];
    }

    public function ingresosVsEgresosGrafica(int $restauranteId, string $desde, string $hasta): array
    {
        $ing = $this->query(
            "SELECT DATE(pagado_at) AS dia, SUM(total) AS total
             FROM rest_tickets WHERE restaurante_id=? AND estado='pagado' AND DATE(pagado_at) BETWEEN ? AND ?
             GROUP BY dia ORDER BY dia",
            [$restauranteId, $desde, $hasta]
        );
        $egresos = $this->query(
            "SELECT fecha AS dia, SUM(monto) AS total
             FROM rest_gastos WHERE restaurante_id=? AND fecha BETWEEN ? AND ?
             GROUP BY dia ORDER BY dia",
            [$restauranteId, $desde, $hasta]
        );
        return ['ingresos' => $ing, 'egresos' => $egresos];
    }

    public function gastosPorCategoria(int $restauranteId, string $desde, string $hasta): array
    {
        return $this->query(
            "SELECT categoria, SUM(monto) AS total
             FROM rest_gastos WHERE restaurante_id=? AND fecha BETWEEN ? AND ?
             GROUP BY categoria ORDER BY total DESC",
            [$restauranteId, $desde, $hasta]
        );
    }

    public function metodosPago(int $restauranteId, string $desde, string $hasta): array
    {
        return $this->query(
            "SELECT metodo_pago, COUNT(*) AS cantidad, SUM(total) AS total
             FROM rest_tickets
             WHERE restaurante_id=? AND estado='pagado' AND DATE(pagado_at) BETWEEN ? AND ?
             GROUP BY metodo_pago",
            [$restauranteId, $desde, $hasta]
        );
    }

    public function actividadReciente(int $restauranteId, int $limit = 15): array
    {
        return $this->query(
            "(SELECT 'gasto' AS tipo, descripcion, monto, created_at FROM rest_gastos WHERE restaurante_id=?)
             UNION ALL
             (SELECT 'retiro', descripcion, monto, created_at FROM rest_retiros WHERE restaurante_id=?)
             UNION ALL
             (SELECT 'corte', CONCAT('Corte ', turno), utilidad_neta, created_at FROM rest_cortes WHERE restaurante_id=?)
             ORDER BY created_at DESC LIMIT $limit",
            [$restauranteId, $restauranteId, $restauranteId]
        );
    }

    // ── Gastos ────────────────────────────────────────────────────

    public function getGastos(int $restauranteId, int $page = 1): array
    {
        $sql = "SELECT g.*, u.nombre AS usuario_nombre
                FROM rest_gastos g JOIN usuarios u ON u.id = g.usuario_id
                WHERE g.restaurante_id = ? ORDER BY g.fecha DESC, g.created_at DESC";
        return $this->paginate($sql, [$restauranteId], $page);
    }

    public function insertGasto(array $data): int
    {
        $this->execute(
            "INSERT INTO rest_gastos (restaurante_id, categoria, descripcion, monto, fecha, comprobante, usuario_id)
             VALUES (?,?,?,?,?,?,?)",
            [$data['restaurante_id'], $data['categoria'], $data['descripcion'], $data['monto'],
             $data['fecha'], $data['comprobante'] ?? null, $data['usuario_id']]
        );
        return (int) $this->db->lastInsertId();
    }

    // ── Retiros ───────────────────────────────────────────────────

    public function getRetiros(int $restauranteId, int $page = 1): array
    {
        $sql = "SELECT r.*, u.nombre AS usuario_nombre
                FROM rest_retiros r JOIN usuarios u ON u.id = r.usuario_id
                WHERE r.restaurante_id = ? ORDER BY r.created_at DESC";
        return $this->paginate($sql, [$restauranteId], $page);
    }

    public function insertRetiro(array $data): int
    {
        $this->execute(
            "INSERT INTO rest_retiros (restaurante_id, descripcion, monto, usuario_id) VALUES (?,?,?,?)",
            [$data['restaurante_id'], $data['descripcion'], $data['monto'], $data['usuario_id']]
        );
        return (int) $this->db->lastInsertId();
    }

    // ── Cortes ────────────────────────────────────────────────────

    public function getCortes(int $restauranteId, int $page = 1): array
    {
        $sql = "SELECT c.*, u.nombre AS usuario_nombre
                FROM rest_cortes c JOIN usuarios u ON u.id = c.usuario_id
                WHERE c.restaurante_id = ? ORDER BY c.created_at DESC";
        return $this->paginate($sql, [$restauranteId], $page);
    }

    public function insertCorte(array $data): int
    {
        $this->execute(
            "INSERT INTO rest_cortes (restaurante_id, turno, usuario_id, ingresos, gastos, retiros, propinas, utilidad_neta, notas)
             VALUES (?,?,?,?,?,?,?,?,?)",
            [
                $data['restaurante_id'], $data['turno'], $data['usuario_id'],
                $data['ingresos'], $data['gastos'], $data['retiros'],
                $data['propinas'], $data['utilidad_neta'], $data['notas'] ?? null,
            ]
        );
        return (int) $this->db->lastInsertId();
    }
}
