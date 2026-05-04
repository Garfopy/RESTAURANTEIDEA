<?php
require_once ROOT_PATH . '/app/controllers/BaseController.php';

class EmpresaDashboardController extends BaseController
{
    public function __construct()
    {
        parent::__construct();
        $this->requireEmpresa();
    }

    public function index(?string $p = null): void
    {
        $this->redirect('empresa/dashboard');
    }

    public function dashboard(?string $p = null): void
    {
        $empresaId = $this->empresaId();
        $rol       = $this->rolActual();
        $db        = Database::getInstance();

        // Datos comunes
        $totalPedidos = (int)$db->prepare('SELECT COUNT(*) FROM pedidos WHERE empresa_id = ?')
            ->execute([$empresaId]) ? 0 : 0;

        $stmt = $db->prepare('SELECT COUNT(*) FROM pedidos WHERE empresa_id = ?');
        $stmt->execute([$empresaId]);
        $totalPedidos = (int)$stmt->fetchColumn();

        $stmt = $db->prepare("SELECT COALESCE(SUM(total),0) FROM pedidos WHERE empresa_id = ? AND estado != 'cancelado' AND MONTH(created_at) = MONTH(NOW())");
        $stmt->execute([$empresaId]);
        $gastomMes = (float)$stmt->fetchColumn();

        // Pedidos recientes
        $stmt = $db->prepare(
            "SELECT p.folio, p.estado, p.total, p.created_at, u.nombre AS comprador
               FROM pedidos p
               JOIN usuarios u ON u.id = p.comprador_id
              WHERE p.empresa_id = ?
           ORDER BY p.created_at DESC LIMIT 8"
        );
        $stmt->execute([$empresaId]);
        $pedidosRecientes = $stmt->fetchAll();

        // Pendientes de aprobación (supervisor y admin_empresa los ven)
        $pendientesAprobacion = 0;
        if (in_array($rol, ['admin_empresa', 'supervisor'], true)) {
            $stmt = $db->prepare("SELECT COUNT(*) FROM pedidos WHERE empresa_id = ? AND requiere_aprobacion = 1 AND estado = 'pendiente'");
            $stmt->execute([$empresaId]);
            $pendientesAprobacion = (int)$stmt->fetchColumn();
        }

        // Cargar empresa a sesión si no está
        if (empty($_SESSION['empresa']) && $empresaId) {
            $empresaModel = new EmpresaModel();
            $_SESSION['empresa'] = $empresaModel->find($empresaId);
        }

        $flash     = $this->getFlash();
        $pageTitle = 'Mi Empresa';
        $activeMenu = 'dashboard';

        ob_start();
        require ROOT_PATH . '/app/views/empresa/dashboard.php';
        $content = ob_get_clean();

        require ROOT_PATH . '/app/views/empresa/layouts/main.php';
    }
}
