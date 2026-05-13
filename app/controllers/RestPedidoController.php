<?php
require_once ROOT_PATH . '/app/controllers/BaseController.php';

class RestPedidoController extends BaseController
{
    private RestPedidoModel $model;

    public function __construct()
    {
        parent::__construct();
        $this->requireMesero();
        $this->model = new RestPedidoModel();
    }

    public function index(?string $p = null): void
    {
        $restauranteId = $this->restauranteId();
        $estado        = $this->get('estado', '');
        $page          = (int)$this->get('page', 1);
        $resultado     = $this->model->listar($restauranteId, $page, $estado);
        $flash         = $this->getFlash();
        $pageTitle     = 'Pedidos';
        $activeMenu    = 'rest_pedidos';
        $this->render('restaurante/pedidos/index', array_merge($resultado, compact('flash','pageTitle','activeMenu','estado')));
    }

    public function detalle(?string $id = null): void
    {
        $pedido    = $this->model->getConItems((int)$id);
        if (!$pedido) { $this->flash('error', 'Pedido no encontrado.'); $this->redirect('rest-pedido/index'); }
        $flash     = $this->getFlash();
        $pageTitle = 'Pedido ' . $pedido['folio'];
        $activeMenu = 'rest_pedidos';
        $this->render('restaurante/pedidos/detalle', compact('pedido','flash','pageTitle','activeMenu'));
    }

    public function nuevo(?string $mesaId = null): void
    {
        $restauranteId = $this->restauranteId();
        $mesa     = $mesaId ? (new RestMesaModel())->find((int)$mesaId) : null;
        $menu     = (new RestMenuModel())->getPlatillosDisponibles($restauranteId);
        $flash    = $this->getFlash();
        $pageTitle = 'Nuevo Pedido';
        $activeMenu = 'rest_pedidos';
        $this->render('restaurante/pedidos/nuevo', compact('mesa','menu','flash','pageTitle','activeMenu'));
    }

    public function crear(?string $p = null): void
    {
        if (!$this->isPost()) $this->redirect('rest-pedido/index');

        $restauranteId = $this->restauranteId();
        $platillosIds  = $this->post('platillo_id', []);
        $cantidades    = $this->post('cantidad', []);
        $notas         = $this->post('notas_item', []);

        $menuModel = new RestMenuModel();
        $items = [];
        foreach ($platillosIds as $k => $platilloId) {
            if (!$platilloId || empty($cantidades[$k])) continue;
            $platillo = $menuModel->find((int)$platilloId);
            if (!$platillo) continue;
            $cant     = max(1, (int)$cantidades[$k]);
            $items[]  = [
                'platillo_id' => (int)$platilloId,
                'cantidad'    => $cant,
                'precio_unit' => (float)$platillo['precio'],
                'subtotal'    => (float)$platillo['precio'] * $cant,
                'notas'       => $notas[$k] ?? null,
            ];
        }

        if (empty($items)) {
            $this->flash('error', 'El pedido no tiene ítems.');
            $this->redirect('rest-pedido/nuevo/' . ($this->post('mesa_id') ?? ''));
        }

        $pedidoId = $this->model->crear([
            'restaurante_id' => $restauranteId,
            'mesa_id'        => $this->post('mesa_id') ?: null,
            'visita_id'      => $this->post('visita_id') ?: null,
            'mesero_id'      => $this->usuarioId(),
            'notas'          => $this->post('notas'),
        ], $items);

        $this->flash('success', 'Pedido creado.');
        $this->redirect('rest-pedido/detalle/' . $pedidoId);
    }

    public function cambiarEstado(?string $id = null): void
    {
        $estado = $this->post('estado') ?? $this->get('estado');
        $this->model->cambiarEstadoPedido((int)$id, $estado);
        $this->json(['ok' => true]);
    }

    public function cambiarEstadoItem(?string $id = null): void
    {
        $estado = $this->post('estado') ?? $this->get('estado');
        $this->model->cambiarEstadoItem((int)$id, $estado);
        $this->json(['ok' => true]);
    }

    public function cancelar(?string $id = null): void
    {
        $this->model->cambiarEstadoPedido((int)$id, 'cancelado');
        $this->flash('success', 'Pedido cancelado.');
        $this->redirect('rest-pedido/index');
    }
}
