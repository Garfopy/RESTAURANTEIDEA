<?php
require_once ROOT_PATH . '/app/controllers/BaseController.php';

class SupervisorController extends BaseController
{
    public function __construct()
    {
        parent::__construct();
        $this->requireRole(['supervisor']);
    }

    public function dashboard(?string $p = null): void
    {
        $empresaId       = $_SESSION['usuario']['empresa_id'] ?? 0;
        $pedidoModel     = new PedidoModel();
        $movimientoModel = new MovimientoInventarioModel();

        $pendientes  = $pedidoModel->pendientesAprobacion($empresaId);
        $enRuta      = $pedidoModel->getPedidosEnRuta($empresaId);
        $entregadosHoy = $pedidoModel->countEntregadosHoy($empresaId);
        $pedidosHoy    = $pedidoModel->countPedidosHoy($empresaId);
        $montoMes      = $pedidoModel->montoMes($empresaId);

        $stockResumen = $movimientoModel->resumenStock($empresaId);
        $alertasStock = array_values(array_filter(
            $stockResumen,
            fn($p) => in_array($p['estado_stock'], ['agotado', 'critico'], true)
        ));

        $ultimosMovimientos = $movimientoModel->ultimosMovimientos($empresaId, 5);

        $countPendientesSidebar = count($pendientes);

        $flash      = $this->getFlash();
        $pageTitle  = 'Panel de Supervisión';
        $activeMenu = 'supervisor_dashboard';

        ob_start();
        require ROOT_PATH . '/app/views/supervisor/dashboard.php';
        $content = ob_get_clean();
        require ROOT_PATH . '/app/views/empresa/layouts/main.php';
    }
}
