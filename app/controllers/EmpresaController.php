<?php
require_once ROOT_PATH . '/app/controllers/BaseController.php';
class EmpresaController extends BaseController {
    public function __construct() { parent::__construct(); $this->requireAdmin(); }
    public function index(?string $p = null): void {
        $model = new EmpresaModel();
        $filtros = ['buscar' => $this->get('buscar',''), 'activo' => 1];
        $page = max(1,(int)$this->get('page',1));
        $resultado = $model->listado($filtros, $page);
        $empresas = $resultado['data']; $paginacion = $resultado;
        $flash = $this->getFlash(); $pageTitle = 'Empresas'; $activeMenu = 'empresas';
        ob_start(); require ROOT_PATH.'/app/views/panel/empresas/index.php'; $content = ob_get_clean();
        require ROOT_PATH.'/app/views/panel/layouts/main.php';
    }
    public function nueva(?string $p = null): void {
        $flash = $this->getFlash(); $pageTitle = 'Nueva empresa'; $activeMenu = 'empresas';
        ob_start(); require ROOT_PATH.'/app/views/panel/empresas/form.php'; $content = ob_get_clean();
        require ROOT_PATH.'/app/views/panel/layouts/main.php';
    }
    public function guardar(?string $p = null): void {
        if (!$this->isPost()) $this->redirect('panel-empresa/nueva');
        $model = new EmpresaModel();
        $model->insert(['razon_social'=>$this->post('razon_social'),'rfc'=>$this->post('rfc') ?: null,'tipo_negocio'=>$this->post('tipo_negocio'),'email'=>$this->post('email'),'telefono'=>$this->post('telefono'),'direccion_fiscal'=>$this->post('direccion_fiscal'),'activo'=>1,'created_by'=>$this->usuarioId()]);
        $this->log('Crear empresa','empresa'); $this->flash('success','Empresa creada.'); $this->redirect('panel-empresa/index');
    }
}
