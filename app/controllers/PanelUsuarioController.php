<?php
require_once ROOT_PATH . '/app/controllers/BaseController.php';

class PanelUsuarioController extends BaseController
{
    private UsuarioModel $usuarioModel;

    public function __construct()
    {
        parent::__construct();
        $this->requireAdmin();
        $this->usuarioModel = new UsuarioModel();
    }

    public function index(?string $p = null): void
    {
        $filtros = [
            'buscar'   => $this->get('buscar', ''),
            'rol_slug' => $this->get('rol_slug', ''),
        ];
        $page       = max(1, (int)$this->get('page', 1));
        $resultado  = $this->usuarioModel->listadoConRol($filtros, $page);
        $usuarios   = $resultado['data'];
        $paginacion = $resultado;
        $roles      = $this->usuarioModel->rolesPermitidosPorAdmin();
        $flash      = $this->getFlash();
        $pageTitle  = 'Usuarios';
        $activeMenu = 'usuarios';

        ob_start();
        require ROOT_PATH . '/app/views/panel/usuarios/index.php';
        $content = ob_get_clean();
        require ROOT_PATH . '/app/views/panel/layouts/main.php';
    }

    public function nuevo(?string $p = null): void
    {
        $roles        = $this->usuarioModel->rolesPermitidosPorAdmin();
        $empresaModel = new EmpresaModel();
        $empresas     = $empresaModel->listadoSimple();
        $usuario      = null;
        $flash        = $this->getFlash();
        $pageTitle    = 'Nuevo Usuario';
        $activeMenu   = 'usuarios';

        ob_start();
        require ROOT_PATH . '/app/views/panel/usuarios/form.php';
        $content = ob_get_clean();
        require ROOT_PATH . '/app/views/panel/layouts/main.php';
    }

    public function guardar(?string $p = null): void
    {
        if (!$this->isPost()) {
            $this->redirect('panel-usuario/nuevo');
        }

        $rolSlug   = $this->post('rol_slug');
        $empresaId = (int)$this->post('empresa_id') ?: null;

        // admin_empresa requiere empresa_id; admin no
        if ($rolSlug === 'admin_empresa' && !$empresaId) {
            $this->flash('error', 'Debes seleccionar una empresa para Admin Empresa.');
            $this->redirect('panel-usuario/nuevo');
        }

        $rolRow = $this->usuarioModel->getRolPorSlug($rolSlug);
        if (!$rolRow) {
            $this->flash('error', 'Rol no válido.');
            $this->redirect('panel-usuario/nuevo');
        }

        $password = $this->post('password');
        if (strlen($password) < 6) {
            $this->flash('error', 'La contraseña debe tener al menos 6 caracteres.');
            $this->redirect('panel-usuario/nuevo');
        }

        $id = $this->usuarioModel->crear([
            'nombre'           => trim($this->post('nombre')),
            'apellido_paterno' => trim($this->post('apellido_paterno', '')),
            'email'            => trim($this->post('email')),
            'telefono'         => trim($this->post('telefono', '')),
            'rol_id'           => $rolRow['id'],
            'empresa_id'       => $empresaId,
            'activo'           => 1,
        ], $password);

        $this->log('Crear usuario', 'usuarios', "ID: $id rol: $rolSlug");
        $this->flash('success', 'Usuario creado. Contraseña: ' . htmlspecialchars($password));
        $this->redirect('panel-usuario/index');
    }

    public function editar(?string $p = null): void
    {
        $usuario = $this->usuarioModel->getConRol((int)$p);
        if (!$usuario) {
            $this->flash('error', 'Usuario no encontrado.');
            $this->redirect('panel-usuario/index');
        }

        $roles        = $this->usuarioModel->rolesPermitidosPorAdmin();
        $empresaModel = new EmpresaModel();
        $empresas     = $empresaModel->listadoSimple();
        $flash        = $this->getFlash();
        $pageTitle    = 'Editar Usuario';
        $activeMenu   = 'usuarios';

        ob_start();
        require ROOT_PATH . '/app/views/panel/usuarios/form.php';
        $content = ob_get_clean();
        require ROOT_PATH . '/app/views/panel/layouts/main.php';
    }

    public function actualizar(?string $p = null): void
    {
        if (!$this->isPost()) {
            $this->redirect('panel-usuario/index');
        }

        $id    = (int)$p;
        $data  = [
            'nombre'           => trim($this->post('nombre')),
            'apellido_paterno' => trim($this->post('apellido_paterno', '')),
            'telefono'         => trim($this->post('telefono', '')),
        ];

        $nuevoPass = trim($this->post('password', ''));
        if ($nuevoPass !== '') {
            if (strlen($nuevoPass) < 6) {
                $this->flash('error', 'La nueva contraseña debe tener al menos 6 caracteres.');
                $this->redirect("panel-usuario/editar/$id");
            }
            $data['password'] = password_hash($nuevoPass, PASSWORD_BCRYPT);
        }

        $this->usuarioModel->update($id, $data);
        $this->log('Editar usuario', 'usuarios', "ID: $id");
        $this->flash('success', 'Usuario actualizado.');
        $this->redirect('panel-usuario/index');
    }

    public function toggle(?string $p = null): void
    {
        $id      = (int)$p;
        $usuario = $this->usuarioModel->find($id);
        if (!$usuario) {
            $this->flash('error', 'Usuario no encontrado.');
            $this->redirect('panel-usuario/index');
        }

        $nuevoEstado = $usuario['activo'] ? 0 : 1;
        $this->usuarioModel->update($id, ['activo' => $nuevoEstado]);
        $this->log('Toggle usuario', 'usuarios', "ID: $id activo: $nuevoEstado");
        $accion = $nuevoEstado ? 'activado' : 'desactivado';
        $this->flash('success', "Usuario $accion.");
        $this->redirect('panel-usuario/index');
    }
}
