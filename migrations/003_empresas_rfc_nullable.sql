-- Migración 003: hacer rfc nullable en empresas
-- El UNIQUE KEY uq_rfc rechazaba múltiples registros con rfc=''
-- NULL no viola UNIQUE (MySQL permite múltiples NULLs en un índice UNIQUE)

ALTER TABLE empresas
  MODIFY rfc VARCHAR(15) DEFAULT NULL;
