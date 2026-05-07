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

        // Generar contraseña segura automáticamente
        $password = PasswordHelper::generar(14);
        $email = trim($this->post('email'));

        // ── Iniciar transacción ──
        $db = Database::getInstance();
        $db->beginTransaction();

        try {
            // 1. Generar token de verificación
            $tokenVerificacion = bin2hex(random_bytes(32));
            $tokenExpira = date('Y-m-d H:i:s', strtotime('+24 hours'));

            // 2. Crear usuario en BD
            $id = $this->usuarioModel->crear([
                'nombre'              => trim($this->post('nombre')),
                'apellido_paterno'    => trim($this->post('apellido_paterno', '')),
                'email'               => $email,
                'telefono'            => trim($this->post('telefono', '')),
                'rol_id'              => $rolRow['id'],
                'empresa_id'          => $empresaId,
                'activo'              => 1,
                'email_verificado'    => 0,
                'token_verificacion'  => $tokenVerificacion,
                'token_expira'        => $tokenExpira,
                'created_by'          => $this->usuarioId(),
            ], $password);

            // 3. Enviar email con credenciales y link de verificación
            $emailService = new EmailService();
            $usuarioCreado = $this->usuarioModel->find($id);
            $emailEnviado = $emailService->enviarCredenciales($usuarioCreado, $password, null, $tokenVerificacion);

            if (!$emailEnviado) {
                error_log("[PanelUsuarioController] No se pudo enviar email a usuario ID: $id");
                // NO hacer rollback, el usuario ya está creado
                // Admin puede reenviar email manualmente o compartir credenciales
            }

            // 4. Commit transacción
            $db->commit();

            $this->log('Crear usuario', 'usuarios', "ID: $id rol: $rolSlug email: $email");

            $mensaje = 'Usuario creado correctamente. Se ha enviado un correo con las credenciales y el link de verificación.';
            if (!$emailEnviado) {
                $mensaje .= ' <strong>AVISO:</strong> No se pudo enviar el email. Credenciales: ' . htmlspecialchars($password);
            }
            $this->flash('success', $mensaje);
            $this->redirect('panel-usuario/index');

        } catch (Exception $e) {
            $db->rollBack();
            error_log("[PanelUsuarioController] Error al crear usuario: " . $e->getMessage());
            $this->flash('error', 'No se pudo crear el usuario: ' . $e->getMessage());
            $this->redirect('panel-usuario/nuevo');
        }
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
