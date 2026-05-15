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
            }
        }

        $this->json(['ok' => true]);
    }

    // GET /rest-chef/armado/{platillo_id}
    // Devuelve ingredientes (con codigo_display) y pasos de preparación para el KDS
    public function armado(?string $platilloId = null): void
    {
        $platilloId    = (int)$platilloId;
        $restauranteId = $this->restauranteId();
        $db            = Database::getInstance();

        $stmtIng = $db->prepare(
            "SELECT ri.codigo_display, ri.tipo_componente, ri.cantidad, ri.unidad,
                    i.nombre
             FROM rest_receta_ingredientes ri
             JOIN rest_recetas            re ON re.id = ri.receta_id
             JOIN rest_ingredientes        i  ON i.id  = ri.ingrediente_id
             WHERE re.platillo_id   = ?
               AND i.restaurante_id = ?
             ORDER BY ri.tipo_componente, ri.codigo_display, i.nombre"
        );
        $stmtIng->execute([$platilloId, $restauranteId]);
        $ingredientes = $stmtIng->fetchAll(PDO::FETCH_ASSOC);

        $stmtPasos = $db->prepare(
            "SELECT orden_paso, descripcion
             FROM rest_pasos_preparacion
             WHERE platillo_id    = ?
               AND restaurante_id = ?
               AND activo         = 1
             ORDER BY orden_paso ASC"
        );
        $stmtPasos->execute([$platilloId, $restauranteId]);
        $pasos = $stmtPasos->fetchAll(PDO::FETCH_ASSOC);

        $this->json(['ingredientes' => $ingredientes, 'pasos' => $pasos]);
    }
}
