<?php
require_once ROOT_PATH . '/app/controllers/BaseController.php';

class AuthController extends BaseController
{
    public function index(?string $p = null): void
    {
        $this->redirect('auth/login');
    }

    public function login(?string $p = null): void
    {
        if (isset($_SESSION['usuario'])) {
            $this->redirect('dashboard/index');
        }
        $pageTitle = 'Iniciar Sesión';
        $flash     = $this->getFlash();
        $this->render('auth/login', compact('pageTitle', 'flash'));
    }

    public function doLogin(?string $p = null): void
    {
        if (!$this->isPost()) {
            $this->redirect('auth/login');
        }

        $email    = trim($this->post('email', ''));
        $password = $this->post('password', '');

        if (!$email || !$password) {
            $this->flash('error', 'Por favor ingresa tu correo y contraseña.');
            $this->redirect('auth/login');
        }

        $usuarioModel = new UsuarioModel();
        $usuario      = $usuarioModel->getByEmail($email);

        if (!$usuario || !password_verify($password, $usuario['password'])) {
            $this->flash('error', 'Credenciales incorrectas. Intenta de nuevo.');
            $this->redirect('auth/login');
        }

        $_SESSION['usuario'] = $usuario;

        // If empresa_id, load empresa
        if (!empty($usuario['empresa_id'])) {
            $empresaModel = new EmpresaModel();
            $_SESSION['empresa'] = $empresaModel->find($usuario['empresa_id']);
        }

        $this->log('Login exitoso', 'auth');

        // Redirect by role
        $rol = $usuario['rol_slug'];
        match (true) {
            $rol === 'repartidor'              => $this->redirect('repartidor/inicio'),
            in_array($rol, ['comprador','supervisor']) => $this->redirect('carrito/inicio'),
            default                            => $this->redirect('dashboard/index'),
        };
    }

    public function logout(?string $p = null): void
    {
        $this->log('Logout', 'auth');
        session_destroy();
        header('Location: ' . BASE_URL . 'auth/login');
        exit;
    }
}
