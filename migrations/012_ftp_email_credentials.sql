-- ============================================================
-- CarniHub v2.7.1 — Sistema FTP + Email automático
-- Migración 012: Agregar campos FTP y configuración SMTP/cPanel
-- ============================================================

-- ── 1. Agregar campos FTP a tabla usuarios (solo si no existen) ──

-- Agregar columna ftp_username
SET @exist := (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
               WHERE TABLE_SCHEMA = DATABASE()
               AND TABLE_NAME = 'usuarios'
               AND COLUMN_NAME = 'ftp_username');
SET @sqlstmt := IF(@exist = 0,
    'ALTER TABLE `usuarios` ADD COLUMN `ftp_username` VARCHAR(50) NULL DEFAULT NULL AFTER `password`',
    'SELECT ''ftp_username ya existe'' AS Info');
PREPARE stmt FROM @sqlstmt;
EXECUTE stmt;

-- Agregar columna ftp_creado
SET @exist := (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
               WHERE TABLE_SCHEMA = DATABASE()
               AND TABLE_NAME = 'usuarios'
               AND COLUMN_NAME = 'ftp_creado');
SET @sqlstmt := IF(@exist = 0,
    'ALTER TABLE `usuarios` ADD COLUMN `ftp_creado` TINYINT(1) NOT NULL DEFAULT 0 AFTER `ftp_username`',
    'SELECT ''ftp_creado ya existe'' AS Info');
PREPARE stmt FROM @sqlstmt;
EXECUTE stmt;

-- Agregar índice único (ignorar si ya existe)
SET @exist := (SELECT COUNT(*) FROM INFORMATION_SCHEMA.STATISTICS
               WHERE TABLE_SCHEMA = DATABASE()
               AND TABLE_NAME = 'usuarios'
               AND INDEX_NAME = 'uq_ftp_username');
SET @sqlstmt := IF(@exist = 0,
    'ALTER TABLE `usuarios` ADD UNIQUE KEY `uq_ftp_username` (`ftp_username`)',
    'SELECT ''índice uq_ftp_username ya existe'' AS Info');
PREPARE stmt FROM @sqlstmt;
EXECUTE stmt;

-- ── 2. Configuración SMTP (PHPMailer) ──
INSERT IGNORE INTO `global_settings` (`clave`, `valor`, `tipo`, `grupo`, `etiqueta`) VALUES
('smtp_host',        '',         'text',     'email', 'Servidor SMTP'),
('smtp_port',        '587',      'number',   'email', 'Puerto SMTP'),
('smtp_encryption',  'tls',      'text',     'email', 'Cifrado (tls/ssl)'),
('smtp_username',    '',         'text',     'email', 'Usuario SMTP'),
('smtp_password',    '',         'password', 'email', 'Contraseña SMTP'),
('smtp_from_email',  '',         'text',     'email', 'Email remitente'),
('smtp_from_name',   'CarniHub', 'text',     'email', 'Nombre remitente');

-- ── 3. Configuración cPanel UAPI ──
INSERT IGNORE INTO `global_settings` (`clave`, `valor`, `tipo`, `grupo`, `etiqueta`) VALUES
('cpanel_host',      '',         'text',     'cpanel', 'Host cPanel'),
('cpanel_username',  '',         'text',     'cpanel', 'Usuario cPanel'),
('cpanel_token',     '',         'password', 'cpanel', 'API Token'),
('cpanel_domain',    '',         'text',     'cpanel', 'Dominio principal'),
('cpanel_ftp_dir',   '/public_html/uploads/usuarios/', 'text', 'cpanel', 'Directorio FTP base'),
('cpanel_ftp_quota', '500',      'number',   'cpanel', 'Cuota FTP (MB)');
