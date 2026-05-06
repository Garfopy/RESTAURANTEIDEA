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
        $empresaId        = $_SESSION['usuario']['empresa_id'] ?? 0;
        $pedidoModel      = new PedidoModel();
        $movimientoModel  = new MovimientoInventarioModel();

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

        $entregadosHoy = (int)($pedidoModel->query(
            "SELECT COUNT(*) AS total FROM pedidos
              WHERE empresa_id = ? AND estado = 'entregado' AND DATE(updated_at) = CURDATE()",
            [$empresaId]
        )[0]['total'] ?? 0);

        $pedidosHoy = (int)($pedidoModel->query(
            "SELECT COUNT(*) AS total FROM pedidos
              WHERE empresa_id = ? AND DATE(created_at) = CURDATE()",
            [$empresaId]
        )[0]['total'] ?? 0);

        $montoMes = (float)($pedidoModel->query(
            "SELECT COALESCE(SUM(total), 0) AS monto FROM pedidos
              WHERE empresa_id = ? AND estado NOT IN ('cancelado')
                AND YEAR(created_at) = YEAR(CURDATE()) AND MONTH(created_at) = MONTH(CURDATE())",
            [$empresaId]
        )[0]['monto'] ?? 0);

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
