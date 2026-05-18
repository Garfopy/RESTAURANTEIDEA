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
        $ingredientes     = $this->model->getByRestaurante($restauranteId);
        $alertas          = $this->model->alertasStockBajo($restauranteId);
        $flash            = $this->getFlash();

        // Recent movements (last 10)
        $movRecientes = [];
        try {
            $resultado = $this->model->getMovimientos($restauranteId, 1);
            $movRecientes = array_slice($resultado['movimientos'] ?? [], 0, 10);
        } catch (\Throwable $e) {}

        $pageTitle        = 'Inventario';
        $activeMenu       = 'rest_inventario';

        // Productos CarniHub disponibles para importar al inventario
        $productosCarnihub = [];
        try {
            $restaurante = (new RestauranteModel())->find($restauranteId);
            $empresaId   = $restaurante['empresa_id'] ?? 0;
            if ($empresaId) {
                $db   = Database::getInstance();
                $stmt = $db->prepare(
                    "SELECT DISTINCT p.id, p.nombre, p.unidad
                     FROM productos p
                     JOIN pedido_items pi ON pi.producto_id = p.id
                     JOIN pedidos ped ON ped.id = pi.pedido_id
                     WHERE ped.empresa_id = ? AND p.activo = 1
                     ORDER BY p.nombre
                     LIMIT 100"
                );
                $stmt->execute([$empresaId]);
                $productosCarnihub = $stmt->fetchAll(PDO::FETCH_ASSOC);
            }
        } catch (\Throwable $e) {}

        $this->render('restaurante/inventario/index', compact(
            'ingredientes','alertas','productosCarnihub','movRecientes','flash','pageTitle','activeMenu'
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
            $unidad = $prod['unidad'] ?? 'kg';
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
        $this->model->update((int)$id, ['activo' => 0]);
        $this->flash('success', 'Ingrediente desactivado.');
        $this->redirect('rest-inventario/index');
    }

    /** Endpoint JSON — devuelve stocks actuales para polling en tiempo real */
    public function stocks(?string $p = null): void
    {
        $rows = $this->model->getByRestaurante($this->restauranteId());
        $this->json(array_map(fn($r) => [
            'id'               => (int)$r['id'],
            'stock'            => (float)$r['stock'],
            'stock_minimo'     => (float)$r['stock_minimo'],
            'unidad_principal' => $r['unidad_principal'],
        ], $rows));
    }

    // ── SISTEMA DE FORECAST Y PEDIDOS SUGERIDOS ───────────────────

    /**
     * Dashboard de proyección inteligente de inventario.
     * Carga todos los ingredientes y calcula el forecast para cada uno.
     */
    public function proyecciones(?string $p = null): void
    {
        require_once ROOT_PATH . '/app/services/RestForecastService.php';
        require_once ROOT_PATH . '/app/models/RestPedidoSugeridoModel.php';

        $restauranteId = $this->restauranteId();
        $ingredientes  = $this->model->getByRestaurante($restauranteId, true);

        $forecast  = new RestForecastService();
        $analisis  = $forecast->analizarIngredientes($ingredientes, $restauranteId);

        $sugeridoModel  = new RestPedidoSugeridoModel();
        $pedidosPendientes = $sugeridoModel->countPendientes($restauranteId);

        $criticos    = array_filter($analisis, fn($i) => $i['nivel_alerta'] === 'critico');
        $advertencias = array_filter($analisis, fn($i) => $i['nivel_alerta'] === 'advertencia');

        $flash      = $this->getFlash();
        $pageTitle  = 'Proyección de Inventario';
        $activeMenu = 'rest_inventario';

        $this->render('restaurante/inventario/proyecciones', compact(
            'analisis', 'criticos', 'advertencias',
            'pedidosPendientes', 'flash', 'pageTitle', 'activeMenu'
        ));
    }

    /**
     * Lista de pedidos sugeridos (órdenes de compra inteligentes).
     */
    public function pedidosSugeridos(?string $p = null): void
    {
        require_once ROOT_PATH . '/app/models/RestPedidoSugeridoModel.php';

        $restauranteId = $this->restauranteId();
        $sugeridoModel = new RestPedidoSugeridoModel();
        $estado        = $this->get('estado', '');

        $pedidos    = $sugeridoModel->getByRestaurante($restauranteId, $estado);
        $flash      = $this->getFlash();
        $pageTitle  = 'Pedidos Sugeridos';
        $activeMenu = 'rest_inventario';

        $this->render('restaurante/inventario/pedidos_sugeridos', compact(
            'pedidos', 'estado', 'flash', 'pageTitle', 'activeMenu'
        ));
    }

    /**
     * Genera pedidos sugeridos para todos los ingredientes críticos que
     * tienen proveedor CarniHub. Crea un pedido sugerido por empresa.
     * Responde JSON.
     */
    public function generarPedidoSugerido(?string $p = null): void
    {
        if (!$this->isPost()) {
            $this->json(['ok' => false, 'error' => 'Método no permitido'], 405);
        }

        require_once ROOT_PATH . '/app/services/RestForecastService.php';
        require_once ROOT_PATH . '/app/models/RestPedidoSugeridoModel.php';

        $restauranteId = $this->restauranteId();
        $ingredientes  = $this->model->getByRestaurante($restauranteId, true);

        $forecast  = new RestForecastService();
        $analisis  = $forecast->analizarIngredientes($ingredientes, $restauranteId);
        $grupos    = $forecast->agruparPorEmpresa($analisis);

        if (empty($grupos)) {
            $this->json(['ok' => false, 'error' => 'No hay ingredientes críticos con proveedor CarniHub vinculado.']);
        }

        $sugeridoModel = new RestPedidoSugeridoModel();
        $creados       = [];

        foreach ($grupos as $empresaId => $grupo) {
            $items = [];
            foreach ($grupo['items'] as $ing) {
                $precio = (float)($ing['empresa']['precio_base'] ?? $ing['costo_unitario'] ?? 0);
                $cant   = (float)$ing['cantidad_sugerida'];
                $items[] = [
                    'ingrediente_id'       => (int)$ing['id'],
                    'carnihub_producto_id' => (int)$ing['carnihub_producto_id'],
                    'cantidad_sugerida'    => $cant,
                    'unidad'              => $ing['unidad_principal'],
                    'precio_unit_estimado' => $precio,
                    'subtotal_estimado'   => round($cant * $precio, 2),
                ];
            }

            try {
                $id = $sugeridoModel->crear([
                    'restaurante_id' => $restauranteId,
                    'empresa_id'     => $empresaId,
                    'usuario_id'     => $this->usuarioId(),
                    'notas'          => 'Generado automáticamente por sistema de forecast ' . date('d/m/Y H:i'),
                ], $items);
                $creados[] = ['id' => $id, 'empresa' => $grupo['empresa']['razon_social']];
            } catch (\Throwable $e) {
                // Continuar con otras empresas si una falla
            }
        }

        if (empty($creados)) {
            $this->json(['ok' => false, 'error' => 'No se pudieron crear los pedidos. Verifica los ingredientes.']);
        }

        $this->json(['ok' => true, 'creados' => $creados]);
    }

    /**
     * Aprueba un pedido sugerido y lo convierte en pedido real de CarniHub.
     * POST: pedido_id, cantidades[] (opcional, para ajustar)
     */
    public function aprobarPedidoSugerido(?string $id = null): void
    {
        if (!$this->isPost()) $this->redirect('rest-inventario/pedidosSugeridos');

        require_once ROOT_PATH . '/app/models/RestPedidoSugeridoModel.php';

        $pedidoId      = (int)($id ?? $this->post('pedido_id'));
        $sugeridoModel = new RestPedidoSugeridoModel();
        $pedido        = $sugeridoModel->find($pedidoId);

        if (!$pedido || (int)$pedido['restaurante_id'] !== $this->restauranteId()) {
            $this->flash('error', 'Pedido no encontrado.');
            $this->redirect('rest-inventario/pedidosSugeridos');
        }

        // Actualizar cantidades aprobadas si se enviaron
        $cantidades = (array)$this->post('cantidades', []);
        if (!empty($cantidades)) {
            $sugeridoModel->actualizarCantidades($pedidoId, $cantidades);
        }

        // Marcar como aprobado
        $sugeridoModel->cambiarEstado($pedidoId, 'aprobado', $this->usuarioId());

        try {
            $carnihubId = $sugeridoModel->convertirACarnihub($pedidoId, $this->usuarioId());
            $this->flash('success', "Pedido aprobado y enviado a CarniHub (folio interno #$carnihubId). La empresa recibirá la orden.");
        } catch (\Throwable $e) {
            $this->flash('error', 'El pedido fue aprobado pero no se pudo crear en CarniHub: ' . $e->getMessage());
        }

        $this->redirect('rest-inventario/pedidosSugeridos');
    }

    /**
     * Rechaza un pedido sugerido.
     */
    public function rechazarPedidoSugerido(?string $id = null): void
    {
        if (!$this->isPost()) $this->redirect('rest-inventario/pedidosSugeridos');

        require_once ROOT_PATH . '/app/models/RestPedidoSugeridoModel.php';

        $pedidoId      = (int)($id ?? $this->post('pedido_id'));
        $sugeridoModel = new RestPedidoSugeridoModel();
        $pedido        = $sugeridoModel->find($pedidoId);

        if (!$pedido || (int)$pedido['restaurante_id'] !== $this->restauranteId()) {
            $this->flash('error', 'Pedido no encontrado.');
            $this->redirect('rest-inventario/pedidosSugeridos');
        }

        $sugeridoModel->cambiarEstado($pedidoId, 'rechazado', $this->usuarioId());
        $this->flash('success', 'Pedido sugerido rechazado.');
        $this->redirect('rest-inventario/pedidosSugeridos');
    }

    /**
     * Endpoint JSON: retorna análisis de forecast para un ingrediente específico.
     * Usado por la vista de proyecciones para el mini-chart al hacer hover/clic.
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

    /**
     * Endpoint JSON: retorna los items de un pedido sugerido (para modal en vista).
     */
    public function pedidoSugeridoItems(?string $id = null): void
    {
        require_once ROOT_PATH . '/app/models/RestPedidoSugeridoModel.php';

        $pedidoId      = (int)$id;
        $sugeridoModel = new RestPedidoSugeridoModel();
        $pedido        = $sugeridoModel->find($pedidoId);

        if (!$pedido || (int)$pedido['restaurante_id'] !== $this->restauranteId()) {
            $this->json(['ok' => false, 'error' => 'No encontrado'], 404);
        }

        $items   = $sugeridoModel->getItems($pedidoId);
        $db      = \Database::getInstance();
        $stmt    = $db->prepare('SELECT razon_social FROM empresas WHERE id = ?');
        $stmt->execute([(int)$pedido['empresa_id']]);
        $emp     = $stmt->fetch(\PDO::FETCH_ASSOC);

        $this->json([
            'ok'      => true,
            'items'   => $items,
            'total'   => $pedido['total_estimado'],
            'empresa' => $emp ? $emp['razon_social'] : '',
        ]);
    }
}
