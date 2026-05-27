<?php
require_once ROOT_PATH . '/app/controllers/BaseController.php';

class RestInventarioController extends BaseController
{
    private RestInventarioModel $model;

    public function __construct()
    {
        parent::__construct();
        $this->requireRestaurante();
        $this->model = new RestInventarioModel();
    }

    public function index(?string $p = null): void
    {
        $restauranteId    = $this->restauranteId();
        $ingredientes     = $this->model->getByRestaurante($restauranteId, true);
        $alertas          = $this->model->alertasStockBajo($restauranteId);
        $flash            = $this->getFlash();

        // Recent movements (last 10)
        $movRecientes = [];
        try {
            $resultado = $this->model->getMovimientos($restauranteId, 1);
            $movRecientes = array_slice($resultado['movimientos'] ?? [], 0, 10);
        } catch (\Throwable $e) {}

        $pageTitle        = 'Ingredientes';
        $activeMenu       = 'rest_inventario';

        // Obtener la empresa proveedora vinculada al restaurante
        $empresaProveedorId   = null;
        $empresaProveedorNombre = null;
        try {
            $db   = Database::getInstance();
            $stmtRest = $db->prepare(
                "SELECT rr.empresa_proveedor_id, e.razon_social
                 FROM rest_restaurantes rr
                 LEFT JOIN empresas e ON e.id = rr.empresa_proveedor_id
                 WHERE rr.id = ?"
            );
            $stmtRest->execute([$restauranteId]);
            $restRow = $stmtRest->fetch(PDO::FETCH_ASSOC);
            if ($restRow) {
                $empresaProveedorId     = $restRow['empresa_proveedor_id'] ? (int)$restRow['empresa_proveedor_id'] : null;
                $empresaProveedorNombre = $restRow['razon_social'] ?? null;
            }
        } catch (\Throwable $e) {}

        // Productos CarniHub disponibles para vincular a ingredientes
        $productosCarnihub = [];
        $carnihubDebug     = [];   // Diagnóstico: ?debug_carnihub=1 lo muestra
        if (defined('RESTAURANTE_STANDALONE') && RESTAURANTE_STANDALONE) {
            // Standalone: obtener catálogo vía API de CarniHub.
            // Estrategia: primero intentamos paginación normal (page=1..N).
            // Si el servidor remoto ignora paginación (bug histórico) y devuelve
            // siempre los mismos productos, completamos con búsqueda por letra.
            try {
                $apiService  = new CarniHubApiService();
                $grupNombre  = $empresaProveedorNombre ?? 'CarniHub';
                $vistos      = [];
                $perPage     = 100;

                $extraerLote = function($result) {
                    if (!empty($result['productos']) && is_array($result['productos'])) return $result['productos'];
                    if (!empty($result['data']['productos']) && is_array($result['data']['productos'])) return $result['data']['productos'];
                    if (!empty($result['data']) && is_array($result['data']) && array_is_list($result['data'])) return $result['data'];
                    return [];
                };
                $acumular = function(array $lote) use (&$productosCarnihub, &$vistos, $grupNombre) {
                    $nuevos = 0;
                    foreach ($lote as $prod) {
                        $pid = (int)($prod['id'] ?? 0);
                        if ($pid <= 0 || isset($vistos[$pid])) continue;
                        $vistos[$pid] = true;
                        $productosCarnihub[] = [
                            'id'             => $pid,
                            'nombre'         => $prod['nombre'] ?? '',
                            'unidad'         => $prod['presentacion'] ?? '',
                            'empresa_nombre' => $grupNombre,
                        ];
                        $nuevos++;
                    }
                    return $nuevos;
                };

                // 1) Paginación normal: hasta 20 páginas o hasta que no haya nuevos
                for ($page = 1; $page <= 20; $page++) {
                    $result = $apiService->buscarProducto($restauranteId, '', '', $page, $perPage);
                    if (isset($result['success']) && $result['success'] === false) {
                        $carnihubDebug[] = ['page' => $page, 'api_error' => $result['error'] ?? 'unknown'];
                        error_log('[RestInventario CarniHub API] ' . ($result['error'] ?? 'sin detalle'));
                        break;
                    }
                    $lote   = $extraerLote($result);
                    $antes  = count($productosCarnihub);
                    $nuevos = $acumular($lote);
                    $carnihubDebug[] = [
                        'page' => $page, 'lote_count' => count($lote),
                        'nuevos' => $nuevos, 'acumulado' => count($productosCarnihub),
                    ];
                    if (empty($lote) || $nuevos === 0) break;
                }

                // 2) Fallback por letra si la paginación no entregó suficiente variedad
                if (count($productosCarnihub) < $perPage) {
                    $consultas = [
                        'a','b','c','d','e','f','g','h','i','j','k','l','m',
                        'n','o','p','q','r','s','t','u','v','w','x','y','z',
                        'á','é','í','ó','ú','ñ','0','1','2','3','4','5','6','7','8','9',
                    ];
                    foreach ($consultas as $q) {
                        $result = $apiService->buscarProducto($restauranteId, $q, '', 1, $perPage);
                        $lote   = $extraerLote($result);
                        $nuevos = $acumular($lote);
                        $carnihubDebug[] = [
                            'q' => $q, 'lote_count' => count($lote),
                            'nuevos' => $nuevos, 'acumulado' => count($productosCarnihub),
                        ];
                    }
                }

                $carnihubDebug[] = ['TOTAL_UNICOS' => count($productosCarnihub)];
                usort($productosCarnihub, function($a, $b){
                    return strcasecmp($a['nombre'] ?? '', $b['nombre'] ?? '');
                });
            } catch (\Throwable $e) {
                $carnihubDebug[] = ['exception' => $e->getMessage()];
                error_log('[RestInventario CarniHub] ' . $e->getMessage());
            }
        } else {
            // Instalación integrada: leer catálogo de la BD local.
            // IMPORTANTE: traemos SIEMPRE todos los productos activos (no
            // sólo los de la empresa proveedora asignada al restaurante),
            // para que la lista del modal y el match-exacto contemplen
            // cualquier producto disponible en CarniHub. Si hay empresa
            // preferida, la ordenamos primero.
            try {
                if (!isset($db)) $db = Database::getInstance();
                if ($empresaProveedorId) {
                    $stmt = $db->prepare(
                        "SELECT p.id, p.nombre, p.presentacion AS unidad, e.razon_social AS empresa_nombre
                         FROM productos p
                         LEFT JOIN empresas e ON e.id = p.empresa_id
                         WHERE p.activo = 1
                         ORDER BY (p.empresa_id = ?) DESC, p.nombre ASC, p.id ASC"
                    );
                    $stmt->execute([$empresaProveedorId]);
                } else {
                    $stmt = $db->query(
                        "SELECT p.id, p.nombre, p.presentacion AS unidad, e.razon_social AS empresa_nombre
                         FROM productos p
                         LEFT JOIN empresas e ON e.id = p.empresa_id
                         WHERE p.activo = 1
                         ORDER BY p.nombre ASC, p.id ASC"
                    );
                }
                $productosCarnihub = $stmt->fetchAll(PDO::FETCH_ASSOC);
            } catch (\Throwable $e) {}
        }

        $inactivos = $this->model->getInactivos($restauranteId);

        // Diagnóstico opcional: agregar ?debug_carnihub=1 a la URL
        if (!empty($_GET['debug_carnihub'])) {
            header('Content-Type: text/plain; charset=utf-8');
            echo "=== Diagnóstico CarniHub (modo " . ((defined('RESTAURANTE_STANDALONE') && RESTAURANTE_STANDALONE) ? 'STANDALONE' : 'INTEGRADO') . ") ===\n";
            echo "Productos cargados al modal: " . count($productosCarnihub) . "\n\n";
            echo "Páginas pedidas al API:\n";
            echo json_encode($carnihubDebug, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n";
            return;
        }

        $this->render('restaurante/inventario/index', compact(
            'ingredientes','alertas','productosCarnihub','empresaProveedorId','empresaProveedorNombre',
            'movRecientes','flash','pageTitle','activeMenu','inactivos'
        ));
    }

    public function guardar(?string $p = null): void
    {
        if (!$this->isPost()) $this->redirect('rest-inventario/index');
        $restauranteId = $this->restauranteId();

        $id              = (int)$this->post('id');
        $esCarnihub      = (int)(bool)$this->post('proveedor_carnihub', 0);
        $carnihubProdId  = $esCarnihub ? ((int)$this->post('carnihub_producto_id') ?: null) : null;

        // Si viene de CarniHub, resolver nombre y unidad
        if ($esCarnihub && $carnihubProdId) {
            if (!defined('RESTAURANTE_STANDALONE') || !RESTAURANTE_STANDALONE) {
                // Instalación B2B: la tabla 'productos' existe localmente
                $db   = Database::getInstance();
                $stmt = $db->prepare("SELECT nombre, presentacion FROM productos WHERE id = ? AND activo = 1");
                $stmt->execute([$carnihubProdId]);
                $prod   = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];
                $nombre = $prod['nombre'] ?? $this->post('nombre', '');
                $unidad = $prod['presentacion'] ?? $this->post('unidad_principal', 'kg');
            } else {
                // Standalone: nombre y unidad vienen del formulario (JS los llena al seleccionar)
                $nombre = trim($this->post('nombre', ''));
                $unidad = $this->post('unidad_principal', 'kg');
            }
        } else {
            $nombre = trim($this->post('nombre', ''));
            $unidad = $this->post('unidad_principal', 'kg');
        }

        $stockMinimo = $esCarnihub
            ? (float)$this->post('stock_minimo_ch', 0)
            : (float)$this->post('stock_minimo', 0);
        $stockInicial = $esCarnihub
            ? (float)$this->post('stock_inicial_ch', 0)
            : (float)$this->post('stock_inicial', 0);

        $data = [
            'restaurante_id'      => $restauranteId,
            'nombre'              => $nombre,
            'codigo'              => $this->post('codigo') ?: null,
            'tipo'                => $this->post('tipo') ?: null,
            'unidad_principal'    => $unidad,
            'costo_unitario'      => (float)$this->post('costo_unitario', 0),
            'stock_minimo'        => $stockMinimo,
            'categoria'           => $this->post('categoria') ?: null,
            'proveedor_carnihub'  => $esCarnihub,
            'carnihub_producto_id'=> $carnihubProdId,
            'proveedor_nombre'    => !$esCarnihub ? ($this->post('proveedor_nombre') ?: null) : null,
        ];

        if ($id) {
            $this->model->update($id, array_diff_key($data, ['restaurante_id' => '']));
        } else {
            $ingId = $this->model->insert($data);
            // Registrar stock inicial si > 0
            if ($stockInicial > 0) {
                $this->model->ajustarStock(
                    $ingId, $stockInicial, 'entrada',
                    'Stock inicial', null, $restauranteId, $this->usuarioId()
                );
            }
        }

        $this->flash('success', 'Ingrediente guardado.');
        $this->redirect('rest-inventario/index');
    }

    public function movimiento(?string $p = null): void
    {
        if (!$this->isPost()) $this->redirect('rest-inventario/index');

        $ingredienteId = (int)$this->post('ingrediente_id');
        $tipo          = $this->post('tipo', 'entrada');
        $cantidad      = abs((float)$this->post('cantidad', 0));
        $delta         = in_array($tipo, ['salida','merma']) ? -$cantidad : $cantidad;

        $this->model->ajustarStock(
            $ingredienteId,
            $delta,
            $tipo,
            $this->post('motivo', ''),
            null,
            $this->restauranteId(),
            $this->usuarioId()
        );

        $this->flash('success', 'Movimiento registrado.');
        $this->redirect('rest-inventario/index');
    }

    public function movimientos(?string $p = null): void
    {
        $restauranteId = $this->restauranteId();
        $page          = (int)($this->get('page', 1));
        $resultado     = $this->model->getMovimientos($restauranteId, $page);
        $flash         = $this->getFlash();
        $pageTitle     = 'Movimientos de Inventario';
        $activeMenu    = 'rest_inventario';
        $this->render('restaurante/inventario/movimientos', array_merge($resultado, compact('flash','pageTitle','activeMenu')));
    }

    public function eliminar(?string $id = null): void
    {
        $isAjax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) &&
            strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';

        $restauranteId = $this->restauranteId();
        $ing = $this->model->find((int)$id);

        if (!$ing || (int)($ing['restaurante_id'] ?? 0) !== $restauranteId) {
            if ($isAjax) $this->json(['ok' => false, 'error' => 'No autorizado']);
            $this->flash('error', 'Ingrediente no encontrado.');
            $this->redirect('rest-inventario/index');
            return;
        }

        $this->model->update((int)$id, ['activo' => 0]);

        if ($isAjax) $this->json(['ok' => true]);

        $this->flash('success', 'Ingrediente eliminado.');
        $this->redirect('rest-inventario/index');
    }

    public function reactivar(?string $id = null): void
    {
        $restauranteId = $this->restauranteId();
        $ing = $this->model->find((int)$id);
        if (!$ing || (int)($ing['restaurante_id'] ?? 0) !== $restauranteId) {
            $this->redirect('rest-inventario/index');
        }
        $this->model->update((int)$id, ['activo' => 1]);
        $this->flash('success', 'Ingrediente restaurado al inventario.');
        $this->redirect('rest-inventario/index');
    }

    /** Endpoint JSON â€” devuelve stocks actuales para polling en tiempo real */
    public function stocks(?string $p = null): void
    {
        $rows = $this->model->getByRestaurante($this->restauranteId(), true);
        $this->json(array_map(fn($r) => [
            'id'               => (int)$r['id'],
            'stock'            => (float)$r['stock'],
            'stock_minimo'     => (float)$r['stock_minimo'],
            'unidad_principal' => $r['unidad_principal'],
        ], $rows));
    }

    // â”€â”€ SISTEMA DE FORECAST Y PEDIDOS AUTOMÃTICOS â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€

    /**
     * Dashboard de proyecciÃ³n inteligente de inventario.
     */
    public function proyecciones(?string $p = null): void
    {
        require_once ROOT_PATH . '/app/services/RestForecastService.php';

        $restauranteId = $this->restauranteId();
        $ingredientes  = $this->model->getByRestaurante($restauranteId, true);

        $forecast     = new RestForecastService();
        $analisis     = $forecast->analizarIngredientes($ingredientes, $restauranteId);
        $criticos     = array_filter($analisis, fn($i) => $i['nivel_alerta'] === 'critico');
        $advertencias = array_filter($analisis, fn($i) => $i['nivel_alerta'] === 'advertencia');

        // â”€â”€ AUTO-GENERAR PEDIDO â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
        $comprobante    = null;
        $ultimoPedidoAt = null;
        $forzar         = (bool)$this->get('forzar', 0);
        $db = \Database::getInstance();

        // ¿Ya se generó un pedido sugerido en las últimas 12 horas?
        $stCheck = $db->prepare(
            "SELECT MAX(created_at) FROM rest_pedidos_sugeridos
             WHERE restaurante_id = ? AND created_at >= DATE_SUB(NOW(), INTERVAL 12 HOUR)"
        );
        $stCheck->execute([$restauranteId]);
        $ultimoPedidoAt = $stCheck->fetchColumn() ?: null;

        if (!$ultimoPedidoAt || $forzar) {
            $grupos = $forecast->agruparPorEmpresa($analisis);
            if (!empty($grupos)) {
                $comprobante = $this->_autoGenerarPedidos($restauranteId, $grupos);
                if (!empty($comprobante)) $ultimoPedidoAt = date('Y-m-d H:i:s');
            }
        }

        $flash      = $this->getFlash();
        $pageTitle  = 'ProyecciÃ³n de Inventario';
        $activeMenu = 'rest_inventario';

        $this->render('restaurante/inventario/proyecciones', compact(
            'analisis', 'criticos', 'advertencias', 'comprobante', 'ultimoPedidoAt',
            'flash', 'pageTitle', 'activeMenu'
        ));
    }

    /**
     * Historial de pedidos generados automÃ¡ticamente por el sistema de forecast.
     */
    public function pedidosSugeridos(?string $p = null): void
    {
        require_once ROOT_PATH . '/app/models/RestPedidoSugeridoModel.php';
        $restauranteId = $this->restauranteId();
        $pedidoModel   = new RestPedidoSugeridoModel();
        $pedidos       = $pedidoModel->getByRestaurante($restauranteId);

        $flash      = $this->getFlash();
        $pageTitle  = 'Historial de Pedidos AutomÃ¡ticos';
        $activeMenu = 'rest_inventario';

        $this->render('restaurante/inventario/pedidos_sugeridos', compact(
            'pedidos', 'flash', 'pageTitle', 'activeMenu'
        ));
    }

    /**
     * Genera pedidos forzados vÃ­a AJAX (sin cooldown). Responde JSON.
     */
    public function generarPedidoAutomatico(?string $p = null): void
    {
        if (!$this->isPost()) {
            $this->json(['ok' => false, 'error' => 'MÃ©todo no permitido'], 405);
        }

        require_once ROOT_PATH . '/app/services/RestForecastService.php';

        $restauranteId = $this->restauranteId();
        $ingredientes  = $this->model->getByRestaurante($restauranteId, true);

        $forecast = new RestForecastService();
        $analisis = $forecast->analizarIngredientes($ingredientes, $restauranteId);
        $grupos   = $forecast->agruparPorEmpresa($analisis);

        if (empty($grupos)) {
            $this->json(['ok' => false, 'error' => 'No hay ingredientes crÃ­ticos con proveedor CarniHub vinculado.']);
        }

        $creados = $this->_autoGenerarPedidos($restauranteId, $grupos);

        if (empty($creados)) {
            $this->json(['ok' => false, 'error' => 'No se pudieron crear los pedidos. Verifica los ingredientes vinculados.']);
        }

        $this->json(['ok' => true, 'pedidos' => $creados]);
    }

    /**
     * Crea pedidos reales en CarniHub para los grupos de empresa dados.
     * Reutilizable desde proyecciones() (auto) y generarPedidoAutomatico() (manual).
     *
     * @param int   $restauranteId
     * @param array $grupos  resultado de RestForecastService::agruparPorEmpresa()
     * @return array pedidos creados [{pedido_id, folio, empresa, total, items}]
     */
    private function _autoGenerarPedidos(int $restauranteId, array $grupos): array
    {
        require_once ROOT_PATH . '/app/models/RestPedidoSugeridoModel.php';
        $pedidoModel = new RestPedidoSugeridoModel();
        $compradorId = $this->usuarioId();
        $creados     = [];

        foreach ($grupos as $empresaId => $grupo) {
            $items    = [];
            $subtotal = 0.0;

            foreach ($grupo['items'] as $ing) {
                $precio = (float)($ing['empresa']['precio_base'] ?? $ing['costo_unitario'] ?? 0);
                $cant   = (float)$ing['cantidad_sugerida'];
                $sub    = round($cant * $precio, 2);
                $subtotal += $sub;
                $items[] = [
                    'ingrediente_id'       => (int)$ing['id'],
                    'carnihub_producto_id' => (int)$ing['carnihub_producto_id'],
                    'cantidad_sugerida'    => $cant,
                    'unidad'               => $ing['unidad_principal'],
                    'precio_unit_estimado' => $precio,
                    'subtotal_estimado'    => $sub,
                ];
            }

            if (empty($items)) continue;

            try {
                $pedidoId = $pedidoModel->crear([
                    'restaurante_id'      => $restauranteId,
                    'carnihub_empresa_id' => $empresaId,
                    'notas'               => 'Pedido automático · Forecast · ' . date('d/m/Y H:i'),
                    'usuario_id'          => $compradorId,
                ], $items);

                $creados[] = [
                    'pedido_sugerido_id' => $pedidoId,
                    'empresa'            => $grupo['empresa']['razon_social'] ?? 'CarniHub',
                    'total'              => $subtotal,
                    'items_count'        => count($items),
                ];
            } catch (\Throwable $e) {
                error_log('[_autoGenerarPedidos] Error: ' . $e->getMessage());
            }
        }

        return $creados;
    }

    /**
     * Endpoint JSON: retorna anÃ¡lisis de forecast para un ingrediente especÃ­fico.
     */
    public function forecastJson(?string $id = null): void
    {
        require_once ROOT_PATH . '/app/services/RestForecastService.php';

        $ingredienteId = (int)$id;
        $restauranteId = $this->restauranteId();

        $ing = $this->model->find($ingredienteId);
        if (!$ing || (int)$ing['restaurante_id'] !== $restauranteId) {
            $this->json(['ok' => false], 404);
        }

        $forecast      = new RestForecastService();
        $cpd           = $forecast->calcularConsumoPromedioDiario($ingredienteId, $restauranteId, 7);
        $movil         = $forecast->calcularPromedioMovil($ingredienteId, $restauranteId, 3);
        $diasRestantes = $forecast->calcularDiasRestantes((float)$ing['stock'], $cpd);
        $proyeccion    = $forecast->proyeccionSemanal($ingredienteId, $restauranteId, (float)$ing['stock'], 7);

        $this->json([
            'ok'             => true,
            'cpd'            => round($cpd, 4),
            'promedio_movil' => $movil['promedio'],
            'dias_restantes' => $diasRestantes === INF ? null : round($diasRestantes, 1),
            'proyeccion_7d'  => $proyeccion,
            'dias_consumo'   => $movil['dias'],
        ]);
    }

    // ── APROBACIÓN Y ENVÍO A CARNIHUB ─────────────────────────────

    /**
     * POST /rest-inventario/aprobarSugerido/{id}
     * Cambia estado de 'sugerido' → 'aprobado'.
     */
    public function aprobarSugerido(?string $id = null): void
    {
        if (!$this->isPost()) {
            $this->json(['ok' => false, 'error' => 'Método no permitido'], 405);
        }

        require_once ROOT_PATH . '/app/models/RestPedidoSugeridoModel.php';
        $pedidoModel   = new RestPedidoSugeridoModel();
        $pedidoId      = (int)$id;
        $restauranteId = $this->restauranteId();

        $pedido = $pedidoModel->find($pedidoId);
        if (!$pedido || (int)$pedido['restaurante_id'] !== $restauranteId) {
            $this->json(['ok' => false, 'error' => 'Pedido no encontrado'], 404);
        }
        if ($pedido['estado'] !== 'sugerido') {
            $this->json(['ok' => false, 'error' => 'El pedido no está en estado sugerido (estado actual: ' . $pedido['estado'] . ')']);
        }

        $pedidoModel->cambiarEstado($pedidoId, 'aprobado', $this->usuarioId());
        $this->json(['ok' => true, 'message' => 'Pedido aprobado. Ya puedes enviarlo a CarniHub.']);
    }

    /**
     * POST /rest-inventario/enviarACarnihub/{id}
     * Envía el pedido aprobado a la API remota de CarniHub y lo marca como 'convertido'.
     */
    public function enviarACarnihub(?string $id = null): void
    {
        if (!$this->isPost()) {
            $this->json(['ok' => false, 'error' => 'Método no permitido'], 405);
        }

        require_once ROOT_PATH . '/app/models/RestPedidoSugeridoModel.php';
        $pedidoModel   = new RestPedidoSugeridoModel();
        $pedidoId      = (int)$id;
        $restauranteId = $this->restauranteId();

        $pedido = $pedidoModel->findConItems($pedidoId);
        if (!$pedido || (int)$pedido['restaurante_id'] !== $restauranteId) {
            $this->json(['ok' => false, 'error' => 'Pedido no encontrado'], 404);
        }
        if ($pedido['estado'] !== 'aprobado') {
            $this->json(['ok' => false, 'error' => 'El pedido debe estar aprobado antes de enviarlo (estado actual: ' . $pedido['estado'] . ')']);
        }

        // Mapear items al formato de CarniHubApiService
        $apiItems = [];
        foreach ($pedido['items'] as $item) {
            $cant   = (float)($item['cantidad_aprobada'] ?? $item['cantidad_sugerida']);
            $precio = (float)$item['precio_unit_estimado'];
            $prodId = (int)($item['carnihub_producto_id'] ?? 0);
            if ($prodId <= 0 || $cant <= 0 || $precio <= 0) continue;
            $apiItems[] = [
                'producto_id' => $prodId,
                'cantidad'    => $cant,
                'precio_unit' => $precio,
            ];
        }

        if (empty($apiItems)) {
            $this->json(['ok' => false, 'error' => 'Ningún item tiene producto CarniHub vinculado con precio y cantidad válidos']);
        }

        $apiService = new CarniHubApiService();
        $resultado  = $apiService->crearPedido(
            $restauranteId,
            $apiItems,
            $pedido['notas'] ?? 'Pedido generado automáticamente por sistema de forecast'
        );

        if (!($resultado['success'] ?? false)) {
            error_log('[enviarACarnihub] Error API: ' . ($resultado['error'] ?? 'desconocido'));
            $this->json(['ok' => false, 'error' => $resultado['error'] ?? 'Error al comunicarse con CarniHub']);
        }

        $pedidoExternoId = (int)($resultado['pedido_id'] ?? $resultado['id'] ?? 0);
        $pedidoModel->marcarConvertido($pedidoId, $pedidoExternoId);

        $this->json([
            'ok'                 => true,
            'message'            => 'Pedido enviado a CarniHub correctamente.',
            'pedido_carnihub_id' => $pedidoExternoId,
            'folio'              => $resultado['folio'] ?? null,
        ]);
    }
}
