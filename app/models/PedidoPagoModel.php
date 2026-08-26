<?php
/**
 * PedidoPagoModel — pagos de un pedido (soporta pago mixto y devoluciones).
 *
 * Reglas que se hacen cumplir aquí (ver plan-web-cajero.md §8):
 *  R5  la suma de pagos debe ser exactamente el total del pedido
 *  R6  en efectivo, recibido >= monto, y el cambio lo calcula el servidor
 *  R12 el prepago de la app (stripe_app) no entra al efectivo esperado
 *  R13 una devolución afecta al turno donde ocurre, no al de la venta
 */
class PedidoPagoModel extends BaseModel
{
    protected string $table = 'rest_pedido_pagos';

    public const METODOS = ['efectivo', 'tarjeta', 'wallet', 'transferencia', 'stripe_app', 'otro'];

    /** Métodos que mueven el cajón de dinero físico. */
    public const METODOS_EFECTIVO = ['efectivo'];

    /**
     * Normaliza y valida la lista de pagos que llega del navegador.
     * NO toca la base: solo devuelve la lista limpia o lanza excepción.
     *
     * @param  array $pagos    [{metodo, monto, recibido?, referencia?}]
     * @param  float $total    total del pedido, ya calculado en servidor
     * @return array{pagos:array, cambio:float}
     */
    public function validar(array $pagos, float $total, array $metodosHabilitados): array
    {
        if (!$pagos) {
            throw new \InvalidArgumentException('Falta indicar cómo se pagó.');
        }

        $limpios = [];
        $suma    = 0.0;
        $cambio  = 0.0;

        foreach ($pagos as $pago) {
            $metodo = strtolower(trim((string)($pago['metodo'] ?? '')));
            if (!in_array($metodo, self::METODOS, true)) {
                throw new \InvalidArgumentException('Método de pago no válido: ' . $metodo);
            }
            if ($metodo !== 'stripe_app' && !in_array($metodo, $metodosHabilitados, true)) {
                throw new \InvalidArgumentException('Ese método de pago no está habilitado para este negocio.');
            }

            $monto = round((float)($pago['monto'] ?? 0), 2);
            if ($monto <= 0) {
                throw new \InvalidArgumentException('El monto de cada pago debe ser mayor a cero.');
            }

            $fila = [
                'metodo'     => $metodo,
                'monto'      => $monto,
                'recibido'   => null,
                'cambio'     => null,
                'referencia' => $this->referencia($pago['referencia'] ?? null),
            ];

            if (in_array($metodo, self::METODOS_EFECTIVO, true)) {
                $recibido = isset($pago['recibido']) ? round((float)$pago['recibido'], 2) : $monto;
                if ($recibido + 0.001 < $monto) {
                    throw new \InvalidArgumentException(
                        'El efectivo recibido ($' . number_format($recibido, 2) . ') ' .
                        'es menor al monto a cobrar ($' . number_format($monto, 2) . ').'
                    );
                }
                $fila['recibido'] = $recibido;
                $fila['cambio']   = round($recibido - $monto, 2);
                $cambio          += $fila['cambio'];
            }

            $suma += $monto;
            $limpios[] = $fila;
        }

        // Tolerancia de un centavo por los redondeos de decimal(10,2).
        if (abs(round($suma, 2) - round($total, 2)) > 0.011) {
            $falta = round($total - $suma, 2);
            throw new \InvalidArgumentException(
                $falta > 0
                    ? 'Faltan $' . number_format($falta, 2) . ' por cubrir.'
                    : 'Los pagos exceden el total por $' . number_format(abs($falta), 2) . '.'
            );
        }

        return ['pagos' => $limpios, 'cambio' => round($cambio, 2)];
    }

    /** Inserta los cobros de un pedido. Se llama DENTRO de la transacción del cobro. */
    public function registrar(int $pedidoId, int $restauranteId, ?int $turnoId, ?int $cajeroId, array $pagos): void
    {
        foreach ($pagos as $pago) {
            $this->execute(
                "INSERT INTO rest_pedido_pagos
                   (pedido_id, restaurante_id, turno_caja_id, cajero_id, tipo, metodo, monto, recibido, cambio, referencia)
                 VALUES (?,?,?,?,'cobro',?,?,?,?,?)",
                [
                    $pedidoId, $restauranteId, $turnoId, $cajeroId,
                    $pago['metodo'], $pago['monto'],
                    $pago['recibido'] ?? null, $pago['cambio'] ?? null,
                    $pago['referencia'] ?? null,
                ]
            );
        }
    }

    /**
     * Contra-movimientos de una cancelación (decisión D7).
     * Devuelve qué métodos quedaron pendientes de reembolso manual.
     *
     * @return array{devuelto:float, efectivo:float, pendientes:string[]}
     */
    public function devolver(int $pedidoId, int $restauranteId, ?int $turnoId, ?int $cajeroId): array
    {
        $cobros = $this->query(
            "SELECT metodo, SUM(monto) AS monto
               FROM rest_pedido_pagos
              WHERE pedido_id = ? AND tipo = 'cobro'
              GROUP BY metodo",
            [$pedidoId]
        );

        $yaDevuelto = $this->query(
            "SELECT metodo, SUM(monto) AS monto
               FROM rest_pedido_pagos
              WHERE pedido_id = ? AND tipo = 'devolucion'
              GROUP BY metodo",
            [$pedidoId]
        );
        $devueltoPorMetodo = array_column($yaDevuelto, 'monto', 'metodo');

        $total = 0.0; $efectivo = 0.0; $pendientes = [];

        foreach ($cobros as $cobro) {
            $metodo = (string)$cobro['metodo'];
            $saldo  = round((float)$cobro['monto'] - (float)($devueltoPorMetodo[$metodo] ?? 0), 2);
            if ($saldo <= 0) continue;

            $this->execute(
                "INSERT INTO rest_pedido_pagos
                   (pedido_id, restaurante_id, turno_caja_id, cajero_id, tipo, metodo, monto, referencia)
                 VALUES (?,?,?,?,'devolucion',?,?,?)",
                [$pedidoId, $restauranteId, $turnoId, $cajeroId, $metodo, $saldo, 'Cancelación de venta']
            );

            $total += $saldo;
            if (in_array($metodo, self::METODOS_EFECTIVO, true)) {
                $efectivo += $saldo;
            }
            // Wallet y prepago de app no los devuelve el cajero: los procesa el Admin.
            if (in_array($metodo, ['wallet', 'stripe_app'], true)) {
                $pendientes[] = $metodo;
            }
        }

        return [
            'devuelto'   => round($total, 2),
            'efectivo'   => round($efectivo, 2),
            'pendientes' => array_values(array_unique($pendientes)),
        ];
    }

    public function porPedido(int $pedidoId): array
    {
        return $this->query(
            "SELECT * FROM rest_pedido_pagos WHERE pedido_id = ? ORDER BY id",
            [$pedidoId]
        );
    }

    /** Etiqueta legible de un método, para ticket y pantallas. */
    public static function etiqueta(string $metodo): string
    {
        return [
            'efectivo'      => 'Efectivo',
            'tarjeta'       => 'Tarjeta',
            'wallet'        => 'Saldo del cliente',
            'transferencia' => 'Transferencia',
            'stripe_app'    => 'Pagado en la app',
            'otro'          => 'Otro',
        ][$metodo] ?? ucfirst($metodo);
    }

    private function referencia(mixed $valor): ?string
    {
        $ref = trim((string)($valor ?? ''));
        return $ref === '' ? null : mb_substr($ref, 0, 120);
    }
}
