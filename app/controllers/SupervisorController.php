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

        $pendientes = $pedidoModel->pendientesAprobacion($empresaId);

        $enRuta = $pedidoModel->query(
            "SELECT p.id, p.folio, p.total, p.created_at,
                    u.nombre AS comprador_nombre
               FROM pedidos p
               JOIN usuarios u ON u.id = p.comprador_id
              WHERE p.empresa_id = ? AND p.estado = 'en_ruta'
              ORDER BY p.created_at DESC LIMIT 10",
            [$empresaId]
        );

        $entregadosHoy = $pedidoModel->query(
            "SELECT COUNT(*) AS total FROM pedidos
              WHERE empresa_id = ? AND estado = 'entregado' AND DATE(updated_at) = CURDATE()",
            [$empresaId]
        )[0]['total'] ?? 0;

        $flash      = $this->getFlash();
        $pageTitle  = 'Panel de Supervisión';
        $activeMenu = 'supervisor_dashboard';

        ob_start();
        require ROOT_PATH . '/app/views/supervisor/dashboard.php';
        $content = ob_get_clean();
        require ROOT_PATH . '/app/views/empresa/layouts/main.php';
    }
}
