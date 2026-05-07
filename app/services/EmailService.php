<?php
/**
 * EmailService
 * Envío de emails usando PHPMailer con SMTP
 */

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

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

    public function __construct()
    {
        $db = Database::getInstance();
        $get = fn(string $k, string $default = '') =>
            $db->query("SELECT valor FROM global_settings WHERE clave = '$k' LIMIT 1")->fetchColumn() ?: $default;

        $this->smtpHost       = $get('smtp_host');
        $this->smtpPort       = $get('smtp_port', '587');
        $this->smtpEncryption = $get('smtp_encryption', 'tls');
        $this->smtpUsername   = $get('smtp_username');
        $this->smtpPassword   = $get('smtp_password');
        $this->smtpFromEmail  = $get('smtp_from_email') ?: $this->smtpUsername;
        $this->smtpFromName   = $get('smtp_from_name', 'CarniHub');

        // Verificar si la configuración está completa
        $this->configured = !empty($this->smtpHost)
                         && !empty($this->smtpUsername)
                         && !empty($this->smtpPassword)
                         && !empty($this->smtpFromEmail);
    }

    /**
     * Envía un correo con las credenciales de acceso al usuario nuevo
     *
     * @param array $usuario Array con datos del usuario (nombre, email)
     * @param string $passwordPlano Contraseña en texto plano (solo para email)
     * @param string|null $ftpUsername Username FTP (opcional)
     * @param string|null $tokenVerificacion Token para verificar email (opcional)
     * @return bool True si se envió correctamente
     */
    public function enviarCredenciales(
        array $usuario,
        string $passwordPlano,
        ?string $ftpUsername = null,
        ?string $tokenVerificacion = null
    ): bool {
        // Validar configuración
        if (!$this->configured) {
            error_log("[EmailService] Configuración SMTP incompleta. Configure credenciales en /config/correo");
            return false;
        }

        $destinatario = $usuario['email'];
        $nombreUsuario = $usuario['nombre'] . ' ' . ($usuario['apellido_paterno'] ?? '');

        try {
            $mail = new PHPMailer(true);

            // ── Configuración del servidor SMTP ──
            $mail->isSMTP();
            $mail->Host       = $this->smtpHost;
            $mail->SMTPAuth   = true;
            $mail->Username   = $this->smtpUsername;
            $mail->Password   = $this->smtpPassword;
            $mail->Port       = (int)$this->smtpPort;

            // Configurar cifrado
            if ($this->smtpEncryption === 'tls') {
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            } elseif ($this->smtpEncryption === 'ssl') {
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
            }

            // Debug (descomentar para troubleshooting)
            // $mail->SMTPDebug = SMTP::DEBUG_SERVER;

            // ── Remitente y destinatario ──
            $mail->setFrom($this->smtpFromEmail, $this->smtpFromName);
            $mail->addAddress($destinatario, trim($nombreUsuario));
            $mail->addReplyTo($this->smtpFromEmail, $this->smtpFromName);

            // ── Contenido ──
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

            // ── Enviar ──
            $mail->send();

            error_log("[EmailService] Email enviado exitosamente a: $destinatario");
            return true;

        } catch (Exception $e) {
            error_log("[EmailService] Error al enviar email a $destinatario: {$mail->ErrorInfo}");
            error_log("[EmailService] Excepción: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Verifica si el servicio de email está configurado correctamente
     *
     * @return bool True si está configurado
     */
    public function isConfigured(): bool
    {
        return $this->configured;
    }

    /**
     * Obtiene el estado de configuración con detalles
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
            error_log("[EmailService] ADVERTENCIA: Token de verificación es NULL para $email");
        } else {
            error_log("[EmailService] Generando email con token de verificación para $email");
        }

        $urlVerificar = $token ? BASE_URL . "auth/verificar?token=" . urlencode($token) : null;

        $ctaButton = $urlVerificar
            ? '<a href="' . $urlVerificar . '" style="display:inline-block;background-color:#C8102E;color:#ffffff;text-decoration:none;padding:14px 40px;border-radius:6px;font-weight:bold;font-size:16px;">Verificar mi email</a>'
            : '<a href="' . $urlLogin . '" style="display:inline-block;background-color:#C8102E;color:#ffffff;text-decoration:none;padding:14px 40px;border-radius:6px;font-weight:bold;font-size:16px;">Iniciar sesión ahora</a>';

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
                            <h1 style="margin:0;color:#ffffff;font-size:28px;font-weight:bold;">¡Bienvenido a CarniHub!</h1>
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
                                ($urlVerificar ? '<strong>Primero debes verificar tu email</strong> haciendo clic en el botón de abajo.' : 'A continuación encontrarás tus credenciales de acceso:') . '
                            </p>

                            <!-- Credenciales Web -->
                            <table width="100%" cellpadding="15" cellspacing="0" style="background-color:#f8f9fa;border-radius:6px;margin-bottom:20px;">
                                <tr>
                                    <td>
                                        <h3 style="margin:0 0 15px;color:#C8102E;font-size:16px;">🌐 Acceso a la plataforma web</h3>
                                        <p style="margin:5px 0;color:#333;font-size:14px;">
                                            <strong>Email:</strong> ' . $email . '
                                        </p>
                                        <p style="margin:5px 0;color:#333;font-size:14px;">
                                            <strong>Contraseña:</strong> <code style="background:#fff;padding:4px 8px;border-radius:4px;font-family:monospace;">' . $password . '</code>
                                        </p>
                                    </td>
                                </tr>
                            </table>

                            ' . ($ftpUser ? '
                            <!-- Credenciales FTP -->
                            <table width="100%" cellpadding="15" cellspacing="0" style="background-color:#f8f9fa;border-radius:6px;margin-bottom:30px;">
                                <tr>
                                    <td>
                                        <h3 style="margin:0 0 15px;color:#C8102E;font-size:16px;">📁 Acceso FTP</h3>
                                        <p style="margin:5px 0;color:#333;font-size:14px;">
                                            <strong>Usuario FTP:</strong> <code style="background:#fff;padding:4px 8px;border-radius:4px;font-family:monospace;">' . $ftpUser . '</code>
                                        </p>
                                        <p style="margin:5px 0;color:#333;font-size:14px;">
                                            <strong>Contraseña:</strong> <code style="background:#fff;padding:4px 8px;border-radius:4px;font-family:monospace;">' . $password . '</code>
                                        </p>
                                        <p style="margin:10px 0 0;color:#666;font-size:12px;">
                                            <em>Usa estas credenciales para conectar por FTP (FileZilla, WinSCP, etc.)</em>
                                        </p>
                                    </td>
                                </tr>
                            </table>
                            ' : '') . '

                            <!-- Botón CTA -->
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
                                                ? '🔐 <strong>Importante:</strong> Debes verificar tu email para poder iniciar sesión. El link de verificación expira en 24 horas.'
                                                : '⚠️ <strong>Importante:</strong> Te recomendamos cambiar tu contraseña después del primer inicio de sesión desde tu perfil.'
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
                                © ' . date('Y') . ' CarniHub - Sistema de gestión para distribuidores de carne
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
     * Genera la versión en texto plano del email (fallback)
     *
     * @param array $data Datos para el template
     * @return string Texto plano del email
     */
    private function plantillaTextoPlano(array $data): string
    {
        $token = $data['token'] ?? null;
        $urlVerificar = $token ? BASE_URL . "auth/verificar?token=" . urlencode($token) : null;

        $texto = "¡BIENVENIDO A CARNIHUB!\n\n";
        $texto .= "Hola {$data['nombre']},\n\n";
        $texto .= "Tu cuenta en CarniHub ha sido creada exitosamente.\n";

        if ($urlVerificar) {
            $texto .= "Primero debes verificar tu email haciendo clic en el siguiente link:\n\n";
            $texto .= "$urlVerificar\n\n";
        }

        $texto .= "A continuación tus credenciales de acceso:\n\n";
        $texto .= "=== ACCESO A LA PLATAFORMA WEB ===\n";
        $texto .= "Email: {$data['email']}\n";
        $texto .= "Contraseña: {$data['password']}\n\n";

        if ($data['ftp_user']) {
            $texto .= "=== ACCESO FTP ===\n";
            $texto .= "Usuario FTP: {$data['ftp_user']}\n";
            $texto .= "Contraseña: {$data['password']}\n";
            $texto .= "(Usa estas credenciales para conectar por FTP)\n\n";
        }

        if ($urlVerificar) {
            $texto .= "🔐 IMPORTANTE: Debes verificar tu email para poder iniciar sesión. El link expira en 24 horas.\n\n";
        } else {
            $texto .= "Iniciar sesión: {$data['url_login']}\n\n";
            $texto .= "⚠️ IMPORTANTE: Te recomendamos cambiar tu contraseña después del primer inicio de sesión.\n\n";
        }

        $texto .= "---\n";
        $texto .= "© " . date('Y') . " CarniHub\n";
        $texto .= "Sistema de gestión para distribuidores de carne\n";

        return $texto;
    }
}
