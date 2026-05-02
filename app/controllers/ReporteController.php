<?php
require_once ROOT_PATH . '/app/controllers/BaseController.php';

class ReporteController extends BaseController
{
    private PedidoModel $model;

    public function __construct()
    {
        parent::__construct();
        $this->model = new PedidoModel();
    }

    public function index(?string $p = null): void
    {
        $this->requireAdmin();
        $pageTitle = 'Reportes';
        $ctrlSlug  = 'reporte';
        $this->render('admin/reportes/index', compact('pageTitle','ctrlSlug'));
    }

    public function ventas(?string $p = null): void
    {
        $this->requireAdmin();
        $desde  = $this->get('desde', date('Y-m-01'));
        $hasta  = $this->get('hasta', date('Y-m-d'));
        $stats  = $this->model->getEstadisticasDashboard();
        $cats   = $this->model->getVentasPorCategoria(30);
        $pageTitle = 'Reporte de Ventas';
        $ctrlSlug  = 'reporte';
        $this->render('admin/reportes/ventas', compact('stats','cats','desde','hasta','pageTitle','ctrlSlug'));
    }

    public function cliente(?string $p = null): void
    {
        $empresaId = $this->empresaIdActual();
        if (!$empresaId) { $this->redirect('dashboard/index'); }
        $stats  = $this->model->getEstadisticasDashboard($empresaId);
        $cats   = $this->model->getVentasPorCategoria(30);
        $pageTitle = 'Mis Reportes';
        $ctrlSlug  = 'reporte';
        $this->render('cliente/reportes/index', compact('stats','cats','pageTitle','ctrlSlug'));
    }
}
