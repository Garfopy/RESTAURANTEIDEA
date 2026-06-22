<?php

class AmareModifierSyncService
{
    private RestMenuModel $menuModel;

    public function __construct()
    {
        $this->menuModel = new RestMenuModel();
    }

    /**
     * Web y Amare-App comparten base de datos. Sincronizar significa dejar
     * materializadas las relaciones oficiales; no se replica por HTTP.
     */
    public function syncPlatillo(int $restauranteId, int $platilloId): array
    {
        try {
            $platillo = $this->menuModel->find($platilloId);
            if (!$platillo || (int)$platillo['restaurante_id'] !== $restauranteId) {
                return ['ok' => false, 'http_code' => 404, 'message' => 'El platillo no pertenece al restaurante.'];
            }
            $this->menuModel->sincronizarExclusionesDesdeReceta($restauranteId, $platilloId);
            $payload = $this->buildPayload($restauranteId, $platilloId);
            return [
                'ok' => true,
                'shared_database' => true,
                'cantidad' => count($payload['modificadores']),
                'payload' => $payload,
            ];
        } catch (\Throwable $e) {
            error_log('[ModificadoresAmare] ' . $e->getMessage());
            return ['ok' => false, 'http_code' => 0, 'message' => $e->getMessage()];
        }
    }

    public function buildPayload(int $restauranteId, int $platilloId): array
    {
        $db = Database::getInstance();
        $stmt = $db->prepare(
            "SELECT exclusiones_app_habilitadas, extras_app_habilitados
             FROM rest_restaurantes WHERE id=?"
        );
        $stmt->execute([$restauranteId]);
        $rest = $stmt->fetch(\PDO::FETCH_ASSOC) ?: [];
        $mods = array_values(array_filter(
            $this->menuModel->getModificadoresPlatillo($platilloId),
            fn($m) => ($m['tipo'] === 'sin' && !empty($rest['exclusiones_app_habilitadas']))
                || ($m['tipo'] === 'extra' && !empty($rest['extras_app_habilitados']))
        ));
        $flat = array_map(fn($m) => [
            'id' => (int)$m['id'],
            'tipo' => $m['tipo'] === 'sin' ? 'exclusion' : 'extra',
            'alcance' => $m['alcance'] ?? 'platillo',
            'nombre' => $m['nombre'],
            'ingrediente_nombre' => $m['ingrediente_nombre'] ?? $m['nombre'],
            'ingrediente_id' => (int)$m['ingrediente_id'],
            'cantidad_unidad' => (float)$m['cantidad_unidad'],
            'unidad' => $m['unidad'],
            'precio_unitario' => (float)$m['precio_extra'],
            'max_cantidad' => (int)$m['max_seleccion'],
        ], $mods);
        $incluidas = array_values(array_map(fn($m) => array_merge($m, [
            'nombre' => $m['ingrediente_nombre'] ?: $m['nombre'],
            'incluida' => true,
            'visible' => true,
            'puede_omitirse' => true,
            'omitida_por_defecto' => false,
            'seleccionada_por_defecto' => true,
            'accion_al_desmarcar' => 'enviar_exclusion',
        ]), array_filter($flat, fn($m) => $m['tipo'] === 'exclusion')));
        $extras = array_values(array_map(fn($m) => array_merge($m, [
            'cantidad_inicial' => 0,
        ]), array_filter($flat, fn($m) => $m['tipo'] === 'extra')));

        return [
            'platillo_id' => $platilloId,
            'modificadores' => array_values($flat),
            'selector' => [
                'tipo' => 'personalizacion_platillo',
                'titulo' => 'Personaliza tu platillo',
                'visible' => !empty($incluidas) || !empty($extras),
                'incluidas' => $incluidas,
                'extras' => $extras,
            ],
        ];
    }

    public function syncTodos(int $restauranteId): array
    {
        try {
            $this->menuModel->prepararSelectorUnificado($restauranteId);
            $platillos = $this->menuModel->getByRestaurante($restauranteId, true);
            return [
                'ok' => true,
                'shared_database' => true,
                'sincronizados' => count($platillos),
                'total' => count($platillos),
                'errores' => [],
            ];
        } catch (\Throwable $e) {
            error_log('[ModificadoresAmare] ' . $e->getMessage());
            return [
                'ok' => false,
                'shared_database' => true,
                'sincronizados' => 0,
                'total' => 0,
                'errores' => [[
                    'nombre' => 'los platillos',
                    'result' => ['http_code' => 0, 'message' => $e->getMessage()],
                ]],
            ];
        }
    }
}
