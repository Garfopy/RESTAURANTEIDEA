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

        // Verificar si es primer login después de verificación
        if ($usuario['email_verificado'] && empty($usuario['primer_login_completado'])) {
            $this->flash('first_login', '¡Bienvenido! Te recomendamos cambiar tu contraseña para mayor seguridad.');
        }

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

        // Si encontramos un usuario existente, procesar verificación normal
        if ($usuario) {
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

        // Si no es un usuario existente, buscar en registros pendientes
        error_log("[AuthController::verificar] Usuario no encontrado en tabla usuarios, buscando en registros pendientes...");

        require_once ROOT_PATH . '/app/models/RegistroPendienteModel.php';
        $regModel = new RegistroPendienteModel();
        $registro = $regModel->getByToken($token);

        if (!$registro) {
            error_log("[AuthController::verificar] ERROR: No se encontró registro pendiente con token");
            $this->flash('error', 'El link de verificación no es válido o ya fue usado.');
            $this->redirect('auth/login');
        }

        error_log("[AuthController::verificar] Registro pendiente encontrado: {$registro['email']} (ID: {$registro['id']})");

        // Verificar si ya fue completado
        if ($registro['estado'] === 'completado') {
            error_log("[AuthController::verificar] Registro ya completado: {$registro['email']}");

            // Buscar usuario existente e iniciar sesión automáticamente
            $userModel = new UsuarioModel();
            $usuarioExistente = $userModel->getByEmail($registro['email']);

            if ($usuarioExistente && $usuarioExistente['email_verificado']) {
                // Obtener datos completos del usuario
                $db = Database::getInstance();
                $stmtUsuarioCompleto = $db->prepare(
                    "SELECT u.*, r.slug AS rol_slug, r.nombre AS rol_nombre, e.razon_social AS empresa_nombre
                     FROM usuarios u
                     INNER JOIN roles r ON r.id = u.rol_id
                     LEFT JOIN empresas e ON e.id = u.empresa_id
                     WHERE u.id = ?
                     LIMIT 1"
                );
                $stmtUsuarioCompleto->execute([$usuarioExistente['id']]);
                $usuarioCompleto = $stmtUsuarioCompleto->fetch(PDO::FETCH_ASSOC);

                if ($usuarioCompleto) {
                    $_SESSION['usuario'] = $usuarioCompleto;
                    $this->log('Login automático desde link de verificación ya usado', 'auth', "Usuario ID: {$usuarioExistente['id']}");
                    error_log("[AuthController::verificar] Login automático para usuario ya verificado");
                    $this->flash('success', '¡Bienvenido de vuelta! Tu cuenta ya estaba activada.');
                    $this->redirect('empresa/');
                }
            }

            $this->flash('success', 'Tu cuenta ya fue activada. Puedes iniciar sesión.');
            $this->redirect('auth/login');
        }

        // Verificar token expirado
        $expira = strtotime($registro['token_expira']);
        if ($expira < time()) {
            error_log("[AuthController::verificar] ERROR: Token de registro expirado para: {$registro['email']}");
            $this->flash('error', 'El link de verificación ha expirado. Contacta a soporte.');
            $this->redirect('auth/login');
        }

        // Verificar que el pago esté confirmado
        if (empty($registro['paypal_subscription_id']) || $registro['estado'] !== 'pendiente_verificacion') {
            error_log("[AuthController::verificar] ERROR: Pago no confirmado para registro: {$registro['email']}");
            $this->flash('error', 'El pago aún no ha sido confirmado. Intenta más tarde.');
            $this->redirect('auth/login');
        }

        // Procesar la creación de empresa y usuario
        try {
            $db->beginTransaction();

            $datosEmpresa = json_decode($registro['datos_empresa'], true);
            error_log("[AuthController::verificar] Procesando empresa: {$datosEmpresa['razon_social']}");

            // 1. Verificar si la empresa ya existe (por RFC o email)
            $stmtCheck = $db->prepare(
                "SELECT id FROM empresas WHERE rfc = ? OR email = ? LIMIT 1"
            );
            $stmtCheck->execute([$datosEmpresa['rfc'], $registro['email']]);
            $empresaExistente = $stmtCheck->fetch(PDO::FETCH_ASSOC);

            if ($empresaExistente) {
                $empresaId = $empresaExistente['id'];
                error_log("[AuthController::verificar] Empresa ya existe con ID: $empresaId");
            } else {
                // Crear nueva empresa
                $stmtEmpresa = $db->prepare(
                    "INSERT INTO empresas (razon_social, rfc, telefono, email, suscripcion_estado, activo)
                     VALUES (?, ?, ?, ?, 'activo', 1)"
                );
                $stmtEmpresa->execute([
                    $datosEmpresa['razon_social'],
                    $datosEmpresa['rfc'],
                    $datosEmpresa['telefono'] ?? '',
                    $registro['email'],
                ]);
                $empresaId = $db->lastInsertId();
                error_log("[AuthController::verificar] Empresa creada con ID: $empresaId");
            }

            // 2. Verificar/crear suscripción
            $stmtCheckSus = $db->prepare(
                "SELECT id FROM suscripciones WHERE empresa_id = ? LIMIT 1"
            );
            $stmtCheckSus->execute([$empresaId]);
            $suscripcionExistente = $stmtCheckSus->fetch(PDO::FETCH_ASSOC);

            if ($suscripcionExistente) {
                error_log("[AuthController::verificar] Suscripción ya existe para empresa $empresaId");
                // Actualizar suscripción existente
                $stmtUpdateSus = $db->prepare(
                    "UPDATE suscripciones
                     SET plan_id = ?, estado = 'activo', ciclo = ?,
                         paypal_subscription_id = ?, paypal_status = ?
                     WHERE empresa_id = ?"
                );
                $stmtUpdateSus->execute([
                    $registro['plan_id'],
                    $registro['ciclo'],
                    $registro['paypal_subscription_id'],
                    $registro['paypal_status'],
                    $empresaId,
                ]);
            } else {
                // Crear nueva suscripción
                $stmtSus = $db->prepare(
                    "INSERT INTO suscripciones
                     (empresa_id, plan_id, estado, ciclo, fecha_inicio, paypal_subscription_id, paypal_status)
                     VALUES (?, ?, 'activo', ?, CURDATE(), ?, ?)"
                );
                $stmtSus->execute([
                    $empresaId,
                    $registro['plan_id'],
                    $registro['ciclo'],
                    $registro['paypal_subscription_id'],
                    $registro['paypal_status'],
                ]);
                error_log("[AuthController::verificar] Suscripción creada");
            }

            // 3. Verificar/crear usuario admin_empresa
            $stmtCheckUser = $db->prepare(
                "SELECT id FROM usuarios WHERE email = ? LIMIT 1"
            );
            $stmtCheckUser->execute([$registro['email']]);
            $usuarioExistente = $stmtCheckUser->fetch(PDO::FETCH_ASSOC);

            if ($usuarioExistente) {
                $usuarioId = $usuarioExistente['id'];
                error_log("[AuthController::verificar] Usuario ya existe con ID: $usuarioId");
            } else {
                // Crear nuevo usuario
                $nombrePartes = explode(' ', $datosEmpresa['razon_social'], 2);
                $nombre = $nombrePartes[0];
                $apellido = $nombrePartes[1] ?? '';

                // Obtener rol admin_empresa
                $stmtRol = $db->prepare("SELECT id FROM roles WHERE slug = 'admin_empresa' LIMIT 1");
                $stmtRol->execute();
                $rolId = $stmtRol->fetchColumn();

                $stmtUser = $db->prepare(
                    "INSERT INTO usuarios
                     (empresa_id, rol_id, nombre, apellido_paterno, email, password, email_verificado, primer_login_completado, activo)
                     VALUES (?, ?, ?, ?, ?, ?, 1, 0, 1)"
                );
                $stmtUser->execute([
                    $empresaId,
                    $rolId,
                    $nombre,
                    $apellido,
                    $registro['email'],
                    $registro['password_hash'],
                ]);
                $usuarioId = $db->lastInsertId();
                error_log("[AuthController::verificar] Usuario admin_empresa creado con ID: $usuarioId");
            }

            // 4. Marcar registro como completado (solo si aún no está completado)
            if ($registro['estado'] !== 'completado') {
                $regModel->marcarCompletado($registro['id']);
                error_log("[AuthController::verificar] Registro marcado como completado");
            } else {
                error_log("[AuthController::verificar] Registro ya estaba completado");
            }

            $db->commit();
            error_log("[AuthController::verificar] Registro completado exitosamente para: {$registro['email']}");

            // 5. Redirigir al login (sin iniciar sesión automáticamente)
            $this->log('Registro completado - Redirección a login', 'auth', "Usuario ID: $usuarioId, Empresa ID: $empresaId");

            // Limpiar cualquier sesión activa para que el usuario vea el login
            unset($_SESSION['usuario'], $_SESSION['empresa']);

            error_log("[AuthController::verificar] Registro completado, redirigiendo a login");
            $this->flash('success', '¡Tu cuenta ha sido activada correctamente! Inicia sesión con tus credenciales.');
            $this->redirect('auth/login');

        } catch (\Throwable $e) {
            $db->rollBack();
            error_log("[AuthController::verificar] ERROR al crear cuenta: " . $e->getMessage());
            error_log("[AuthController::verificar] Stack trace: " . $e->getTraceAsString());
            $this->flash('error', 'Error al crear tu cuenta. Contacta a soporte con código: REG-' . $registro['id']);
            $this->redirect('auth/login');
        }
    }

    public function logout(?string $p = null): void
    {
        $this->log('Logout', 'auth');
        session_destroy();
        header('Location: ' . BASE_URL . 'auth/login');
        exit;
    }
}
