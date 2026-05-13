<?php
require_once ROOT_PATH . '/app/controllers/BaseController.php';

class RestPublicoController extends BaseController
{
    private RestauranteModel $restModel;
    private RestMenuModel    $menuModel;
    private RestPedidoModel  $pedidoModel;
    private RestVisitaModel  $visitaModel;
    private RestTicketModel  $ticketModel;

    public function __construct()
    {
        parent::__construct();
        $this->restModel   = new RestauranteModel();
        $this->menuModel   = new RestMenuModel();
        $this->pedidoModel = new RestPedidoModel();
        $this->visitaModel = new RestVisitaModel();
        $this->ticketModel = new RestTicketModel();
    }

    // /menu/{slug}  o  /menu/{slug}?mesa={qr_codigo}
    public function index(?string $slug = null): void
    {
        $restaurante = $this->restModel->getBySlug($slug ?? '');
        if (!$restaurante) {
            http_response_code(404);
            die('<h1>Restaurante no encontrado</h1>');
        }

        $categorias = $this->menuModel->getCategorias((int)$restaurante['id'], true);
        $platillos  = $this->menuModel->getPlatillosDisponibles((int)$restaurante['id']);

        $mesa = null;
        $mesaQr = $this->get('mesa');
        if ($mesaQr) {
            $mesa = (new RestMesaModel())->getByQr($mesaQr);
        }

        // Recuperar visita previa de cookie (para agregar más pedidos a la misma visita)
        $cookieName = 'visita_' . $restaurante['id'];
        $visitaId   = (int)($_COOKIE[$cookieName] ?? 0);
        if ($visitaId) {
            $visita = $this->visitaModel->find($visitaId);
            // Si la visita ya terminó, ignorar cookie
            if (!$visita || in_array($visita['estado'], ['pagada','cancelada'])) {
                $visitaId = 0;
                setcookie($cookieName, '', time() - 1, '/');
            }
        }

        $pageTitle = $restaurante['nombre'];
        $this->render('publico/menu/index', compact('restaurante','categorias','platillos','mesa','visitaId','pageTitle'));
    }

    public function ordenar(?string $slug = null): void
    {
        if (!$this->isPost()) $this->redirect('menu/' . $slug);

        $restaurante = $this->restModel->getBySlug($slug ?? '');
        if (!$restaurante) { http_response_code(404); exit; }

        $restauranteId = (int)$restaurante['id'];
        $mesaQr        = $this->post('mesa_qr');
        $mesa          = $mesaQr ? (new RestMesaModel())->getByQr($mesaQr) : null;
        $visitaId      = $this->post('visita_id') ?: null;

        // Crear visita si no existe
        if (!$visitaId) {
            $visitaId = $this->visitaModel->crear(
                $restauranteId,
                $mesa ? (int)$mesa['id'] : null
            );
            // Guardar en cookie por 4 horas
            $cookieName = 'visita_' . $restauranteId;
            setcookie($cookieName, (string)$visitaId, time() + 4 * 3600, '/');
        }

        $platillosIds = $this->post('platillo_id', []);
        $cantidades   = $this->post('cantidad', []);

        $items = [];
        foreach ($platillosIds as $k => $platilloId) {
            if (!$platilloId || empty($cantidades[$k])) continue;
            $platillo = $this->menuModel->find((int)$platilloId);
            if (!$platillo || !$platillo['disponible']) continue;
            $cant    = max(1, (int)$cantidades[$k]);
            $items[] = [
                'platillo_id' => (int)$platilloId,
                'cantidad'    => $cant,
                'precio_unit' => (float)$platillo['precio'],
                'subtotal'    => (float)$platillo['precio'] * $cant,
                'notas'       => null,
            ];
        }

        if (empty($items)) {
            $this->redirect('menu/' . $slug . ($mesaQr ? '?mesa=' . urlencode($mesaQr) : ''));
        }

        $pedidoId = $this->pedidoModel->crear([
            'restaurante_id' => $restauranteId,
            'mesa_id'        => $mesa ? (int)$mesa['id'] : null,
            'visita_id'      => $visitaId,
            'mesero_id'      => null,
        ], $items);

        $this->visitaModel->actualizarTotales((int)$visitaId);

        $this->redirect('menu/' . $slug . '/confirmacion/' . $visitaId);
    }

    public function confirmacion(?string $slug = null): void
    {
        $parts       = explode('/', $slug ?? '');
        $realSlug    = $parts[0] ?? '';
        $visitaId    = (int)($parts[1] ?? 0);
        $restaurante = $this->restModel->getBySlug($realSlug);
        $visita      = $this->visitaModel->find($visitaId);
        $pedidos     = $this->pedidoModel->getByVisita($visitaId);
        $pageTitle   = '¡Pedido recibido!';
        $this->render('publico/menu/confirmacion', compact('restaurante','visita','pedidos','pageTitle'));
    }

    public function pagar(?string $slug = null): void
    {
        $parts       = explode('/', $slug ?? '');
        $realSlug    = $parts[0] ?? '';
        $visitaId    = (int)($parts[1] ?? 0);
        $restaurante = $this->restModel->getBySlug($realSlug);
        $ticket      = $this->ticketModel->getByVisita($visitaId);
        if (!$ticket) {
            $propina  = (float)$this->get('propina', 0);
            $ticketId = $this->ticketModel->consolidar($visitaId, $propina);
            $ticket   = $this->ticketModel->find($ticketId);
        }
        $pageTitle = 'Pagar cuenta';
        $this->render('publico/menu/pagar', compact('restaurante','ticket','pageTitle'));
    }
}
