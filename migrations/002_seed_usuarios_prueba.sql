-- ============================================================
-- MIGRACIÓN 002 — Usuarios de prueba por rol (empresa_id = 1)
-- Contraseña de todos: Admin2024!
-- ============================================================

-- Sucursal de entrega para el comprador de prueba (si no existe)
INSERT IGNORE INTO `sucursales` (`id`, `empresa_id`, `nombre`, `direccion`, `lat`, `lng`, `responsable`, `telefono`, `activo`)
VALUES (10, 1, 'Taquería Centro', 'Av. Juárez 120, Centro, Querétaro', 20.5888, -100.3899, 'María López', '4421112233', 1);

-- Vehículo de prueba para el repartidor
INSERT IGNORE INTO `vehiculos` (`id`, `empresa_id`, `placa`, `modelo`, `capacidad`, `activo`)
VALUES (10, 1, 'QRO-123', 'Toyota Hilux 2022', 1000.00, 1);

-- Usuarios de prueba (password = Admin2024!)
INSERT INTO `usuarios`
    (`id`, `nombre`, `apellido_paterno`, `apellido_materno`, `email`, `password`, `rol_id`, `empresa_id`, `activo`, `telefono`, `created_by`, `created_at`)
VALUES
-- Supervisor de prueba (rol_id = 4)
(10, 'Carlos', 'Martínez', 'Ruiz',
    'supervisor@buensabor.mx',
    '$2a$12$DaUtPPcEXFA.5OH/Zq68De0S3ZFrbSqKYomPWFWpCD2ha.WOV5eNy',
    4, 1, 1, '4421000001', 2, NOW()),

-- Comprador de prueba (rol_id = 5)
(11, 'Ana', 'García', 'Flores',
    'comprador@buensabor.mx',
    '$2a$12$DaUtPPcEXFA.5OH/Zq68De0S3ZFrbSqKYomPWFWpCD2ha.WOV5eNy',
    5, 1, 1, '4421000002', 2, NOW()),

-- Repartidor de prueba (rol_id = 6)
(12, 'Luis', 'Hernández', NULL,
    'repartidor@buensabor.mx',
    '$2a$12$DaUtPPcEXFA.5OH/Zq68De0S3ZFrbSqKYomPWFWpCD2ha.WOV5eNy',
    6, 1, 1, '4421000003', 2, NOW());

-- Asignar vehículo al repartidor de prueba
INSERT IGNORE INTO `repartidor_vehiculo` (`repartidor_id`, `vehiculo_id`, `activo`)
VALUES (12, 10, 1);

-- ============================================================
-- Credenciales de prueba
-- ============================================================
-- Admin Empresa:  juan@buensabor.mx      / Admin2024!
-- Supervisor:     supervisor@buensabor.mx / Admin2024!
-- Comprador:      comprador@buensabor.mx  / Admin2024!
-- Repartidor:     repartidor@buensabor.mx / Admin2024!
