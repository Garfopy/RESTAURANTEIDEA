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
        if (defined('RESTAURANTE_STANDALONE') && RESTAURANTE_STANDALONE) {
            // Standalone: obtener catálogo vía API de CarniHub
            try {
                $apiService = new CarniHubApiService();
                $result     = $apiService->buscarProducto($restauranteId, '', '', 1, 5000);
                if ($result['success'] && !empty($result['data']['productos'])) {
                    $grupNombre = $empresaProveedorNombre ?? 'CarniHub';
                    foreach ($result['data']['productos'] as $prod) {
                        $productosCarnihub[] = [
                            'id'             => $prod['id'],
                            'nombre'         => $prod['nombre'],
                            'unidad'         => $prod['presentacion'] ?? '',
                            'empresa_nombre' => $grupNombre,
                        ];
                    }
                }
            } catch (\Throwable $e) {}
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

        // Si viene de CarniHub, usar datos del producto
        if ($esCarnihub && $carnihubProdId) {
            $db   = Database::getInstance();
            $stmt = $db->prepare("SELECT * FROM productos WHERE id = ?");
            $stmt->execute([$carnihubProdId]);
            $prod   = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];
            $nombre = $prod['nombre'] ?? $this->post('nombre', '');
            $unidad = $prod['presentacion'] ?? 'kg';
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

        // Â¿Ya se generÃ³ un pedido automÃ¡tico en las Ãºltimas 12 horas?
        $stCheck = $db->prepare(
            "SELECT MAX(created_at) FROM pedidos
             WHERE notas LIKE ? AND created_at >= DATE_SUB(NOW(), INTERVAL 12 HOUR)"
        );
        $stCheck->execute(['%Restaurante ID: ' . $restauranteId . '%']);
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
        $restauranteId = $this->restauranteId();
        $db   = \Database::getInstance();
        $stmt = $db->prepare(
            "SELECT p.id, p.folio, p.total, p.estado, p.created_at, p.notas,
                    e.razon_social AS empresa_nombre
             FROM pedidos p
             LEFT JOIN empresas e ON e.id = p.empresa_id
             WHERE p.notas LIKE ?
             ORDER BY p.created_at DESC
             LIMIT 60"
        );
        $stmt->execute(['%Restaurante ID: ' . $restauranteId . '%']);
        $pedidos = $stmt->fetchAll(\PDO::FETCH_ASSOC);

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
        $db          = \Database::getInstance();
        $anio        = date('Y');
        $compradorId = $this->usuarioId();
        $creados     = [];

        foreach ($grupos as $empresaId => $grupo) {
            $lineas   = [];
            $subtotal = 0.0;

            foreach ($grupo['items'] as $ing) {
                $precio   = (float)($ing['empresa']['precio_base'] ?? $ing['costo_unitario'] ?? 0);
                $cant     = (float)$ing['cantidad_sugerida'];
                $sub      = round($cant * $precio, 2);
                $subtotal += $sub;
                $lineas[] = [
                    'nombre'      => $ing['nombre'],
                    'producto_id' => (int)$ing['carnihub_producto_id'],
                    'cantidad'    => $cant,
                    'unidad'      => $ing['unidad_principal'],
                    'precio_unit' => $precio,
                    'subtotal'    => $sub,
                ];
            }

            $db->beginTransaction();
            try {
                $st = $db->prepare(
                    "SELECT MAX(CAST(SUBSTRING_INDEX(folio,'-',-1) AS UNSIGNED)) AS ul
                     FROM pedidos WHERE folio LIKE ?"
                );
                $st->execute(["CHB-{$anio}-%"]);
                $num   = (int)($st->fetch(\PDO::FETCH_ASSOC)['ul'] ?? 0) + 1;
                $folio = sprintf('CHB-%s-%04d', $anio, $num);

                $db->prepare(
                    "INSERT INTO pedidos (folio, empresa_id, usuario_id, fecha_pedido, total, estado, notas)
                     VALUES (?, ?, ?, NOW(), ?, 'pendiente', ?)"
                )->execute([
                    $folio,
                    $empresaId,
                    $compradorId,
                    $subtotal,
                    'Pedido automÃ¡tico Â· Forecast de inventario Â· Restaurante ID: ' . $restauranteId . ' Â· ' . date('d/m/Y H:i'),
                ]);
                $pedidoId = (int)$db->lastInsertId();

                $stLinea = $db->prepare(
                    "INSERT INTO pedido_detalle (pedido_id, producto_id, cantidad, precio_unitario, subtotal)
                     VALUES (?, ?, ?, ?, ?)"
                );
                foreach ($lineas as $l) {
                    $stLinea->execute([$pedidoId, $l['producto_id'], $l['cantidad'], $l['precio_unit'], $l['subtotal']]);
                }

                $db->commit();

                $creados[] = [
                    'pedido_id' => $pedidoId,
                    'folio'     => $folio,
                    'empresa'   => $grupo['empresa']['razon_social'],
                    'total'     => $subtotal,
                    'items'     => array_map(fn($l) => [
                        'nombre'   => $l['nombre'],
                        'cantidad' => $l['cantidad'],
                        'unidad'   => $l['unidad'],
                        'precio'   => $l['precio_unit'],
                        'subtotal' => $l['subtotal'],
                    ], $lineas),
                ];
            } catch (\Throwable $e) {
                $db->rollBack();
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
}
