-- Migration 004: Agregar rol admin_empresa (Administrador de Empresa — rol CLIENTE)
-- RF-U02: "Administrador empresa" es un ROL CLIENTE que gestiona su propia empresa.
-- NOTA: el slug 'admin' (id=2) se mantiene intacto — es empleado interno de CarniHub.
-- Ejecutar en cPanel → phpMyAdmin → idactivo_carnihubdb

-- 1. Agregar el nuevo rol al catálogo
INSERT INTO roles (nombre, slug)
VALUES ('Administrador Empresa', 'admin_empresa');

-- 2. Actualizar el usuario de prueba juan.perez a admin_empresa
--    (él creó la empresa "Taquería El Buen Sabor", debe ser su administrador)
UPDATE usuarios
SET rol_id = (SELECT id FROM roles WHERE slug = 'admin_empresa')
WHERE email = 'juan.perez@carnihub.mx';

-- 3. Verificar resultado
SELECT u.id, u.nombre, u.email, r.slug AS rol, u.empresa_id
FROM usuarios u
JOIN roles r ON u.rol_id = r.id
ORDER BY u.id;
