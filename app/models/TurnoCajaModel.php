<?php
/**
 * TurnoCajaModel — apertura, movimientos de efectivo y cierre de caja.
 *
 * Regla central (R9 de plan-web-cajero.md): los totales del turno SIEMPRE
 * se recalculan desde `rest_pedido_pagos` y `rest_pedidos`. Las columnas
 * `total_*` de `turnos_caja` son una foto congelada al cerrar, para que el
 * reporte histórico no cambie si después se toca un pedido.
 */
class TurnoCajaModel extends BaseModel
{
    protected string $table = 'turnos_caja';

    /** Estados de pedido que ya no cuentan como "pendiente de atender". */
    private const ESTADOS_FINALES = ['entregado', 'cancelado'];

    // ── Turno ────────────────────────────────────────────────────

    public function abierto(int $cajeroId): ?array
    {
        return $this->queryOne(
            "SELECT * FROM turnos_caja WHERE cajero_id = ? AND estado = 'abierto' LIMIT 1",
            [$cajeroId]
        );
    }

    public function delRestaurante(int $turnoId, int $restauranteId): ?array
    {
        return $this->queryOne(
            "SELECT t.*, u.nombre AS cajero_nombre, u.apellido_paterno AS cajero_apellido
               FROM turnos_caja t
               LEFT JOIN usuarios u ON u.id = t.cajero_id
              WHERE t.id = ? AND t.restaurante_id = ? LIMIT 1",
            [$turnoId, $restauranteId]
        );
    }

    /**
     * Abre un turno. El índice UNIQUE `uq_turno_cajero_abierto` es la
     * verdadera garantía de que no haya dos abiertos (R2); esta consulta
     * previa solo sirve para dar un mensaje bonito.
     */
    public function abrir(int $restauranteId, int $cajeroId, float $fondoInicial, ?int $terminalUsuarioId, ?string $notas = null): int
    {
        if ($this->abierto($cajeroId)) {
            throw new \RuntimeException('Ya tienes un turno abierto.');
        }

        try {
            $this->execute(
                "INSERT INTO turnos_caja (restaurante_id, cajero_id, terminal_usuario_id, fondo_inicial, notas)
                 VALUES (?,?,?,?,?)",
                [$restauranteId, $cajeroId, $terminalUsuarioId, round($fondoInicial, 2), $notas]
            );
        } catch (\PDOException $e) {
            // 23000 = violación de UNIQUE: alguien abrió turno entre el
            // SELECT de arriba y este INSERT.
            if ($e->getCode() === '23000') {
                throw new \RuntimeException('Ya tienes un turno abierto.');
            }
            throw $e;
        }

        return (int)$this->db->lastInsertId();
    }

    // ── Totales ──────────────────────────────────────────────────

    /**
     * Estado financiero del turno, calculado en vivo.
     *
     * @return array{
     *   por_metodo: array<string,float>, efectivo_cobrado: float, efectivo_devuelto: float,
     *   propinas: float, descuentos: float, cancelado: float, ventas: float,
     *   pedidos_vendidos: int, retiros: float, ingresos: float, efectivo_esperado: float
     * }
     */
    public function totales(int $turnoId): array
    {
        $turno = $this->find($turnoId);
        $fondo = (float)($turno['fondo_inicial'] ?? 0);

        $porMetodo = array_fill_keys(PedidoPagoModel::METODOS, 0.0);
        $efectivoCobrado = 0.0;
        $efectivoDevuelto = 0.0;

        $filas = $this->query(
            "SELECT metodo, tipo, SUM(monto) AS monto
               FROM rest_pedido_pagos
              WHERE turno_caja_id = ?
              GROUP BY metodo, tipo",
            [$turnoId]
        );

        foreach ($filas as $fila) {
            $metodo = (string)$fila['metodo'];
            $monto  = (float)$fila['monto'];
            $signo  = $fila['tipo'] === 'devolucion' ? -1 : 1;

            $porMetodo[$metodo] = round(($porMetodo[$metodo] ?? 0) + ($monto * $signo), 2);

            if (in_array($metodo, PedidoPagoModel::METODOS_EFECTIVO, true)) {
                if ($signo > 0) { $efectivoCobrado  += $monto; }
                else            { $efectivoDevuelto += $monto; }
            }
        }

        $ped = $this->queryOne(
            "SELECT
                COUNT(CASE WHEN estado <> 'cancelado' THEN 1 END)                  AS pedidos_vendidos,
                COALESCE(SUM(CASE WHEN estado <> 'cancelado' THEN propina_mxn END), 0) AS propinas,
                COALESCE(SUM(CASE WHEN estado <> 'cancelado' THEN descuento END), 0)   AS descuentos,
                COALESCE(SUM(CASE WHEN estado <> 'cancelado' THEN total END), 0)       AS ventas,
                COALESCE(SUM(CASE WHEN estado =  'cancelado' THEN total END), 0)       AS cancelado
               FROM rest_pedidos
              WHERE turno_caja_id = ?",
            [$turnoId]
        ) ?: [];

        $mov = ['retiro' => 0.0, 'ingreso' => 0.0];
        foreach ($this->query(
            "SELECT tipo, SUM(monto) AS monto FROM turno_caja_movimientos
              WHERE turno_caja_id = ? GROUP BY tipo",
            [$turnoId]
        ) as $fila) {
            $mov[$fila['tipo']] = (float)$fila['monto'];
        }

        $efectivoEsperado = $fondo + $efectivoCobrado - $efectivoDevuelto + $mov['ingreso'] - $mov['retiro'];

        return [
            'fondo_inicial'     => round($fondo, 2),
            'por_metodo'        => $porMetodo,
            'efectivo_cobrado'  => round($efectivoCobrado, 2),
            'efectivo_devuelto' => round($efectivoDevuelto, 2),
            'propinas'          => round((float)($ped['propinas'] ?? 0), 2),
            'descuentos'        => round((float)($ped['descuentos'] ?? 0), 2),
            'ventas'            => round((float)($ped['ventas'] ?? 0), 2),
            'cancelado'         => round((float)($ped['cancelado'] ?? 0), 2),
            'pedidos_vendidos'  => (int)($ped['pedidos_vendidos'] ?? 0),
            'retiros'           => round($mov['retiro'], 2),
            'ingresos'          => round($mov['ingreso'], 2),
            'efectivo_esperado' => round($efectivoEsperado, 2),
        ];
    }

    /** Ventas del turno, para el historial y la reimpresión de tickets. */
    public function ventas(int $turnoId): array
    {
        return $this->query(
            "SELECT p.id, p.folio, p.total, p.propina_mxn, p.descuento, p.estado,
                    p.pedido_origen, p.created_at, p.metodo_pago, p.cliente_nombre,
                    p.motivo_cancelacion,
                    (SELECT COUNT(*) FROM rest_pedido_items pi WHERE pi.pedido_id = p.id) AS items
               FROM rest_pedidos p
              WHERE p.turno_caja_id = ?
              ORDER BY p.id DESC",
            [$turnoId]
        );
    }

    // ── Movimientos de efectivo ──────────────────────────────────

    public function movimientos(int $turnoId): array
    {
        return $this->query(
            "SELECT * FROM turno_caja_movimientos WHERE turno_caja_id = ? ORDER BY id DESC",
            [$turnoId]
        );
    }

    /**
     * Registra un retiro o ingreso de efectivo y lo espejea en las tablas
     * que ya usa Finanzas, para que el Admin lo vea sin cambios de su lado.
     */
    public function movimiento(array $turno, int $cajeroId, string $tipo, float $monto, string $motivo): int
    {
        if (!in_array($tipo, ['retiro', 'ingreso'], true)) {
            throw new \InvalidArgumentException('Tipo de movimiento no válido.');
        }
        $monto = round($monto, 2);
        if ($monto <= 0) {
            throw new \InvalidArgumentException('El monto debe ser mayor a cero.');
        }
        $motivo = trim($motivo);
        if (mb_strlen($motivo) < 3) {
            throw new \InvalidArgumentException('Escribe el motivo del movimiento.');
        }

        $restauranteId = (int)$turno['restaurante_id'];
        $espejoTabla = null;
        $espejoId    = null;

        if ($tipo === 'retiro') {
            $this->execute(
                "INSERT INTO rest_retiros (restaurante_id, descripcion, monto, usuario_id)
                 VALUES (?,?,?,?)",
                [$restauranteId, 'Caja turno #' . $turno['id'] . ': ' . $motivo, $monto, $cajeroId]
            );
            $espejoTabla = 'rest_retiros';
            $espejoId    = (int)$this->db->lastInsertId();
        }

        $this->execute(
            "INSERT INTO turno_caja_movimientos
               (turno_caja_id, restaurante_id, cajero_id, tipo, monto, motivo, espejo_tabla, espejo_id)
             VALUES (?,?,?,?,?,?,?,?)",
            [$turno['id'], $restauranteId, $cajeroId, $tipo, $monto, mb_substr($motivo, 0, 255), $espejoTabla, $espejoId]
        );

        return (int)$this->db->lastInsertId();
    }

    // ── Pedidos de la app sin atender ────────────────────────────

    public function pendientesApp(int $restauranteId): array
    {
        $placeholders = implode(',', array_fill(0, count(self::ESTADOS_FINALES), '?'));
        return $this->query(
            "SELECT p.id, p.folio, p.total, p.estado, p.cliente_nombre, p.comprador_telefono,
                    p.tipo_pedido, p.pickup_at, p.pagado_at, p.created_at, p.metodo_pago,
                    (SELECT COUNT(*) FROM rest_pedido_items pi WHERE pi.pedido_id = p.id) AS items
               FROM rest_pedidos p
              WHERE p.restaurante_id = ?
                AND p.turno_caja_id IS NULL
                AND p.estado NOT IN ($placeholders)
              ORDER BY p.created_at ASC",
            array_merge([$restauranteId], self::ESTADOS_FINALES)
        );
    }

    public function contarPendientesApp(int $restauranteId): int
    {
        $placeholders = implode(',', array_fill(0, count(self::ESTADOS_FINALES), '?'));
        $row = $this->queryOne(
            "SELECT COUNT(*) AS c FROM rest_pedidos
              WHERE restaurante_id = ? AND turno_caja_id IS NULL AND estado NOT IN ($placeholders)",
            array_merge([$restauranteId], self::ESTADOS_FINALES)
        );
        return (int)($row['c'] ?? 0);
    }

    // ── Cierre ───────────────────────────────────────────────────

    /**
     * Cierra el turno congelando los totales calculados.
     * No bloquea si quedan pedidos de app pendientes (decisión D10): los
     * cuenta, los deja sin turno y el siguiente turno los ve.
     */
    public function cerrar(array $turno, float $efectivoContado, ?array $denominaciones, ?string $notas, float $umbralAlerta): array
    {
        $turnoId = (int)$turno['id'];
        $t       = $this->totales($turnoId);

        $diferencia = round($efectivoContado - $t['efectivo_esperado'], 2);
        $alerta     = abs($diferencia) > $umbralAlerta ? 1 : 0;
        $pendientes = $this->contarPendientesApp((int)$turno['restaurante_id']);

        $this->execute(
            "UPDATE turnos_caja SET
                total_efectivo = ?, total_tarjeta = ?, total_wallet = ?, total_transferencia = ?,
                total_prepagado_app = ?, total_propinas = ?, total_descuentos = ?, total_cancelado = ?,
                total_retiros = ?, total_ingresos_extra = ?, pedidos_vendidos = ?,
                pedidos_pendientes_al_cierre = ?,
                efectivo_esperado = ?, efectivo_contado = ?, diferencia = ?, alerta_diferencia = ?,
                denominaciones_json = ?, notas = ?, estado = 'cerrado', cerrado_at = NOW()
              WHERE id = ? AND estado = 'abierto'",
            [
                $t['por_metodo']['efectivo'] ?? 0,
                $t['por_metodo']['tarjeta'] ?? 0,
                $t['por_metodo']['wallet'] ?? 0,
                $t['por_metodo']['transferencia'] ?? 0,
                $t['por_metodo']['stripe_app'] ?? 0,
                $t['propinas'], $t['descuentos'], $t['cancelado'],
                $t['retiros'], $t['ingresos'], $t['pedidos_vendidos'],
                $pendientes,
                $t['efectivo_esperado'], round($efectivoContado, 2), $diferencia, $alerta,
                $denominaciones ? json_encode($denominaciones, JSON_UNESCAPED_UNICODE) : null,
                $notas,
                $turnoId,
            ]
        );

        $this->espejarCorte($turnoId);

        return [
            'turno_id'          => $turnoId,
            'efectivo_esperado' => $t['efectivo_esperado'],
            'efectivo_contado'  => round($efectivoContado, 2),
            'diferencia'        => $diferencia,
            'alerta'            => (bool)$alerta,
            'pendientes'        => $pendientes,
        ];
    }

    /**
     * Espejo del cierre en `rest_cortes` (decisión D3), la tabla que ya lee
     * la pantalla de cortes del Admin.
     *
     * OJO: es una FOTO, no una fuente de ingresos. Sumar rest_cortes además
     * de las ventas contaría el dinero dos veces.
     */
    public function espejarCorte(int $turnoId): void
    {
        $turno = $this->find($turnoId);
        if (!$turno) return;

        $ingresos = (float)$turno['total_efectivo'] + (float)$turno['total_tarjeta']
                  + (float)$turno['total_wallet'] + (float)$turno['total_transferencia'];

        $nota = 'Cierre automático de caja — turno #' . $turnoId;
        if (!empty($turno['notas'])) {
            $nota .= ' · ' . $turno['notas'];
        }
        if ((float)$turno['diferencia'] != 0.0) {
            $nota .= ' · Diferencia: $' . number_format((float)$turno['diferencia'], 2);
        }

        try {
            $this->execute(
                "INSERT INTO rest_cortes
                   (restaurante_id, turno, usuario_id, ingresos, gastos, retiros, propinas, utilidad_neta, notas)
                 VALUES (?,?,?,?,?,?,?,?,?)",
                [
                    $turno['restaurante_id'],
                    'Caja #' . $turnoId,
                    $turno['cajero_id'],
                    round($ingresos, 2),
                    0,
                    (float)$turno['total_retiros'],
                    (float)$turno['total_propinas'],
                    round($ingresos - (float)$turno['total_retiros'], 2),
                    $nota,
                ]
            );
        } catch (\Throwable $e) {
            // El espejo es una comodidad para el Admin: si `rest_cortes`
            // cambió o no existe, el cierre del cajero no se cae por eso.
            error_log('[caja] No se pudo espejar el corte del turno ' . $turnoId . ': ' . $e->getMessage());
        }
    }

    // ── Historial ────────────────────────────────────────────────

    public function historial(int $cajeroId, int $limite = 30): array
    {
        return $this->query(
            "SELECT * FROM turnos_caja
              WHERE cajero_id = ? AND estado = 'cerrado'
              ORDER BY id DESC LIMIT " . max(1, min(100, $limite)),
            [$cajeroId]
        );
    }
}
