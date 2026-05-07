-- Migration 014: First login tracking for password change prompt
-- 2026-05-07
-- Agrega campo para trackear si el usuario ya completó su primer inicio de sesión

ALTER TABLE usuarios
ADD COLUMN primer_login_completado TINYINT(1) DEFAULT 0 COMMENT 'Indica si ya completó su primer inicio de sesión'
AFTER email_verificado;

-- Usuarios existentes ya verificados se marcan como "completado"
-- para que no vean el banner de cambio de contraseña
UPDATE usuarios
SET primer_login_completado = 1
WHERE email_verificado = 1;
