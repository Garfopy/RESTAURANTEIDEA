<?php
require_once ROOT_PATH . '/app/controllers/BaseController.php';

class RegistroController extends BaseController
{
    // GET /registro/index
    public function index(?string $p = null): void
    {
        if (isset($_SESSION['usuario'])) { $this->redirect('dashboard/index'); }
        $this->render('auth/registro', ['pageTitle' => 'Crear cuenta — CarniHub']);
    }

    // GET /registro/comprador
    public function comprador(?string $p = null): void
    {
        if (isset($_SESSION['usuario'])) { $this->redirect('dashboard/index'); }
        $flash = $this->getFlash();
        $this->render('auth/registro_form', [
            'pageTitle' => 'Registro Comprador',
            'tipo'      => 'comprador',
            'flash'     => $flash,
            'mapsKey'   => $this->getMapsKey(),
        ]);
    }

    // GET /registro/repartidor
    public function repartidor(?string $p = null): void
    {
        if (isset($_SESSION['usuario'])) { $this->redirect('dashboard/index'); }
        $flash = $this->getFlash();
        $this->render('auth/registro_form', [
            'pageTitle' => 'Registro Repartidor',
            'tipo'      => 'repartidor',
            'flash'     => $flash,
            'mapsKey'   => $this->getMapsKey(),
        ]);
    }

    // POST /registro/guardar
    public function guardar(?string $p = null): void
    {
        if (!$this->isPost()) { $this->redirect('registro/index'); }

        $tipo = $this->post('tipo', '');
        if (!in_array($tipo, ['comprador', 'repartidor'], true)) {
            $this->redirect('registro/index');
        }

        $nombre           = trim($this->post('nombre', ''));
        $apellido_paterno = trim($this->post('apellido_paterno', ''));
        $apellido_materno = trim($this->post('apellido_materno', ''));
        $email            = strtolower(trim($this->post('email', '')));
        $password         = $this->post('password', '');
        $confirmar        = $this->post('confirmar_password', '');
        $telefono         = trim($this->post('telefono', ''));
        $ubicacion        = trim($this->post('ubicacion', ''));
        $ubicacion_lat    = $this->post('ubicacion_lat', '');
        $ubicacion_lng    = $this->post('ubicacion_lng', '');
        $nombre_empresa   = trim($this->post('nombre_empresa', ''));

        // Tipo de negocio: si es "otro", usar campo libre
        $tipo_negocio = trim($this->post('tipo_negocio', ''));
        if ($tipo_negocio === 'otro') {
            $tipo_negocio_libre = trim($this->post('tipo_negocio_otro', ''));
            $tipo_negocio = $tipo_negocio_libre ?: 'otro';
        }

        // Validaciones
        $errores = [];
        if (!$nombre)                                                  $errores[] = 'El nombre es requerido.';
        if (!$apellido_paterno)                                        $errores[] = 'El apellido paterno es requerido.';
        if (!$email || !filter_var($email, FILTER_VALIDATE_EMAIL))    $errores[] = 'Correo electrónico inválido.';
        if (strlen($password) < 8)                                     $errores[] = 'La contraseña debe tener al menos 8 caracteres.';
        if ($password !== $confirmar)                                  $errores[] = 'Las contraseñas no coinciden.';
        if (!$telefono)                                                $errores[] = 'El teléfono es requerido.';
        if (!$ubicacion)                                               $errores[] = 'La ubicación es requerida.';
        if ($tipo === 'comprador' && !$nombre_empresa)                 $errores[] = 'El nombre del negocio es requerido.';

        if ($errores) {
            $this->flash('error', implode(' ', $errores));
            $this->redirect('registro/' . $tipo);
        }

        $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
        $db = Database::getInstance();

        // Rate limit: máx 5 registros por IP en 1 hora
        $stmt = $db->prepare(
            "SELECT COUNT(*) FROM registro_intentos WHERE ip = ? AND created_at > DATE_SUB(NOW(), INTERVAL 1 HOUR)"
        );
        $stmt->execute([$ip]);
        if ((int)$stmt->fetchColumn() >= 5) {
            $this->flash('error', 'Demasiados intentos de registro desde tu conexión. Intenta más tarde.');
            $this->redirect('registro/' . $tipo);
        }

        // Email único
        $usuarioModel = new UsuarioModel();
        if ($usuarioModel->getByEmail($email)) {
            $this->flash('error', 'Este correo ya está registrado. ¿Olvidaste tu contraseña?');
            $this->redirect('registro/' . $tipo);
        }

        // Obtener rol_id
        $stmt = $db->prepare("SELECT id FROM roles WHERE slug = ? LIMIT 1");
        $stmt->execute([$tipo === 'repartidor' ? 'repartidor' : 'comprador']);
        $rol = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$rol) {
            $this->flash('error', 'Error interno. Intenta de nuevo.');
            $this->redirect('registro/' . $tipo);
        }

        // Insertar usuario (activo = 0 hasta verificar correo)
        $hash = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $db->prepare("
            INSERT INTO usuarios
              (nombre, apellido_paterno, apellido_materno, email, password,
               rol_id, activo, telefono, ubicacion_texto, ubicacion_lat, ubicacion_lng)
            VALUES (?, ?, ?, ?, ?, ?, 0, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $nombre,
            $apellido_paterno,
            $apellido_materno ?: null,
            $email,
            $hash,
            $rol['id'],
            $telefono,
            $ubicacion,
            $ubicacion_lat ?: null,
            $ubicacion_lng ?: null,
        ]);
        $userId = (int)$db->lastInsertId();

        // Si es comprador, crear empresa y asociarla
        if ($tipo === 'comprador') {
            $stmt = $db->prepare("INSERT INTO empresas (razon_social, tipo_negocio, activo, created_at) VALUES (?, ?, 0, NOW())");
            $stmt->execute([$nombre_empresa, $tipo_negocio ?: null]);
            $empresaId = (int)$db->lastInsertId();
            $db->prepare("UPDATE usuarios SET empresa_id = ? WHERE id = ?")->execute([$empresaId, $userId]);
        }

        // Token de verificación (expira en 24 h)
        $token = bin2hex(random_bytes(32));
        $stmt  = $db->prepare("INSERT INTO verificacion_tokens (usuario_id, token, tipo, expires_at) VALUES (?, ?, 'email_verificacion', DATE_ADD(NOW(), INTERVAL 24 HOUR))");
        $stmt->execute([$userId, $token]);

        // Registrar intento
        $db->prepare("INSERT INTO registro_intentos (ip, email) VALUES (?, ?)")->execute([$ip, $email]);

        // Enviar correo de verificación
        $this->enviarEmailVerificacion($email, $nombre, $token);

        $this->flash('success', 'Cuenta creada. Revisa tu correo (' . htmlspecialchars($email) . ') y haz clic en el enlace de verificación.');
        $this->redirect('registro/pendiente');
    }

    // GET /registro/verificar/{token}
    public function verificar(?string $token = null): void
    {
        if (!$token) {
            $this->flash('error', 'Enlace inválido.');
            $this->redirect('auth/login');
        }

        $db   = Database::getInstance();
        $stmt = $db->prepare("
            SELECT vt.id, vt.usuario_id, u.nombre, u.apellido_paterno
            FROM verificacion_tokens vt
            JOIN usuarios u ON vt.usuario_id = u.id
            WHERE vt.token = ?
              AND vt.tipo  = 'email_verificacion'
              AND vt.usado = 0
              AND vt.expires_at > NOW()
            LIMIT 1
        ");
        $stmt->execute([$token]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$row) {
            $this->flash('error', 'El enlace de verificación es inválido o ya expiró. Regístrate de nuevo.');
            $this->redirect('auth/login');
        }

        // Activar usuario y marcar token
        $db->prepare("UPDATE usuarios SET activo = 1 WHERE id = ?")->execute([$row['usuario_id']]);
        $db->prepare("UPDATE verificacion_tokens SET usado = 1 WHERE id = ?")->execute([$row['id']]);

        $nombreCompleto = htmlspecialchars($row['nombre'] . ' ' . $row['apellido_paterno']);
        $this->flash('success', '✓ ¡Correo verificado exitosamente, ' . $nombreCompleto . '! Tu cuenta está activa. Ya puedes iniciar sesión.');
        $this->redirect('auth/login');
    }

    // GET /registro/pendiente
    public function pendiente(?string $p = null): void
    {
        $flash = $this->getFlash();
        $this->render('auth/verificar_pendiente', [
            'pageTitle' => 'Verifica tu correo',
            'flash'     => $flash,
        ]);
    }

    // ── helpers ────────────────────────────────────────────────────────────────

    private function getMapsKey(): string
    {
        try {
            $configModel = new ConfigModel();
            $key = $configModel->get('api_google_maps_key', '');
            if ($key) return $key;
        } catch (Exception $e) {}
        return defined('GOOGLE_MAPS_KEY') ? GOOGLE_MAPS_KEY : '';
    }

    private function enviarEmailVerificacion(string $email, string $nombre, string $token): void
    {
        $url    = BASE_URL . 'registro/verificar/' . $token;
        $asunto = 'Verifica tu cuenta en CarniHub';
        $cuerpo = "Hola $nombre,\r\n\r\n"
                . "Gracias por registrarte en CarniHub — Abasto Inteligente de Carne.\r\n\r\n"
                . "Haz clic en el siguiente enlace para verificar tu correo electrónico:\r\n\r\n"
                . "$url\r\n\r\n"
                . "Este enlace es válido por 24 horas.\r\n\r\n"
                . "Si no solicitaste este registro, ignora este mensaje.\r\n\r\n"
                . "— Equipo CarniHub";

        $headers = implode("\r\n", [
            'From: CarniHub <noreply@' . ($_SERVER['HTTP_HOST'] ?? 'carnihub.mx') . '>',
            'Reply-To: contacto@carnihub.mx',
            'MIME-Version: 1.0',
            'Content-Type: text/plain; charset=UTF-8',
            'X-Mailer: PHP/' . PHP_VERSION,
        ]);

        mail($email, $asunto, $cuerpo, $headers);
    }
}
