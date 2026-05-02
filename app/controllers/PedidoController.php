<?php
require_once ROOT_PATH . '/app/controllers/BaseController.php';

class PedidoController extends BaseController
{
    private PedidoModel $model;

    public function __construct()
    {
        parent::__construct();
        $this->model = new PedidoModel();
    }

    public function index(?string $p = null): void
    {
        $rol = $_SESSION['usuario']['rol_slug'] ?? '';
        $filtros = [
            'estado'     => $this->get('estado',''),
            'busqueda'   => $this->get('q',''),
            'empresa_id' => $this->get('empresa_id',''),
        ];
        $page = max(1,(int)$this->get('page',1));

        if (in_array($rol, ['comprador','supervisor'])) {
            $empresaId = $this->empresaIdActual();
            $pedidos   = $this->model->getByEmpresa($empresaId, $filtros, $page);
            $pageTitle = 'Mis Pedidos';
            $ctrlSlug  = 'pedido';
            $flash     = $this->getFlash();
            $this->render('cliente/pedidos/index', compact('pedidos','filtros','flash','pageTitle','ctrlSlug'));
        } else {
            $this->requireAdmin();
            $pedidos   = $this->model->getAll($filtros, $page);
            $pageTitle = 'Pedidos';
            $ctrlSlug  = 'pedido';
            $flash     = $this->getFlash();
            $this->render('admin/pedidos/index', compact('pedidos','filtros','flash','pageTitle','ctrlSlug'));
        }
    }

    public function detalle(?string $id = null): void
    {
        $pedido = $this->model->getDetalle((int)$id);
        if (!$pedido) { $this->redirect('pedido/index'); }

        $rol = $_SESSION['usuario']['rol_slug'] ?? '';
        if (in_array($rol, ['comprador','supervisor'])) {
            $pageTitle = 'Pedido #' . $pedido['folio'];
            $ctrlSlug  = 'pedido';
            $this->render('cliente/pedidos/detalle', compact('pedido','pageTitle','ctrlSlug'));
        } else {
            $this->requireAdmin();
            $pageTitle = 'Pedido ' . $pedido['folio'];
            $ctrlSlug  = 'pedido';
            $this->render('admin/pedidos/detalle', compact('pedido','pageTitle','ctrlSlug'));
        }
    }

    public function cambiarEstado(?string $id = null): void
    {
        $this->requireAdmin();
        $estado = $this->post('estado','');
        $estados = ['pendiente','confirmado','en_preparacion','en_ruta','entregado','cancelado'];
        if (!in_array($estado, $estados)) { $this->json(['ok'=>false,'error'=>'Estado inválido']); }

        $this->model->cambiarEstado((int)$id, $estado);
        $this->log("Pedido #$id → $estado", 'pedidos');
        $this->json(['ok' => true, 'estado' => $estado]);
    }

    public function reordenar(?string $id = null): void
    {
        $pedido = $this->model->getDetalle((int)$id);
        if (!$pedido) { $this->redirect('pedido/index'); }

        // Copy items to session cart
        $_SESSION['carrito'] = [];
        foreach ($pedido['por_sucursal'] as $item) {
            $key = $item['producto_id'];
            if (!isset($_SESSION['carrito'][$key])) {
                $_SESSION['carrito'][$key] = ['producto_id' => $key, 'sucursales' => []];
            }
            $sId = $item['sucursal_id'];
            $_SESSION['carrito'][$key]['sucursales'][$sId] = ($item['cantidad']);
        }

        $this->log("Reorden del pedido #$id", 'pedidos');
        $this->flash('success', 'Pedido cargado en el carrito. Revisa y confirma.');
        $this->redirect('carrito/index');
    }
}
