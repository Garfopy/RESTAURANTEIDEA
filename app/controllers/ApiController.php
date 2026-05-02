<?php
require_once ROOT_PATH . '/app/controllers/BaseController.php';

class ApiController extends BaseController
{
    private ProductoModel $prodModel;

    public function __construct()
    {
        parent::__construct();
        $this->prodModel = new ProductoModel();
    }

    public function precioEscalonado(?string $p = null): void
    {
        $productoId = (int)$this->get('producto_id', 0);
        $cantidad   = (float)$this->get('cantidad', 0);
        if (!$productoId || $cantidad <= 0) {
            $this->json(['error' => 'Parámetros inválidos'], 400);
        }
        $precio   = $this->prodModel->getPrecioParaCantidad($productoId, $cantidad);
        $subtotal = round($precio * $cantidad, 2);
        $this->json(compact('precio','subtotal','cantidad'));
    }

    public function stockProducto(?string $p = null): void
    {
        $productoId = (int)$this->get('producto_id', 0);
        $invModel   = new InventarioModel();
        $inv        = $invModel->getByProducto($productoId);
        $this->json($inv ?? ['disponible' => 0]);
    }

    public function sucursalesEmpresa(?string $p = null): void
    {
        $empresaId = $this->empresaIdActual() ?? (int)$this->get('empresa_id', 0);
        $sucModel  = new SucursalModel();
        $sucursales = $sucModel->getActivasByEmpresa($empresaId);
        $this->json($sucursales);
    }

    public function estadoPedido(?string $p = null): void
    {
        $folio     = $this->get('folio', '');
        $db        = Database::getInstance();
        $stmt      = $db->prepare('SELECT folio, estado, fecha_entrega FROM pedidos WHERE folio = ?');
        $stmt->execute([$folio]);
        $row = $stmt->fetch();
        $this->json($row ?: ['error' => 'No encontrado']);
    }

    public function shellyStatus(?string $id = null): void
    {
        $this->requireAdmin();
        require_once ROOT_PATH . '/app/services/ShellyService.php';
        try {
            $svc    = new ShellyService((int)$id);
            $status = $svc->getStatus();
            $this->json($status);
        } catch (Exception $e) {
            $this->json(['error' => $e->getMessage()], 500);
        }
    }

    public function shellyToggle(?string $id = null): void
    {
        $this->requireAdmin();
        require_once ROOT_PATH . '/app/services/ShellyService.php';
        $action = $this->post('action', 'toggle');
        try {
            $svc = new ShellyService((int)$id);
            $ok  = match($action) {
                'on'  => $svc->turnOn(),
                'off' => $svc->turnOff(),
                default => $svc->toggle(),
            };
            $this->json(['ok' => $ok]);
        } catch (Exception $e) {
            $this->json(['error' => $e->getMessage()], 500);
        }
    }
}
