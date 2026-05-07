<?php
require_once ROOT_PATH . '/app/controllers/BaseController.php';

class PanelController extends BaseController
{
    public function __construct()
    {
        parent::__construct();
        $this->requireAdmin();
    }

    public function index(?string $p = null): void
    {
        $this->redirect('panel/dashboard');
    }

    public function dashboard(?string $p = null): void
    {
        $db = Database::getInstance();

        // KPIs globales
        $totalEmpresas  = (int)$db->query('SELECT COUNT(*) FROM empresas WHERE activo = 1')->fetchColumn();
        $totalUsuarios  = (int)$db->query('SELECT COUNT(*) FROM usuarios WHERE activo = 1 AND rol_id > 1')->fetchColumn();
        $pedidosMes     = (int)$db->query("SELECT COUNT(*) FROM pedidos WHERE MONTH(created_at) = MONTH(NOW()) AND YEAR(created_at) = YEAR(NOW())")->fetchColumn();
        $ventasMes      = (float)$db->query("SELECT COALESCE(SUM(total),0) FROM pedidos WHERE estado != 'cancelado' AND MONTH(created_at) = MONTH(NOW()) AND YEAR(created_at) = YEAR(NOW())")->fetchColumn();

        // Últimos pedidos
        $ultimosPedidos = $db->query(
            "SELECT p.folio, p.estado, p.total, p.created_at,
                    e.razon_social AS empresa, u.nombre AS comprador
               FROM pedidos p
               JOIN empresas e ON e.id = p.empresa_id
               JOIN usuarios u ON u.id = p.comprador_id
           ORDER BY p.created_at DESC LIMIT 10"
        )->fetchAll();

        // Alertas de stock bajo
        $stockBajo = $db->query(
            'SELECT p.nombre, inv.stock, inv.umbral_minimo
               FROM inventario inv
               JOIN productos p ON p.id = inv.producto_id
              WHERE inv.stock <= inv.umbral_minimo AND p.activo = 1'
        )->fetchAll();

        $flash     = $this->getFlash();
        $pageTitle = 'Dashboard';
        $activeMenu = 'dashboard';

        ob_start();
        require ROOT_PATH . '/app/views/panel/dashboard.php';
        $content = ob_get_clean();

        require ROOT_PATH . '/app/views/panel/layouts/main.php';
    }
}
