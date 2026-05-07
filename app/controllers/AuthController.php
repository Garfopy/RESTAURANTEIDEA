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

        // Verificar que el email esté verificado
        if (empty($usuario['email_verificado'])) {
            $this->flash('error', 'Debes verificar tu email antes de iniciar sesión. Revisa tu bandeja de entrada (y spam) y haz clic en el link de verificación.');
            $this->redirect('auth/login');
        }

        // Cuenta inactiva
        if (empty($usuario['activo'])) {
            $this->flash('error', 'Tu cuenta está desactivada. Contacta al administrador.');
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

    public function verificar(?string $p = null): void
    {
        $token = trim($_GET['token'] ?? '');

        error_log("[AuthController::verificar] Iniciando verificación. Token presente: " . ($token ? 'SÍ' : 'NO'));

        if (!$token) {
            error_log("[AuthController::verificar] ERROR: Token vacío o no proporcionado");
            $this->flash('error', 'Token de verificación inválido.');
            $this->redirect('auth/login');
        }

        $db = Database::getInstance();
        $stmt = $db->prepare(
            "SELECT id, email, nombre, apellido_paterno, token_expira, email_verificado
             FROM usuarios
             WHERE token_verificacion = ?
             LIMIT 1"
        );
        $stmt->execute([$token]);
        $usuario = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$usuario) {
            error_log("[AuthController::verificar] ERROR: Usuario no encontrado con token: " . substr($token, 0, 10) . "...");
            $this->flash('error', 'El link de verificación no es válido o ya fue usado.');
            $this->redirect('auth/login');
        }

        error_log("[AuthController::verificar] Usuario encontrado: {$usuario['email']} (ID: {$usuario['id']})");

        // Verificar si ya está verificado
        if ($usuario['email_verificado']) {
            error_log("[AuthController::verificar] Email ya verificado previamente para: {$usuario['email']}");
            $nombreCompleto = $usuario['nombre'] . ' ' . $usuario['apellido_paterno'];
            $this->flash('success', "Tu email ya está verificado, $nombreCompleto. Puedes iniciar sesión.");
            $this->redirect('auth/login');
        }

        // Verificar si el token expiró
        $expira = strtotime($usuario['token_expira']);
        if ($expira < time()) {
            error_log("[AuthController::verificar] ERROR: Token expirado para: {$usuario['email']}");
            $this->flash('error', 'El link de verificación ha expirado. Contacta al administrador para reenviar el email.');
            $this->redirect('auth/login');
        }

        // Marcar email como verificado
        $stmt = $db->prepare(
            "UPDATE usuarios
             SET email_verificado = 1,
                 token_verificacion = NULL,
                 token_expira = NULL
             WHERE id = ?"
        );
        $stmt->execute([$usuario['id']]);

        error_log("[AuthController::verificar] Email verificado exitosamente para: {$usuario['email']}");
        $this->log('Email verificado', 'auth', "Usuario ID: {$usuario['id']}");

        $nombreCompleto = $usuario['nombre'] . ' ' . $usuario['apellido_paterno'];
        $this->flash('success', "¡Email verificado correctamente! Hola $nombreCompleto, ya puedes iniciar sesión.");
        $this->redirect('auth/login');
    }

    public function logout(?string $p = null): void
    {
        $this->log('Logout', 'auth');
        session_destroy();
        header('Location: ' . BASE_URL . 'auth/login');
        exit;
    }
}
