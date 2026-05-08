<?php
require_once ROOT_PATH . '/app/controllers/BaseController.php';

class PanelReporteController extends BaseController
{
    public function __construct()
    {
        parent::__construct();
    }

    public function index(?string $p = null): void
    {
        $this->redirect('empresa-reporte/index');
    }
}
