<?php
require_once ROOT_PATH . '/app/controllers/BaseController.php';

class RestMeseroController extends BaseController
{
    private RestPedidoModel $model;

    public function __construct()
    {
        parent::__construct();
        $this->requireMesero();
        $this->model = new RestPedidoModel();
    }

    public function dashboard(?string $p = null): void
    {
        $restauranteId = $this->restauranteId();
        $restaurante   = (new RestauranteModel())->find($restauranteId);
        $mesas         = (new RestMesaModel())->getByRestaurante($restauranteId, true);
        $listos        = $this->model->listar($restauranteId, 1, 'listo')['data'] ?? [];
        $flash         = $this->getFlash();
        $pageTitle     = 'Mesero';
        $this->render('mesero/dashboard', compact('restaurante','mesas','listos','flash','pageTitle'));
    }

    public function marcarEntregado(?string $pedidoId = null): void
    {
        $this->model->cambiarEstadoPedido((int)$pedidoId, 'entregado');
        $db   = Database::getInstance();
        $stmt = $db->prepare("UPDATE rest_pedido_items SET estado='entregado' WHERE pedido_id = ?");
        $stmt->execute([(int)$pedidoId]);
        $this->json(['ok' => true]);
    }
}
