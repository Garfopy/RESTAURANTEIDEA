<?php
require_once ROOT_PATH . '/app/controllers/BaseController.php';

class DashboardController extends BaseController
{
    public function index(?string $p = null): void
    {
        $rol = $_SESSION['usuario']['rol_slug'] ?? '';

        if ($rol === 'repartidor') {
            $this->redirect('repartidor/inicio');
        }

        if (in_array($rol, ['comprador', 'supervisor'])) {
            $this->redirect('carrito/inicio');
        }

        // Admin / SuperAdmin
        $this->requireAdmin();

        $pedidoModel   = new PedidoModel();
        $empresaModel  = new EmpresaModel();
        $inventarioM   = new InventarioModel();
        $productoModel = new ProductoModel();

        $estadisticas = $pedidoModel->getEstadisticasDashboard();
        $statsEmpresa = $empresaModel->getEstadisticas();
        $statsInv     = $inventarioM->getResumen();
        $topProductos = $productoModel->getTopVendidos(5);
        $categorias   = $pedidoModel->getVentasPorCategoria(30);

        $pageTitle = 'Dashboard';
        $ctrlSlug  = 'dashboard';

        $this->render('admin/dashboard', compact(
            'pageTitle', 'ctrlSlug',
            'estadisticas', 'statsEmpresa', 'statsInv', 'topProductos', 'categorias'
        ));
    }
}
