<?php
require_once ROOT_PATH . '/app/controllers/BaseController.php';

class PanelReporteController extends BaseController
{
    public function __construct()
    {
        parent::__construct();
        $this->requireAdmin();
    }

    public function index(?string $p = null): void
    {
        $db     = Database::getInstance();
        $meses  = 6;

        // ── Ingresos SaaS por mes ─────────────────────────────────────────
        // Aproximación: empresas activas × precio de su plan por mes
        $ingresosPorMes = $db->query(
            "SELECT DATE_FORMAT(e.created_at,'%Y-%m') AS mes,
                    COALESCE(SUM(ps.precio_mensual),0) AS ingresos
               FROM empresas e
               JOIN suscripciones s  ON s.empresa_id = e.id
               JOIN planes_saas ps   ON ps.id = s.plan_id
              WHERE e.activo = 1
                AND e.created_at >= DATE_SUB(NOW(), INTERVAL $meses MONTH)
           GROUP BY mes
           ORDER BY mes ASC"
        )->fetchAll();

        // ── Distribución de planes ────────────────────────────────────────
        $distPlanes = $db->query(
            "SELECT ps.nombre AS plan, ps.precio_mensual,
                    COUNT(s.id) AS total_empresas,
                    SUM(CASE WHEN s.estado='activo' THEN 1 ELSE 0 END) AS activas
               FROM planes_saas ps
          LEFT JOIN suscripciones s ON s.plan_id = ps.id
              WHERE ps.activo = 1
           GROUP BY ps.id
           ORDER BY ps.precio_mensual ASC"
        )->fetchAll();

        // ── Estado de suscripciones ───────────────────────────────────────
        $estadoSus = $db->query(
            "SELECT estado, COUNT(*) AS total
               FROM suscripciones
           GROUP BY estado"
        )->fetchAll();

        // ── Pedidos totales por mes ───────────────────────────────────────
        $pedidosMes = $db->query(
            "SELECT DATE_FORMAT(created_at,'%Y-%m') AS mes,
                    COUNT(*) AS total,
                    COALESCE(SUM(total),0) AS monto
               FROM pedidos
              WHERE created_at >= DATE_SUB(NOW(), INTERVAL $meses MONTH)
           GROUP BY mes ORDER BY mes ASC"
        )->fetchAll();

        // ── Top 5 empresas por volumen de pedidos ─────────────────────────
        $topEmpresas = $db->query(
            "SELECT e.razon_social, COUNT(p.id) AS total_pedidos,
                    COALESCE(SUM(p.total),0) AS monto
               FROM empresas e
          LEFT JOIN pedidos p ON p.empresa_id = e.id AND p.estado != 'cancelado'
              WHERE e.activo = 1
           GROUP BY e.id
           ORDER BY total_pedidos DESC LIMIT 5"
        )->fetchAll();

        // ── Empresas nuevas por mes ───────────────────────────────────────
        $empresasNuevas = $db->query(
            "SELECT DATE_FORMAT(created_at,'%Y-%m') AS mes, COUNT(*) AS total
               FROM empresas
              WHERE created_at >= DATE_SUB(NOW(), INTERVAL $meses MONTH)
           GROUP BY mes ORDER BY mes ASC"
        )->fetchAll();

        // ── Tasa de error (errores/día últimos 30 días) ───────────────────
        $tasaErrores = [];
        try {
            $tasaErrores = $db->query(
                "SELECT DATE(created_at) AS dia, COUNT(*) AS total
                   FROM error_logs
                  WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
               GROUP BY dia ORDER BY dia ASC"
            )->fetchAll();
        } catch (\Throwable $e) { /* tabla puede no existir */ }

        // ── KPIs resumen ─────────────────────────────────────────────────
        $totalEmpresas   = (int)$db->query('SELECT COUNT(*) FROM empresas WHERE activo=1')->fetchColumn();
        $suscActivas     = (int)$db->query("SELECT COUNT(*) FROM suscripciones WHERE estado='activo'")->fetchColumn();
        $ingresosMes     = (float)$db->query(
            "SELECT COALESCE(SUM(ps.precio_mensual),0) FROM suscripciones s JOIN planes_saas ps ON ps.id=s.plan_id WHERE s.estado='activo'"
        )->fetchColumn();
        $pedidosMesCount = (int)$db->query(
            "SELECT COUNT(*) FROM pedidos WHERE MONTH(created_at)=MONTH(NOW()) AND YEAR(created_at)=YEAR(NOW())"
        )->fetchColumn();

        $flash      = $this->getFlash();
        $pageTitle  = 'Reportes de plataforma';
        $activeMenu = 'reportes';

        ob_start();
        require ROOT_PATH . '/app/views/panel/reportes/index.php';
        $content = ob_get_clean();
        require ROOT_PATH . '/app/views/panel/layouts/main.php';
    }
}
