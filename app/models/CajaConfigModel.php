<?php
/**
 * CajaConfigModel — parámetros del POS con defaults seguros.
 *
 * Todo lo configurable del cajero pasa por aquí. Dos motivos:
 *  1. `rest_configuracion` puede no tener fila para un restaurante.
 *  2. Si la migración 002 todavía no corrió, el POS abre con defaults
 *     en vez de tronar.
 */
class CajaConfigModel extends BaseModel
{
    protected string $table = 'rest_configuracion';

    /** @var array<string,mixed> */
    public const DEFAULTS = [
        'descuento_max_cajero_pct'   => 10.00,
        'impresora_ancho_ticket'     => '80mm',
        'iva_habilitado'             => 0,
        'iva_porcentaje'             => 16.00,
        'propinas_pos_habilitadas'   => 1,
        'diferencia_caja_alerta_mxn' => 20.00,
        'pin_intentos_max'           => 5,
        'pin_bloqueo_minutos'        => 5,
        'pos_polling_segundos'       => 15,
        'ticket_leyenda'             => null,
    ];

    /** Métodos de pago que el POS sabe cobrar, en el orden en que se muestran. */
    public const METODOS_POS = ['efectivo', 'tarjeta', 'transferencia', 'wallet'];

    private static array $cache = [];

    /**
     * Config completa del POS para un restaurante: defaults + fila de
     * rest_configuracion + datos del restaurante.
     */
    public function get(int $restauranteId): array
    {
        if (isset(self::$cache[$restauranteId])) {
            return self::$cache[$restauranteId];
        }

        $fila = $this->queryOne(
            "SELECT * FROM rest_configuracion WHERE restaurante_id = ? LIMIT 1",
            [$restauranteId]
        ) ?: [];

        $rest = $this->queryOne(
            "SELECT * FROM rest_restaurantes WHERE id = ? LIMIT 1",
            [$restauranteId]
        ) ?: [];

        $cfg = [];
        foreach (self::DEFAULTS as $clave => $default) {
            $valor = $fila[$clave] ?? null;
            $cfg[$clave] = ($valor === null || $valor === '') ? $default : $valor;
        }

        $cfg['metodos_pago']       = $this->metodosPagoHabilitados($fila);
        $cfg['propinas_sugeridas'] = $this->propinasSugeridas($rest);
        $cfg['pos_habilitado']     = array_key_exists('pos_habilitado', $rest)
            ? (int)$rest['pos_habilitado'] === 1
            : true; // migración sin correr: no bloquear
        $cfg['restaurante']        = $rest;
        $cfg['config_version']     = (int)($fila['config_version'] ?? 0);

        // Normalización de tipos: viene todo como string desde PDO.
        $cfg['descuento_max_cajero_pct']   = (float)$cfg['descuento_max_cajero_pct'];
        $cfg['iva_porcentaje']             = (float)$cfg['iva_porcentaje'];
        $cfg['diferencia_caja_alerta_mxn'] = (float)$cfg['diferencia_caja_alerta_mxn'];
        $cfg['iva_habilitado']             = (int)$cfg['iva_habilitado'] === 1;
        $cfg['propinas_pos_habilitadas']   = (int)$cfg['propinas_pos_habilitadas'] === 1;
        $cfg['pin_intentos_max']           = max(1, (int)$cfg['pin_intentos_max']);
        $cfg['pin_bloqueo_minutos']        = max(1, (int)$cfg['pin_bloqueo_minutos']);
        $cfg['pos_polling_segundos']       = max(5, (int)$cfg['pos_polling_segundos']);

        return self::$cache[$restauranteId] = $cfg;
    }

    /**
     * `rest_configuracion.metodos_pago` guarda tokens de la app
     * (["card","cash"]). Aquí se traducen a los métodos que entiende la
     * caja, y siempre se deja efectivo: una caja física sin efectivo no
     * tiene sentido y dejaría al cajero sin poder cobrar.
     */
    private function metodosPagoHabilitados(array $fila): array
    {
        $crudo = $fila['metodos_pago'] ?? null;
        $lista = is_string($crudo) ? json_decode($crudo, true) : $crudo;
        if (!is_array($lista) || !$lista) {
            return ['efectivo', 'tarjeta'];
        }

        $alias = [
            'cash' => 'efectivo', 'efectivo' => 'efectivo',
            'card' => 'tarjeta',  'tarjeta'  => 'tarjeta', 'stripe' => 'tarjeta',
            'transfer' => 'transferencia', 'transferencia' => 'transferencia',
            'wallet' => 'wallet', 'saldo' => 'wallet',
        ];

        $habilitados = ['efectivo'];
        foreach ($lista as $token) {
            $metodo = $alias[strtolower(trim((string)$token))] ?? null;
            if ($metodo && !in_array($metodo, $habilitados, true)) {
                $habilitados[] = $metodo;
            }
        }

        // Respetar el orden canónico de METODOS_POS.
        return array_values(array_filter(self::METODOS_POS, fn($m) => in_array($m, $habilitados, true)));
    }

    /** @return int[] Porcentajes sugeridos de propina, sin el 0 y sin duplicados. */
    private function propinasSugeridas(array $rest): array
    {
        $crudo = (string)($rest['propinas_sugeridas'] ?? '0,10,15,20');
        $pcts  = [];
        foreach (explode(',', $crudo) as $parte) {
            $pct = (int)trim($parte);
            if ($pct > 0 && $pct <= 100 && !in_array($pct, $pcts, true)) {
                $pcts[] = $pct;
            }
        }
        sort($pcts);
        return $pcts;
    }

    /** Solo para tests manuales: fuerza releer la config tras un cambio. */
    public static function limpiarCache(): void
    {
        self::$cache = [];
    }
}
