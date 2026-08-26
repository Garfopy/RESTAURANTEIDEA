<?php
/**
 * WalletModel — fachada del monedero del cliente.
 *
 * El POS NUNCA consulta las tablas del wallet directamente. Motivo: en el
 * repo conviven dos nombres para lo mismo (`amare_wallets` en el esquema
 * actual, `jungle_wallets` en el anterior) y el Sprint 1 del marketplace
 * las va a renombrar otra vez al quitar el branding. Con esta fachada, ese
 * cambio se resuelve en un archivo.
 */
class WalletModel extends BaseModel
{
    protected string $table = 'amare_wallets';

    private const TABLAS_WALLET = ['amare_wallets', 'jungle_wallets'];
    private const TABLAS_TX     = ['amare_wallet_transactions', 'jungle_wallet_transactions'];

    private static ?string $tablaWallet = null;
    private static ?string $tablaTx     = null;
    private static array   $columnas    = [];

    // ── Detección de esquema ─────────────────────────────────────

    private function tablaWallet(): ?string
    {
        if (self::$tablaWallet !== null) {
            return self::$tablaWallet ?: null;
        }
        self::$tablaWallet = $this->primeraTablaExistente(self::TABLAS_WALLET) ?? '';
        return self::$tablaWallet ?: null;
    }

    private function tablaTx(): ?string
    {
        if (self::$tablaTx !== null) {
            return self::$tablaTx ?: null;
        }
        self::$tablaTx = $this->primeraTablaExistente(self::TABLAS_TX) ?? '';
        return self::$tablaTx ?: null;
    }

    private function primeraTablaExistente(array $candidatas): ?string
    {
        foreach ($candidatas as $tabla) {
            try {
                $this->db->query("SELECT 1 FROM `{$tabla}` LIMIT 1");
                return $tabla;
            } catch (\Throwable $e) {
                continue;
            }
        }
        return null;
    }

    private function columnas(string $tabla): array
    {
        if (isset(self::$columnas[$tabla])) {
            return self::$columnas[$tabla];
        }
        $cols = [];
        try {
            $stmt = $this->db->query("SHOW COLUMNS FROM `{$tabla}`");
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $cols[] = (string)$row['Field'];
            }
        } catch (\Throwable $e) {
            $cols = [];
        }
        return self::$columnas[$tabla] = $cols;
    }

    /** ¿Este despliegue tiene monedero? Si no, el POS oculta el método. */
    public function disponible(): bool
    {
        return $this->tablaWallet() !== null;
    }

    // ── Consulta ─────────────────────────────────────────────────

    /** @param int $mobileUsuarioId id de `mobile_usuarios`, no de `usuarios`. */
    public function saldo(int $mobileUsuarioId): float
    {
        $tabla = $this->tablaWallet();
        if (!$tabla) return 0.0;

        $row = $this->queryOne(
            "SELECT balance_mxn FROM `{$tabla}` WHERE user_id = ? LIMIT 1",
            [$mobileUsuarioId]
        );
        return round((float)($row['balance_mxn'] ?? 0), 2);
    }

    // ── Movimientos ──────────────────────────────────────────────

    /**
     * Descuenta saldo. Se llama DENTRO de la transacción del cobro: si algo
     * falla después, el saldo se revierte con el resto de la venta.
     *
     * El UPDATE condicionado (`balance_mxn >= ?`) es lo que evita que dos
     * cobros simultáneos dejen el saldo en negativo.
     */
    public function debitar(int $mobileUsuarioId, float $monto, int $pedidoId, string $descripcion): void
    {
        $tabla = $this->tablaWallet();
        if (!$tabla) {
            throw new \RuntimeException('El monedero no está disponible en este sistema.');
        }
        $monto = round($monto, 2);
        if ($monto <= 0) {
            throw new \InvalidArgumentException('El monto a cobrar del saldo debe ser mayor a cero.');
        }

        $stmt = $this->db->prepare(
            "UPDATE `{$tabla}` SET balance_mxn = balance_mxn - ?, updated_at = NOW()
              WHERE user_id = ? AND balance_mxn >= ?"
        );
        $stmt->execute([$monto, $mobileUsuarioId, $monto]);

        if ($stmt->rowCount() === 0) {
            throw new \RuntimeException(
                'Saldo insuficiente. Disponible: $' . number_format($this->saldo($mobileUsuarioId), 2)
            );
        }

        $this->registrarTx($mobileUsuarioId, -$monto, $pedidoId, $descripcion, 'wallet_payment');
    }

    /** Devuelve saldo (reembolso). Hoy no lo usa el cajero — lo resuelve el Admin (D7). */
    public function acreditar(int $mobileUsuarioId, float $monto, int $pedidoId, string $descripcion): void
    {
        $tabla = $this->tablaWallet();
        if (!$tabla) {
            throw new \RuntimeException('El monedero no está disponible en este sistema.');
        }
        $monto = round($monto, 2);
        if ($monto <= 0) return;

        $this->execute(
            "UPDATE `{$tabla}` SET balance_mxn = balance_mxn + ?, updated_at = NOW() WHERE user_id = ?",
            [$monto, $mobileUsuarioId]
        );
        $this->registrarTx($mobileUsuarioId, $monto, $pedidoId, $descripcion, 'refund');
    }

    /**
     * Inserta el movimiento en la bitácora del wallet armando el INSERT solo
     * con las columnas que existan: las dos versiones de la tabla no tienen
     * el mismo juego de campos.
     */
    private function registrarTx(int $mobileUsuarioId, float $delta, int $pedidoId, string $descripcion, string $tipo): void
    {
        $tabla = $this->tablaTx();
        if (!$tabla) return;

        $cols      = $this->columnas($tabla);
        $saldo     = $this->saldo($mobileUsuarioId);
        $walletId  = $this->walletId($mobileUsuarioId);
        $posibles  = [
            'wallet_id'         => $walletId,
            'user_id'           => $mobileUsuarioId,
            'usuario_id'        => $mobileUsuarioId,
            'type'              => $tipo,
            'tipo'              => $delta < 0 ? 'purchase' : 'adjustment',
            'context'           => 'pos',
            'reference_type'    => 'rest_pedido',
            'reference_id'      => $pedidoId,
            'referencia_tipo'   => 'rest_pedido',
            'referencia_id'     => (string)$pedidoId,
            'amount_mxn'        => $delta,
            'monto'             => $delta,
            'balance_after_mxn' => $saldo,
            'saldo_resultante'  => $saldo,
            'description'       => $descripcion,
            'descripcion'       => $descripcion,
        ];

        $datos = [];
        foreach ($posibles as $col => $valor) {
            if (in_array($col, $cols, true)) {
                $datos[$col] = $valor;
            }
        }
        if (!$datos) return;

        try {
            $nombres = '`' . implode('`, `', array_keys($datos)) . '`';
            $marcas  = implode(',', array_fill(0, count($datos), '?'));
            $this->execute("INSERT INTO `{$tabla}` ({$nombres}) VALUES ({$marcas})", array_values($datos));
        } catch (\Throwable $e) {
            // La bitácora no debe tumbar una venta ya cobrada.
            error_log('[caja] No se pudo registrar el movimiento de wallet: ' . $e->getMessage());
        }
    }

    private function walletId(int $mobileUsuarioId): int
    {
        $tabla = $this->tablaWallet();
        if (!$tabla) return 0;
        $row = $this->queryOne("SELECT id FROM `{$tabla}` WHERE user_id = ? LIMIT 1", [$mobileUsuarioId]);
        return (int)($row['id'] ?? 0);
    }
}
