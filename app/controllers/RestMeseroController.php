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

    // POST /rest-mesero/atenderAlerta/{alertaId}
    public function atenderAlerta(?string $alertaId = null): void
    {
        $db   = Database::getInstance();
        $stmt = $db->prepare("UPDATE rest_alertas SET atendida=1 WHERE id=? AND restaurante_id=?");
        $stmt->execute([(int)$alertaId, $this->restauranteId()]);
        $this->json(['ok' => true]);
    }

    // GET /rest-mesero/alertas  — polling JSON para el dashboard
    public function alertas(?string $p = null): void
    {
        $db   = Database::getInstance();
        $stmt = $db->prepare(
            "SELECT a.id, a.tipo, a.created_at,
                    m.nombre AS mesa_nombre
             FROM rest_alertas a
             LEFT JOIN rest_mesas m ON m.id = a.mesa_id
             WHERE a.restaurante_id = ? AND a.atendida = 0
             ORDER BY a.created_at DESC
             LIMIT 20"
        );
        $stmt->execute([$this->restauranteId()]);
        $this->json(['ok' => true, 'alertas' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
    }

    // GET /rest-mesero/pedidosMesa/{mesaId}  — pedidos activos de una mesa (para modal)
    public function pedidosMesa(?string $mesaId = null): void
    {
        $db   = Database::getInstance();
        $stmt = $db->prepare(
            "SELECT p.id, p.folio, p.estado, p.created_at
             FROM rest_pedidos p
             WHERE p.mesa_id = ? AND p.restaurante_id = ?
               AND p.estado NOT IN ('entregado','cancelado')
             ORDER BY p.created_at DESC"
        );
        $stmt->execute([(int)$mesaId, $this->restauranteId()]);
        $pedidos = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($pedidos as &$ped) {
            $stmt2 = $db->prepare(
                "SELECT pi.id, pl.nombre AS nombre, pi.cantidad, pi.estado
                 FROM rest_pedido_items pi
                 JOIN rest_platillos pl ON pl.id = pi.platillo_id
                 WHERE pi.pedido_id = ? AND pi.estado != 'cancelado'"
            );
            $stmt2->execute([(int)$ped['id']]);
            $ped['items'] = $stmt2->fetchAll(PDO::FETCH_ASSOC);
        }
        unset($ped);

        $this->json(['ok' => true, 'pedidos' => $pedidos]);
    }

    // GET /rest-mesero/listos  — pedidos en estado 'listo' para entregar
    public function listos(?string $p = null): void
    {
        $db   = Database::getInstance();
        $stmt = $db->prepare(
            "SELECT p.id, p.folio, p.estado, p.created_at, m.nombre AS mesa_nombre
             FROM rest_pedidos p
             LEFT JOIN rest_mesas m ON m.id = p.mesa_id
             WHERE p.restaurante_id = ? AND p.estado = 'listo'
             ORDER BY p.created_at ASC
             LIMIT 50"
        );
        $stmt->execute([$this->restauranteId()]);
        $pedidos = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($pedidos as &$ped) {
            $stmt2 = $db->prepare(
                "SELECT pi.id, pl.nombre AS nombre, pi.cantidad
                 FROM rest_pedido_items pi
                 JOIN rest_platillos pl ON pl.id = pi.platillo_id
                 WHERE pi.pedido_id = ? AND pi.estado != 'cancelado'"
            );
            $stmt2->execute([(int)$ped['id']]);
            $ped['items'] = $stmt2->fetchAll(PDO::FETCH_ASSOC);
        }
        unset($ped);

        $this->json(['ok' => true, 'listos' => $pedidos]);
    }
}
