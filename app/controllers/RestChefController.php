<?php
require_once ROOT_PATH . '/app/controllers/BaseController.php';

class RestChefController extends BaseController
{
    private RestPedidoModel $model;

    public function __construct()
    {
        parent::__construct();
        $this->requireChef();
        $this->model = new RestPedidoModel();
    }

    public function dashboard(?string $p = null): void
    {
        $restauranteId = $this->restauranteId();
        $restaurante   = (new RestauranteModel())->find($restauranteId);
        $pageTitle     = 'Cocina — KDS';
        $this->render('chef/dashboard', compact('restaurante','pageTitle'));
    }

    public function queue(?string $p = null): void
    {
        $restauranteId = $this->restauranteId();
        $items = $this->model->getKitchenQueue($restauranteId);
        $this->json($items);
    }

    public function marcarPreparacion(?string $itemId = null): void
    {
        $this->model->cambiarEstadoItem((int)$itemId, 'en_preparacion');
        $this->json(['ok' => true]);
    }

    public function marcarListo(?string $itemId = null): void
    {
        $this->model->cambiarEstadoItem((int)$itemId, 'listo');

        $db   = Database::getInstance();
        $stmt = $db->prepare(
            "SELECT pedido_id FROM rest_pedido_items WHERE id = ?"
        );
        $stmt->execute([(int)$itemId]);
        $pedidoId = (int)$stmt->fetchColumn();

        if ($pedidoId) {
            $stmt2 = $db->prepare(
                "SELECT COUNT(*) FROM rest_pedido_items
                 WHERE pedido_id = ? AND estado NOT IN ('listo','entregado','cancelado')"
            );
            $stmt2->execute([$pedidoId]);
            if ((int)$stmt2->fetchColumn() === 0) {
                $this->model->cambiarEstadoPedido($pedidoId, 'listo');

                // Descuento automático de inventario por receta
                try {
                    (new RestInventarioModel())->descontarPorOrden(
                        $pedidoId,
                        $this->restauranteId(),
                        $this->usuarioId()
                    );
                } catch (\Throwable $e) {
                    error_log('descontarPorOrden falló pedido ' . $pedidoId . ': ' . $e->getMessage());
                }
            }
        }

        $this->json(['ok' => true]);
    }
}
