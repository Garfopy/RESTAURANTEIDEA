<?php
/**
 * EmailService
 * Envío de emails usando mail() nativa de PHP con SMTP configurado en cPanel
 * NOTA: Para producción se recomienda migrar a PHPMailer
 */
class EmailService
{
    private string $smtpHost;
    private string $smtpPort;
    private string $smtpUsername;
    private string $smtpFromEmail;
    private string $smtpFromName;

    public function __construct()
    {
        $db = Database::getInstance();
        $get = fn(string $k) => $db->query("SELECT valor FROM global_settings WHERE clave = '$k' LIMIT 1")->fetchColumn() ?: '';

        $this->smtpHost      = $get('smtp_host');
        $this->smtpPort      = $get('smtp_port') ?: '587';
        $this->smtpUsername  = $get('smtp_username');
        $this->smtpFromEmail = $get('smtp_from_email') ?: $this->smtpUsername;
        $this->smtpFromName  = $get('smtp_from_name') ?: 'CarniHub';
    }

    /**
     * Envía un correo con las credenciales de acceso al usuario nuevo
     *
     * @param array $usuario Array con datos del usuario (nombre, email)
     * @param string $passwordPlano Contraseña en texto plano (solo para email)
     * @param string|null $ftpUsername Username FTP (opcional)
     * @return bool True si se envió correctamente
     */
    public function enviarCredenciales(array $usuario, string $passwordPlano, ?string $ftpUsername = null): bool
    {
        // Validar configuración
        if (!$this->smtpFromEmail) {
            error_log("[EmailService] Configuración SMTP incompleta. Configure email remitente.");
            return false;
        }

        $destinatario = $usuario['email'];
        $nombreUsuario = $usuario['nombre'] . ' ' . ($usuario['apellido_paterno'] ?? '');

        // Asunto
        $subject = 'Bienvenido a CarniHub - Tus credenciales de acceso';

        // Cuerpo HTML
        $htmlBody = $this->plantillaCredenciales([
            'nombre'      => trim($nombreUsuario),
            'email'       => $destinatario,
            'password'    => $passwordPlano,
            'ftp_user'    => $ftpUsername,
            'url_login'   => BASE_URL . 'auth/login'
        ]);

        // Cuerpo texto plano (fallback)
        $textBody = $this->plantillaTextoPlano([
            'nombre'   => trim($nombreUsuario),
            'email'    => $destinatario,
            'password' => $passwordPlano,
            'ftp_user' => $ftpUsername,
            'url_login' => BASE_URL . 'auth/login'
        ]);

        // Headers optimizados para evitar spam
        $boundary = md5(uniqid(time()));

        $headers = [
            'MIME-Version: 1.0',
            'Content-Type: multipart/alternative; boundary="' . $boundary . '"',
            'From: ' . $this->smtpFromName . ' <' . $this->smtpFromEmail . '>',
            'Reply-To: ' . $this->smtpFromEmail,
            'X-Mailer: CarniHub/2.7.1',
            'X-Priority: 1',
            'Importance: High'
        ];

        // Mensaje multipart (texto plano + HTML)
        $message = "--{$boundary}\r\n";
        $message .= "Content-Type: text/plain; charset=UTF-8\r\n";
        $message .= "Content-Transfer-Encoding: 7bit\r\n\r\n";
        $message .= $textBody . "\r\n\r\n";

        $message .= "--{$boundary}\r\n";
        $message .= "Content-Type: text/html; charset=UTF-8\r\n";
        $message .= "Content-Transfer-Encoding: 7bit\r\n\r\n";
        $message .= $htmlBody . "\r\n\r\n";

        $message .= "--{$boundary}--";

        // Enviar email
        $enviado = @mail($destinatario, $subject, $message, implode("\r\n", $headers));

        if (!$enviado) {
            error_log("[EmailService] No se pudo enviar email a: " . $destinatario);
        }

        return $enviado;
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
                                Tu cuenta en CarniHub ha sido creada exitosamente. A continuación encontrarás tus credenciales de acceso:
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
                                        <a href="' . $urlLogin . '" style="display:inline-block;background-color:#C8102E;color:#ffffff;text-decoration:none;padding:14px 40px;border-radius:6px;font-weight:bold;font-size:16px;">
                                            Iniciar sesión ahora
                                        </a>
                                    </td>
                                </tr>
                            </table>

                            <!-- Aviso de seguridad -->
                            <table width="100%" cellpadding="15" cellspacing="0" style="background-color:#fff3cd;border-left:4px solid #ffc107;border-radius:4px;margin-top:30px;">
                                <tr>
                                    <td>
                                        <p style="margin:0;color:#856404;font-size:13px;line-height:1.5;">
                                            ⚠️ <strong>Importante:</strong> Te recomendamos cambiar tu contraseña después del primer inicio de sesión desde tu perfil.
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
        $texto = "¡BIENVENIDO A CARNIHUB!\n\n";
        $texto .= "Hola {$data['nombre']},\n\n";
        $texto .= "Tu cuenta en CarniHub ha sido creada exitosamente.\n";
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

        $texto .= "Iniciar sesión: {$data['url_login']}\n\n";
        $texto .= "⚠️ IMPORTANTE: Te recomendamos cambiar tu contraseña después del primer inicio de sesión.\n\n";
        $texto .= "---\n";
        $texto .= "© " . date('Y') . " CarniHub\n";
        $texto .= "Sistema de gestión para distribuidores de carne\n";

        return $texto;
    }
}
