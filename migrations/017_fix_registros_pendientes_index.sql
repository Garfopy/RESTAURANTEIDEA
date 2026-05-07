-- ============================================================
-- MIGRACIÓN 017 — Solucionar problema de índice único en registros_pendientes
-- Problema: El índice uq_email_activo impide múltiples registros "completado"
-- ============================================================

-- 1. VERIFICAR índices existentes (ejecuta esto primero)
SHOW INDEX FROM `registros_pendientes`;

-- 2. ELIMINAR el índice único problemático (si existe)
ALTER TABLE `registros_pendientes`
DROP INDEX IF EXISTS `uq_email_activo`;

-- 2. SOLUCIÓN: Usar triggers para validar duplicados solo en estados activos
-- Esto permite múltiples registros completados/expirados pero previene duplicados activos

DELIMITER $$

CREATE TRIGGER `check_email_estado_insert`
BEFORE INSERT ON `registros_pendientes`
FOR EACH ROW
BEGIN
  IF NEW.estado IN ('pendiente_pago', 'pendiente_verificacion') THEN
    IF EXISTS (
      SELECT 1 FROM registros_pendientes
      WHERE email = NEW.email
      AND estado IN ('pendiente_pago', 'pendiente_verificacion')
    ) THEN
      SIGNAL SQLSTATE '45000'
      SET MESSAGE_TEXT = 'Ya existe un registro pendiente para este email';
    END IF;
  END IF;
END$$

CREATE TRIGGER `check_email_estado_update`
BEFORE UPDATE ON `registros_pendientes`
FOR EACH ROW
BEGIN
  IF NEW.estado IN ('pendiente_pago', 'pendiente_verificacion') THEN
    IF EXISTS (
      SELECT 1 FROM registros_pendientes
      WHERE email = NEW.email
      AND id != NEW.id
      AND estado IN ('pendiente_pago', 'pendiente_verificacion')
    ) THEN
      SIGNAL SQLSTATE '45000'
      SET MESSAGE_TEXT = 'Ya existe un registro pendiente para este email';
    END IF;
  END IF;
END$$

DELIMITER ;

-- 3. VERIFICACIÓN: Ver registros del email problemático
SELECT id, email, estado, created_at, completed_at
FROM registros_pendientes
WHERE email = '2024310029@uteq.edu.mx'
ORDER BY created_at DESC;
