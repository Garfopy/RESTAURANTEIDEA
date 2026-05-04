<?php
require_once ROOT_PATH . '/app/controllers/BaseController.php';

class RegistroController extends BaseController
{
    // GET /registro/index
    public function index(?string $p = null): void
    {
        if (isset($_SESSION['usuario'])) { $this->redirectSegunRol($_SESSION['usuario']['rol_slug'] ?? ''); }
        $this->render('auth/registro', ['pageTitle' => 'Crear cuenta — CarniHub']);
    }

    // GET /registro/comprador
    public function comprador(?string $p = null): void
    {
        if (isset($_SESSION['usuario'])) { $this->redirectSegunRol($_SESSION['usuario']['rol_slug'] ?? ''); }
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
        if (isset($_SESSION['usuario'])) { $this->redirectSegunRol($_SESSION['usuario']['rol_slug'] ?? ''); }
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

        // Email único (incluyendo cuentas pendientes de verificación)
        $stmtEmail = $db->prepare("SELECT id FROM usuarios WHERE email = ? LIMIT 1");
        $stmtEmail->execute([$email]);
        if ($stmtEmail->fetchColumn()) {
            $this->flash('error', 'Este correo ya está registrado. Revisa tu bandeja (incluyendo spam) o usa otro correo.');
            $this->redirect('registro/' . $tipo);
        }

        // Obtener rol_id
        // Quien crea una empresa al registrarse es el Administrador Empresa (RF-U02)
        // Los repartidores siempre obtienen el rol repartidor
        $rolSlug = match($tipo) {
            'repartidor' => 'repartidor',
            default      => 'admin_empresa',  // el que crea la empresa es su admin
        };
        $stmt = $db->prepare("SELECT id FROM roles WHERE slug = ? LIMIT 1");
        $stmt->execute([$rolSlug]);
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
            $stmt = $db->prepare("INSERT INTO empresas (razon_social, tipo_negocio, rfc, activo, created_at) VALUES (?, ?, NULL, 0, NOW())");
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
        $this->flash('success', '¡Correo verificado exitosamente, ' . $nombreCompleto . '! Tu cuenta está activa. Ya puedes iniciar sesión.');
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
        $host   = $_SERVER['HTTP_HOST'] ?? 'carnihub.mx';

        $html = '<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Verifica tu cuenta</title>
</head>
<body style="margin:0;padding:0;background:#F3F4F6;font-family:Arial,Helvetica,sans-serif">
  <table width="100%" cellpadding="0" cellspacing="0" border="0" style="background:#F3F4F6;padding:40px 16px">
    <tr>
      <td align="center">
        <table width="100%" cellpadding="0" cellspacing="0" border="0" style="max-width:560px">

          <!-- Logo / Header -->
          <tr>
            <td align="center" style="padding-bottom:24px">
              <div style="display:inline-block;background:#C8102E;border-radius:12px;padding:12px 28px">
                <span style="color:#fff;font-size:22px;font-weight:800;letter-spacing:1px">CarniHub</span>
              </div>
            </td>
          </tr>

          <!-- Card -->
          <tr>
            <td style="background:#fff;border-radius:16px;box-shadow:0 4px 24px rgba(0,0,0,.07);overflow:hidden">

              <!-- Red top bar -->
              <table width="100%" cellpadding="0" cellspacing="0" border="0">
                <tr><td style="background:#C8102E;height:6px;font-size:0">&nbsp;</td></tr>
              </table>

              <!-- Body -->
              <table width="100%" cellpadding="0" cellspacing="0" border="0">
                <tr>
                  <td style="padding:40px 40px 32px">

                    <p style="margin:0 0 8px;font-size:28px;text-align:center">📧</p>
                    <h1 style="margin:0 0 16px;font-size:22px;font-weight:800;color:#111827;text-align:center">
                      ¡Hola, ' . htmlspecialchars($nombre) . '!
                    </h1>
                    <p style="margin:0 0 24px;font-size:15px;color:#4B5563;line-height:1.7;text-align:center">
                      Gracias por registrarte en <strong>CarniHub — Abasto Inteligente de Carne</strong>.<br>
                      Solo falta un paso: confirma tu correo electrónico.
                    </p>

                    <!-- CTA Button -->
                    <table width="100%" cellpadding="0" cellspacing="0" border="0">
                      <tr>
                        <td align="center" style="padding-bottom:28px">
                          <a href="' . $url . '"
                             style="display:inline-block;background:#C8102E;color:#fff;font-size:15px;font-weight:700;
                                    text-decoration:none;padding:14px 40px;border-radius:8px;letter-spacing:.3px">
                            Verificar mi correo
                          </a>
                        </td>
                      </tr>
                    </table>

                    <!-- Info box -->
                    <table width="100%" cellpadding="0" cellspacing="0" border="0">
                      <tr>
                        <td style="background:#FEF2F2;border-radius:10px;padding:16px 20px">
                          <p style="margin:0 0 6px;font-size:13px;font-weight:700;color:#991B1B">
                            ¿No funciona el botón?
                          </p>
                          <p style="margin:0;font-size:12px;color:#6B7280;word-break:break-all;line-height:1.6">
                            Copia y pega este enlace en tu navegador:<br>
                            <a href="' . $url . '" style="color:#C8102E">' . $url . '</a>
                          </p>
                        </td>
                      </tr>
                    </table>

                  </td>
                </tr>
              </table>

              <!-- Footer inside card -->
              <table width="100%" cellpadding="0" cellspacing="0" border="0">
                <tr>
                  <td style="background:#F9FAFB;border-top:1px solid #E5E7EB;padding:20px 40px">
                    <p style="margin:0;font-size:12px;color:#9CA3AF;text-align:center;line-height:1.6">
                      Este enlace es válido por <strong>24 horas</strong>.<br>
                      Si no solicitaste este registro, puedes ignorar este mensaje.
                    </p>
                  </td>
                </tr>
              </table>

            </td>
          </tr>

          <!-- Footer -->
          <tr>
            <td style="padding-top:24px;text-align:center">
              <p style="margin:0;font-size:12px;color:#9CA3AF">
                &copy; ' . date('Y') . ' CarniHub &mdash; Todos los derechos reservados
              </p>
            </td>
          </tr>

        </table>
      </td>
    </tr>
  </table>
</body>
</html>';

        $headers = implode("\r\n", [
            'From: CarniHub <noreply@' . $host . '>',
            'Reply-To: contacto@carnihub.mx',
            'MIME-Version: 1.0',
            'Content-Type: text/html; charset=UTF-8',
            'X-Mailer: PHP/' . PHP_VERSION,
        ]);

        mail($email, $asunto, $html, $headers);
    }
}
