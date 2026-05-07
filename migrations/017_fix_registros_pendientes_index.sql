-- ============================================================
-- MIGRACIÓN 017 — Solucionar problema de índice único en registros_pendientes
-- Problema: El índice uq_email_activo impide múltiples registros "completado"
-- ============================================================

-- 1. ELIMINAR el índice único problemático
ALTER TABLE `registros_pendientes`
DROP INDEX `uq_email_activo`;

-- 2. CREAR índice único solo para estados activos (no completado ni expirado)
-- Esto permite múltiples registros completados/expirados pero previene duplicados activos
ALTER TABLE `registros_pendientes`
ADD UNIQUE KEY `uq_email_pendiente` (`email`, `estado`)
WHERE `estado` IN ('pendiente_pago', 'pendiente_verificacion');

-- NOTA: Si tu versión de MySQL no soporta índices parciales (WHERE),
-- usar esta alternativa:

-- Eliminar el índice único y usar un trigger para validar
-- (Descomenta si es necesario)

-- DROP INDEX IF EXISTS `uq_email_activo`;
--
-- DELIMITER $$
-- CREATE TRIGGER `check_email_estado_insert`
-- BEFORE INSERT ON `registros_pendientes`
-- FOR EACH ROW
-- BEGIN
--   IF NEW.estado IN ('pendiente_pago', 'pendiente_verificacion') THEN
--     IF EXISTS (
--       SELECT 1 FROM registros_pendientes
--       WHERE email = NEW.email
--       AND estado IN ('pendiente_pago', 'pendiente_verificacion')
--     ) THEN
--       SIGNAL SQLSTATE '45000'
--       SET MESSAGE_TEXT = 'Ya existe un registro pendiente para este email';
--     END IF;
--   END IF;
-- END$$
--
-- CREATE TRIGGER `check_email_estado_update`
-- BEFORE UPDATE ON `registros_pendientes`
-- FOR EACH ROW
-- BEGIN
--   IF NEW.estado IN ('pendiente_pago', 'pendiente_verificacion') THEN
--     IF EXISTS (
--       SELECT 1 FROM registros_pendientes
--       WHERE email = NEW.email
--       AND id != NEW.id
--       AND estado IN ('pendiente_pago', 'pendiente_verificacion')
--     ) THEN
--       SIGNAL SQLSTATE '45000'
--       SET MESSAGE_TEXT = 'Ya existe un registro pendiente para este email';
--     END IF;
--   END IF;
-- END$$
-- DELIMITER ;

-- 3. VERIFICACIÓN: Ver registros del email problemático
SELECT id, email, estado, created_at, completed_at
FROM registros_pendientes
WHERE email = '2024310029@uteq.edu.mx'
ORDER BY created_at DESC;
