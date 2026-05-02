<?php
require_once ROOT_PATH . '/app/controllers/BaseController.php';

class UsuarioController extends BaseController
{
    private UsuarioModel $model;

    public function __construct()
    {
        parent::__construct();
        $this->requireAdmin();
        $this->model = new UsuarioModel();
    }

    public function index(?string $p = null): void
    {
        $busqueda = $this->get('q','');
        $page     = max(1,(int)$this->get('page',1));
        $usuarios = $busqueda
            ? $this->model->search($busqueda, $page)
            : $this->model->getAll($page);
        $flash     = $this->getFlash();
        $pageTitle = 'Usuarios';
        $ctrlSlug  = 'usuario';
        $this->render('admin/usuarios/index', compact('usuarios','busqueda','flash','pageTitle','ctrlSlug'));
    }

    public function crear(?string $p = null): void
    {
        $usuario   = [];
        $roles     = Database::getInstance()->query('SELECT * FROM roles ORDER BY id')->fetchAll();
        $empresas  = Database::getInstance()->query('SELECT id, razon_social FROM empresas WHERE activo=1 ORDER BY razon_social')->fetchAll();
        $pageTitle = 'Nuevo Usuario';
        $ctrlSlug  = 'usuario';
        $this->render('admin/usuarios/form', compact('usuario','roles','empresas','pageTitle','ctrlSlug'));
    }

    public function editar(?string $id = null): void
    {
        $usuario   = $this->model->find((int)$id);
        if (!$usuario) { $this->redirect('usuario/index'); }
        $roles     = Database::getInstance()->query('SELECT * FROM roles ORDER BY id')->fetchAll();
        $empresas  = Database::getInstance()->query('SELECT id, razon_social FROM empresas WHERE activo=1 ORDER BY razon_social')->fetchAll();
        $pageTitle = 'Editar: ' . $usuario['nombre'];
        $ctrlSlug  = 'usuario';
        $this->render('admin/usuarios/form', compact('usuario','roles','empresas','pageTitle','ctrlSlug'));
    }

    public function guardar(?string $p = null): void
    {
        if (!$this->isPost()) { $this->redirect('usuario/index'); }

        $id   = (int)$this->post('id', 0);
        $data = [
            'nombre'     => trim($this->post('nombre')),
            'email'      => trim($this->post('email')),
            'rol_id'     => (int)$this->post('rol_id'),
            'empresa_id' => $this->post('empresa_id') ?: null,
            'activo'     => (int)$this->post('activo', 1),
        ];

        $pass = $this->post('password','');
        if ($pass) {
            $data['password'] = password_hash($pass, PASSWORD_DEFAULT);
        }

        if ($id > 0) {
            $this->model->update($id, $data);
            $this->flash('success', 'Usuario actualizado.');
        } else {
            if (!$pass) { $this->flash('error','La contraseña es requerida.'); $this->redirect('usuario/crear'); }
            $data['password'] = password_hash($pass, PASSWORD_DEFAULT);
            $this->model->insert($data);
            $this->flash('success', 'Usuario creado.');
        }

        $this->log("Usuario guardado: {$data['nombre']}", 'usuarios');
        $this->redirect('usuario/index');
    }

    public function eliminar(?string $id = null): void
    {
        $this->model->update((int)$id, ['activo' => 0]);
        $this->flash('success', 'Usuario desactivado.');
        $this->redirect('usuario/index');
    }
}
