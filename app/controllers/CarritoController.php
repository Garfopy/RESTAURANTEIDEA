<?php
require_once ROOT_PATH . '/app/controllers/BaseController.php';

class CarritoController extends BaseController
{
    private ProductoModel $prodModel;

    public function __construct()
    {
        parent::__construct();
        $this->requireRole(['comprador','supervisor','admin','superadmin']);
        $this->prodModel = new ProductoModel();
        if (!isset($_SESSION['carrito'])) {
            $_SESSION['carrito'] = [];
        }
    }

    public function inicio(?string $p = null): void
    {
        $empresaId    = $this->empresaIdActual();
        $pedidoModel  = new PedidoModel();
        $recModel     = new RecurrenteModel();
        $invModel     = new InventarioModel();

        $ultimoPedido = null;
        $recurrentes  = [];

        if ($empresaId) {
            $pedidos = $pedidoModel->getByEmpresa($empresaId, [], 1);
            $ultimoPedido = $pedidos['data'][0] ?? null;
            $recurrentes  = $recModel->getByEmpresa($empresaId);
        }

        $alertas   = $invModel->getAlertas();
        $pageTitle = 'Inicio';
        $ctrlSlug  = 'inicio';
        $this->render('cliente/inicio', compact('ultimoPedido','recurrentes','alertas','pageTitle','ctrlSlug'));
    }

    public function index(?string $p = null): void
    {
        $empresaId  = $this->empresaIdActual();
        $sucModel   = new SucursalModel();
        $sucursales = $empresaId ? $sucModel->getActivasByEmpresa($empresaId) : [];

        $carrito    = $_SESSION['carrito'];
        $items      = [];
        $total      = 0;

        foreach ($carrito as $productoId => $entry) {
            $prod  = $this->prodModel->getConPreciosEscalonados((int)$productoId);
            if (!$prod) continue;

            $cantTotal = array_sum($entry['sucursales']);
            $precio    = $this->prodModel->getPrecioParaCantidad((int)$productoId, $cantTotal);
            $subtotal  = $precio * $cantTotal;
            $total    += $subtotal;

            $items[] = [
                'producto'     => $prod,
                'sucursales'   => $entry['sucursales'],
                'cantidad_total' => $cantTotal,
                'precio'       => $precio,
                'subtotal'     => $subtotal,
            ];
        }

        $flash     = $this->getFlash();
        $pageTitle = 'Carrito';
        $ctrlSlug  = 'carrito';
        $this->render('cliente/carrito/paso1_carrito', compact('items','sucursales','total','flash','pageTitle','ctrlSlug'));
    }

    public function agregar(?string $p = null): void
    {
        $data = json_decode(file_get_contents('php://input'), true) ?? $_POST;
        $productoId = (int)($data['producto_id'] ?? 0);
        if (!$productoId) { $this->json(['ok'=>false,'error'=>'Producto inválido']); }

        $sucursales = $data['sucursales'] ?? [];
        if (!isset($_SESSION['carrito'][$productoId])) {
            $_SESSION['carrito'][$productoId] = ['producto_id' => $productoId, 'sucursales' => []];
        }

        foreach ($sucursales as $sucId => $cant) {
            $cant = (float)$cant;
            if ($cant > 0) {
                $_SESSION['carrito'][$productoId]['sucursales'][$sucId] = $cant;
            } elseif (isset($_SESSION['carrito'][$productoId]['sucursales'][$sucId])) {
                unset($_SESSION['carrito'][$productoId]['sucursales'][$sucId]);
            }
        }

        if (empty($_SESSION['carrito'][$productoId]['sucursales'])) {
            unset($_SESSION['carrito'][$productoId]);
        }

        $this->json(['ok' => true, 'count' => count($_SESSION['carrito'])]);
    }

    public function actualizar(?string $p = null): void
    {
        $data       = json_decode(file_get_contents('php://input'), true) ?? $_POST;
        $productoId = (int)($data['producto_id'] ?? 0);
        $sucursalId = (int)($data['sucursal_id'] ?? 0);
        $cantidad   = (float)($data['cantidad'] ?? 0);

        if ($cantidad > 0) {
            $_SESSION['carrito'][$productoId]['sucursales'][$sucursalId] = $cantidad;
        } else {
            unset($_SESSION['carrito'][$productoId]['sucursales'][$sucursalId]);
            if (empty($_SESSION['carrito'][$productoId]['sucursales'])) {
                unset($_SESSION['carrito'][$productoId]);
            }
        }

        $this->json(['ok' => true]);
    }

    public function eliminar(?string $p = null): void
    {
        $data       = json_decode(file_get_contents('php://input'), true) ?? $_POST;
        $productoId = (int)($data['producto_id'] ?? 0);
        unset($_SESSION['carrito'][$productoId]);
        $this->json(['ok' => true, 'count' => count($_SESSION['carrito'])]);
    }

    public function calcularPrecio(?string $p = null): void
    {
        $productoId = (int)$this->get('producto_id', 0);
        $cantidad   = (float)$this->get('cantidad', 0);
        $precio     = $this->prodModel->getPrecioParaCantidad($productoId, $cantidad);
        $this->json(['precio' => $precio, 'subtotal' => round($precio * $cantidad, 2)]);
    }

    public function entrega(?string $p = null): void
    {
        if (empty($_SESSION['carrito'])) { $this->redirect('carrito/index'); }
        $empresaId  = $this->empresaIdActual();
        $sucModel   = new SucursalModel();
        $sucursales = $empresaId ? $sucModel->getActivasByEmpresa($empresaId) : [];
        $pageTitle  = 'Entrega — Paso 2';
        $ctrlSlug   = 'carrito';
        $this->render('cliente/carrito/paso2_entrega', compact('sucursales','pageTitle','ctrlSlug'));
    }

    public function resumen(?string $p = null): void
    {
        if (empty($_SESSION['carrito'])) { $this->redirect('carrito/index'); }

        $empresaId   = $this->empresaIdActual();
        $sucModel    = new SucursalModel();
        $allSucursales = $empresaId ? $sucModel->getActivasByEmpresa($empresaId) : [];
        $sucMap      = array_column($allSucursales, null, 'id');

        $carrito     = $_SESSION['carrito'];
        $itemsGlobal = [];
        $porSucursal = [];
        $total       = 0;

        foreach ($carrito as $productoId => $entry) {
            $prod      = $this->prodModel->getConPreciosEscalonados((int)$productoId);
            if (!$prod) continue;
            $cantTotal = array_sum($entry['sucursales']);
            $precio    = $this->prodModel->getPrecioParaCantidad((int)$productoId, $cantTotal);

            $itemsGlobal[] = ['producto'=>$prod,'cantidad'=>$cantTotal,'precio'=>$precio,'subtotal'=>$precio*$cantTotal];

            foreach ($entry['sucursales'] as $sucId => $cant) {
                if (!isset($porSucursal[$sucId])) {
                    $porSucursal[$sucId] = ['sucursal' => $sucMap[$sucId] ?? [], 'items' => [], 'subtotal' => 0];
                }
                $sub = $precio * $cant;
                $porSucursal[$sucId]['items'][] = ['producto'=>$prod,'cantidad'=>$cant,'precio'=>$precio,'subtotal'=>$sub];
                $porSucursal[$sucId]['subtotal'] += $sub;
                $total += $sub;
            }
        }

        // Store for confirm
        $_SESSION['checkout'] = [
            'items_global' => $itemsGlobal,
            'por_sucursal' => $porSucursal,
            'total'        => $total,
        ];

        $pageTitle  = 'Resumen — Paso 3';
        $ctrlSlug   = 'carrito';
        $fechaEntrega = $_SESSION['checkout_entrega']['fecha'] ?? '';
        $ventana      = $_SESSION['checkout_entrega']['ventana'] ?? '';
        $this->render('cliente/carrito/paso3_resumen', compact('itemsGlobal','porSucursal','total','fechaEntrega','ventana','pageTitle','ctrlSlug'));
    }

    public function confirmar(?string $p = null): void
    {
        if ($this->isPost()) {
            // Save delivery preferences
            $_SESSION['checkout_entrega'] = [
                'fecha'    => $this->post('fecha_entrega', ''),
                'ventana'  => $this->post('ventana_entrega',''),
                'notas'    => $this->post('notas',''),
                'metodo'   => $this->post('metodo_pago','transferencia'),
            ];
            $this->redirect('carrito/resumen');
        }

        if (empty($_SESSION['checkout'])) { $this->redirect('carrito/index'); }
        $pageTitle = 'Confirmación — Paso 4';
        $ctrlSlug  = 'carrito';
        $checkout  = $_SESSION['checkout'];
        $entrega   = $_SESSION['checkout_entrega'] ?? [];
        $this->render('cliente/carrito/paso4_confirmacion', compact('checkout','entrega','pageTitle','ctrlSlug'));
    }

    public function procesarPedido(?string $p = null): void
    {
        if (!$this->isPost()) { $this->redirect('carrito/confirmar'); }
        $empresaId  = $this->empresaIdActual();
        $checkout   = $_SESSION['checkout'] ?? null;
        $entrega    = $_SESSION['checkout_entrega'] ?? [];

        if (!$checkout || !$empresaId) { $this->redirect('carrito/index'); }

        $pedidoData = [
            'empresa_id'      => $empresaId,
            'usuario_id'      => $_SESSION['usuario']['id'],
            'fecha_pedido'    => date('Y-m-d H:i:s'),
            'fecha_entrega'   => $entrega['fecha'] ?? null,
            'ventana_entrega' => $entrega['ventana'] ?? null,
            'total'           => $checkout['total'],
            'estado'          => 'pendiente',
            'metodo_pago'     => $entrega['metodo'] ?? 'transferencia',
            'notas'           => $entrega['notas'] ?? null,
        ];

        $detalles    = [];
        foreach ($checkout['items_global'] as $item) {
            $detalles[] = [
                'producto_id'     => $item['producto']['id'],
                'cantidad'        => $item['cantidad'],
                'precio_unitario' => $item['precio'],
                'subtotal'        => $item['subtotal'],
            ];
        }

        $porSucursal = [];
        foreach ($checkout['por_sucursal'] as $sucId => $group) {
            foreach ($group['items'] as $item) {
                $porSucursal[] = [
                    'sucursal_id'     => $sucId,
                    'producto_id'     => $item['producto']['id'],
                    'cantidad'        => $item['cantidad'],
                    'precio_unitario' => $item['precio'],
                    'subtotal'        => $item['subtotal'],
                ];
            }
        }

        $pedidoModel = new PedidoModel();
        $pedidoId    = $pedidoModel->crearConDetalle($pedidoData, $detalles, $porSucursal);

        $this->log("Pedido creado #$pedidoId por empresa #$empresaId", 'pedidos');

        // Clear cart
        $_SESSION['carrito']           = [];
        $_SESSION['checkout']          = null;
        $_SESSION['checkout_entrega']  = null;

        $pedido    = $pedidoModel->getDetalle($pedidoId);
        $pageTitle = 'Pedido Confirmado';
        $ctrlSlug  = 'carrito';
        $this->render('cliente/carrito/paso4_confirmacion', compact('pedido','pageTitle','ctrlSlug'));
    }
}
