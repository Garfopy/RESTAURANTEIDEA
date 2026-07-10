<?php
require_once ROOT_PATH . '/app/controllers/BaseController.php';

class RestClienteController extends BaseController
{
    private RestClienteModel $model;

    public function __construct()
    {
        parent::__construct();
        $this->requireRestaurante();
        $this->model = new RestClienteModel();
    }

    public function index(?string $p = null): void
    {
        $restauranteId = $this->restauranteId();
        $this->enviarPromocionesAutomaticasReactivacion($restauranteId);
        $page      = (int)$this->get('page', 1);
        $tipoParam = $p ?: (string)$this->get('tipo', 'todos');
        $tipo      = in_array($tipoParam, ['todos', 'web', 'mobile'], true) ? $tipoParam : 'todos';
        $resultado = $this->model->getByRestaurante($restauranteId, $page, $tipo);
        $flash     = $this->getFlash();
        $pageTitle  = 'Comensales';
        $activeMenu = 'rest_clientes';
        $this->render('restaurante/clientes/index', array_merge($resultado, compact('flash','pageTitle','activeMenu','tipo')));
    }

    public function detalle(?string $id = null): void
    {
        $parsed = $this->parseClienteDetalleId($id);
        $clienteId = $parsed['cliente_id'];
        $esDetalleMobile = $parsed['es_mobile'];
        $mobileDetalleId = $parsed['mobile_id'];
        $restauranteId = $this->restauranteId();
        $comensal = $esDetalleMobile
            ? $this->model->getDetalleMobile($mobileDetalleId, $restauranteId)
            : $this->model->getDetalle($clienteId);
        if (!$comensal) { $this->flash('error', 'Comensal no encontrado.'); $this->redirect('rest-cliente/index'); }
        $historial = $esDetalleMobile
            ? $this->model->getHistorialMobile($mobileDetalleId, $restauranteId)
            : $this->model->getHistorialVisitas($clienteId);
        if (!$esDetalleMobile && $clienteId > 0 && !empty($comensal['mobile_usuario_id'])) {
            $historial = array_merge(
                $historial,
                $this->model->getHistorialMobile((int)$comensal['mobile_usuario_id'], $restauranteId)
            );
            usort($historial, fn($a, $b) => strtotime($b['created_at'] ?? '1970-01-01') <=> strtotime($a['created_at'] ?? '1970-01-01'));
        }
        $mobileUsuarioId = $esDetalleMobile ? $mobileDetalleId : (int)($comensal['mobile_usuario_id'] ?? 0);
        $productosFavoritos = $esDetalleMobile
            ? $this->model->getProductosFavoritosMobile($mobileUsuarioId, $restauranteId)
            : $this->model->getProductosFavoritosComensal($clienteId, $restauranteId, $mobileUsuarioId ?: null);
        $promocionSugerida = $this->model->sugerirPromocion($productosFavoritos, $comensal);
        $promocionApp = $mobileUsuarioId > 0
            ? $this->model->definirPromocionApp($productosFavoritos, $comensal, $restauranteId, 'manual')
            : [];
        $detalleParam = $esDetalleMobile ? 'app-' . $mobileDetalleId : (string)$clienteId;
        $flash     = $this->getFlash();
        $pageTitle  = $comensal['nombre'] ?? $comensal['mobile_nombre'] ?? 'Comensal';
        $activeMenu = 'rest_clientes';
        $this->render('restaurante/clientes/detalle', compact('comensal','historial','productosFavoritos','promocionSugerida','promocionApp','detalleParam','flash','pageTitle','activeMenu'));
    }

    public function enviarPromocion(?string $id = null): void
    {
        if (!$this->isPost()) {
            $this->redirect('rest-cliente/index');
        }

        $parsed = $this->parseClienteDetalleId($id);
        $clienteId = $parsed['cliente_id'];
        $esDetalleMobile = $parsed['es_mobile'];
        $mobileDetalleId = $parsed['mobile_id'];
        $restauranteId = $this->restauranteId();
        $redirectId = $esDetalleMobile ? 'app-' . $mobileDetalleId : (string)$clienteId;

        $comensal = $esDetalleMobile
            ? $this->model->getDetalleMobile($mobileDetalleId, $restauranteId)
            : $this->model->getDetalle($clienteId);

        if (!$comensal) {
            $this->flash('error', 'Comensal no encontrado.');
            $this->redirect('rest-cliente/index');
        }

        $mobileUsuarioId = $esDetalleMobile ? $mobileDetalleId : (int)($comensal['mobile_usuario_id'] ?? 0);
        if ($mobileUsuarioId <= 0) {
            $this->flash('error', 'Este comensal no tiene usuario de App vinculado para enviar promociones.');
            $this->redirect('rest-cliente/detalle/' . $redirectId);
        }

        $productosFavoritos = $esDetalleMobile
            ? $this->model->getProductosFavoritosMobile($mobileUsuarioId, $restauranteId)
            : $this->model->getProductosFavoritosComensal($clienteId, $restauranteId, $mobileUsuarioId);
        $payload = $this->model->definirPromocionApp($productosFavoritos, $comensal, $restauranteId, 'manual');

        if ($this->model->promocionYaEnviada($restauranteId, $mobileUsuarioId, 'manual')) {
            $this->flash('error', 'Ya se envio una promocion manual a este comensal durante este mes.');
            $this->redirect('rest-cliente/detalle/' . $redirectId);
        }

        $resultado = $this->crearPromocionEnApp($restauranteId, $payload);
        if (!$resultado['success']) {
            $this->flash('error', 'No se pudo enviar la promocion a la app: ' . $resultado['message']);
            $this->redirect('rest-cliente/detalle/' . $redirectId);
        }

        $this->model->registrarPromocionEnviada(
            $restauranteId,
            $mobileUsuarioId,
            'manual',
            $payload,
            $resultado['remote_id'],
            $clienteId > 0 ? $clienteId : null
        );

        $this->flash('success', 'Promocion enviada a la app movil correctamente.');
        $this->redirect('rest-cliente/detalle/' . $redirectId);
    }

    public function topConsumo(?string $p = null): void
    {
        $top       = $this->model->topPorConsumo($this->restauranteId());
        $flash     = $this->getFlash();
        $pageTitle  = 'Top por Consumo';
        $activeMenu = 'rest_clientes';
        $this->render('restaurante/clientes/top', compact('top','flash','pageTitle','activeMenu'));
    }

    public function topVisitas(?string $p = null): void
    {
        $top       = $this->model->topPorVisitas($this->restauranteId());
        $flash     = $this->getFlash();
        $pageTitle  = 'Top por Visitas';
        $activeMenu = 'rest_clientes';
        $this->render('restaurante/clientes/top', compact('top','flash','pageTitle','activeMenu'));
    }

    private function parseClienteDetalleId(?string $id): array
    {
        $idRaw = trim((string)$id);
        $esMobile = false;
        $mobileId = 0;

        if (preg_match('/^(?:app|mobile)-(\d+)$/i', $idRaw, $match)) {
            $esMobile = true;
            $mobileId = (int)$match[1];
        }

        $clienteId = $esMobile ? 0 : (int)$idRaw;
        if ($clienteId < 0) {
            $esMobile = true;
            $mobileId = abs($clienteId);
            $clienteId = 0;
        }

        return [
            'cliente_id' => $clienteId,
            'es_mobile' => $esMobile,
            'mobile_id' => $mobileId,
        ];
    }

    private function enviarPromocionesAutomaticasReactivacion(int $restauranteId): void
    {
        try {
            $candidatos = $this->model->getClientesMobileParaReactivacion($restauranteId, 5);
            foreach ($candidatos as $comensal) {
                $mobileUsuarioId = (int)($comensal['mobile_usuario_id'] ?? 0);
                if ($mobileUsuarioId <= 0 || $this->model->promocionYaEnviada($restauranteId, $mobileUsuarioId, 'reactivacion')) {
                    continue;
                }

                $productos = $this->model->getProductosFavoritosMobile($mobileUsuarioId, $restauranteId);
                $payload = $this->model->definirPromocionApp($productos, $comensal, $restauranteId, 'reactivacion');
                $resultado = $this->crearPromocionEnApp($restauranteId, $payload);

                if ($resultado['success']) {
                    $this->model->registrarPromocionEnviada(
                        $restauranteId,
                        $mobileUsuarioId,
                        'reactivacion',
                        $payload,
                        $resultado['remote_id']
                    );
                } else {
                    error_log('[reactivacionPromocion] No se pudo enviar a mobile_usuario_id=' . $mobileUsuarioId . ': ' . $resultado['message']);
                }
            }
        } catch (\Throwable $e) {
            error_log('[reactivacionPromocion] ' . $e->getMessage());
        }
    }

    private function crearPromocionEnApp(int $restauranteId, array $payload): array
    {
        $branchId = $this->getAmareBranchIdByRestaurante($restauranteId);
        if (!$branchId) {
            return $this->guardarPromocionAppLocal($payload, 'Restaurante sin sucursal remota vinculada.');
        }

        $body = [
            'usuario_id' => (int)$payload['usuario_id'],
            'titulo' => (string)$payload['titulo'],
            'descripcion' => (string)$payload['descripcion'],
            'code' => (string)$payload['code'],
            'tipo' => (string)$payload['tipo'],
            'valor_descuento' => (float)$payload['valor_descuento'],
            'fecha_inicio' => (string)$payload['fecha_inicio'],
            'fecha_fin' => (string)$payload['fecha_fin'],
            'expires_at' => (string)$payload['expires_at'],
            'imagen' => (string)($payload['producto_imagen'] ?? ''),
            'producto_id' => isset($payload['producto_id']) ? (int)$payload['producto_id'] : null,
            'platillo_id' => isset($payload['platillo_id']) ? (int)$payload['platillo_id'] : null,
            'activo' => 1,
        ];

        $result = $this->callAmareApi('POST', "branches/{$branchId}/promotions", $body);
        if (!$result['success'] && $this->esEndpointNoEncontrado($result)) {
            $result = $this->callAmareApi('POST', "branches/{$branchId}/promociones", $body);
        }
        if (!$result['success'] && $this->esEndpointNoEncontrado($result)) {
            $legacyPayload = $this->buildLegacyPromocionesPayload($payload);
            $result = $this->callAmareApi('PUT', "branches/{$branchId}/promociones", $legacyPayload);
        }
        if (!$result['success']) {
            if ($this->esEndpointNoEncontrado($result)) {
                return $this->guardarPromocionAppLocal($payload, 'Endpoint remoto de promociones no disponible.');
            }

            return [
                'success' => false,
                'message' => $result['error'] ?? 'Error de conexion con Amare-App.',
                'remote_id' => null,
            ];
        }

        $data = $result['data'] ?? [];
        $promotion = $data['data']['promotion'] ?? $data['promotion'] ?? $data;
        return [
            'success' => true,
            'message' => 'Promocion creada',
            'remote_id' => isset($promotion['id']) ? (int)$promotion['id'] : null,
        ];
    }

    private function guardarPromocionAppLocal(array $payload, string $motivo): array
    {
        try {
            $localId = $this->model->guardarPromocionAppLocal($payload);
            error_log('[promocionAppLocal] ' . $motivo . ' Guardada en BD local con id=' . $localId);

            return [
                'success' => true,
                'message' => 'Promocion guardada localmente para la app movil',
                'remote_id' => $localId,
                'source' => 'local',
            ];
        } catch (\Throwable $e) {
            error_log('[promocionAppLocal] No se pudo guardar promocion local: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'No se pudo guardar la promocion local: ' . $e->getMessage(),
                'remote_id' => null,
            ];
        }
    }

    private function esEndpointNoEncontrado(array $result): bool
    {
        $message = mb_strtolower((string)($result['error'] ?? ''));
        return (int)($result['httpCode'] ?? 0) === 404
            || str_contains($message, 'endpoint no encontrado')
            || str_contains($message, 'ruta no encontrada')
            || str_contains($message, 'not found');
    }

    private function buildLegacyPromocionesPayload(array $payload): array
    {
        return [
            'promociones' => [[
                'id' => (int)($payload['mobile_usuario_id'] ?? $payload['usuario_id'] ?? 0),
                'titulo' => (string)$payload['titulo'],
                'descripcion' => (string)$payload['descripcion'],
                'tipo' => (string)$payload['tipo'],
                'valor_descuento' => (float)$payload['valor_descuento'],
                'fecha_inicio' => (string)$payload['fecha_inicio'],
                'fecha_fin' => (string)$payload['fecha_fin'],
                'expires_at' => (string)$payload['expires_at'],
                'imagen' => (string)($payload['producto_imagen'] ?? ''),
                'code' => (string)$payload['code'],
                'producto_id' => isset($payload['producto_id']) ? (int)$payload['producto_id'] : null,
                'platillo_id' => isset($payload['platillo_id']) ? (int)$payload['platillo_id'] : null,
                'activo' => true,
                'usuario_id' => (int)$payload['usuario_id'],
                'mobile_usuario_id' => (int)($payload['mobile_usuario_id'] ?? $payload['usuario_id']),
                'comensales' => [],
            ]],
        ];
    }

    private function getAmareConfig(): ?array
    {
        $db = Database::getInstance();
        $stmt = $db->prepare(
            "SELECT clave, valor FROM global_settings WHERE clave IN ('amare_api_url','amare_api_token') AND grupo = 'pagos'"
        );
        $stmt->execute();
        $settings = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $settings[$row['clave']] = $row['valor'] ?? '';
        }

        $url = rtrim($settings['amare_api_url'] ?? '', '/');
        $token = $settings['amare_api_token'] ?? '';
        return $url && $token ? ['url' => $url, 'token' => $token] : null;
    }

    private function getAmareBranchIdByRestaurante(int $restauranteId): ?int
    {
        $db = Database::getInstance();
        $column = null;
        foreach (['sucursal_id', 'sucursal_carnihub_id'] as $candidate) {
            $stmt = $db->prepare(
                "SELECT 1
                 FROM information_schema.columns
                 WHERE table_schema = DATABASE()
                   AND table_name = 'rest_restaurantes'
                   AND column_name = ?
                 LIMIT 1"
            );
            $stmt->execute([$candidate]);
            if ($stmt->fetch()) {
                $column = $candidate;
                break;
            }
        }

        $branchSelect = $column ? "{$column} AS branch_ref" : "NULL AS branch_ref";
        $stmt = $db->prepare("SELECT id, {$branchSelect}, empresa_id FROM rest_restaurantes WHERE id = ? LIMIT 1");
        $stmt->execute([$restauranteId]);
        $restaurante = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$restaurante) {
            return null;
        }

        $value = $restaurante['branch_ref'] ?? null;
        if ($value) {
            return (int)$value;
        }

        $empresaId = (int)($restaurante['empresa_id'] ?? 0);
        if ($empresaId <= 0) {
            return null;
        }

        try {
            $stmt = $db->prepare("SHOW TABLES LIKE 'sucursales'");
            $stmt->execute();
            if ($stmt->fetch()) {
                $stmt = $db->prepare("SELECT id FROM sucursales WHERE empresa_id = ? AND activo = 1 ORDER BY id ASC LIMIT 1");
                $stmt->execute([$empresaId]);
                $fallback = $stmt->fetchColumn();
                if ($fallback) {
                    return (int)$fallback;
                }
            }
        } catch (\Throwable $e) {
            // Si no hay tabla de sucursales, se usa el restaurante local.
        }

        return (int)$restaurante['id'];
    }

    private function callAmareApi(string $method, string $endpoint, ?array $body = null): array
    {
        $config = $this->getAmareConfig();
        if (!$config) {
            return ['success' => false, 'httpCode' => 0, 'data' => null, 'error' => 'API Amare no configurada.'];
        }

        if (!function_exists('curl_init')) {
            return ['success' => false, 'httpCode' => 0, 'data' => null, 'error' => 'cURL no esta disponible en PHP.'];
        }

        $url = $config['url'] . '/' . ltrim($endpoint, '/');
        $ch = curl_init($url);
        $opts = [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 15,
            CURLOPT_CONNECTTIMEOUT => 5,
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'Authorization: Bearer ' . $config['token'],
                'Accept: application/json',
            ],
        ];

        if (in_array($method, ['POST', 'PUT'], true)) {
            $opts[CURLOPT_CUSTOMREQUEST] = $method;
            $opts[CURLOPT_POSTFIELDS] = json_encode($body ?? []);
        }

        curl_setopt_array($ch, $opts);
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        if ($error) {
            return ['success' => false, 'httpCode' => 0, 'data' => null, 'error' => $error];
        }

        $decoded = json_decode((string)$response, true);
        if (!is_array($decoded)) {
            return ['success' => false, 'httpCode' => $httpCode, 'data' => null, 'error' => 'Respuesta invalida de Amare-App.'];
        }

        if ($httpCode >= 200 && $httpCode < 300) {
            return ['success' => true, 'httpCode' => $httpCode, 'data' => $decoded, 'error' => null];
        }

        return [
            'success' => false,
            'httpCode' => $httpCode,
            'data' => $decoded,
            'error' => $decoded['error'] ?? $decoded['message'] ?? 'Error HTTP ' . $httpCode,
        ];
    }
}
