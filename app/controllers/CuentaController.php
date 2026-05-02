<?php
require_once ROOT_PATH . '/app/controllers/BaseController.php';

class CuentaController extends BaseController
{
    public function __construct()
    {
        parent::__construct();
        $this->requireRole(['comprador', 'supervisor', 'admin', 'superadmin']);
    }

    public function perfil(?string $p = null): void
    {
        $usuMod  = new UsuarioModel();
        $usuario = $usuMod->find($_SESSION['usuario']['id']);
        $empresa = !empty($usuario['empresa_id'])
            ? (new EmpresaModel())->find($usuario['empresa_id'])
            : null;
        $flash    = $this->getFlash();
        $ctrlSlug = 'cuenta';
        $pageTitle = 'Mi cuenta';
        $this->render('cliente/cuenta/perfil', compact('usuario', 'empresa', 'flash', 'ctrlSlug', 'pageTitle'));
    }

    public function guardarPerfil(?string $p = null): void
    {
        if (!$this->isPost()) { $this->redirect('cuenta/perfil'); }

        $id = (int)($_SESSION['usuario']['id'] ?? 0);
        if (!$id) { $this->redirect('auth/login'); }

        $data = [
            'nombre'           => trim($this->post('nombre', '')),
            'apellido_paterno' => trim($this->post('apellido_paterno', '')),
            'apellido_materno' => trim($this->post('apellido_materno', '')) ?: null,
            'email'            => trim($this->post('email', '')),
        ];

        (new UsuarioModel())->update($id, $data);

        $_SESSION['usuario']['nombre']           = $data['nombre'];
        $_SESSION['usuario']['apellido_paterno'] = $data['apellido_paterno'];
        $_SESSION['usuario']['apellido_materno'] = $data['apellido_materno'];
        $_SESSION['usuario']['email']            = $data['email'];

        $this->log('Perfil actualizado', 'cuenta');
        $this->flash('success', 'Perfil actualizado correctamente.');
        $this->redirect('cuenta/perfil');
    }

    public function cambiarPassword(?string $p = null): void
    {
        if (!$this->isPost()) { $this->redirect('cuenta/perfil'); }

        $id             = (int)($_SESSION['usuario']['id'] ?? 0);
        $passwordActual = $this->post('password_actual', '');
        $passwordNueva  = $this->post('password_nueva', '');
        $passwordConf   = $this->post('password_confirm', '');

        $usuMod  = new UsuarioModel();
        $usuario = $usuMod->find($id);

        if (!$usuario || !password_verify($passwordActual, $usuario['password'])) {
            $this->flash('error', 'La contraseña actual es incorrecta.');
            $this->redirect('cuenta/perfil');
        }

        if ($passwordNueva !== $passwordConf) {
            $this->flash('error', 'Las contraseñas nuevas no coinciden.');
            $this->redirect('cuenta/perfil');
        }

        if (strlen($passwordNueva) < 6) {
            $this->flash('error', 'La contraseña debe tener al menos 6 caracteres.');
            $this->redirect('cuenta/perfil');
        }

        $usuMod->updatePassword($id, password_hash($passwordNueva, PASSWORD_DEFAULT));
        $this->log('Contraseña cambiada', 'cuenta');
        $this->flash('success', 'Contraseña actualizada correctamente.');
        $this->redirect('cuenta/perfil');
    }
}
