<?php
require_once ROOT_PATH . '/app/controllers/BaseController.php';

class ClienteController extends BaseController
{
    private EmpresaModel $model;

    public function __construct()
    {
        parent::__construct();
        $this->requireAdmin();
        $this->model = new EmpresaModel();
    }

    public function index(?string $p = null): void
    {
        $filtros  = [
            'busqueda' => $this->get('q', ''),
            'activo'   => $this->get('activo', ''),
        ];
        $page     = max(1, (int)$this->get('page', 1));
        $clientes = $this->model->getAll($page, $filtros);
        $stats    = $this->model->getEstadisticas();
        $flash    = $this->getFlash();
        $pageTitle = 'Clientes';
        $ctrlSlug  = 'cliente';
        $this->render('admin/clientes/index', compact('clientes','stats','filtros','flash','pageTitle','ctrlSlug'));
    }

    public function detalle(?string $id = null): void
    {
        $empresa = $this->model->getConSucursales((int)$id);
        if (!$empresa) { $this->redirect('cliente/index'); }

        $pedidoModel = new PedidoModel();
        $pedidos     = $pedidoModel->getByEmpresa((int)$id, [], 1);
        $pageTitle   = $empresa['razon_social'];
        $ctrlSlug    = 'cliente';
        $this->render('admin/clientes/detalle', compact('empresa','pedidos','pageTitle','ctrlSlug'));
    }

    public function crear(?string $p = null): void
    {
        $pageTitle = 'Nuevo Cliente';
        $ctrlSlug  = 'cliente';
        $empresa   = [];
        $flash     = $this->getFlash();
        $this->render('admin/clientes/form', compact('empresa','pageTitle','ctrlSlug','flash'));
    }

    public function editar(?string $id = null): void
    {
        $empresa = $this->model->find((int)$id);
        if (!$empresa) { $this->redirect('cliente/index'); }
        $pageTitle = 'Editar Cliente';
        $ctrlSlug  = 'cliente';
        $flash     = $this->getFlash();
        $this->render('admin/clientes/form', compact('empresa','pageTitle','ctrlSlug','flash'));
    }

    public function guardar(?string $p = null): void
    {
        if (!$this->isPost()) { $this->redirect('cliente/index'); }

        $id   = (int)$this->post('id', 0);
        $data = [
            'razon_social'          => trim($this->post('razon_social')),
            'rfc'                   => strtoupper(trim($this->post('rfc'))),
            'regimen_fiscal'        => $this->post('regimen_fiscal'),
            'email'                 => trim($this->post('email')),
            'telefono'              => $this->post('telefono'),
            'direccion_fiscal'      => $this->post('direccion_fiscal'),
            'metodo_pago_preferido' => $this->post('metodo_pago_preferido', 'transferencia'),
            'fecha_registro'        => $this->post('fecha_registro') ?: date('Y-m-d'),
            'activo'                => (int)$this->post('activo', 1),
        ];

        if ($id > 0) {
            $this->model->update($id, $data);
            $this->log("Cliente actualizado: {$data['razon_social']}", 'clientes');
            $this->flash('success', 'Cliente actualizado correctamente.');
            $this->redirect("cliente/detalle/$id");
        } else {
            $newId = $this->model->insert($data);
            $this->log("Cliente creado: {$data['razon_social']}", 'clientes');
            $this->flash('success', 'Cliente creado correctamente.');
            $this->redirect("cliente/detalle/$newId");
        }
    }

    public function activarCredito(?string $id = null): void
    {
        $empresa = $this->model->find((int)$id);
        if (!$empresa) { $this->json(['ok' => false], 404); }

        $nuevoEstado = $empresa['credito_activo'] ? 0 : 1;
        $limite      = $this->isPost() ? (float)$this->post('limite', 0) : 0;

        $this->model->toggleCredito((int)$id, $nuevoEstado);
        if ($nuevoEstado && $limite > 0) {
            $this->model->update((int)$id, ['limite_credito' => $limite]);
        }

        $this->log("Crédito " . ($nuevoEstado ? 'activado' : 'desactivado') . " para cliente #$id", 'clientes');
        $this->json(['ok' => true, 'estado' => $nuevoEstado]);
    }

    public function eliminar(?string $id = null): void
    {
        $this->model->update((int)$id, ['activo' => 0]);
        $this->log("Cliente desactivado #$id", 'clientes');
        $this->flash('success', 'Cliente desactivado.');
        $this->redirect('cliente/index');
    }
}
