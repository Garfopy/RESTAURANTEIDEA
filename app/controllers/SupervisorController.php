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
        $empresaId   = $_SESSION['usuario']['empresa_id'] ?? 0;
        $pedidoModel = new PedidoModel();

        $pendientes    = $pedidoModel->pendientesAprobacion($empresaId);
        $enRuta        = $pedidoModel->getPedidosEnRutaEmpresa($empresaId, 10);
        $entregadosHoy = $pedidoModel->getEntregadosHoy($empresaId);

        $flash      = $this->getFlash();
        $pageTitle  = 'Panel de Supervisión';
        $activeMenu = 'supervisor_dashboard';

        ob_start();
        require ROOT_PATH . '/app/views/supervisor/dashboard.php';
        $content = ob_get_clean();
        require ROOT_PATH . '/app/views/empresa/layouts/main.php';
    }
}
