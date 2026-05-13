-- 027_test_staff_la_comalada.sql
-- Usuarios de prueba para LA COMALADA (restaurante_id=1, empresa_id=1).
-- Password en claro: Test1234! (bcrypt $2a$12$DaUtPPcEXFA.5OH/Zq68De0S3ZFrbSqKYomPWFWpCD2ha.WOV5eNy)
-- Login por slug: BASE_URL/acceso/la-comalada

INSERT INTO usuarios
  (nombre, apellido_paterno, apellido_materno, email, email_verificado,
   primer_login_completado, password, rol_id, empresa_id, restaurante_id, activo)
VALUES
  ('Mesero',  'Demo', 'Uno', 'mesero1@la-comalada.test',  1, 1,
   '$2a$12$DaUtPPcEXFA.5OH/Zq68De0S3ZFrbSqKYomPWFWpCD2ha.WOV5eNy', 7, 1, 1, 1),
  ('Chef',    'Demo', 'Uno', 'chef1@la-comalada.test',    1, 1,
   '$2a$12$DaUtPPcEXFA.5OH/Zq68De0S3ZFrbSqKYomPWFWpCD2ha.WOV5eNy', 8, 1, 1, 1),
  ('Portero', 'Demo', 'Uno', 'portero1@la-comalada.test', 1, 1,
   '$2a$12$DaUtPPcEXFA.5OH/Zq68De0S3ZFrbSqKYomPWFWpCD2ha.WOV5eNy', 9, 1, 1, 1);

INSERT INTO rest_staff (restaurante_id, usuario_id, codigo, rol_slug, activo, fecha_ingreso)
SELECT 1, id, 'ME001', 'mesero',  1, CURRENT_DATE FROM usuarios WHERE email = 'mesero1@la-comalada.test'
UNION ALL
SELECT 1, id, 'CH001', 'chef',    1, CURRENT_DATE FROM usuarios WHERE email = 'chef1@la-comalada.test'
UNION ALL
SELECT 1, id, 'PT001', 'portero', 1, CURRENT_DATE FROM usuarios WHERE email = 'portero1@la-comalada.test';
