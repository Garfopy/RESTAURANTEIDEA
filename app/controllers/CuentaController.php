<?php
require_once ROOT_PATH . '/app/controllers/BaseController.php';

class CuentaController extends BaseController
{
    public function __construct()
    {
        parent::__construct();
        $this->requireAuth();
    }

    public function perfil(?string $p = null): void
    {
        $model   = new UsuarioModel();
        $usuario = $model->find($this->usuarioId());
        $rol     = $this->rolActual();
        $flash   = $this->getFlash();
        $pageTitle  = 'Mi perfil';
        $activeMenu = 'cuenta';

        ob_start();
        require ROOT_PATH . '/app/views/empresa/cuenta/perfil.php';
        $content = ob_get_clean();

        // Elegir layout según rol
        if (in_array($rol, ['superadmin', 'admin'], true)) {
            require ROOT_PATH . '/app/views/panel/layouts/main.php';
        } elseif ($rol === 'repartidor') {
            // Repartidor usa su propio layout
            echo $content;
        } else {
            require ROOT_PATH . '/app/views/empresa/layouts/main.php';
        }
    }

    public function guardar(?string $p = null): void
    {
        if (!$this->isPost()) $this->redirect('cuenta/perfil');

        $model  = new UsuarioModel();
        $data   = [
            'nombre'           => trim($this->post('nombre', '')),
            'apellido_paterno' => trim($this->post('apellido_paterno', '')),
            'telefono'         => trim($this->post('telefono', '')),
        ];

        $model->update($this->usuarioId(), $data);
        $_SESSION['usuario'] = array_merge($_SESSION['usuario'], $data);

        $this->log('Actualizar perfil', 'cuenta');
        $this->flash('success', 'Perfil actualizado.');
        $this->redirect('cuenta/perfil');
    }

    public function cambiarPassword(?string $p = null): void
    {
        if (!$this->isPost()) $this->redirect('cuenta/perfil');

        $actual  = $this->post('password_actual', '');
        $nuevo   = $this->post('password_nuevo', '');
        $confirm = $this->post('password_confirm', '');

        $model   = new UsuarioModel();
        $usuario = $model->find($this->usuarioId());

        if (!password_verify($actual, $usuario['password'])) {
            $this->flash('error', 'La contraseña actual es incorrecta.');
            $this->redirect('cuenta/perfil');
        }
        if ($nuevo !== $confirm || strlen($nuevo) < 8) {
            $this->flash('error', 'La nueva contraseña debe tener al menos 8 caracteres y coincidir.');
            $this->redirect('cuenta/perfil');
        }

        $model->update($this->usuarioId(), ['password' => password_hash($nuevo, PASSWORD_BCRYPT)]);
        $this->log('Cambiar contraseña', 'cuenta');
        $this->flash('success', 'Contraseña actualizada.');
        $this->redirect('cuenta/perfil');
    }
}
