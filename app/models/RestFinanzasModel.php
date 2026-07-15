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

    private function firstExistingColumn(string $table, array $columns): ?string
    {
        foreach ($columns as $column) {
            if ($this->columnExists($table, $column)) {
                return $column;
            }
        }

        return null;
    }

    private function pedidoFechaExpr(string $alias = 'p'): string
    {
        $columns = [];
        foreach (['pagado_at', 'cerrado_at', 'actualizado_at', 'updated_at', 'created_at'] as $column) {
            if ($this->columnExists('rest_pedidos', $column)) {
                $columns[] = "{$alias}.{$column}";
            }
        }

        if (!$columns) {
            return 'NULL';
        }

        return count($columns) === 1 ? $columns[0] : 'COALESCE(' . implode(', ', $columns) . ')';
    }

    private function pedidoTotalExpr(string $alias = 'p'): string
    {
        $hasTotal = $this->columnExists('rest_pedidos', 'total');
        $hasSubtotal = $this->columnExists('rest_pedidos', 'subtotal');

        if ($hasTotal && $hasSubtotal) {
            return "COALESCE(NULLIF({$alias}.total, 0), {$alias}.subtotal)";
        }

        if ($hasTotal) {
            return "{$alias}.total";
        }

        if ($hasSubtotal) {
            return "{$alias}.subtotal";
        }

        return '0';
    }

    private function ticketTotalExpr(string $alias = 't'): string
    {
        return $this->columnExists('rest_tickets', 'total') ? "{$alias}.total" : '0';
    }

    private function paymentMethodIsWalletSql(string $table, string $alias): string
    {
        $column = $this->firstExistingColumn($table, ['metodo_pago', 'payment_method', 'payment_type', 'forma_pago']);
        if (!$column) {
            return '0 = 1';
        }

        return "LOWER(COALESCE({$alias}.{$column}, '')) IN (
            'saldo_amare',
            'saldo amare',
            'amare_saldo',
            'amare wallet',
            'amare_wallet',
            'wallet',
            'monedero',
            'billetera',
            'saldo'
        )";
    }

    private function walletColumnExpr(string $table, string $alias): string
    {
        $column = $this->firstExistingColumn($table, [
            'amare_wallet_used_mxn',
            'amare_saldo_usado',
            'saldo_amare_usado',
            'wallet_used_mxn',
            'wallet_usado',
            'saldo_usado',
            'monedero_usado',
        ]);

        return $column ? "COALESCE({$alias}.{$column}, 0)" : '0';
    }

    private function pedidoWalletTransactionsExpr(string $alias = 'p'): string
    {
        if (!$this->tableExists('amare_wallet_transactions')) {
            return '0';
        }

        $amountColumn = $this->firstExistingColumn('amare_wallet_transactions', ['amount_mxn', 'monto', 'amount']);
        if (!$amountColumn || !$this->columnExists('amare_wallet_transactions', 'reference_id')) {
            return '0';
        }

        $referenceWhere = $this->columnExists('amare_wallet_transactions', 'reference_type')
            ? " AND wt.reference_type IN ('order', 'pedido', 'rest_pedido', 'rest_pedidos')"
            : '';
        $typeWhere = $this->columnExists('amare_wallet_transactions', 'type')
            ? " AND wt.type IN ('wallet_payment', 'saldo_payment', 'saldo_amare_payment', 'pago_saldo')"
            : '';

        return "COALESCE((
            SELECT SUM(ABS(wt.{$amountColumn}))
            FROM amare_wallet_transactions wt
            WHERE wt.reference_id = {$alias}.id
              AND wt.{$amountColumn} < 0
              {$referenceWhere}
              {$typeWhere}
        ), 0)";
    }

    private function pedidoWalletUsedExpr(string $alias = 'p'): string
    {
        return 'GREATEST(0, ' . $this->walletColumnExpr('rest_pedidos', $alias) . ', ' . $this->pedidoWalletTransactionsExpr($alias) . ')';
    }

    private function ticketWalletUsedExpr(string $alias = 't'): string
    {
        $parts = [
            '0',
            $this->walletColumnExpr('rest_tickets', $alias),
        ];

        if (
            $this->tableExists('rest_pedidos')
            && $this->columnExists('rest_tickets', 'visita_id')
            && $this->columnExists('rest_pedidos', 'visita_id')
        ) {
            $pedidoWalletExpr = $this->pedidoWalletUsedExpr('pw');
            $parts[] = "COALESCE((
                SELECT SUM({$pedidoWalletExpr})
                FROM rest_pedidos pw
                WHERE pw.visita_id = {$alias}.visita_id
                  {$this->pedidoNoCanceladoSql('pw')}
            ), 0)";
        }

        return 'GREATEST(' . implode(', ', $parts) . ')';
    }

    private function ingresoContableExpr(string $totalExpr, string $walletUsedExpr, string $walletMethodSql): string
    {
        return "GREATEST({$totalExpr} - {$walletUsedExpr}, 0)";
    }

    private function pedidoIngresoContableExpr(string $alias = 'p'): string
    {
        return $this->ingresoContableExpr(
            $this->pedidoTotalExpr($alias),
            $this->pedidoWalletUsedExpr($alias),
            $this->paymentMethodIsWalletSql('rest_pedidos', $alias)
        );
    }

    private function ticketIngresoContableExpr(string $alias = 't'): string
    {
        return $this->ingresoContableExpr(
            $this->ticketTotalExpr($alias),
            $this->ticketWalletUsedExpr($alias),
            $this->paymentMethodIsWalletSql('rest_tickets', $alias)
        );
    }

    private function pedidoNoCanceladoSql(string $alias = 'p'): string
    {
        return $this->columnExists('rest_pedidos', 'estado')
            ? " AND COALESCE({$alias}.estado, '') <> 'cancelado'"
            : '';
    }

    private function pedidoAppWhereSql(string $alias = 'p'): string
    {
        $conditions = [];
        foreach (['mobile_usuario_id', 'mobile_user_id', 'usuario_app_id', 'app_cliente_id', 'usuario_mobile_id'] as $column) {
            if ($this->columnExists('rest_pedidos', $column)) {
                $conditions[] = "{$alias}.{$column} IS NOT NULL";
            }
        }

        foreach (['tipo_origen', 'tipo_pedido', 'origen', 'canal'] as $column) {
            if ($this->columnExists('rest_pedidos', $column)) {
                $conditions[] = "LOWER(COALESCE({$alias}.{$column}, '')) IN ('app', 'mobile', 'movil', 'amare_app')";
            }
        }

        return $conditions ? '(' . implode(' OR ', $conditions) . ')' : '0 = 1';
    }

    private function pedidoSinTicketPagadoSql(string $alias = 'p'): string
    {
        $conditions = [];

        if ($this->tableExists('rest_tickets')) {
            if ($this->columnExists('rest_tickets', 'pedido_id')) {
                $conditions[] = "NOT EXISTS (
                    SELECT 1 FROM rest_tickets tp
                    WHERE tp.pedido_id = {$alias}.id
                      AND tp.estado = 'pagado'
                )";
            }

            if ($this->columnExists('rest_pedidos', 'ticket_id')) {
                $conditions[] = "NOT EXISTS (
                    SELECT 1 FROM rest_tickets ti
                    WHERE ti.id = {$alias}.ticket_id
                      AND ti.estado = 'pagado'
                )";
            }

            if ($this->columnExists('rest_pedidos', 'visita_id') && $this->columnExists('rest_tickets', 'visita_id')) {
                $conditions[] = "NOT EXISTS (
                    SELECT 1 FROM rest_tickets tv
                    WHERE tv.visita_id = {$alias}.visita_id
                      AND tv.estado = 'pagado'
                )";
            }
        }

        return $conditions ? ' AND ' . implode(' AND ', $conditions) : '';
    }

    private function ingresosPedidosApp(int $restauranteId, string $desde, string $hasta): array
    {
        if (!$this->tableExists('rest_pedidos')) {
            return ['total' => 0.0, 'cantidad' => 0];
        }

        $fechaExpr = $this->pedidoFechaExpr('p');
        if ($fechaExpr === 'NULL') {
            return ['total' => 0.0, 'cantidad' => 0];
        }

        $row = $this->queryOne(
            "SELECT COALESCE(SUM({$this->pedidoIngresoContableExpr('p')}), 0) AS total,
                    COUNT(DISTINCT p.id) AS cantidad
             FROM rest_pedidos p
             WHERE p.restaurante_id = ?
               AND DATE({$fechaExpr}) BETWEEN ? AND ?
               {$this->pedidoNoCanceladoSql('p')}
               AND {$this->pedidoAppWhereSql('p')}
               {$this->pedidoSinTicketPagadoSql('p')}",
            [$restauranteId, $desde, $hasta]
        );

        return [
            'total' => (float)($row['total'] ?? 0),
            'cantidad' => (int)($row['cantidad'] ?? 0),
        ];
    }

    private function ingresosPedidosAppPorDia(int $restauranteId, string $desde, string $hasta): array
    {
        if (!$this->tableExists('rest_pedidos')) {
            return [];
        }

        $fechaExpr = $this->pedidoFechaExpr('p');
        if ($fechaExpr === 'NULL') {
            return [];
        }

        return $this->query(
            "SELECT DATE({$fechaExpr}) AS dia,
                    COALESCE(SUM({$this->pedidoIngresoContableExpr('p')}), 0) AS total
             FROM rest_pedidos p
             WHERE p.restaurante_id = ?
               AND DATE({$fechaExpr}) BETWEEN ? AND ?
               {$this->pedidoNoCanceladoSql('p')}
               AND {$this->pedidoAppWhereSql('p')}
               {$this->pedidoSinTicketPagadoSql('p')}
             GROUP BY dia
             ORDER BY dia",
            [$restauranteId, $desde, $hasta]
        );
    }

    private function recargasAmarePorDia(string $desde, string $hasta): array
    {
        if ($this->tableExists('amare_wallet_topups')) {
            $fechaColumn = $this->firstExistingColumn('amare_wallet_topups', ['confirmed_at', 'pagado_at', 'created_at']);
            $amountColumn = $this->firstExistingColumn('amare_wallet_topups', ['amount_received', 'requested_amount', 'amount_mxn', 'monto']);
            if ($fechaColumn && $amountColumn) {
                $amountExpr = $this->columnExists('amare_wallet_topups', 'amount_received')
                    && $this->columnExists('amare_wallet_topups', 'requested_amount')
                    ? 'COALESCE(amount_received, requested_amount)'
                    : $amountColumn;
                $statusWhere = $this->columnExists('amare_wallet_topups', 'status')
                    ? " AND status IN ('confirmed', 'pagado', 'paid', 'success', 'completed')"
                    : '';

                $rows = $this->query(
                    "SELECT DATE({$fechaColumn}) AS dia,
                            COALESCE(SUM({$amountExpr}), 0) AS total
                     FROM amare_wallet_topups
                     WHERE DATE({$fechaColumn}) BETWEEN ? AND ?
                     {$statusWhere}
                     GROUP BY dia
                     ORDER BY dia",
                    [$desde, $hasta]
                );
                $total = array_sum(array_map(fn($row) => (float)($row['total'] ?? 0), $rows));
                if ($total > 0) {
                    return $rows;
                }
            }
        }

        if ($this->tableExists('amare_wallet_transactions')) {
            $fechaColumn = $this->firstExistingColumn('amare_wallet_transactions', ['created_at', 'fecha', 'paid_at']);
            $amountExpr = $this->walletTransactionAmountExpr();
            if ($fechaColumn && $amountExpr !== '0') {
                $topupWhere = $this->walletTopupWhereSql();

                return $this->query(
                    "SELECT DATE({$fechaColumn}) AS dia,
                            COALESCE(SUM({$amountExpr}), 0) AS total
                     FROM amare_wallet_transactions
                     WHERE {$amountExpr} > 0
                       AND DATE({$fechaColumn}) BETWEEN ? AND ?
                       AND {$topupWhere}
                     GROUP BY dia
                     ORDER BY dia",
                    [$desde, $hasta]
                );
            }
        }

        return [];
    }

    private function mergeDailyTotals(array ...$series): array
    {
        $totals = [];
        foreach ($series as $rows) {
            foreach ($rows as $row) {
                $dia = (string)($row['dia'] ?? '');
                if ($dia === '') {
                    continue;
                }
                $totals[$dia] = ($totals[$dia] ?? 0) + (float)($row['total'] ?? 0);
            }
        }

        ksort($totals);
        return array_map(
            fn($dia, $total) => ['dia' => $dia, 'total' => $total],
            array_keys($totals),
            array_values($totals)
        );
    }

    private function walletTransactionAmountExpr(): string
    {
        $amounts = [];
        foreach (['amount_mxn', 'monto', 'amount'] as $column) {
            if ($this->columnExists('amare_wallet_transactions', $column)) {
                $amounts[] = "NULLIF({$column}, 0)";
            }
        }

        return $amounts ? 'COALESCE(' . implode(', ', $amounts) . ', 0)' : '0';
    }

    private function walletTopupWhereSql(): string
    {
        $conditions = [];
        if ($this->columnExists('amare_wallet_transactions', 'type')) {
            $conditions[] = "type IN ('wallet_topup','topup','recarga','demo_credit')";
        }
        if ($this->columnExists('amare_wallet_transactions', 'tipo')) {
            $conditions[] = "tipo = 'topup'";
        }
        if ($this->columnExists('amare_wallet_transactions', 'context')) {
            $conditions[] = "context = 'topup'";
        }
        if ($this->columnExists('amare_wallet_transactions', 'reference_type')) {
            $conditions[] = "reference_type = 'wallet_topup'";
        }

        return $conditions ? '(' . implode(' OR ', $conditions) . ')' : '1 = 1';
    }

    private function recargasAmarePeriodo(string $desde, string $hasta): float
    {
        if ($this->tableExists('amare_wallet_topups')) {
            $fechaColumn = $this->firstExistingColumn('amare_wallet_topups', ['confirmed_at', 'pagado_at', 'created_at']);
            $amountColumn = $this->firstExistingColumn('amare_wallet_topups', ['amount_received', 'requested_amount', 'amount_mxn', 'monto']);
            if ($fechaColumn && $amountColumn) {
                $amountExpr = $this->columnExists('amare_wallet_topups', 'amount_received')
                    && $this->columnExists('amare_wallet_topups', 'requested_amount')
                    ? 'COALESCE(amount_received, requested_amount)'
                    : $amountColumn;
                $statusWhere = $this->columnExists('amare_wallet_topups', 'status')
                    ? " AND status IN ('confirmed', 'pagado', 'paid', 'success', 'completed')"
                    : '';

                $topupsTotal = (float)($this->queryOne(
                    "SELECT COALESCE(SUM({$amountExpr}),0) AS v
                     FROM amare_wallet_topups
                     WHERE DATE({$fechaColumn}) BETWEEN ? AND ?
                     {$statusWhere}",
                    [$desde, $hasta]
                )['v'] ?? 0);
                if ($topupsTotal > 0) {
                    return $topupsTotal;
                }
            }
        }

        if ($this->tableExists('amare_wallet_transactions')) {
            $fechaColumn = $this->firstExistingColumn('amare_wallet_transactions', ['created_at', 'fecha', 'paid_at']);
            $amountExpr = $this->walletTransactionAmountExpr();
            if ($fechaColumn && $amountExpr !== '0') {
                $topupWhere = $this->walletTopupWhereSql();

                return (float)($this->queryOne(
                    "SELECT COALESCE(SUM({$amountExpr}),0) AS v
                     FROM amare_wallet_transactions
                     WHERE {$amountExpr} > 0
                       AND DATE({$fechaColumn}) BETWEEN ? AND ?
                       AND {$topupWhere}",
                    [$desde, $hasta]
                )['v'] ?? 0);
            }
        }

        return 0.0;
    }

    public function kpisDashboard(int $restauranteId, string $desde, string $hasta): array
    {
        $params = [$restauranteId, $desde, $hasta];

        $ingresosTickets = (float) $this->queryOne(
            "SELECT COALESCE(SUM({$this->ticketIngresoContableExpr('t')}),0) AS v
             FROM rest_tickets t
             WHERE t.restaurante_id=? AND t.estado='pagado' AND DATE(t.pagado_at) BETWEEN ? AND ?",
            $params
        )['v'];

        $pedidosApp = $this->ingresosPedidosApp($restauranteId, $desde, $hasta);
        $ingresosPedidosApp = (float)$pedidosApp['total'];
        $totalPedidosApp = (int)$pedidosApp['cantidad'];
        $ingresosRecargasAmare = $this->recargasAmarePeriodo($desde, $hasta);
        $ingresos = $ingresosTickets + $ingresosPedidosApp + $ingresosRecargasAmare;

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

        $ticketPromedio = $totalTickets > 0 ? round($ingresosTickets / $totalTickets, 2) : 0.0;

        $pendiente = (float) $this->queryOne(
            "SELECT COALESCE(SUM(total),0) AS v FROM rest_tickets
             WHERE restaurante_id=? AND estado='pendiente' AND DATE(created_at) BETWEEN ? AND ?",
            $params
        )['v'];

        $utilidad  = $ingresos - $gastos - $retiros;
        $margen    = $ingresos > 0 ? round(($utilidad / $ingresos) * 100, 2) : 0;

        return compact(
            'ingresos',
            'ingresosTickets',
            'ingresosPedidosApp',
            'ingresosRecargasAmare',
            'totalPedidosApp',
            'gastos',
            'retiros',
            'propinas',
            'utilidad',
            'margen',
            'totalTickets',
            'ticketPromedio',
            'pendiente'
        );
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

        $recargas = $this->recargasAmarePeriodo($desde, $hasta);

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

            if ($this->columnExists('rest_pedidos', 'amare_wallet_used_mxn')) {
                $walletUsadoPedidos = (float)($this->queryOne(
                    "SELECT COALESCE(SUM(amare_wallet_used_mxn),0) AS v
                     FROM rest_pedidos
                     WHERE restaurante_id = ? AND DATE({$pedidoFechaExpr}) BETWEEN ? AND ?",
                    [$restauranteId, $desde, $hasta]
                )['v'] ?? 0);
                $walletUsado = max($walletUsado, $walletUsadoPedidos);
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
        $ingTickets = $this->query(
            "SELECT DATE(t.pagado_at) AS dia,
                    SUM({$this->ticketIngresoContableExpr('t')}) AS total
             FROM rest_tickets t
             WHERE t.restaurante_id=? AND t.estado='pagado' AND DATE(t.pagado_at) BETWEEN ? AND ?
             GROUP BY dia ORDER BY dia",
            [$restauranteId, $desde, $hasta]
        );
        $ing = $this->mergeDailyTotals(
            $ingTickets,
            $this->ingresosPedidosAppPorDia($restauranteId, $desde, $hasta),
            $this->recargasAmarePorDia($desde, $hasta)
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
        $metodos = $this->query(
            "SELECT t.metodo_pago,
                    COUNT(*) AS cantidad,
                    SUM({$this->ticketIngresoContableExpr('t')}) AS total
             FROM rest_tickets t
             WHERE t.restaurante_id=? AND t.estado='pagado' AND DATE(t.pagado_at) BETWEEN ? AND ?
             GROUP BY t.metodo_pago",
            [$restauranteId, $desde, $hasta]
        );

        if ($this->tableExists('rest_pedidos')) {
            $fechaExpr = $this->pedidoFechaExpr('p');
            if ($fechaExpr !== 'NULL') {
                $metodoExpr = $this->columnExists('rest_pedidos', 'metodo_pago')
                    ? "COALESCE(NULLIF(p.metodo_pago, ''), 'App movil')"
                    : "'App movil'";
                $appMetodos = $this->query(
                    "SELECT {$metodoExpr} AS metodo_pago,
                            COUNT(DISTINCT p.id) AS cantidad,
                            COALESCE(SUM({$this->pedidoIngresoContableExpr('p')}), 0) AS total
                     FROM rest_pedidos p
                     WHERE p.restaurante_id = ?
                       AND DATE({$fechaExpr}) BETWEEN ? AND ?
                       {$this->pedidoNoCanceladoSql('p')}
                       AND {$this->pedidoAppWhereSql('p')}
                       {$this->pedidoSinTicketPagadoSql('p')}
                     GROUP BY metodo_pago",
                    [$restauranteId, $desde, $hasta]
                );

                $merged = [];
                foreach (array_merge($metodos, $appMetodos) as $row) {
                    $key = (string)($row['metodo_pago'] ?? 'efectivo');
                    if (!isset($merged[$key])) {
                        $merged[$key] = ['metodo_pago' => $key, 'cantidad' => 0, 'total' => 0.0];
                    }
                    $merged[$key]['cantidad'] += (int)($row['cantidad'] ?? 0);
                    $merged[$key]['total'] += (float)($row['total'] ?? 0);
                }

                return array_values($merged);
            }
        }

        return $metodos;
    }

    private function pedidoItemNoCanceladoSql(string $alias = 'i'): string
    {
        return $this->columnExists('rest_pedido_items', 'estado')
            ? " AND COALESCE({$alias}.estado, '') <> 'cancelado'"
            : '';
    }

    private function pedidoItemSubtotalExpr(string $alias = 'i'): string
    {
        $hasSubtotal = $this->columnExists('rest_pedido_items', 'subtotal');
        $hasPrecio = $this->columnExists('rest_pedido_items', 'precio_unit');
        $hasCantidad = $this->columnExists('rest_pedido_items', 'cantidad');

        if ($hasSubtotal && $hasPrecio && $hasCantidad) {
            return "COALESCE(NULLIF({$alias}.subtotal, 0), COALESCE({$alias}.precio_unit, 0) * COALESCE({$alias}.cantidad, 0))";
        }

        if ($hasSubtotal) {
            return "COALESCE({$alias}.subtotal, 0)";
        }

        if ($hasPrecio && $hasCantidad) {
            return "COALESCE({$alias}.precio_unit, 0) * COALESCE({$alias}.cantidad, 0)";
        }

        return '0';
    }

    private function ventasDisponibles(): bool
    {
        return $this->tableExists('rest_pedidos') && $this->tableExists('rest_pedido_items');
    }

    private function ventasWhereSql(string $pedidoAlias = 'p', string $itemAlias = 'i'): string
    {
        $fechaExpr = $this->pedidoFechaExpr($pedidoAlias);
        return " {$pedidoAlias}.restaurante_id = ?
               AND DATE({$fechaExpr}) BETWEEN ? AND ?
               {$this->pedidoNoCanceladoSql($pedidoAlias)}
               {$this->pedidoItemNoCanceladoSql($itemAlias)}";
    }

    private function ventasEstacionWhereSql(string $fechaExpr, string $estacion): string
    {
        $meses = match (strtolower($estacion)) {
            'primavera' => [3, 4, 5],
            'verano' => [6, 7, 8],
            'otono' => [9, 10, 11],
            'invierno' => [12, 1, 2],
            default => [],
        };

        return $meses ? ' AND MONTH(' . $fechaExpr . ') IN (' . implode(',', $meses) . ')' : '';
    }

    public function ventasDashboard(
        int $restauranteId,
        string $desde,
        string $hasta,
        string $ordenProductos = 'desc',
        int $limiteProductos = 20,
        string $estacionProductos = 'todas'
    ): array
    {
        if (!$this->ventasDisponibles()) {
            return [
                'kpis' => ['ventas' => 0, 'unidades' => 0, 'pedidos' => 0, 'productos' => 0, 'ticketPromedio' => 0],
                'topProductos' => [],
                'menosVendidos' => [],
                'categorias' => [],
                'porMes' => [],
                'porEstacion' => [],
                'porCanal' => [],
                'insights' => [],
            ];
        }

        $fechaExpr = $this->pedidoFechaExpr('p');
        if ($fechaExpr === 'NULL') {
            return [
                'kpis' => ['ventas' => 0, 'unidades' => 0, 'pedidos' => 0, 'productos' => 0, 'ticketPromedio' => 0],
                'topProductos' => [],
                'menosVendidos' => [],
                'categorias' => [],
                'porMes' => [],
                'porEstacion' => [],
                'porCanal' => [],
                'insights' => [],
            ];
        }

        $subtotalExpr = $this->pedidoItemSubtotalExpr('i');
        $where = $this->ventasWhereSql('p', 'i');
        $params = [$restauranteId, $desde, $hasta];

        $kpiRow = $this->queryOne(
            "SELECT COALESCE(SUM(COALESCE(i.cantidad, 0)), 0) AS unidades,
                    COALESCE(SUM({$subtotalExpr}), 0) AS ventas,
                    COUNT(DISTINCT p.id) AS pedidos,
                    COUNT(DISTINCT i.platillo_id) AS productos
             FROM rest_pedido_items i
             JOIN rest_pedidos p ON p.id = i.pedido_id
             WHERE {$where}",
            $params
        ) ?: [];

        $pedidos = (int)($kpiRow['pedidos'] ?? 0);
        $ventas = (float)($kpiRow['ventas'] ?? 0);
        $kpis = [
            'ventas' => $ventas,
            'unidades' => (float)($kpiRow['unidades'] ?? 0),
            'pedidos' => $pedidos,
            'productos' => (int)($kpiRow['productos'] ?? 0),
            'ticketPromedio' => $pedidos > 0 ? round($ventas / $pedidos, 2) : 0.0,
        ];

        $ordenProductos = strtolower($ordenProductos) === 'asc' ? 'asc' : 'desc';
        $limiteProductos = max(5, min(100, $limiteProductos));
        $estacionProductos = in_array($estacionProductos, ['primavera', 'verano', 'otono', 'invierno'], true)
            ? $estacionProductos
            : 'todas';
        $topProductos = $ordenProductos === 'asc'
            ? $this->ventasProductosMenosVendidos($restauranteId, $desde, $hasta, $limiteProductos, $estacionProductos)
            : $this->ventasProductos($restauranteId, $desde, $hasta, 'DESC', $limiteProductos, $estacionProductos);
        $productoEstrella = $this->ventasProductos($restauranteId, $desde, $hasta, 'DESC', 1, $estacionProductos);
        $menosVendidos = $this->ventasProductosMenosVendidos($restauranteId, $desde, $hasta, 10, $estacionProductos);
        $categorias = $this->ventasPorCategoria($restauranteId, $desde, $hasta);
        $porMes = $this->ventasPorMes($restauranteId, $desde, $hasta);
        $porEstacion = $this->ventasPorEstacion($restauranteId, $desde, $hasta);

        return [
            'kpis' => $kpis,
            'topProductos' => $topProductos,
            'menosVendidos' => $menosVendidos,
            'categorias' => $categorias,
            'porMes' => $porMes,
            'porEstacion' => $porEstacion,
            'porCanal' => [],
            'insights' => $this->ventasInsights($productoEstrella, $menosVendidos, $categorias),
        ];
    }

    private function ventasProductos(
        int $restauranteId,
        string $desde,
        string $hasta,
        string $order = 'DESC',
        int $limit = 10,
        string $estacion = 'todas'
    ): array
    {
        $limit = max(1, min(100, $limit));
        $order = strtoupper($order) === 'ASC' ? 'ASC' : 'DESC';
        $fechaExpr = $this->pedidoFechaExpr('p');
        $subtotalExpr = $this->pedidoItemSubtotalExpr('i');
        $where = $this->ventasWhereSql('p', 'i');
        $estacionWhere = $this->ventasEstacionWhereSql($fechaExpr, $estacion);

        $platilloJoin = $this->tableExists('rest_platillos') ? 'LEFT JOIN rest_platillos pl ON pl.id = i.platillo_id' : '';
        $categoriaJoin = $this->tableExists('rest_categorias_menu') ? 'LEFT JOIN rest_categorias_menu cm ON cm.id = pl.categoria_id' : '';
        $nombreExpr = $this->tableExists('rest_platillos') ? "COALESCE(pl.nombre, CONCAT('Producto #', i.platillo_id))" : "CONCAT('Producto #', i.platillo_id)";
        $categoriaExpr = $this->tableExists('rest_categorias_menu') ? 'cm.nombre' : 'NULL';

        return $this->query(
            "SELECT i.platillo_id,
                    {$nombreExpr} AS nombre,
                    {$categoriaExpr} AS categoria,
                    COALESCE(SUM(COALESCE(i.cantidad, 0)), 0) AS unidades,
                    COUNT(DISTINCT p.id) AS pedidos,
                    COALESCE(SUM({$subtotalExpr}), 0) AS ventas,
                    MAX({$fechaExpr}) AS ultima_venta
             FROM rest_pedido_items i
             JOIN rest_pedidos p ON p.id = i.pedido_id
             {$platilloJoin}
             {$categoriaJoin}
             WHERE {$where}
               {$estacionWhere}
             GROUP BY i.platillo_id, nombre, categoria
             ORDER BY unidades {$order}, ventas {$order}, nombre ASC
             LIMIT {$limit}",
            [$restauranteId, $desde, $hasta]
        );
    }

    private function ventasProductosMenosVendidos(
        int $restauranteId,
        string $desde,
        string $hasta,
        int $limit = 10,
        string $estacion = 'todas'
    ): array
    {
        if (!$this->tableExists('rest_platillos')) {
            return $this->ventasProductos($restauranteId, $desde, $hasta, 'ASC', $limit, $estacion);
        }

        $limit = max(1, min(100, $limit));
        $fechaExpr = $this->pedidoFechaExpr('p');
        $subtotalExpr = $this->pedidoItemSubtotalExpr('i');
        $estacionWhere = $this->ventasEstacionWhereSql($fechaExpr, $estacion);
        $categoriaJoin = $this->tableExists('rest_categorias_menu') ? 'LEFT JOIN rest_categorias_menu cm ON cm.id = pl.categoria_id' : '';
        $categoriaExpr = $this->tableExists('rest_categorias_menu') ? 'cm.nombre' : 'NULL';
        $activoWhere = $this->columnExists('rest_platillos', 'activo') ? ' AND pl.activo = 1' : '';

        return $this->query(
            "SELECT pl.id AS platillo_id,
                    pl.nombre,
                    {$categoriaExpr} AS categoria,
                    COALESCE(SUM(CASE WHEN p.id IS NOT NULL THEN COALESCE(i.cantidad, 0) ELSE 0 END), 0) AS unidades,
                    COUNT(DISTINCT p.id) AS pedidos,
                    COALESCE(SUM(CASE WHEN p.id IS NOT NULL THEN {$subtotalExpr} ELSE 0 END), 0) AS ventas,
                    MAX({$fechaExpr}) AS ultima_venta
             FROM rest_platillos pl
             LEFT JOIN rest_pedido_items i ON i.platillo_id = pl.id {$this->pedidoItemNoCanceladoSql('i')}
             LEFT JOIN rest_pedidos p ON p.id = i.pedido_id
                  AND p.restaurante_id = ?
                  AND DATE({$fechaExpr}) BETWEEN ? AND ?
                  {$this->pedidoNoCanceladoSql('p')}
                  {$estacionWhere}
             {$categoriaJoin}
             WHERE pl.restaurante_id = ? {$activoWhere}
             GROUP BY pl.id, pl.nombre, categoria
             ORDER BY unidades ASC, ventas ASC, pl.nombre ASC
             LIMIT {$limit}",
            [$restauranteId, $desde, $hasta, $restauranteId]
        );
    }

    private function ventasPorCategoria(int $restauranteId, string $desde, string $hasta): array
    {
        $fechaExpr = $this->pedidoFechaExpr('p');
        $subtotalExpr = $this->pedidoItemSubtotalExpr('i');
        $where = $this->ventasWhereSql('p', 'i');
        if (!$this->tableExists('rest_platillos') || !$this->tableExists('rest_categorias_menu')) {
            return [];
        }

        $platilloJoin = 'JOIN rest_platillos pl ON pl.id = i.platillo_id';
        $categoriaJoin = 'JOIN rest_categorias_menu cm ON cm.id = pl.categoria_id';
        $categoriaExpr = 'cm.nombre';

        return $this->query(
            "SELECT {$categoriaExpr} AS categoria,
                    COALESCE(SUM(COALESCE(i.cantidad, 0)), 0) AS unidades,
                    COALESCE(SUM({$subtotalExpr}), 0) AS ventas
             FROM rest_pedido_items i
             JOIN rest_pedidos p ON p.id = i.pedido_id
             {$platilloJoin}
             {$categoriaJoin}
             WHERE {$where}
               AND cm.nombre IS NOT NULL
               AND cm.nombre <> ''
             GROUP BY categoria
             ORDER BY ventas DESC, unidades DESC",
            [$restauranteId, $desde, $hasta]
        );
    }

    private function ventasPorMes(int $restauranteId, string $desde, string $hasta): array
    {
        $fechaExpr = $this->pedidoFechaExpr('p');
        $subtotalExpr = $this->pedidoItemSubtotalExpr('i');
        $where = $this->ventasWhereSql('p', 'i');

        return $this->query(
            "SELECT DATE_FORMAT({$fechaExpr}, '%Y-%m') AS periodo,
                    COALESCE(SUM(COALESCE(i.cantidad, 0)), 0) AS unidades,
                    COALESCE(SUM({$subtotalExpr}), 0) AS ventas
             FROM rest_pedido_items i
             JOIN rest_pedidos p ON p.id = i.pedido_id
             WHERE {$where}
             GROUP BY periodo
             ORDER BY periodo",
            [$restauranteId, $desde, $hasta]
        );
    }

    private function ventasPorEstacion(int $restauranteId, string $desde, string $hasta): array
    {
        $fechaExpr = $this->pedidoFechaExpr('p');
        $subtotalExpr = $this->pedidoItemSubtotalExpr('i');
        $where = $this->ventasWhereSql('p', 'i');
        $estacionExpr = "CASE
            WHEN MONTH({$fechaExpr}) IN (3,4,5) THEN 'Primavera'
            WHEN MONTH({$fechaExpr}) IN (6,7,8) THEN 'Verano'
            WHEN MONTH({$fechaExpr}) IN (9,10,11) THEN 'Otono'
            ELSE 'Invierno'
        END";

        return $this->query(
            "SELECT {$estacionExpr} AS estacion,
                    COALESCE(SUM(COALESCE(i.cantidad, 0)), 0) AS unidades,
                    COALESCE(SUM({$subtotalExpr}), 0) AS ventas
             FROM rest_pedido_items i
             JOIN rest_pedidos p ON p.id = i.pedido_id
             WHERE {$where}
             GROUP BY estacion
             ORDER BY FIELD(estacion, 'Primavera', 'Verano', 'Otono', 'Invierno')",
            [$restauranteId, $desde, $hasta]
        );
    }

    private function ventasInsights(array $topProductos, array $menosVendidos, array $categorias): array
    {
        $insights = [];

        if (!empty($topProductos[0])) {
            $insights[] = [
                'titulo' => 'Producto estrella',
                'texto' => $topProductos[0]['nombre'] . ' lidera el periodo con ' . number_format((float)$topProductos[0]['unidades'], 0) . ' unidades.',
            ];
        }

        if (!empty($categorias[0])) {
            $insights[] = [
                'titulo' => 'Categoria fuerte',
                'texto' => $categorias[0]['categoria'] . ' concentra $' . number_format((float)$categorias[0]['ventas'], 2) . ' en ventas.',
            ];
        }

        if (!empty($menosVendidos[0])) {
            $insights[] = [
                'titulo' => 'Oportunidad de menu',
                'texto' => $menosVendidos[0]['nombre'] . ' tiene baja rotacion; conviene revisar precio, foto o promocion.',
            ];
        }

        return $insights;
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
