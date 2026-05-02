<?php
require_once ROOT_PATH . '/app/controllers/BaseController.php';

class SucursalController extends BaseController
{
    private SucursalModel $model;

    public function __construct()
    {
        parent::__construct();
        $this->model = new SucursalModel();
    }

    public function index(?string $p = null): void
    {
        $rol = $_SESSION['usuario']['rol_slug'] ?? '';
        if (in_array($rol, ['comprador','supervisor'])) {
            $empresaId  = $this->empresaIdActual();
            $sucursales = $this->model->getByEmpresa($empresaId);
            $pageTitle  = 'Mis Sucursales';
            $ctrlSlug   = 'sucursal';
            $this->render('cliente/sucursales/index', compact('sucursales','pageTitle','ctrlSlug'));
        } else {
            $this->requireAdmin();
            $page      = max(1,(int)$this->get('page',1));
            $sucursales = $this->model->getAll($page);
            $pageTitle  = 'Sucursales';
            $ctrlSlug   = 'sucursal';
            $this->render('admin/clientes/index', compact('sucursales','pageTitle','ctrlSlug'));
        }
    }

    public function guardar(?string $p = null): void
    {
        if (!$this->isPost()) { $this->redirect('sucursal/index'); }
        $id   = (int)$this->post('id', 0);
        $data = [
            'empresa_id'        => (int)$this->post('empresa_id'),
            'nombre'            => $this->post('nombre'),
            'direccion'         => $this->post('direccion'),
            'ciudad'            => $this->post('ciudad'),
            'estado'            => $this->post('estado', 'Querétaro'),
            'cp'                => $this->post('cp'),
            'lat'               => $this->post('lat') ?: null,
            'lng'               => $this->post('lng') ?: null,
            'contacto_nombre'   => $this->post('contacto_nombre'),
            'contacto_telefono' => $this->post('contacto_telefono'),
            'activo'            => (int)$this->post('activo', 1),
        ];
        if ($id > 0) { $this->model->update($id, $data); }
        else         { $this->model->insert($data); }
        $this->flash('success', 'Sucursal guardada.');
        $this->redirect('sucursal/index');
    }
}
