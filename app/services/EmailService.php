<?php
/**
 * EmailService
 * EnvÃ­o de emails usando PHPMailer con SMTP
 */

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

if (!class_exists(PHPMailer::class)) {
    $phpMailerSrc = dirname(__DIR__, 2) . '/vendor/phpmailer/phpmailer/src';
    if (is_file($phpMailerSrc . '/Exception.php')
        && is_file($phpMailerSrc . '/PHPMailer.php')
        && is_file($phpMailerSrc . '/SMTP.php')) {
        require_once $phpMailerSrc . '/Exception.php';
        require_once $phpMailerSrc . '/PHPMailer.php';
        require_once $phpMailerSrc . '/SMTP.php';
    }
}

class EmailService
{
    private string $smtpHost;
    private string $smtpPort;
    private string $smtpEncryption;
    private string $smtpUsername;
    private string $smtpPassword;
    private string $smtpFromEmail;
    private string $smtpFromName;
    private bool $configured;
    private bool $nativeMailAvailable;
    private bool $phpMailerAvailable;

    // Cache estÃ¡tico para evitar mÃºltiples consultas DB por request
    private static ?array $configCache = null;

    public function __construct()
    {
        // Cargar configuraciÃ³n una sola vez por request
        if (self::$configCache === null) {
            $db = Database::getInstance();
            $stmt = $db->prepare("SELECT valor FROM global_settings WHERE clave = ? LIMIT 1");
            $get = function (array $keys, string $default = '') use ($stmt): string {
                foreach ($keys as $key) {
                    $stmt->execute([$key]);
                    $value = $stmt->fetchColumn();
                    if ($value !== false && $value !== null && trim((string)$value) !== '') {
                        return (string)$value;
                    }
                }

                return $default;
            };

            self::$configCache = [
                'host'       => $get(['smtp_host']),
                'port'       => $get(['smtp_port'], '587'),
                'encryption' => $this->normalizarEncryption($get(['smtp_encryption'], 'tls')),
                'username'   => $get(['smtp_username', 'smtp_user']),
                'password'   => $get(['smtp_password', 'smtp_pass']),
                'from_email' => $get(['smtp_from_email', 'smtp_from', 'smtp_username', 'smtp_user']),
                'from_name'  => $get(['smtp_from_name', 'app_name'], 'CarniHub'),
            ];

            self::$configCache['from_email'] = self::$configCache['from_email'] ?: self::$configCache['username'];
        }

        $this->smtpHost       = self::$configCache['host'];
        $this->smtpPort       = self::$configCache['port'];
        $this->smtpEncryption = self::$configCache['encryption'];
        $this->smtpUsername   = self::$configCache['username'];
        $this->smtpPassword   = self::$configCache['password'];
        $this->smtpFromEmail  = self::$configCache['from_email'];
        $this->smtpFromName   = self::$configCache['from_name'];
        $this->nativeMailAvailable = function_exists('mail');
        $this->phpMailerAvailable = class_exists(PHPMailer::class);

        // Verificar si la configuraciÃ³n estÃ¡ completa
        $this->configured = !empty($this->smtpHost)
                         && !empty($this->smtpUsername)
                         && !empty($this->smtpPassword)
                         && !empty($this->smtpFromEmail);
    }

    private function registrarFallbackMailer(string $contexto): void
    {
        if (defined('EMAIL_DEBUG') && EMAIL_DEBUG) {
            error_log("[EmailService] PHPMailer no esta instalado. $contexto se intentara enviar con mail() nativo.");
        }
    }

    private function normalizarEncryption(string $value): string
    {
        $value = strtolower(trim($value));

        switch ($value) {
            case 'starttls':
                return 'tls';
            case 'smtps':
                return 'ssl';
            default:
                return $value;
        }
    }

    private function fromEmail(): string
    {
        if (!empty($this->smtpFromEmail)) {
            return $this->smtpFromEmail;
        }

        if (!empty($this->smtpUsername) && filter_var($this->smtpUsername, FILTER_VALIDATE_EMAIL)) {
            return $this->smtpUsername;
        }

        $host = preg_replace('/^www\./', '', (string)($_SERVER['HTTP_HOST'] ?? 'localhost'));
        $host = preg_replace('/:\d+$/', '', $host);
        return 'no-reply@' . ($host ?: 'localhost');
    }

    private function fromName(): string
    {
        return $this->smtpFromName ?: 'CarniHub';
    }

    private function encodeHeader(string $text): string
    {
        if (function_exists('mb_encode_mimeheader')) {
            return mb_encode_mimeheader($text, 'UTF-8', 'B', "\r\n");
        }

        return $text;
    }

    private function enviarConMailNativo(
        string $destEmail,
        string $subject,
        string $htmlBody,
        string $altBody,
        ?string $replyTo = null
    ): bool {
        if (!$this->nativeMailAvailable || !$destEmail) {
            return false;
        }

        $fromEmail = $this->fromEmail();
        $fromName  = $this->fromName();
        $replyTo   = $replyTo ?: $fromEmail;

        $headers = [
            'MIME-Version: 1.0',
            'Content-Type: text/html; charset=UTF-8',
            'From: ' . $this->encodeHeader($fromName) . ' <' . $fromEmail . '>',
            'Reply-To: ' . $replyTo,
            'X-Mailer: PHP/' . PHP_VERSION,
        ];

        $ok = @mail(
            $destEmail,
            $this->encodeHeader($subject),
            $htmlBody,
            implode("\r\n", $headers)
        );

        if ($ok) {
            if (defined('EMAIL_DEBUG') && EMAIL_DEBUG) {
                error_log("[EmailService] Email enviado via mail() nativo a: $destEmail");
            }
            return true;
        }

        error_log("[EmailService] mail() nativo fallo para: $destEmail");
        if ($altBody !== '') {
            error_log('[EmailService] AltBody diagnostico: ' . mb_substr($altBody, 0, 180));
        }

        return false;
    }

    /**
     * EnvÃ­a un correo con las credenciales de acceso al usuario nuevo
     *
     * @param array $usuario Array con datos del usuario (nombre, email)
     * @param string $passwordPlano ContraseÃ±a en texto plano (solo para email)
     * @param string|null $ftpUsername Username FTP (opcional)
     * @param string|null $tokenVerificacion Token para verificar email (opcional)
     * @return bool True si se enviÃ³ correctamente
     */
    public function enviarCredenciales(
        array $usuario,
        string $passwordPlano,
        ?string $ftpUsername = null,
        ?string $tokenVerificacion = null
    ): bool {
        // Validar configuraciÃ³n
        if (!$this->configured) {
            error_log("[EmailService] ConfiguraciÃ³n SMTP incompleta. Configure credenciales en /config/correo");
            return false;
        }

        $destinatario = $usuario['email'];
        $nombreUsuario = $usuario['nombre'] . ' ' . ($usuario['apellido_paterno'] ?? '');

        try {
            $mail = new PHPMailer(true);

            // â”€â”€ ConfiguraciÃ³n del servidor SMTP â”€â”€
            $mail->isSMTP();
            $mail->Host       = $this->smtpHost;
            $mail->SMTPAuth   = true;
            $mail->Username   = $this->smtpUsername;
            $mail->Password   = $this->smtpPassword;
            $mail->Port       = (int)$this->smtpPort;

            // Timeout de 10 segundos para conexiÃ³n SMTP
            $mail->Timeout = 10;

            // Opciones SSL/TLS optimizadas para cPanel
            $mail->SMTPOptions = [
                'ssl' => [
                    'verify_peer'       => false,
                    'verify_peer_name'  => false,
                    'allow_self_signed' => true
                ]
            ];

            // Configurar cifrado
            if ($this->smtpEncryption === 'tls') {
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            } elseif ($this->smtpEncryption === 'ssl') {
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
            }

            // Debug (descomentar para troubleshooting)
            // $mail->SMTPDebug = SMTP::DEBUG_SERVER;

            // â”€â”€ Remitente y destinatario â”€â”€
            $mail->setFrom($this->smtpFromEmail, $this->smtpFromName);
            $mail->addAddress($destinatario, trim($nombreUsuario));
            $mail->addReplyTo($this->smtpFromEmail, $this->smtpFromName);

            // â”€â”€ Contenido â”€â”€
            $mail->isHTML(true);
            $mail->CharSet = 'UTF-8';
            $mail->Subject = 'Bienvenido a CarniHub - Verifica tu email';

            $mail->Body = $this->plantillaCredenciales([
                'nombre'      => trim($nombreUsuario),
                'email'       => $destinatario,
                'password'    => $passwordPlano,
                'ftp_user'    => $ftpUsername,
                'url_login'   => BASE_URL . 'auth/login',
                'token'       => $tokenVerificacion
            ]);

            $mail->AltBody = $this->plantillaTextoPlano([
                'nombre'   => trim($nombreUsuario),
                'email'    => $destinatario,
                'password' => $passwordPlano,
                'ftp_user' => $ftpUsername,
                'url_login' => BASE_URL . 'auth/login',
                'token'    => $tokenVerificacion
            ]);

            // â”€â”€ Enviar â”€â”€
            $mail->send();

            error_log("[EmailService] Email enviado exitosamente a: $destinatario");
            return true;

        } catch (Exception $e) {
            error_log("[EmailService] Error al enviar email a $destinatario: {$mail->ErrorInfo}");
            error_log("[EmailService] ExcepciÃ³n: " . $e->getMessage());
            return false;
        }
    }

    /**
     * EnvÃ­a el link de recuperaciÃ³n de contraseÃ±a al usuario
     */
    public function enviarResetPassword(array $usuario, string $token): bool
    {
        if (!$this->configured) {
            error_log("[EmailService] ConfiguraciÃ³n SMTP incompleta. No se puede enviar reset de contraseÃ±a.");
            return false;
        }

        $destinatario  = $usuario['email'];
        $nombreUsuario = trim($usuario['nombre'] . ' ' . ($usuario['apellido_paterno'] ?? ''));
        $urlReset      = BASE_URL . 'auth/reset?token=' . urlencode($token);

        try {
            $mail = new PHPMailer(true);

            $mail->isSMTP();
            $mail->Host       = $this->smtpHost;
            $mail->SMTPAuth   = true;
            $mail->Username   = $this->smtpUsername;
            $mail->Password   = $this->smtpPassword;
            $mail->Port       = (int)$this->smtpPort;
            $mail->Timeout    = 10;
            $mail->SMTPOptions = [
                'ssl' => [
                    'verify_peer'       => false,
                    'verify_peer_name'  => false,
                    'allow_self_signed' => true,
                ],
            ];

            if ($this->smtpEncryption === 'tls') {
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            } elseif ($this->smtpEncryption === 'ssl') {
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
            }

            $mail->setFrom($this->smtpFromEmail, $this->smtpFromName);
            $mail->addAddress($destinatario, $nombreUsuario);
            $mail->addReplyTo($this->smtpFromEmail, $this->smtpFromName);

            $mail->isHTML(true);
            $mail->CharSet = 'UTF-8';
            $mail->Subject = 'Recupera tu contraseÃ±a â€” CarniHub';

            $nombreEsc   = htmlspecialchars($nombreUsuario);
            $urlResetEsc = htmlspecialchars($urlReset);

            $mail->Body = '<!DOCTYPE html>
<html lang="es">
<head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1.0"></head>
<body style="margin:0;padding:0;background-color:#f4f4f4;font-family:Arial,sans-serif;">
<table width="100%" cellpadding="0" cellspacing="0" style="background-color:#f4f4f4;padding:20px 0;">
  <tr><td align="center">
    <table width="600" cellpadding="0" cellspacing="0" style="background-color:#ffffff;border-radius:8px;overflow:hidden;box-shadow:0 2px 8px rgba(0,0,0,0.1);">
      <tr>
        <td style="background:linear-gradient(135deg,#C8102E 0%,#8B0A1F 100%);padding:40px 30px;text-align:center;">
          <h1 style="margin:0;color:#ffffff;font-size:26px;font-weight:bold;">Recupera tu contraseÃ±a</h1>
        </td>
      </tr>
      <tr>
        <td style="padding:40px 30px;">
          <p style="margin:0 0 16px;color:#333;font-size:16px;line-height:1.6;">Hola <strong>' . $nombreEsc . '</strong>,</p>
          <p style="margin:0 0 30px;color:#666;font-size:14px;line-height:1.6;">
            Recibimos una solicitud para restablecer la contraseÃ±a de tu cuenta en CarniHub.
            Haz clic en el botÃ³n de abajo para crear una nueva contraseÃ±a.
          </p>
          <table width="100%" cellpadding="0" cellspacing="0">
            <tr>
              <td align="center" style="padding:10px 0 30px;">
                <a href="' . $urlResetEsc . '" style="display:inline-block;background-color:#C8102E;color:#ffffff;text-decoration:none;padding:14px 40px;border-radius:6px;font-weight:bold;font-size:16px;">Restablecer contraseÃ±a</a>
              </td>
            </tr>
          </table>
          <table width="100%" cellpadding="15" cellspacing="0" style="background-color:#FEF3C7;border-left:4px solid #F59E0B;border-radius:4px;">
            <tr>
              <td>
                <p style="margin:0;color:#92400E;font-size:13px;line-height:1.5;">
                  â± <strong>Este link es vÃ¡lido por 1 hora.</strong> Si no solicitaste este cambio, puedes ignorar este mensaje.
                </p>
              </td>
            </tr>
          </table>
          <p style="margin:24px 0 0;color:#9CA3AF;font-size:12px;">
            Si el botÃ³n no funciona, copia y pega este enlace en tu navegador:<br>
            <a href="' . $urlResetEsc . '" style="color:#C8102E;word-break:break-all;">' . $urlResetEsc . '</a>
          </p>
        </td>
      </tr>
      <tr>
        <td style="background-color:#f8f9fa;padding:20px 30px;text-align:center;border-top:1px solid #e9ecef;">
          <p style="margin:0 0 10px;color:#6c757d;font-size:12px;">Â© ' . date('Y') . ' CarniHub â€” Sistema de gestiÃ³n para distribuidores de carne</p>
          <p style="margin:0;"><a href="' . BASE_URL . '" style="color:#C8102E;text-decoration:none;font-size:12px;">Visitar sitio web</a></p>
        </td>
      </tr>
    </table>
  </td></tr>
</table>
</body>
</html>';

            $mail->AltBody = "RECUPERA TU CONTRASEÃ‘A â€” CARNIHUB\n\n"
                . "Hola $nombreUsuario,\n\n"
                . "Recibimos una solicitud para restablecer tu contraseÃ±a.\n"
                . "Haz clic en el siguiente enlace (vÃ¡lido por 1 hora):\n\n"
                . "$urlReset\n\n"
                . "Si no solicitaste este cambio, ignora este mensaje.\n\n"
                . "---\nÂ© " . date('Y') . " CarniHub\n";

            $mail->send();
            error_log("[EmailService] Reset de contraseÃ±a enviado a: $destinatario");
            return true;

        } catch (Exception $e) {
            error_log("[EmailService] Error al enviar reset a $destinatario: {$mail->ErrorInfo}");
            return false;
        }
    }

    /**
     * Notifica al restaurante que llegÃ³ una nueva solicitud de reservaciÃ³n del comensal (vÃ­a QR).
     */
    public function enviarNuevaReserva(
        string $destEmail,
        string $destNombre,
        array $restaurante,
        array $reserva
    ): bool {
        if (!$destEmail) return false;

        $restNombre = htmlspecialchars($restaurante['nombre'] ?? '');
        $nombre     = htmlspecialchars($reserva['nombre'] ?? '');
        $fecha      = $reserva['fecha'] ?? '';
        $hora       = substr($reserva['hora'] ?? '', 0, 5);
        $personas   = (int)($reserva['personas'] ?? 1);
        $telefono   = htmlspecialchars($reserva['telefono'] ?? 'â€”');
        $notas      = htmlspecialchars($reserva['notas'] ?? '');
        $urlAdmin   = BASE_URL . 'rest-reserva/index';

        if (!$this->phpMailerAvailable) {
            $this->registrarFallbackMailer('La notificacion de nueva reserva');
            $subject = "Nueva solicitud de reservacion - $restNombre";
            $notasRow = $notas ? "<tr><td style='color:#6B7280;padding:10px 14px;background:#F9FAFB'>Notas</td><td style='padding:10px 14px;font-style:italic'>$notas</td></tr>" : '';
            $htmlBody = '<!DOCTYPE html><html lang="es"><head><meta charset="UTF-8"></head>
<body style="margin:0;padding:0;background:#F3F4F6;font-family:Arial,sans-serif;">
<table width="100%" cellpadding="0" cellspacing="0" style="background:#F3F4F6;padding:24px 0;">
<tr><td align="center">
<table width="560" cellpadding="0" cellspacing="0" style="background:#fff;border-radius:12px;overflow:hidden;box-shadow:0 2px 8px rgba(0,0,0,.08);">
  <tr><td style="background:#1F2937;padding:28px 30px;">
    <p style="margin:0;color:#9CA3AF;font-size:.78rem;text-transform:uppercase;letter-spacing:.06em;">' . $restNombre . '</p>
    <h1 style="margin:6px 0 0;color:#fff;font-size:1.25rem;font-weight:700;">Nueva solicitud de reservacion</h1>
  </td></tr>
  <tr><td style="padding:28px 30px;">
    <table width="100%" cellpadding="0" cellspacing="0" style="border:1px solid #E5E7EB;border-radius:8px;font-size:.88rem;border-collapse:collapse;">
      <tr><td style="color:#6B7280;padding:10px 14px;width:38%">Nombre</td><td style="padding:10px 14px;font-weight:700">' . $nombre . '</td></tr>
      <tr style="background:#F9FAFB"><td style="color:#6B7280;padding:10px 14px">Telefono</td><td style="padding:10px 14px">' . $telefono . '</td></tr>
      <tr><td style="color:#6B7280;padding:10px 14px">Fecha</td><td style="padding:10px 14px;font-weight:700">' . date('d/m/Y', strtotime($fecha)) . '</td></tr>
      <tr style="background:#F9FAFB"><td style="color:#6B7280;padding:10px 14px">Hora</td><td style="padding:10px 14px;font-weight:700">' . $hora . '</td></tr>
      <tr><td style="color:#6B7280;padding:10px 14px">Personas</td><td style="padding:10px 14px">' . $personas . '</td></tr>
      ' . $notasRow . '
    </table>
    <table width="100%" cellpadding="0" cellspacing="0" style="margin-top:24px;">
      <tr><td align="center">
        <a href="' . $urlAdmin . '" style="display:inline-block;background:#1F2937;color:#fff;text-decoration:none;padding:12px 32px;border-radius:8px;font-weight:700;font-size:.95rem;">
          Ver en panel de reservaciones
        </a>
      </td></tr>
    </table>
  </td></tr>
  <tr><td style="background:#F9FAFB;padding:16px 30px;text-align:center;border-top:1px solid #E5E7EB;">
    <p style="margin:0;color:#9CA3AF;font-size:.72rem;">(c) ' . date('Y') . ' CarniHub</p>
  </td></tr>
</table>
</td></tr></table>
</body></html>';
            $altBody = "Nueva solicitud de reservacion en $restNombre\n\nNombre: {$reserva['nombre']}\nTelefono: {$reserva['telefono']}\nFecha: $fecha\nHora: $hora\nPersonas: $personas" . ($reserva['notas'] ? "\nNotas: {$reserva['notas']}" : '') . "\n\nVer panel: $urlAdmin";
            return $this->enviarConMailNativo($destEmail, $subject, $htmlBody, $altBody);
        }

        try {
            $mail = $this->nuevoMailer();
            $mail->addAddress($destEmail, $destNombre);
            $mail->isHTML(true);
            $mail->CharSet = 'UTF-8';
            $mail->Subject = "ðŸ“… Nueva solicitud de reservaciÃ³n â€” $restNombre";

            $notasRow = $notas ? "<tr><td style='color:#6B7280;padding:10px 14px;background:#F9FAFB'>Notas</td><td style='padding:10px 14px;font-style:italic'>$notas</td></tr>" : '';

            $mail->Body = '<!DOCTYPE html><html lang="es"><head><meta charset="UTF-8"></head>
<body style="margin:0;padding:0;background:#F3F4F6;font-family:Arial,sans-serif;">
<table width="100%" cellpadding="0" cellspacing="0" style="background:#F3F4F6;padding:24px 0;">
<tr><td align="center">
<table width="560" cellpadding="0" cellspacing="0" style="background:#fff;border-radius:12px;overflow:hidden;box-shadow:0 2px 8px rgba(0,0,0,.08);">
  <tr><td style="background:#1F2937;padding:28px 30px;">
    <p style="margin:0;color:#9CA3AF;font-size:.78rem;text-transform:uppercase;letter-spacing:.06em;">' . $restNombre . '</p>
    <h1 style="margin:6px 0 0;color:#fff;font-size:1.25rem;font-weight:700;">ðŸ“… Nueva solicitud de reservaciÃ³n</h1>
  </td></tr>
  <tr><td style="padding:28px 30px;">
    <table width="100%" cellpadding="0" cellspacing="0" style="border:1px solid #E5E7EB;border-radius:8px;font-size:.88rem;border-collapse:collapse;">
      <tr><td style="color:#6B7280;padding:10px 14px;width:38%">Nombre</td><td style="padding:10px 14px;font-weight:700">' . $nombre . '</td></tr>
      <tr style="background:#F9FAFB"><td style="color:#6B7280;padding:10px 14px">TelÃ©fono</td><td style="padding:10px 14px">' . $telefono . '</td></tr>
      <tr><td style="color:#6B7280;padding:10px 14px">Fecha</td><td style="padding:10px 14px;font-weight:700">' . date('d/m/Y', strtotime($fecha)) . '</td></tr>
      <tr style="background:#F9FAFB"><td style="color:#6B7280;padding:10px 14px">Hora</td><td style="padding:10px 14px;font-weight:700">' . $hora . '</td></tr>
      <tr><td style="color:#6B7280;padding:10px 14px">Personas</td><td style="padding:10px 14px">' . $personas . '</td></tr>
      ' . $notasRow . '
    </table>
    <table width="100%" cellpadding="0" cellspacing="0" style="margin-top:24px;">
      <tr><td align="center">
        <a href="' . $urlAdmin . '" style="display:inline-block;background:#1F2937;color:#fff;text-decoration:none;padding:12px 32px;border-radius:8px;font-weight:700;font-size:.95rem;">
          Ver en panel de reservaciones â†’
        </a>
      </td></tr>
    </table>
  </td></tr>
  <tr><td style="background:#F9FAFB;padding:16px 30px;text-align:center;border-top:1px solid #E5E7EB;">
    <p style="margin:0;color:#9CA3AF;font-size:.72rem;">Â© ' . date('Y') . ' CarniHub</p>
  </td></tr>
</table>
</td></tr></table>
</body></html>';

            $mail->AltBody = "Nueva solicitud de reservaciÃ³n en $restNombre\n\n"
                . "Nombre: {$reserva['nombre']}\nTelÃ©fono: {$reserva['telefono']}\n"
                . "Fecha: $fecha  Hora: $hora  Personas: $personas\n"
                . ($reserva['notas'] ? "Notas: {$reserva['notas']}\n" : '')
                . "\nVer panel: $urlAdmin";

            $mail->send();
            error_log("[EmailService] NotificaciÃ³n reserva enviada a: $destEmail");
            return true;
        } catch (\Exception $e) {
            error_log("[EmailService] Error enviarNuevaReserva a $destEmail: {$mail->ErrorInfo}");
            error_log("[EmailService] Excepcion enviarNuevaReserva a $destEmail: {$e->getMessage()}");
            return false;
        }
    }

    /**
     * ConfirmaciÃ³n al comensal de que su reservaciÃ³n fue registrada.
     * Incluye datos de la mesa asignada y link de cancelaciÃ³n.
     */
    public function enviarConfirmacionReserva(
        string $destEmail,
        array $restaurante,
        array $reserva,
        string $cancelUrl
    ): bool {
        if (!$destEmail) return false;

        $restNombre = htmlspecialchars($restaurante['nombre'] ?? '');
        $color      = htmlspecialchars($restaurante['color_primario'] ?? '#C8102E');
        $direccion  = htmlspecialchars($restaurante['direccion'] ?? '');
        $telRest    = htmlspecialchars($restaurante['telefono'] ?? '');
        $nombre     = htmlspecialchars($reserva['nombre'] ?? '');
        $fecha      = $reserva['fecha'] ?? '';
        $hora       = substr($reserva['hora'] ?? '', 0, 5);
        $personas   = (int)($reserva['personas'] ?? 1);
        $mesa       = htmlspecialchars($reserva['mesa_nombre'] ?? 'Por asignar');
        $cancelUrlRaw = $cancelUrl;
        $cancelUrl  = htmlspecialchars($cancelUrl);
        $fechaFmt   = $fecha ? date('d/m/Y', strtotime($fecha)) : '';
        $subjectAmare = "Reserva confirmada - $restNombre";
        $htmlBodyAmare = $this->plantillaReservaAmare(
            $restNombre,
            'Reserva confirmada',
            'Tu mesa está lista',
            'Hola <strong>' . $nombre . '</strong>, preparamos los detalles de tu visita para que llegues con calma.',
            [
                'Fecha' => $fechaFmt,
                'Hora' => $hora,
                'Personas' => (string)$personas,
                'Mesa' => $mesa,
                'Dirección' => $direccion,
                'Teléfono' => $telRest,
            ],
            'Cancelar reservación',
            $cancelUrl,
            'Si tus planes cambian, puedes cancelar desde este enlace.'
        );
        $altBodyAmare = "Tu reservación en $restNombre está confirmada.\n"
            . ($fechaFmt ? "Fecha: $fechaFmt\n" : '')
            . "Hora: $hora\nPersonas: $personas\nMesa: $mesa"
            . ($direccion ? "\nDirección: $direccion" : '')
            . ($telRest ? "\nTeléfono: $telRest" : '')
            . "\n\nCancelar: $cancelUrlRaw";

        if (!$this->phpMailerAvailable) {
            $this->registrarFallbackMailer('La confirmación de reservación');
            $subject = "Reservación confirmada - $restNombre";
            $htmlBody = $this->plantillaReservaAmare(
                $restNombre,
                'Reserva confirmada',
                'Tu mesa está lista',
                'Hola <strong>' . $nombre . '</strong>, preparamos los detalles de tu visita para que llegues con calma.',
                [
                    'Fecha' => date('d/m/Y', strtotime($fecha)),
                    'Hora' => $hora,
                    'Personas' => (string)$personas,
                    'Mesa' => $mesa,
                    'Dirección' => $direccion,
                    'Teléfono' => $telRest,
                ],
                'Cancelar reservación',
                $cancelUrl,
                'Si tus planes cambian, puedes cancelar desde este enlace.'
            );
            $altBody = "Tu reservación en $restNombre está confirmada.\nFecha: " . date('d/m/Y', strtotime($fecha)) . "\nHora: $hora\nPersonas: $personas\nMesa: $mesa" . ($direccion ? "\nDirección: $direccion" : '') . ($telRest ? "\nTeléfono: $telRest" : '') . "\n\nCancelar: $cancelUrlRaw";
            return $this->enviarConMailNativo($destEmail, $subject, $htmlBody, $altBody);
        }

        try {
            $mail = $this->nuevoMailer();
            $mail->addAddress($destEmail, $nombre);
            $mail->isHTML(true);
            $mail->CharSet = 'UTF-8';
            $mail->Subject = "âœ… ReservaciÃ³n confirmada â€” $restNombre";

            $mail->Body = '<!DOCTYPE html><html lang="es"><head><meta charset="UTF-8"></head>
<body style="margin:0;padding:0;background:#F3F4F6;font-family:Arial,sans-serif;">
<table width="100%" cellpadding="0" cellspacing="0" style="background:#F3F4F6;padding:24px 0;">
<tr><td align="center">
<table width="560" cellpadding="0" cellspacing="0" style="background:#fff;border-radius:12px;overflow:hidden;box-shadow:0 2px 8px rgba(0,0,0,.08);">
  <tr><td style="background:' . $color . ';padding:28px 30px;">
    <p style="margin:0;color:rgba(255,255,255,.8);font-size:.78rem;text-transform:uppercase;letter-spacing:.06em;">' . $restNombre . '</p>
    <h1 style="margin:6px 0 0;color:#fff;font-size:1.35rem;font-weight:700;">âœ… Tu reservaciÃ³n estÃ¡ confirmada</h1>
  </td></tr>
  <tr><td style="padding:28px 30px;">
    <p style="margin:0 0 18px;color:#374151;font-size:.95rem;">Hola <strong>' . $nombre . '</strong>, Â¡te esperamos!</p>
    <table width="100%" cellpadding="0" cellspacing="0" style="border:1px solid #E5E7EB;border-radius:8px;font-size:.88rem;border-collapse:collapse;">
      <tr><td style="color:#6B7280;padding:10px 14px;width:38%">Fecha</td><td style="padding:10px 14px;font-weight:700">' . date('d/m/Y', strtotime($fecha)) . '</td></tr>
      <tr style="background:#F9FAFB"><td style="color:#6B7280;padding:10px 14px">Hora</td><td style="padding:10px 14px;font-weight:700">' . $hora . '</td></tr>
      <tr><td style="color:#6B7280;padding:10px 14px">Personas</td><td style="padding:10px 14px">' . $personas . '</td></tr>
      <tr style="background:#F9FAFB"><td style="color:#6B7280;padding:10px 14px">Mesa</td><td style="padding:10px 14px;font-weight:700">' . $mesa . '</td></tr>
      ' . ($direccion ? '<tr><td style="color:#6B7280;padding:10px 14px">DirecciÃ³n</td><td style="padding:10px 14px">' . $direccion . '</td></tr>' : '') . '
      ' . ($telRest   ? '<tr style="background:#F9FAFB"><td style="color:#6B7280;padding:10px 14px">TelÃ©fono</td><td style="padding:10px 14px">' . $telRest . '</td></tr>' : '') . '
    </table>
    <p style="margin:24px 0 0;color:#6B7280;font-size:.82rem;text-align:center;">
      Â¿No podrÃ¡s asistir? <a href="' . $cancelUrl . '" style="color:' . $color . ';font-weight:600">Cancela tu reservaciÃ³n aquÃ­</a>
    </p>
  </td></tr>
  <tr><td style="background:#F9FAFB;padding:16px 30px;text-align:center;border-top:1px solid #E5E7EB;">
    <p style="margin:0;color:#9CA3AF;font-size:.72rem;">Â© ' . date('Y') . ' CarniHub</p>
  </td></tr>
</table>
</td></tr></table>
</body></html>';

            $mail->AltBody = "Tu reservaciÃ³n en $restNombre estÃ¡ confirmada.\n"
                . "Fecha: " . date('d/m/Y', strtotime($fecha)) . "  Hora: $hora\n"
                . "Personas: $personas  Mesa: $mesa\n"
                . ($direccion ? "DirecciÃ³n: $direccion\n" : '')
                . "\nCancelar: $cancelUrl";

            $mail->Subject = $subjectAmare;
            $mail->Body = $htmlBodyAmare;
            $mail->AltBody = $altBodyAmare;
            $mail->send();
            error_log("[EmailService] ConfirmaciÃ³n reserva enviada a: $destEmail");
            return true;
        } catch (\Exception $e) {
            error_log("[EmailService] Error enviarConfirmacionReserva a $destEmail: {$mail->ErrorInfo}");
            error_log("[EmailService] Excepcion enviarConfirmacionReserva a $destEmail: {$e->getMessage()}");
            return false;
        }
    }

    /**
     * Recordatorio 24h antes de la reservaciÃ³n. Disparado por cron.
     */
    public function enviarRecordatorioReserva(
        string $destEmail,
        array $restaurante,
        array $reserva,
        string $cancelUrl
    ): bool {
        if (!$destEmail) return false;

        $restNombre = htmlspecialchars($restaurante['nombre'] ?? '');
        $color      = htmlspecialchars($restaurante['color_primario'] ?? '#C8102E');
        $nombre     = htmlspecialchars($reserva['nombre'] ?? '');
        $fecha      = $reserva['fecha'] ?? '';
        $hora       = substr($reserva['hora'] ?? '', 0, 5);
        $personas   = (int)($reserva['personas'] ?? 1);
        $mesa       = htmlspecialchars($reserva['mesa_nombre'] ?? 'Por asignar');
        $cancelUrlRaw = $cancelUrl;
        $cancelUrl  = htmlspecialchars($cancelUrl);
        $fechaFmt   = $fecha ? date('d/m/Y', strtotime($fecha)) : 'Mañana';
        $subjectAmare = "Mañana te esperamos - $restNombre";
        $htmlBodyAmare = $this->plantillaReservaAmare(
            $restNombre,
            'Recordatorio',
            'Mañana te esperamos',
            'Hola <strong>' . $nombre . '</strong>, tu mesa está reservada para mañana. Ven con calma; nosotros cuidamos los detalles.',
            [
                'Fecha' => $fechaFmt,
                'Hora' => $hora,
                'Personas' => (string)$personas,
                'Mesa' => $mesa,
            ],
            'Cancelar reservación',
            $cancelUrl,
            'Si surge un imprevisto, puedes cancelar tu reservación desde este enlace.'
        );
        $altBodyAmare = "Recordatorio de reservación en $restNombre.\n"
            . "Fecha: $fechaFmt\nHora: $hora\nPersonas: $personas\nMesa: $mesa\n\nCancelar: $cancelUrlRaw";

        if (!$this->phpMailerAvailable) {
            $this->registrarFallbackMailer('El recordatorio de reservación');
            $subject = "Recordatorio de reservación - $restNombre";
            $htmlBody = '<!DOCTYPE html><html lang="es"><head><meta charset="UTF-8"></head>
<body style="margin:0;padding:0;background:#F3F4F6;font-family:Arial,sans-serif;">
<table width="100%" cellpadding="0" cellspacing="0" style="background:#F3F4F6;padding:24px 0;"><tr><td align="center">
<table width="560" cellpadding="0" cellspacing="0" style="background:#fff;border-radius:12px;overflow:hidden;box-shadow:0 2px 8px rgba(0,0,0,.08);">
  <tr><td style="background:' . $color . ';padding:28px 30px;">
    <p style="margin:0;color:rgba(255,255,255,.8);font-size:.78rem;text-transform:uppercase;letter-spacing:.06em;">' . $restNombre . '</p>
    <h1 style="margin:6px 0 0;color:#fff;font-size:1.35rem;font-weight:700;">Recordatorio: mañana tienes reservación</h1>
  </td></tr>
  <tr><td style="padding:28px 30px;">
    <p style="margin:0 0 18px;color:#374151;font-size:.95rem;">Hola <strong>' . $nombre . '</strong>, te recordamos tu reservación para mañana.</p>
    <table width="100%" cellpadding="0" cellspacing="0" style="border:1px solid #E5E7EB;border-radius:8px;font-size:.88rem;border-collapse:collapse;">
      <tr><td style="color:#6B7280;padding:10px 14px;width:38%">Hora</td><td style="padding:10px 14px;font-weight:700">' . $hora . '</td></tr>
      <tr style="background:#F9FAFB"><td style="color:#6B7280;padding:10px 14px">Personas</td><td style="padding:10px 14px">' . $personas . '</td></tr>
      <tr><td style="color:#6B7280;padding:10px 14px">Mesa</td><td style="padding:10px 14px;font-weight:700">' . $mesa . '</td></tr>
    </table>
    <p style="margin:24px 0 0;color:#6B7280;font-size:.82rem;text-align:center;">
      ¿Imprevisto? <a href="' . $cancelUrl . '" style="color:' . $color . ';font-weight:600">Cancela tu reservación aquí</a>
    </p>
  </td></tr>
</table>
</td></tr></table></body></html>';
            $altBody = "Recordatorio de reservación en $restNombre.\nHora: $hora\nPersonas: $personas\nMesa: $mesa\n\nCancelar: $cancelUrlRaw";
            return $this->enviarConMailNativo($destEmail, $subject, $htmlBody, $altBody);
        }

        try {
            $mail = $this->nuevoMailer();
            $mail->addAddress($destEmail, $nombre);
            $mail->isHTML(true);
            $mail->CharSet = 'UTF-8';
            $mail->Subject = "â° MaÃ±ana te esperamos â€” $restNombre";

            $mail->Body = '<!DOCTYPE html><html lang="es"><head><meta charset="UTF-8"></head>
<body style="margin:0;padding:0;background:#F3F4F6;font-family:Arial,sans-serif;">
<table width="100%" cellpadding="0" cellspacing="0" style="background:#F3F4F6;padding:24px 0;"><tr><td align="center">
<table width="560" cellpadding="0" cellspacing="0" style="background:#fff;border-radius:12px;overflow:hidden;box-shadow:0 2px 8px rgba(0,0,0,.08);">
  <tr><td style="background:' . $color . ';padding:28px 30px;">
    <p style="margin:0;color:rgba(255,255,255,.8);font-size:.78rem;text-transform:uppercase;letter-spacing:.06em;">' . $restNombre . '</p>
    <h1 style="margin:6px 0 0;color:#fff;font-size:1.35rem;font-weight:700;">â° Recordatorio: maÃ±ana tienes reservaciÃ³n</h1>
  </td></tr>
  <tr><td style="padding:28px 30px;">
    <p style="margin:0 0 18px;color:#374151;font-size:.95rem;">Hola <strong>' . $nombre . '</strong>, te recordamos tu reservaciÃ³n para maÃ±ana.</p>
    <table width="100%" cellpadding="0" cellspacing="0" style="border:1px solid #E5E7EB;border-radius:8px;font-size:.88rem;border-collapse:collapse;">
      <tr><td style="color:#6B7280;padding:10px 14px;width:38%">Hora</td><td style="padding:10px 14px;font-weight:700">' . $hora . '</td></tr>
      <tr style="background:#F9FAFB"><td style="color:#6B7280;padding:10px 14px">Personas</td><td style="padding:10px 14px">' . $personas . '</td></tr>
      <tr><td style="color:#6B7280;padding:10px 14px">Mesa</td><td style="padding:10px 14px;font-weight:700">' . $mesa . '</td></tr>
    </table>
    <p style="margin:24px 0 0;color:#6B7280;font-size:.82rem;text-align:center;">
      Â¿Imprevisto? <a href="' . $cancelUrl . '" style="color:' . $color . ';font-weight:600">Cancela tu reservaciÃ³n aquÃ­</a>
    </p>
  </td></tr>
</table>
</td></tr></table></body></html>';

            $mail->AltBody = "Recordatorio: maÃ±ana tienes reservaciÃ³n en $restNombre.\n"
                . "Hora: $hora  Personas: $personas  Mesa: $mesa\n"
                . "\nCancelar: $cancelUrl";

            $mail->Subject = $subjectAmare;
            $mail->Body = $htmlBodyAmare;
            $mail->AltBody = $altBodyAmare;
            $mail->send();
            error_log("[EmailService] Recordatorio reserva enviado a: $destEmail");
            return true;
        } catch (\Exception $e) {
            error_log("[EmailService] Error enviarRecordatorioReserva a $destEmail: {$mail->ErrorInfo}");
            error_log("[EmailService] Excepcion enviarRecordatorioReserva a $destEmail: {$e->getMessage()}");
            return false;
        }
    }

    /**
     * Confirma al comensal que su reservacion fue cancelada correctamente.
     */
    public function enviarCancelacionReservaCliente(
        string $destEmail,
        array $restaurante,
        array $reserva,
        string $reservarUrl
    ): bool {
        if (!$destEmail) return false;

        $restNombre = htmlspecialchars($restaurante['nombre'] ?? 'AMARE');
        $nombre     = htmlspecialchars($reserva['nombre'] ?? '');
        $fecha      = $reserva['fecha'] ?? '';
        $hora       = substr($reserva['hora'] ?? '', 0, 5);
        $personas   = (int)($reserva['personas'] ?? 1);
        $mesa       = htmlspecialchars($reserva['mesa_nombre'] ?? 'Por asignar');
        $reservarUrlEsc = htmlspecialchars($reservarUrl);

        $subject = "Reservación cancelada - $restNombre";
        $htmlBody = $this->plantillaReservaAmare(
            $restNombre,
            'Reserva cancelada',
            'Tu reservación quedó cancelada',
            'Hola <strong>' . $nombre . '</strong>, procesamos la cancelación de tu visita. Cuando quieras volver, estaremos listos para recibirte.',
            [
                'Fecha' => $fecha ? date('d/m/Y', strtotime($fecha)) : '',
                'Hora' => $hora,
                'Personas' => (string)$personas,
                'Mesa' => $mesa,
            ],
            'Reservar otra vez',
            $reservarUrlEsc,
            'Este correo confirma que ya no tienes una mesa activa para ese horario.'
        );
        $altBody = "Tu reservación en $restNombre fue cancelada.\n"
            . ($fecha ? "Fecha: " . date('d/m/Y', strtotime($fecha)) . "\n" : '')
            . "Hora: $hora\nPersonas: $personas\nMesa: $mesa\n\n"
            . "Reservar otra vez: $reservarUrl";

        if (!$this->phpMailerAvailable) {
            return $this->enviarConMailNativo($destEmail, $subject, $htmlBody, $altBody);
        }

        try {
            $mail = $this->nuevoMailer();
            $mail->addAddress($destEmail, $nombre);
            $mail->isHTML(true);
            $mail->CharSet = 'UTF-8';
            $mail->Subject = $subject;
            $mail->Body = $htmlBody;
            $mail->AltBody = $altBody;
            $mail->send();
            return true;
        } catch (\Exception $e) {
            error_log("[EmailService] Error enviarCancelacionReservaCliente a $destEmail: {$mail->ErrorInfo}");
            error_log("[EmailService] Excepcion enviarCancelacionReservaCliente a $destEmail: {$e->getMessage()}");
            return false;
        }
    }

    private function plantillaReservaAmare(
        string $restNombre,
        string $eyebrow,
        string $titulo,
        string $introHtml,
        array $rows,
        string $ctaTexto,
        string $ctaUrl,
        string $nota
    ): string {
        $rowHtml = '';
        foreach ($rows as $label => $value) {
            $value = trim((string)$value);
            if ($value === '') {
                continue;
            }

            $rowHtml .= '<tr>'
                . '<td style="padding:16px 18px;border-bottom:1px solid rgba(7,6,5,.08);color:#8c7a63;font-family:Arial,sans-serif;font-size:10px;font-weight:800;letter-spacing:.18em;text-transform:uppercase;width:34%;vertical-align:top;">' . htmlspecialchars((string)$label) . '</td>'
                . '<td style="padding:16px 18px;border-bottom:1px solid rgba(7,6,5,.08);color:#120f0c;font-family:Arial,sans-serif;font-size:16px;line-height:1.65;font-weight:800;vertical-align:top;">' . $value . '</td>'
                . '</tr>';
        }

        return '<!DOCTYPE html>
<html lang="es">
<head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1.0"></head>
<body style="margin:0;padding:0;background:#070605;font-family:Arial,sans-serif;">
  <table width="100%" cellpadding="0" cellspacing="0" style="background:#070605;padding:34px 12px;">
    <tr><td align="center">
      <table width="640" cellpadding="0" cellspacing="0" style="width:100%;max-width:640px;background:#0b0907;border:1px solid rgba(216,183,122,.22);border-radius:22px;overflow:hidden;box-shadow:0 26px 80px rgba(0,0,0,.42);">
        <tr><td style="height:4px;background:linear-gradient(90deg,#7b5427,#d8b77a,#9f6d32);font-size:0;line-height:0;">&nbsp;</td></tr>
        <tr>
          <td style="padding:40px 38px 34px;background:#070605;border-bottom:1px solid rgba(216,183,122,.16);">
            <div style="color:#d8b77a;font-family:Arial,sans-serif;font-size:11px;font-weight:900;letter-spacing:.38em;text-transform:uppercase;">AMARE</div>
            <div style="margin-top:18px;display:inline-block;padding:8px 14px;border:1px solid rgba(216,183,122,.38);border-radius:999px;color:#d8b77a;font-family:Arial,sans-serif;font-size:10px;font-weight:900;letter-spacing:.22em;text-transform:uppercase;">' . htmlspecialchars($eyebrow) . '</div>
            <h1 style="margin:22px 0 0;color:#f6f0e7;font-family:Georgia,serif;font-size:40px;line-height:1.05;font-weight:500;">' . htmlspecialchars($titulo) . '</h1>
            <p style="margin:18px 0 0;color:#d8cab7;font-family:Arial,sans-serif;font-size:16px;line-height:1.8;">' . $introHtml . '</p>
          </td>
        </tr>
        <tr><td style="padding:32px 38px 14px;background:#f6f0e7;">
          <table width="100%" cellpadding="0" cellspacing="0" style="background:#fffaf1;border:1px solid rgba(7,6,5,.08);border-radius:18px;overflow:hidden;">' . $rowHtml . '</table>
        </td></tr>
        <tr><td align="center" style="padding:18px 38px 12px;background:#f6f0e7;">
          <a href="' . $ctaUrl . '" style="display:inline-block;background:linear-gradient(135deg,#d8b77a,#a77634);color:#090705;text-decoration:none;padding:15px 30px;border-radius:999px;font-family:Arial,sans-serif;font-weight:900;font-size:12px;letter-spacing:.16em;text-transform:uppercase;">' . htmlspecialchars($ctaTexto) . '</a>
        </td></tr>
        <tr><td style="padding:10px 38px 34px;background:#f6f0e7;color:#5c5144;font-family:Arial,sans-serif;font-size:12px;line-height:1.8;text-align:center;">' . htmlspecialchars($nota) . '</td></tr>
        <tr><td style="padding:18px 34px;background:#070605;color:#b8aa97;font-family:Arial,sans-serif;font-size:10px;font-weight:800;letter-spacing:.24em;text-transform:uppercase;text-align:center;">Restaurant Connecting Club</td></tr>
      </table>
    </td></tr>
  </table>
</body>
</html>';
    }

    /** Construye un PHPMailer SMTP con la configuraciÃ³n cacheada. */
    private function nuevoMailer()
    {
        if (!$this->phpMailerAvailable) {
            throw new \RuntimeException('PHPMailer no esta instalado en este entorno.');
        }

        $mail = new PHPMailer(true);
        $mail->Timeout     = 10;

        if ($this->configured) {
            $mail->isSMTP();
            $mail->Host        = $this->smtpHost;
            $mail->SMTPAuth    = true;
            $mail->Username    = $this->smtpUsername;
            $mail->Password    = $this->smtpPassword;
            $mail->Port        = (int)$this->smtpPort;
            $mail->SMTPOptions = ['ssl' => ['verify_peer' => false, 'verify_peer_name' => false, 'allow_self_signed' => true]];
            if ($this->smtpEncryption === 'tls') {
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            } elseif ($this->smtpEncryption === 'ssl') {
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
            }
        } else {
            $mail->isMail();
            error_log('[EmailService] SMTP no configurado. Se intentarÃ¡ enviar con mail() nativo.');
        }

        $mail->setFrom($this->fromEmail(), $this->fromName());
        $mail->addReplyTo($this->fromEmail(), $this->fromName());
        return $mail;
    }

    /**
     * Verifica si el servicio de email estÃ¡ configurado correctamente
     *
     * @return bool True si estÃ¡ configurado
     */
    public function isConfigured(): bool
    {
        return $this->configured;
    }

    /**
     * Obtiene el estado de configuraciÃ³n con detalles
     *
     * @return array Estado de cada campo
     */
    public function getConfigStatus(): array
    {
        return [
            'smtp_host'       => !empty($this->smtpHost),
            'smtp_port'       => !empty($this->smtpPort),
            'smtp_username'   => !empty($this->smtpUsername),
            'smtp_password'   => !empty($this->smtpPassword),
            'smtp_from_email' => !empty($this->smtpFromEmail),
            'configured'      => $this->configured
        ];
    }

    /**
     * Notifica al admin del restaurante que un pedido B2B fue cancelado o rechazado por CarniHub.
     */
    public function enviarCancelacionPedido(
        string $destEmail,
        string $destNombre,
        string $folio,
        int    $carnihubPedidoId,
        string $estado
    ): bool {
        if (!$this->configured || !$destEmail) return false;

        $nombreSafe = htmlspecialchars($destNombre, ENT_QUOTES, 'UTF-8');
        $folioSafe  = htmlspecialchars($folio,      ENT_QUOTES, 'UTF-8');
        $estadoSafe = htmlspecialchars($estado,     ENT_QUOTES, 'UTF-8');

        try {
            $mail = new PHPMailer(true);
            $mail->isSMTP();
            $mail->Host        = $this->smtpHost;
            $mail->SMTPAuth    = true;
            $mail->Username    = $this->smtpUsername;
            $mail->Password    = $this->smtpPassword;
            $mail->Port        = (int)$this->smtpPort;
            $mail->Timeout     = 10;
            $mail->SMTPOptions = ['ssl' => ['verify_peer' => false, 'verify_peer_name' => false, 'allow_self_signed' => true]];
            if ($this->smtpEncryption === 'tls')     $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            elseif ($this->smtpEncryption === 'ssl') $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;

            $mail->setFrom($this->smtpFromEmail, $this->smtpFromName);
            $mail->addAddress($destEmail, $destNombre);
            $mail->isHTML(true);
            $mail->CharSet = 'UTF-8';
            $mail->Subject = "Pedido CarniHub $folioSafe fue $estadoSafe";
            $mail->Body = "<!DOCTYPE html><html lang='es'><head><meta charset='UTF-8'></head>
<body style='font-family:Arial,sans-serif;background:#F3F4F6;padding:24px;'>
<div style='max-width:540px;margin:0 auto;background:#fff;border-radius:10px;padding:28px;box-shadow:0 2px 8px rgba(0,0,0,.08);'>
  <h2 style='color:#1F2937;margin-top:0;'>Pedido $folioSafe fue $estadoSafe</h2>
  <p>Hola <strong>$nombreSafe</strong>,</p>
  <p>Tu pedido <strong>$folioSafe</strong> (ID CarniHub: $carnihubPedidoId) fue <strong style='color:#DC2626;'>$estadoSafe</strong> por el proveedor.</p>
  <p>Por favor revisa tu inventario y genera un nuevo pedido si es necesario.</p>
  <a href='" . BASE_URL . "rest-inventario/pedidosSugeridos' style='display:inline-block;background:#1F2937;color:#fff;text-decoration:none;padding:10px 24px;border-radius:6px;font-weight:700;margin-top:12px;'>
    Ver pedidos â†’
  </a>
</div>
</body></html>";
            $mail->AltBody = "Hola $destNombre, tu pedido $folio (ID CarniHub: $carnihubPedidoId) fue $estado por el proveedor.";
            $mail->send();
            return true;
        } catch (\Exception $e) {
            error_log("[EmailService] Error enviarCancelacionPedido a $destEmail: {$e->getMessage()}");
            return false;
        }
    }

    /**
     * Genera la plantilla HTML del email con credenciales
     *
     * @param array $data Datos para el template
     * @return string HTML del email
     */
    private function plantillaCredenciales(array $data): string
    {
        $nombre    = htmlspecialchars($data['nombre']);
        $email     = htmlspecialchars($data['email']);
        $password  = htmlspecialchars($data['password']);
        $ftpUser   = $data['ftp_user'] ? htmlspecialchars($data['ftp_user']) : null;
        $urlLogin  = htmlspecialchars($data['url_login']);
        $token     = $data['token'] ?? null;

        // Debug logging
        if (!$token) {
            error_log("[EmailService] ADVERTENCIA: Token de verificaciÃ³n es NULL para $email");
        } else {
            error_log("[EmailService] Generando email con token de verificaciÃ³n para $email");
        }

        $urlVerificar = $token ? BASE_URL . "auth/verificar?token=" . urlencode($token) : null;

        $ctaButton = $urlVerificar
            ? '<a href="' . $urlVerificar . '" style="display:inline-block;background-color:#C8102E;color:#ffffff;text-decoration:none;padding:14px 40px;border-radius:6px;font-weight:bold;font-size:16px;">Verificar mi email</a>'
            : '<a href="' . $urlLogin . '" style="display:inline-block;background-color:#C8102E;color:#ffffff;text-decoration:none;padding:14px 40px;border-radius:6px;font-weight:bold;font-size:16px;">Iniciar sesiÃ³n ahora</a>';

        return '
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bienvenido a CarniHub</title>
</head>
<body style="margin:0;padding:0;background-color:#f4f4f4;font-family:Arial,sans-serif;">
    <table width="100%" cellpadding="0" cellspacing="0" style="background-color:#f4f4f4;padding:20px 0;">
        <tr>
            <td align="center">
                <table width="600" cellpadding="0" cellspacing="0" style="background-color:#ffffff;border-radius:8px;overflow:hidden;box-shadow:0 2px 8px rgba(0,0,0,0.1);">

                    <!-- Header -->
                    <tr>
                        <td style="background:linear-gradient(135deg, #C8102E 0%, #8B0A1F 100%);padding:40px 30px;text-align:center;">
                            <h1 style="margin:0;color:#ffffff;font-size:28px;font-weight:bold;">Â¡Bienvenido a CarniHub!</h1>
                        </td>
                    </tr>

                    <!-- Contenido -->
                    <tr>
                        <td style="padding:40px 30px;">
                            <p style="margin:0 0 20px;color:#333;font-size:16px;line-height:1.6;">
                                Hola <strong>' . $nombre . '</strong>,
                            </p>
                            <p style="margin:0 0 30px;color:#666;font-size:14px;line-height:1.6;">
                                Tu cuenta en CarniHub ha sido creada exitosamente. ' .
                                ($urlVerificar ? '<strong>Primero debes verificar tu email</strong> haciendo clic en el botÃ³n de abajo.' : 'A continuaciÃ³n encontrarÃ¡s tus credenciales de acceso:') . '
                            </p>

                            <!-- Credenciales Web -->
                            <table width="100%" cellpadding="15" cellspacing="0" style="background-color:#f8f9fa;border-radius:6px;margin-bottom:20px;">
                                <tr>
                                    <td>
                                        <h3 style="margin:0 0 15px;color:#C8102E;font-size:16px;">ðŸŒ Acceso a la plataforma web</h3>
                                        <p style="margin:5px 0;color:#333;font-size:14px;">
                                            <strong>Email:</strong> ' . $email . '
                                        </p>
                                        <p style="margin:5px 0;color:#333;font-size:14px;">
                                            <strong>ContraseÃ±a:</strong> <code style="background:#fff;padding:4px 8px;border-radius:4px;font-family:monospace;">' . $password . '</code>
                                        </p>
                                    </td>
                                </tr>
                            </table>

                            ' . ($ftpUser ? '
                            <!-- Credenciales FTP -->
                            <table width="100%" cellpadding="15" cellspacing="0" style="background-color:#f8f9fa;border-radius:6px;margin-bottom:30px;">
                                <tr>
                                    <td>
                                        <h3 style="margin:0 0 15px;color:#C8102E;font-size:16px;">ðŸ“ Acceso FTP</h3>
                                        <p style="margin:5px 0;color:#333;font-size:14px;">
                                            <strong>Usuario FTP:</strong> <code style="background:#fff;padding:4px 8px;border-radius:4px;font-family:monospace;">' . $ftpUser . '</code>
                                        </p>
                                        <p style="margin:5px 0;color:#333;font-size:14px;">
                                            <strong>ContraseÃ±a:</strong> <code style="background:#fff;padding:4px 8px;border-radius:4px;font-family:monospace;">' . $password . '</code>
                                        </p>
                                        <p style="margin:10px 0 0;color:#666;font-size:12px;">
                                            <em>Usa estas credenciales para conectar por FTP (FileZilla, WinSCP, etc.)</em>
                                        </p>
                                    </td>
                                </tr>
                            </table>
                            ' : '') . '

                            <!-- BotÃ³n CTA -->
                            <table width="100%" cellpadding="0" cellspacing="0">
                                <tr>
                                    <td align="center" style="padding:20px 0;">
                                        ' . $ctaButton . '
                                    </td>
                                </tr>
                            </table>

                            <!-- Aviso de seguridad -->
                            <table width="100%" cellpadding="15" cellspacing="0" style="background-color:#' . ($urlVerificar ? 'DBEAFE' : 'fff3cd') . ';border-left:4px solid #' . ($urlVerificar ? '3B82F6' : 'ffc107') . ';border-radius:4px;margin-top:30px;">
                                <tr>
                                    <td>
                                        <p style="margin:0;color:#' . ($urlVerificar ? '1E40AF' : '856404') . ';font-size:13px;line-height:1.5;">
                                            ' . ($urlVerificar
                                                ? 'ðŸ” <strong>Importante:</strong> Debes verificar tu email para poder iniciar sesiÃ³n. El link de verificaciÃ³n expira en 24 horas.'
                                                : 'âš ï¸ <strong>Importante:</strong> Te recomendamos cambiar tu contraseÃ±a despuÃ©s del primer inicio de sesiÃ³n desde tu perfil.'
                                            ) . '
                                        </p>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    <!-- Footer -->
                    <tr>
                        <td style="background-color:#f8f9fa;padding:20px 30px;text-align:center;border-top:1px solid #e9ecef;">
                            <p style="margin:0 0 10px;color:#6c757d;font-size:12px;">
                                Â© ' . date('Y') . ' CarniHub - Sistema de gestiÃ³n para distribuidores de carne
                            </p>
                            <p style="margin:0;color:#6c757d;font-size:12px;">
                                <a href="' . BASE_URL . '" style="color:#C8102E;text-decoration:none;">Visitar sitio web</a>
                            </p>
                        </td>
                    </tr>

                </table>
            </td>
        </tr>
    </table>
</body>
</html>';
    }

    /**
     * Genera la versiÃ³n en texto plano del email (fallback)
     *
     * @param array $data Datos para el template
     * @return string Texto plano del email
     */
    private function plantillaTextoPlano(array $data): string
    {
        $token = $data['token'] ?? null;
        $urlVerificar = $token ? BASE_URL . "auth/verificar?token=" . urlencode($token) : null;

        $texto = "Â¡BIENVENIDO A CARNIHUB!\n\n";
        $texto .= "Hola {$data['nombre']},\n\n";
        $texto .= "Tu cuenta en CarniHub ha sido creada exitosamente.\n";

        if ($urlVerificar) {
            $texto .= "Primero debes verificar tu email haciendo clic en el siguiente link:\n\n";
            $texto .= "$urlVerificar\n\n";
        }

        $texto .= "A continuaciÃ³n tus credenciales de acceso:\n\n";
        $texto .= "=== ACCESO A LA PLATAFORMA WEB ===\n";
        $texto .= "Email: {$data['email']}\n";
        $texto .= "ContraseÃ±a: {$data['password']}\n\n";

        if ($data['ftp_user']) {
            $texto .= "=== ACCESO FTP ===\n";
            $texto .= "Usuario FTP: {$data['ftp_user']}\n";
            $texto .= "ContraseÃ±a: {$data['password']}\n";
            $texto .= "(Usa estas credenciales para conectar por FTP)\n\n";
        }

        if ($urlVerificar) {
            $texto .= "ðŸ” IMPORTANTE: Debes verificar tu email para poder iniciar sesiÃ³n. El link expira en 24 horas.\n\n";
        } else {
            $texto .= "Iniciar sesiÃ³n: {$data['url_login']}\n\n";
            $texto .= "âš ï¸ IMPORTANTE: Te recomendamos cambiar tu contraseÃ±a despuÃ©s del primer inicio de sesiÃ³n.\n\n";
        }

        $texto .= "---\n";
        $texto .= "Â© " . date('Y') . " CarniHub\n";
        $texto .= "Sistema de gestiÃ³n para distribuidores de carne\n";

        return $texto;
    }
}
