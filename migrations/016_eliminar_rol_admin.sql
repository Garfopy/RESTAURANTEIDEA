-- ============================================================
-- MIGRACIÓN 016 — Eliminar rol "Administrador" (ID 2)
-- Solo mantener Super Admin (ID 1) y Admin Empresa (ID 3)
-- ============================================================

-- 1. VERIFICAR que no existan usuarios con rol_id = 2
SELECT
    COUNT(*) AS total_usuarios_admin,
    GROUP_CONCAT(email SEPARATOR ', ') AS emails
FROM usuarios
WHERE rol_id = 2;

-- 2. Si existen usuarios con rol_id = 2, PRIMERO actualizar a superadmin
-- DESCOMENTAR Y EJECUTAR SOLO SI ES NECESARIO:
-- UPDATE usuarios SET rol_id = 1 WHERE rol_id = 2;

-- 3. ELIMINAR el rol "Administrador" (ID 2)
DELETE FROM roles WHERE id = 2;

-- 4. VERIFICACIÓN: Confirmar que solo quedan los roles esperados
SELECT id, nombre, slug FROM roles ORDER BY id;

-- Resultado esperado:
-- 1 | Super Admin      | superadmin
-- 3 | Admin Empresa    | admin_empresa
-- 4 | Supervisor       | supervisor
-- 5 | Comprador        | comprador
-- 6 | Repartidor       | repartidor
