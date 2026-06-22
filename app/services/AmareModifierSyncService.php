<?php

class AmareModifierSyncService
{
    private RestMenuModel $menuModel;

    public function __construct()
    {
        $this->menuModel = new RestMenuModel();
    }

    public function syncPlatillo(int $restauranteId, int $platilloId): array
    {
        try {
            $db = Database::getInstance();
            $restStmt = $db->prepare("SELECT exclusiones_app_habilitadas, extras_app_habilitados FROM rest_restaurantes WHERE id=?");
            $restStmt->execute([$restauranteId]);
            $rest = $restStmt->fetch(\PDO::FETCH_ASSOC) ?: [];
            $cfgStmt = $db->query("SELECT clave, valor FROM global_settings WHERE clave IN ('amare_api_url','amare_api_token')");
            $cfg = array_column($cfgStmt->fetchAll(\PDO::FETCH_ASSOC), 'valor', 'clave');
            if (empty($cfg['amare_api_url']) || empty($cfg['amare_api_token'])) {
                return ['ok' => true, 'skipped' => true, 'message' => 'Amare-App no esta conectada.'];
            }

            $mods = array_values(array_filter(
                $this->menuModel->getModificadoresPlatillo($platilloId),
                fn($m) => ($m['tipo'] === 'sin' && !empty($rest['exclusiones_app_habilitadas']))
                    || ($m['tipo'] === 'extra' && !empty($rest['extras_app_habilitados']))
            ));
            $flat = array_map(fn($m) => [
                'id' => (int)$m['id'],
                'tipo' => $m['tipo'] === 'sin' ? 'exclusion' : 'extra',
                'nombre' => $m['nombre'],
                'ingrediente_id' => (int)$m['ingrediente_id'],
                'cantidad_unidad' => (float)$m['cantidad_unidad'],
                'unidad' => $m['unidad'],
                'precio_unitario' => (float)$m['precio_extra'],
                'max_cantidad' => (int)$m['max_seleccion'],
            ], $mods);
            $incluidas = array_values(array_map(fn($m) => array_merge($m, [
                'seleccionada_por_defecto' => true,
                'accion_al_desmarcar' => 'excluir',
            ]), array_filter($flat, fn($m) => $m['tipo'] === 'exclusion')));
            $extras = array_values(array_map(fn($m) => array_merge($m, [
                'cantidad_inicial' => 0,
            ]), array_filter($flat, fn($m) => $m['tipo'] === 'extra')));
            $payload = [
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
            $branchId = $this->resolveBranchId($restauranteId);
            $url = rtrim($cfg['amare_api_url'], '/') . '/branches/' . $branchId . '/menu-items/' . $platilloId . '/modifiers';
            $ch = curl_init($url);
            curl_setopt_array($ch, [
                CURLOPT_CUSTOMREQUEST => 'PUT', CURLOPT_POSTFIELDS => json_encode($payload), CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT => 10, CURLOPT_CONNECTTIMEOUT => 5,
                CURLOPT_HTTPHEADER => ['Content-Type: application/json', 'Accept: application/json', 'Authorization: Bearer ' . $cfg['amare_api_token']],
            ]);
            $response = curl_exec($ch); $error = curl_error($ch); $code = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE); curl_close($ch);
            if ($error || $code < 200 || $code >= 300) {
                $detail = $error ?: trim((string)$response);
                error_log('[ModificadoresAmare] PUT ' . $url . ' HTTP ' . $code . ' ' . $detail);
                return ['ok' => false, 'http_code' => $code, 'message' => $detail ?: 'Respuesta vacia de Amare-App.'];
            }
            return ['ok' => true, 'http_code' => $code, 'cantidad' => count($mods)];
        } catch (\Throwable $e) {
            error_log('[ModificadoresAmare] ' . $e->getMessage());
            return ['ok' => false, 'http_code' => 0, 'message' => $e->getMessage()];
        }
    }

    public function syncTodos(int $restauranteId): array
    {
        $platillos = $this->menuModel->getByRestaurante($restauranteId, true);
        $sincronizados = 0; $errores = [];
        foreach ($platillos as $platillo) {
            $result = $this->syncPlatillo($restauranteId, (int)$platillo['id']);
            if (!empty($result['ok'])) $sincronizados++;
            else $errores[] = ['platillo_id' => (int)$platillo['id'], 'nombre' => $platillo['nombre'], 'result' => $result];
        }
        return ['ok' => !$errores, 'sincronizados' => $sincronizados, 'total' => count($platillos), 'errores' => $errores];
    }

    private function resolveBranchId(int $restauranteId): int
    {
        try {
            $db = Database::getInstance();
            foreach (['sucursal_id', 'sucursal_carnihub_id'] as $column) {
                $exists = $db->prepare("SELECT COUNT(*) FROM information_schema.columns WHERE table_schema=DATABASE() AND table_name='rest_restaurantes' AND column_name=?");
                $exists->execute([$column]);
                if (!(int)$exists->fetchColumn()) continue;
                $stmt = $db->prepare("SELECT `{$column}` FROM rest_restaurantes WHERE id=?");
                $stmt->execute([$restauranteId]);
                $value = (int)$stmt->fetchColumn();
                if ($value > 0) return $value;
            }
        } catch (\Throwable $e) {
            error_log('[ModificadoresAmare branch] ' . $e->getMessage());
        }
        return $restauranteId;
    }
}
