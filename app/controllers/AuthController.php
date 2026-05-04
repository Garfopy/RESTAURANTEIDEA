<?php
require_once ROOT_PATH . '/app/controllers/BaseController.php';

class AuthController extends BaseController
{
    private const MAX_INTENTOS  = 5;
    private const BLOQUEO_MINS  = 2;

    public function index(?string $p = null): void
    {
        $this->redirect('auth/login');
    }

    public function login(?string $p = null): void
    {
        if (isset($_SESSION['usuario'])) {
            $this->redirectSegunRol($_SESSION['usuario']['rol_slug'] ?? '');
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
        $ip       = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';

        if (!$email || !$password) {
            $this->flash('error', 'Por favor ingresa tu correo y contraseña.');
            $this->redirect('auth/login');
        }

        // Brute-force check: contar intentos fallidos en últimos N minutos desde esta IP
        $db = Database::getInstance();
        $stmt = $db->prepare(
            "SELECT COUNT(*) FROM login_intentos
             WHERE ip = ? AND created_at > DATE_SUB(NOW(), INTERVAL ? MINUTE)"
        );
        $stmt->execute([$ip, self::BLOQUEO_MINS]);
        $intentos = (int)$stmt->fetchColumn();

        if ($intentos >= self::MAX_INTENTOS) {
            $this->flash('error', 'Demasiados intentos fallidos. Espera ' . self::BLOQUEO_MINS . ' minutos e intenta de nuevo.');
            $this->redirect('auth/login');
        }

        $usuarioModel = new UsuarioModel();
        $usuario      = $usuarioModel->getByEmail($email);

        if (!$usuario || !password_verify($password, $usuario['password'])) {
            // Registrar intento fallido
            $stmt = $db->prepare("INSERT INTO login_intentos (ip, email) VALUES (?, ?)");
            $stmt->execute([$ip, $email]);

            $restantes = self::MAX_INTENTOS - $intentos - 1;
            $msg = 'Credenciales incorrectas.';
            if ($restantes > 0) {
                $msg .= " Te quedan $restantes intentos antes del bloqueo temporal.";
            }
            $this->flash('error', $msg);
            $this->redirect('auth/login');
        }

        // Cuenta pendiente de verificación
        if (empty($usuario['activo'])) {
            $this->flash('error', 'Tu cuenta no está activa. Revisa tu correo y haz clic en el enlace de verificación.');
            $this->redirect('auth/login');
        }

        // Login exitoso — limpiar intentos fallidos de esta IP
        $stmt = $db->prepare("DELETE FROM login_intentos WHERE ip = ?");
        $stmt->execute([$ip]);

        $_SESSION['usuario'] = $usuario;

        if (!empty($usuario['empresa_id'])) {
            $empresaModel = new EmpresaModel();
            $_SESSION['empresa'] = $empresaModel->find($usuario['empresa_id']);
        }

        $this->log('Login exitoso', 'auth');

        $this->redirectSegunRol($usuario['rol_slug']);
    }

    public function logout(?string $p = null): void
    {
        $this->log('Logout', 'auth');
        session_destroy();
        header('Location: ' . BASE_URL . 'auth/login');
        exit;
    }
}
