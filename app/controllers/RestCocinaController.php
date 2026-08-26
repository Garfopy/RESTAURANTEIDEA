<?php
require_once ROOT_PATH . '/app/controllers/BaseController.php';

class RestCocinaController extends BaseController
{
    private RestPedidoModel $model;

    public function __construct()
    {
        parent::__construct();
        $this->requireCocina();
        $this->model = new RestPedidoModel();
    }

    public function index(?string $p = null): void
    {
        $restauranteId = $this->restauranteId();
        $restaurante   = (new RestauranteModel())->find($restauranteId);
        $pedidos       = $this->model->getColaCocina($restauranteId);
        $flash         = $this->getFlash();
        $pageTitle     = 'Cocina';
        $this->render('cocina/index', compact('pedidos', 'restaurante', 'flash', 'pageTitle'));
    }

    /** POST — avanza el estado de un item: pendiente -> en_preparacion -> listo. Responde JSON (fetch). */
    public function avanzarItem(?string $id = null): void
    {
        $itemId = (int)$id;
        $siguiente = [
            'pendiente'      => 'en_preparacion',
            'en_preparacion' => 'listo',
        ];
        $pedidoId = $this->model->getPedidoIdPorItem($itemId);
        if (!$pedidoId) {
            $this->json(['ok' => false, 'msg' => 'Item no encontrado'], 404);
        }

        $estadoActual = null;
        foreach ($this->model->getColaCocina($this->restauranteId()) as $ped) {
            if ((int)$ped['id'] !== $pedidoId) continue;
            foreach ($ped['items'] as $it) {
                if ((int)$it['id'] === $itemId) { $estadoActual = $it['estado']; break 2; }
            }
        }

        $nuevoEstado = $siguiente[$estadoActual] ?? null;
        if (!$nuevoEstado) {
            $this->json(['ok' => false, 'msg' => 'El item ya no se puede avanzar desde aquí.'], 400);
        }

        $this->model->cambiarEstadoItem($itemId, $nuevoEstado);

        // Si todos los items del pedido ya están 'listo' (o cancelados), el pedido pasa a 'listo'.
        $pedidoConItems = $this->model->getConItemsSinMesas($pedidoId, $this->restauranteId());
        $pendientes = array_filter(
            $pedidoConItems['items'] ?? [],
            fn($it) => !in_array($it['estado'], ['listo', 'cancelado'], true)
        );
        if ($pedidoConItems && empty($pendientes)) {
            $this->model->cambiarEstadoPedido($pedidoId, 'listo');
        } elseif ($pedidoConItems && ($pedidoConItems['estado'] ?? '') === 'pendiente') {
            $this->model->cambiarEstadoPedido($pedidoId, 'en_preparacion');
        }

        $this->json(['ok' => true, 'estado' => $nuevoEstado]);
    }

    /** POST — marca el pedido completo como entregado/recogido (sale de la cola). */
    public function entregarPedido(?string $id = null): void
    {
        $pedidoId = (int)$id;
        $pedido = $this->model->getConItemsSinMesas($pedidoId, $this->restauranteId());
        if (!$pedido) {
            $this->json(['ok' => false, 'msg' => 'Pedido no encontrado'], 404);
        }
        $this->model->cambiarEstadoPedido($pedidoId, 'entregado');
        $this->json(['ok' => true]);
    }
}
