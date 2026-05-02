-- ============================================================
-- CarniHub — Base de Datos Completa
-- MySQL 5.7  |  Charset: utf8mb4
-- Datos de ejemplo del Estado de Querétaro
-- ============================================================

SET NAMES utf8mb4;
SET time_zone = '-06:00';
SET foreign_key_checks = 0;

-- ─────────────────────────────────────────
-- 1. roles
-- ─────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `roles` (
  `id`     TINYINT UNSIGNED     NOT NULL AUTO_INCREMENT,
  `nombre` VARCHAR(50)          NOT NULL,
  `slug`   VARCHAR(50)          NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_rol_slug` (`slug`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO `roles` (`id`, `nombre`, `slug`) VALUES
(1, 'SuperAdmin',       'superadmin'),
(2, 'Administrador',    'admin'),
(3, 'Comprador',        'comprador'),
(4, 'Supervisor',       'supervisor'),
(5, 'Repartidor',       'repartidor');

-- ─────────────────────────────────────────
-- 2. usuarios
-- ─────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `usuarios` (
  `id`               INT UNSIGNED     NOT NULL AUTO_INCREMENT,
  `nombre`           VARCHAR(100)     NOT NULL,
  `apellido_paterno` VARCHAR(100)     NOT NULL DEFAULT '',
  `apellido_materno` VARCHAR(100)     NULL DEFAULT NULL,
  `email`            VARCHAR(150)     NOT NULL,
  `password`         VARCHAR(255)     NOT NULL,
  `rol_id`           TINYINT UNSIGNED NOT NULL,
  `empresa_id`       INT UNSIGNED     NULL DEFAULT NULL,
  `activo`           TINYINT(1)       NOT NULL DEFAULT 1,
  `avatar`           VARCHAR(255)     NULL DEFAULT NULL,
  `created_at`       TIMESTAMP        NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_email` (`email`),
  KEY `fk_usuario_rol` (`rol_id`),
  CONSTRAINT `fk_usuario_rol` FOREIGN KEY (`rol_id`) REFERENCES `roles` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Passwords: admin123 para todas las cuentas de prueba
-- Hash generado con: password_hash('admin123', PASSWORD_DEFAULT)
INSERT INTO `usuarios` (`id`, `nombre`, `apellido_paterno`, `apellido_materno`, `email`, `password`, `rol_id`, `empresa_id`, `activo`) VALUES
(1, 'Super',  'Admin',    NULL,        'admin@carnihub.mx',          '$2y$10$KSAVxGMNNFsqvR6XTjQaAOg1p1y6q3.vcglZc.VUSbEAty3nd7Iqy', 1, NULL, 1),
(2, 'Ana',    'Martínez', NULL,        'ana.martinez@carnihub.mx',   '$2y$10$KSAVxGMNNFsqvR6XTjQaAOg1p1y6q3.vcglZc.VUSbEAty3nd7Iqy', 2, NULL, 1),
(3, 'Juan',   'Pérez',    NULL,        'juan.perez@carnihub.mx',     '$2y$10$KSAVxGMNNFsqvR6XTjQaAOg1p1y6q3.vcglZc.VUSbEAty3nd7Iqy', 3, 1,   1),
(4, 'María',  'González', NULL,        'maria.gonzalez@carnihub.mx', '$2y$10$KSAVxGMNNFsqvR6XTjQaAOg1p1y6q3.vcglZc.VUSbEAty3nd7Iqy', 4, 1,   1),
(5, 'Luis',   'Martínez', NULL,        'luis.martinez@carnihub.mx',  '$2y$10$KSAVxGMNNFsqvR6XTjQaAOg1p1y6q3.vcglZc.VUSbEAty3nd7Iqy', 5, NULL, 1),
(6, 'Pedro',  'Ramírez',  NULL,        'pedro.ramirez@carnihub.mx',  '$2y$10$KSAVxGMNNFsqvR6XTjQaAOg1p1y6q3.vcglZc.VUSbEAty3nd7Iqy', 5, NULL, 1),
(7, 'Carlos', 'Mendoza',  NULL,        'carlos@bsabor.mx',           '$2y$10$KSAVxGMNNFsqvR6XTjQaAOg1p1y6q3.vcglZc.VUSbEAty3nd7Iqy', 3, 2,   1);

-- ─────────────────────────────────────────
-- 3. empresas
-- ─────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `empresas` (
  `id`                     INT UNSIGNED   NOT NULL AUTO_INCREMENT,
  `razon_social`           VARCHAR(200)   NOT NULL,
  `rfc`                    VARCHAR(15)    NOT NULL,
  `regimen_fiscal`         VARCHAR(100)   NULL DEFAULT NULL,
  `email`                  VARCHAR(150)   NULL DEFAULT NULL,
  `telefono`               VARCHAR(20)    NULL DEFAULT NULL,
  `direccion_fiscal`       TEXT           NULL DEFAULT NULL,
  `vendedor_asignado`      INT UNSIGNED   NULL DEFAULT NULL,
  `metodo_pago_preferido`  VARCHAR(50)    NOT NULL DEFAULT 'transferencia',
  `credito_activo`         TINYINT(1)     NOT NULL DEFAULT 0,
  `limite_credito`         DECIMAL(12,2)  NOT NULL DEFAULT 0.00,
  `saldo_credito`          DECIMAL(12,2)  NOT NULL DEFAULT 0.00,
  `dias_credito`           TINYINT        NOT NULL DEFAULT 0,
  `fecha_registro`         DATE           NULL DEFAULT NULL,
  `activo`                 TINYINT(1)     NOT NULL DEFAULT 1,
  `created_at`             TIMESTAMP      NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_rfc` (`rfc`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO `empresas` VALUES
(1, 'Taquería El Buen Sabor S.A. de C.V.', 'TBS180101ABC', '601 - General de Ley Personas Morales',
   'contacto@elbuensabor.com', '442 123 4567',
   'Av. Juárez 123, Centro, Querétaro, Qro. 76000',
   2, 'transferencia', 0, 0.00, 0.00, 0, '2023-01-15', 1, NOW()),
(2, 'Carnes Finas del Norte S.C.',         'CFN200601XYZ', '612 - Personas Físicas con Actividades Empresariales',
   'contacto@carnesfinas.mx', '442 987 6543',
   'Blvd. Juriquilla 456, Juriquilla, Querétaro, Qro. 76230',
   2, 'credito', 1, 90000.00, 8750.00, 30, '2023-06-10', 1, NOW()),
(3, 'Comedores Industriales del Bajío SA', 'CIB190404JKL', '601 - General de Ley Personas Morales',
   'ventas@comeind.mx', '442 555 0011',
   'Carretera a El Marqués Km 12, El Marqués, Qro. 76250',
   2, 'transferencia', 0, 0.00, 0.00, 0, '2024-01-20', 1, NOW());

-- ─────────────────────────────────────────
-- 4. sucursales
-- ─────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `sucursales` (
  `id`                INT UNSIGNED  NOT NULL AUTO_INCREMENT,
  `empresa_id`        INT UNSIGNED  NOT NULL,
  `nombre`            VARCHAR(150)  NOT NULL,
  `direccion`         TEXT          NOT NULL,
  `ciudad`            VARCHAR(100)  NULL DEFAULT NULL,
  `estado`            VARCHAR(100)  NOT NULL DEFAULT 'Querétaro',
  `cp`                VARCHAR(10)   NULL DEFAULT NULL,
  `lat`               DECIMAL(10,8) NULL DEFAULT NULL,
  `lng`               DECIMAL(11,8) NULL DEFAULT NULL,
  `contacto_nombre`   VARCHAR(100)  NULL DEFAULT NULL,
  `contacto_telefono` VARCHAR(20)   NULL DEFAULT NULL,
  `activo`            TINYINT(1)    NOT NULL DEFAULT 1,
  PRIMARY KEY (`id`),
  KEY `fk_suc_empresa` (`empresa_id`),
  CONSTRAINT `fk_suc_empresa` FOREIGN KEY (`empresa_id`) REFERENCES `empresas` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO `sucursales` VALUES
(1,  1, 'Centro Histórico',   'Av. Juárez 123, Centro, Querétaro',             'Querétaro',   'Querétaro', '76000', 20.58790000, -100.38980000, 'Juan Pérez',     '442 123 4567', 1),
(2,  1, 'Juriquilla',         'Blvd. Juriquilla 789, Juriquilla, Querétaro',   'Querétaro',   'Querétaro', '76230', 20.71200000, -100.44900000, 'Laura Soto',     '442 234 5678', 1),
(3,  2, 'El Marqués',         'Carretera El Marqués Km 5, El Marqués',         'El Marqués',  'Querétaro', '76250', 20.60100000, -100.28800000, 'Roberto García', '442 345 6789', 1),
(4,  2, 'Corregidora',        'Av. 5 de Febrero 321, Corregidora, Qro.',       'Corregidora', 'Querétaro', '76900', 20.53500000, -100.42100000, 'Sofía Ruiz',     '442 456 7890', 1),
(5,  3, 'Planta El Marqués',  'Carretera a El Marqués Km 12, El Marqués',      'El Marqués',  'Querétaro', '76250', 20.59800000, -100.27600000, 'Marcos López',   '442 567 8901', 1),
(6,  1, 'Peñafiel (Tequisquiapan)', 'Calle Morelos 45, Tequisquiapan, Qro.', 'Tequisquiapan','Querétaro', '76750', 20.51900000, -99.89700000,  'Diana Cruz',     '442 678 9012', 0);

-- ─────────────────────────────────────────
-- 5. categorias
-- ─────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `categorias` (
  `id`     INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `nombre` VARCHAR(100) NOT NULL,
  `slug`   VARCHAR(100) NOT NULL,
  `activo` TINYINT(1)   NOT NULL DEFAULT 1,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_cat_slug` (`slug`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO `categorias` VALUES
(1, 'Res',    'res',    1),
(2, 'Cerdo',  'cerdo',  1),
(3, 'Pollo',  'pollo',  1),
(4, 'Otros',  'otros',  1);

-- ─────────────────────────────────────────
-- 6. productos
-- ─────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `productos` (
  `id`           INT UNSIGNED  NOT NULL AUTO_INCREMENT,
  `nombre`       VARCHAR(150)  NOT NULL,
  `categoria_id` INT UNSIGNED  NOT NULL,
  `descripcion`  TEXT          NULL DEFAULT NULL,
  `presentacion` VARCHAR(50)   NOT NULL DEFAULT 'kg',
  `precio_base`  DECIMAL(10,2) NOT NULL,
  `imagen`       VARCHAR(255)  NULL DEFAULT NULL,
  `activo`       TINYINT(1)    NOT NULL DEFAULT 1,
  `created_at`   TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `fk_prod_cat` (`categoria_id`),
  CONSTRAINT `fk_prod_cat` FOREIGN KEY (`categoria_id`) REFERENCES `categorias` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO `productos` VALUES
(1, 'Bistec de res',   1, 'Corte suave y de excelente calidad, ideal para una gran variedad de platillos.',          'kg', 185.00, 'bistec.jpg',   1, NOW()),
(2, 'Pastor',          2, 'Carne de cerdo marinada lista para trompo, sazonada con achiote y especias.',             'kg', 175.00, 'pastor.jpg',   1, NOW()),
(3, 'Falda de res',    1, 'Corte versátil con buen contenido de grasa, perfecto para birria y guisados.',            'kg', 165.00, 'falda.jpg',    1, NOW()),
(4, 'Suadero',         1, 'Corte fino entre la piel y la costilla, jugoso y de sabor intenso.',                      'kg', 180.00, 'suadero.jpg',  1, NOW()),
(5, 'Chuleta de res',  1, 'Corte con hueso de gran sabor, ideal para asar o cocer.',                                 'kg', 190.00, 'chuleta.jpg',  1, NOW()),
(6, 'Milanesa de res', 1, 'Corte delgado y suavizado, listo para empanizar o preparar a la mexicana.',               'kg', 195.00, 'milanesa.jpg', 1, NOW()),
(7, 'Pechuga de pollo',3, 'Pechuga sin hueso, fresca, baja en grasa. Presentación entera o fileteada.',             'kg', 85.00,  'pechuga.jpg',  1, NOW()),
(8, 'Costilla de cerdo',2,'Costilla carnuda con hueso, ideal para barbacoa y horno.',                                'kg', 140.00, 'costilla.jpg', 1, NOW());

-- ─────────────────────────────────────────
-- 7. precios_escalonados
-- ─────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `precios_escalonados` (
  `id`               INT UNSIGNED  NOT NULL AUTO_INCREMENT,
  `producto_id`      INT UNSIGNED  NOT NULL,
  `rango_min`        DECIMAL(10,2) NOT NULL,
  `rango_max`        DECIMAL(10,2) NULL DEFAULT NULL,
  `precio_por_unidad`DECIMAL(10,2) NOT NULL,
  `activo`           TINYINT(1)    NOT NULL DEFAULT 1,
  PRIMARY KEY (`id`),
  KEY `fk_pe_prod` (`producto_id`),
  CONSTRAINT `fk_pe_prod` FOREIGN KEY (`producto_id`) REFERENCES `productos` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO `precios_escalonados` VALUES
-- Bistec de res
(1,  1, 1,   10,   185.00, 1),
(2,  1, 11,  50,   175.00, 1),
(3,  1, 51,  100,  170.00, 1),
(4,  1, 101, NULL, 165.00, 1),
-- Pastor
(5,  2, 1,   10,   175.00, 1),
(6,  2, 11,  50,   165.00, 1),
(7,  2, 51,  100,  158.00, 1),
(8,  2, 101, NULL, 150.00, 1),
-- Falda de res
(9,  3, 1,   10,   165.00, 1),
(10, 3, 11,  50,   155.00, 1),
(11, 3, 51,  100,  148.00, 1),
(12, 3, 101, NULL, 140.00, 1),
-- Suadero
(13, 4, 1,   10,   180.00, 1),
(14, 4, 11,  50,   170.00, 1),
(15, 4, 51,  100,  163.00, 1),
(16, 4, 101, NULL, 155.00, 1),
-- Chuleta de res
(17, 5, 1,   10,   190.00, 1),
(18, 5, 11,  50,   180.00, 1),
(19, 5, 51,  100,  173.00, 1),
(20, 5, 101, NULL, 165.00, 1),
-- Milanesa
(21, 6, 1,   10,   195.00, 1),
(22, 6, 11,  50,   185.00, 1),
(23, 6, 51,  100,  178.00, 1),
(24, 6, 101, NULL, 170.00, 1),
-- Pechuga de pollo
(25, 7, 1,   20,   85.00,  1),
(26, 7, 21,  80,   78.00,  1),
(27, 7, 81,  NULL, 72.00,  1),
-- Costilla de cerdo
(28, 8, 1,   15,   140.00, 1),
(29, 8, 16,  60,   130.00, 1),
(30, 8, 61,  NULL, 120.00, 1);

-- ─────────────────────────────────────────
-- 8. inventario
-- ─────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `inventario` (
  `id`            INT UNSIGNED  NOT NULL AUTO_INCREMENT,
  `producto_id`   INT UNSIGNED  NOT NULL,
  `disponible`    DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `en_transito`   DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `reservado`     DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `minimo_alerta` DECIMAL(10,2) NOT NULL DEFAULT 50.00,
  `unidad`        VARCHAR(20)   NOT NULL DEFAULT 'kg',
  `updated_at`    TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_inv_prod` (`producto_id`),
  CONSTRAINT `fk_inv_prod` FOREIGN KEY (`producto_id`) REFERENCES `productos` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO `inventario` VALUES
(1, 1, 1250.00, 350.00, 120.00, 200.00, 'kg', NOW()),
(2, 2,  980.00, 200.00,  80.00, 150.00, 'kg', NOW()),
(3, 3,  450.00, 150.00,  60.00, 100.00, 'kg', NOW()),
(4, 4,  300.00, 100.00,  40.00,  80.00, 'kg', NOW()),
(5, 5, 1100.00, 250.00, 100.00, 150.00, 'kg', NOW()),
(6, 6,  600.00,  80.00,  50.00,  80.00, 'kg', NOW()),
(7, 7, 2000.00, 400.00, 100.00, 200.00, 'kg', NOW()),
(8, 8,  750.00, 100.00,  30.00,  80.00, 'kg', NOW());

-- ─────────────────────────────────────────
-- 9. vehiculos
-- ─────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `vehiculos` (
  `id`           INT UNSIGNED  NOT NULL AUTO_INCREMENT,
  `placa`        VARCHAR(20)   NOT NULL,
  `modelo`       VARCHAR(100)  NULL DEFAULT NULL,
  `marca`        VARCHAR(100)  NULL DEFAULT NULL,
  `capacidad_kg` DECIMAL(10,2) NULL DEFAULT NULL,
  `activo`       TINYINT(1)    NOT NULL DEFAULT 1,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_placa` (`placa`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO `vehiculos` VALUES
(1, 'GTQ-1234-A', 'NP300', 'Nissan',     1500.00, 1),
(2, 'GTQ-5678-B', 'Transit', 'Ford',     2000.00, 1),
(3, 'GTQ-9012-C', 'Sprinter', 'Mercedes',2500.00, 1);

-- ─────────────────────────────────────────
-- 10. choferes
-- ─────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `choferes` (
  `id`           INT UNSIGNED   NOT NULL AUTO_INCREMENT,
  `usuario_id`   INT UNSIGNED   NOT NULL,
  `vehiculo_id`  INT UNSIGNED   NULL DEFAULT NULL,
  `licencia`     VARCHAR(50)    NULL DEFAULT NULL,
  `calificacion` DECIMAL(3,2)   NOT NULL DEFAULT 5.00,
  `activo`       TINYINT(1)     NOT NULL DEFAULT 1,
  PRIMARY KEY (`id`),
  KEY `fk_cho_usr` (`usuario_id`),
  KEY `fk_cho_veh` (`vehiculo_id`),
  CONSTRAINT `fk_cho_usr` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`),
  CONSTRAINT `fk_cho_veh` FOREIGN KEY (`vehiculo_id`) REFERENCES `vehiculos` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO `choferes` VALUES
(1, 5, 1, 'QRO-2023-001', 4.80, 1),
(2, 6, 2, 'QRO-2022-045', 4.60, 1);

-- ─────────────────────────────────────────
-- 11. pedidos
-- ─────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `pedidos` (
  `id`              INT UNSIGNED   NOT NULL AUTO_INCREMENT,
  `folio`           VARCHAR(30)    NOT NULL,
  `empresa_id`      INT UNSIGNED   NOT NULL,
  `usuario_id`      INT UNSIGNED   NOT NULL,
  `fecha_pedido`    DATETIME       NOT NULL,
  `fecha_entrega`   DATE           NULL DEFAULT NULL,
  `ventana_entrega` VARCHAR(50)    NULL DEFAULT NULL,
  `total`           DECIMAL(12,2)  NOT NULL DEFAULT 0.00,
  `estado`          ENUM('pendiente','confirmado','en_preparacion','en_ruta','entregado','cancelado') NOT NULL DEFAULT 'pendiente',
  `metodo_pago`     VARCHAR(50)    NULL DEFAULT NULL,
  `notas`           TEXT           NULL DEFAULT NULL,
  `created_at`      TIMESTAMP      NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_folio` (`folio`),
  KEY `fk_ped_emp` (`empresa_id`),
  KEY `fk_ped_usr` (`usuario_id`),
  CONSTRAINT `fk_ped_emp` FOREIGN KEY (`empresa_id`) REFERENCES `empresas` (`id`),
  CONSTRAINT `fk_ped_usr` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO `pedidos` VALUES
(1,  'CHB-2024-1250', 1, 3, '2024-05-24 09:00:00', '2024-05-24', '08:00-12:00', 12200.00,  'entregado',       'transferencia', NULL, '2024-05-24 09:00:00'),
(2,  'CHB-2024-1249', 2, 7, '2024-05-24 10:00:00', '2024-05-24', '10:00-14:00',  8750.00,  'confirmado',      'credito',       NULL, '2024-05-24 10:00:00'),
(3,  'CHB-2024-1248', 1, 3, '2024-05-23 08:30:00', '2024-05-23', '08:00-12:00', 15680.00,  'en_preparacion',  'transferencia', NULL, '2024-05-23 08:30:00'),
(4,  'CHB-2024-1247', 3, 7, '2024-05-23 11:00:00', '2024-05-23', '12:00-16:00', 28540.00,  'en_ruta',         'transferencia', NULL, '2024-05-23 11:00:00'),
(5,  'CHB-2024-1246', 2, 7, '2024-05-22 09:00:00', '2024-05-22', '08:00-12:00',  6250.00,  'entregado',       'credito',       NULL, '2024-05-22 09:00:00'),
(6,  'CHB-2024-0587', 1, 3, '2024-05-20 08:00:00', '2024-05-20', '08:00-12:00', 12200.00,  'entregado',       'transferencia', NULL, '2024-05-20 08:00:00'),
(7,  'CHB-2024-0571', 1, 3, '2024-05-13 08:00:00', '2024-05-13', '08:00-12:00',  9850.00,  'entregado',       'transferencia', NULL, '2024-05-13 08:00:00'),
(8,  'CHB-2024-0566', 1, 3, '2024-05-06 08:00:00', '2024-05-06', '08:00-12:00',  8450.00,  'entregado',       'transferencia', NULL, '2024-05-06 08:00:00'),
(9,  'CHB-2024-0540', 1, 3, '2024-04-29 08:00:00', '2024-04-29', '08:00-12:00', 11300.00,  'en_ruta',         'transferencia', NULL, '2024-04-29 08:00:00'),
(10, 'CHB-2024-0522', 1, 3, '2024-04-22 08:00:00', '2024-04-22', '08:00-12:00',  7200.00,  'entregado',       'transferencia', NULL, '2024-04-22 08:00:00');

-- ─────────────────────────────────────────
-- 12. pedido_detalle
-- ─────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `pedido_detalle` (
  `id`             INT UNSIGNED  NOT NULL AUTO_INCREMENT,
  `pedido_id`      INT UNSIGNED  NOT NULL,
  `producto_id`    INT UNSIGNED  NOT NULL,
  `cantidad`       DECIMAL(10,2) NOT NULL,
  `precio_unitario`DECIMAL(10,2) NOT NULL,
  `subtotal`       DECIMAL(12,2) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_pd_ped` (`pedido_id`),
  KEY `fk_pd_pro` (`producto_id`),
  CONSTRAINT `fk_pd_ped` FOREIGN KEY (`pedido_id`) REFERENCES `pedidos` (`id`),
  CONSTRAINT `fk_pd_pro` FOREIGN KEY (`producto_id`) REFERENCES `productos` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO `pedido_detalle` VALUES
(1,  1, 1, 30.00, 175.00,  5250.00),
(2,  1, 2, 20.00, 175.00,  3500.00),
(3,  1, 3, 20.00, 155.00,  3100.00),
(4,  1, 4, 10.00, 175.00,  1750.00), -- adjusted for demo rounding
(5,  2, 1, 20.00, 175.00,  3500.00),
(6,  2, 3, 20.00, 155.00,  3100.00),
(7,  2, 4, 10.00, 175.00,  1750.00),
(8,  2, 5,  2.00, 190.00,   380.00);

-- ─────────────────────────────────────────
-- 13. pedido_sucursal
-- ─────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `pedido_sucursal` (
  `id`             INT UNSIGNED  NOT NULL AUTO_INCREMENT,
  `pedido_id`      INT UNSIGNED  NOT NULL,
  `sucursal_id`    INT UNSIGNED  NOT NULL,
  `producto_id`    INT UNSIGNED  NOT NULL,
  `cantidad`       DECIMAL(10,2) NOT NULL,
  `precio_unitario`DECIMAL(10,2) NOT NULL,
  `subtotal`       DECIMAL(12,2) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_ps_ped` (`pedido_id`),
  KEY `fk_ps_suc` (`sucursal_id`),
  KEY `fk_ps_pro` (`producto_id`),
  CONSTRAINT `fk_ps_ped` FOREIGN KEY (`pedido_id`) REFERENCES `pedidos` (`id`),
  CONSTRAINT `fk_ps_suc` FOREIGN KEY (`sucursal_id`) REFERENCES `sucursales` (`id`),
  CONSTRAINT `fk_ps_pro` FOREIGN KEY (`producto_id`) REFERENCES `productos` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO `pedido_sucursal` VALUES
(1, 1, 1, 1, 10.00, 185.00, 1850.00),
(2, 1, 1, 2, 15.00, 175.00, 2625.00),
(3, 1, 2, 3, 10.00, 165.00, 1650.00),
(4, 1, 2, 4,  5.00, 180.00,  900.00);

-- ─────────────────────────────────────────
-- 14. pedidos_recurrentes
-- ─────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `pedidos_recurrentes` (
  `id`             INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `empresa_id`     INT UNSIGNED NOT NULL,
  `nombre`         VARCHAR(150) NOT NULL,
  `frecuencia`     ENUM('diario','semanal','quincenal') NOT NULL DEFAULT 'semanal',
  `proximo_pedido` DATE         NULL DEFAULT NULL,
  `ultimo_pedido`  DATE         NULL DEFAULT NULL,
  `activo`         TINYINT(1)   NOT NULL DEFAULT 1,
  `pausado`        TINYINT(1)   NOT NULL DEFAULT 0,
  `created_at`     TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `fk_pr_emp` (`empresa_id`),
  CONSTRAINT `fk_pr_emp` FOREIGN KEY (`empresa_id`) REFERENCES `empresas` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO `pedidos_recurrentes` VALUES
(1, 1, 'Pedido Semanal Taquería',   'semanal',   '2024-05-31', '2024-05-24', 1, 0, NOW()),
(2, 1, 'Pedido Diario Pequeño',     'diario',    '2024-05-25', '2024-05-24', 1, 1, NOW()),
(3, 2, 'Pedido Quincenal Carnes',   'quincenal', '2024-06-01', '2024-05-17', 1, 0, NOW());

-- ─────────────────────────────────────────
-- 15. plantilla_recurrente_detalle
-- ─────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `plantilla_recurrente_detalle` (
  `id`            INT UNSIGNED  NOT NULL AUTO_INCREMENT,
  `recurrente_id` INT UNSIGNED  NOT NULL,
  `sucursal_id`   INT UNSIGNED  NOT NULL,
  `producto_id`   INT UNSIGNED  NOT NULL,
  `cantidad`      DECIMAL(10,2) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_prd_rec`  (`recurrente_id`),
  KEY `fk_prd_suc`  (`sucursal_id`),
  KEY `fk_prd_prod` (`producto_id`),
  CONSTRAINT `fk_prd_rec`  FOREIGN KEY (`recurrente_id`) REFERENCES `pedidos_recurrentes` (`id`),
  CONSTRAINT `fk_prd_suc`  FOREIGN KEY (`sucursal_id`)   REFERENCES `sucursales` (`id`),
  CONSTRAINT `fk_prd_prod` FOREIGN KEY (`producto_id`)   REFERENCES `productos` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO `plantilla_recurrente_detalle` VALUES
(1, 1, 1, 1, 20.00),
(2, 1, 1, 2, 30.00),
(3, 1, 2, 3, 15.00),
(4, 1, 2, 4, 10.00);

-- ─────────────────────────────────────────
-- 16. rutas
-- ─────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `rutas` (
  `id`             INT UNSIGNED  NOT NULL AUTO_INCREMENT,
  `nombre`         VARCHAR(150)  NOT NULL,
  `fecha`          DATE          NOT NULL,
  `chofer_id`      INT UNSIGNED  NULL DEFAULT NULL,
  `vehiculo_id`    INT UNSIGNED  NULL DEFAULT NULL,
  `estado`         ENUM('pendiente','en_preparacion','en_ruta','completada') NOT NULL DEFAULT 'pendiente',
  `total_entregas` INT           NOT NULL DEFAULT 0,
  `km_estimados`   DECIMAL(10,2) NULL DEFAULT NULL,
  `created_at`     TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `fk_rut_cho` (`chofer_id`),
  KEY `fk_rut_veh` (`vehiculo_id`),
  CONSTRAINT `fk_rut_cho` FOREIGN KEY (`chofer_id`)  REFERENCES `choferes` (`id`),
  CONSTRAINT `fk_rut_veh` FOREIGN KEY (`vehiculo_id`) REFERENCES `vehiculos` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO `rutas` VALUES
(1, 'Ruta 1 — Centro Norte', '2024-05-24', 1, 1, 'en_ruta',    5, 85.00, NOW()),
(2, 'Ruta 2 — León Sur',     '2024-05-24', 2, 2, 'en_preparacion', 3, 65.00, NOW()),
(3, 'Ruta 3 — Silao Apaseo', '2024-05-24', NULL, NULL, 'pendiente', 4, 120.00, NOW());

-- ─────────────────────────────────────────
-- 17. ruta_detalle
-- ─────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `ruta_detalle` (
  `id`             INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `ruta_id`        INT UNSIGNED NOT NULL,
  `pedido_id`      INT UNSIGNED NOT NULL,
  `sucursal_id`    INT UNSIGNED NOT NULL,
  `orden_entrega`  TINYINT      NOT NULL DEFAULT 1,
  `hora_estimada`  TIME         NULL DEFAULT NULL,
  `estado`         ENUM('pendiente','en_ruta','entregado','incidente') NOT NULL DEFAULT 'pendiente',
  PRIMARY KEY (`id`),
  KEY `fk_rd_rut` (`ruta_id`),
  KEY `fk_rd_ped` (`pedido_id`),
  KEY `fk_rd_suc` (`sucursal_id`),
  CONSTRAINT `fk_rd_rut` FOREIGN KEY (`ruta_id`)    REFERENCES `rutas` (`id`),
  CONSTRAINT `fk_rd_ped` FOREIGN KEY (`pedido_id`)  REFERENCES `pedidos` (`id`),
  CONSTRAINT `fk_rd_suc` FOREIGN KEY (`sucursal_id`) REFERENCES `sucursales` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO `ruta_detalle` VALUES
(1, 1, 1, 1, 1, '10:30:00', 'en_ruta'),
(2, 1, 3, 2, 2, '11:30:00', 'pendiente'),
(3, 1, 4, 5, 3, '12:30:00', 'pendiente');

-- ─────────────────────────────────────────
-- 18. evidencias_entrega
-- ─────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `evidencias_entrega` (
  `id`                INT UNSIGNED  NOT NULL AUTO_INCREMENT,
  `ruta_detalle_id`   INT UNSIGNED  NOT NULL,
  `tipo`              ENUM('foto','firma') NOT NULL,
  `archivo`           VARCHAR(255)  NULL DEFAULT NULL,
  `receptor_nombre`   VARCHAR(100)  NULL DEFAULT NULL,
  `timestamp_entrega` DATETIME      NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_ev_rd` (`ruta_detalle_id`),
  CONSTRAINT `fk_ev_rd` FOREIGN KEY (`ruta_detalle_id`) REFERENCES `ruta_detalle` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ─────────────────────────────────────────
-- 19. pagos
-- ─────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `pagos` (
  `id`         INT UNSIGNED  NOT NULL AUTO_INCREMENT,
  `pedido_id`  INT UNSIGNED  NOT NULL,
  `monto`      DECIMAL(12,2) NOT NULL,
  `metodo`     VARCHAR(50)   NULL DEFAULT NULL,
  `referencia` VARCHAR(100)  NULL DEFAULT NULL,
  `estado`     ENUM('pendiente','procesado','fallido') NOT NULL DEFAULT 'pendiente',
  `fecha`      DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `fk_pago_ped` (`pedido_id`),
  CONSTRAINT `fk_pago_ped` FOREIGN KEY (`pedido_id`) REFERENCES `pedidos` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ─────────────────────────────────────────
-- 20. facturas
-- ─────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `facturas` (
  `id`            INT UNSIGNED  NOT NULL AUTO_INCREMENT,
  `pedido_id`     INT UNSIGNED  NOT NULL,
  `empresa_id`    INT UNSIGNED  NOT NULL,
  `uuid_cfdi`     VARCHAR(100)  NULL DEFAULT NULL,
  `folio_fiscal`  VARCHAR(100)  NULL DEFAULT NULL,
  `xml_url`       VARCHAR(255)  NULL DEFAULT NULL,
  `pdf_url`       VARCHAR(255)  NULL DEFAULT NULL,
  `total`         DECIMAL(12,2) NULL DEFAULT NULL,
  `fecha_emision` DATETIME      NULL DEFAULT NULL,
  `estado`        ENUM('pendiente','timbrada','cancelada') NOT NULL DEFAULT 'pendiente',
  PRIMARY KEY (`id`),
  KEY `fk_fac_ped` (`pedido_id`),
  KEY `fk_fac_emp` (`empresa_id`),
  CONSTRAINT `fk_fac_ped` FOREIGN KEY (`pedido_id`)  REFERENCES `pedidos` (`id`),
  CONSTRAINT `fk_fac_emp` FOREIGN KEY (`empresa_id`) REFERENCES `empresas` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ─────────────────────────────────────────
-- 21. global_settings
-- ─────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `global_settings` (
  `id`          INT UNSIGNED  NOT NULL AUTO_INCREMENT,
  `clave`       VARCHAR(100)  NOT NULL,
  `valor`       TEXT          NULL DEFAULT NULL,
  `grupo`       VARCHAR(50)   NOT NULL DEFAULT 'general',
  `descripcion` VARCHAR(255)  NULL DEFAULT NULL,
  `updated_at`  TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_clave` (`clave`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO `global_settings` (`clave`, `valor`, `grupo`, `descripcion`) VALUES
-- General
('app_nombre',        'CarniHub',                      'general',    'Nombre del sistema'),
('app_email',         'contacto@carnihub.mx',          'general',    'Correo principal'),
('app_telefono',      '442 123 0000',                  'general',    'Teléfono principal'),
('app_horario',       'Lun–Vie 07:00–17:00',           'general',    'Horario de atención'),
('app_logo',          '',                              'general',    'Ruta al logotipo'),
-- Estilos
('estilo_color',      '#C8102E',                       'estilos',    'Color primario hex'),
-- APIs
('api_paypal_key',    '',                              'apis',       'PayPal Client ID'),
('api_paypal_secret', '',                              'apis',       'PayPal Secret'),
('api_facturalo_key', '',                              'apis',       'Factura-lo API Key'),
('api_traccar_url',   '',                              'apis',       'Traccar Server URL'),
('api_traccar_user',  '',                              'apis',       'Traccar Usuario'),
('api_traccar_pass',  '',                              'apis',       'Traccar Contraseña'),
('api_whatsapp_token','',                              'apis',       'WhatsApp API Token'),
('api_whatsapp_phone','',                              'apis',       'WhatsApp Phone ID'),
-- Notificaciones
('notif_email',       '1',                             'notificaciones','Enviar notificaciones por email'),
('notif_whatsapp',    '0',                             'notificaciones','Enviar notificaciones por WhatsApp'),
-- Facturación
('sat_rfc',           '',                              'facturacion','RFC del emisor'),
('sat_razon_social',  '',                              'facturacion','Razón social del emisor');

-- ─────────────────────────────────────────
-- 22. action_logs
-- ─────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `action_logs` (
  `id`          BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `usuario_id`  INT UNSIGNED    NULL DEFAULT NULL,
  `accion`      VARCHAR(200)    NOT NULL,
  `modulo`      VARCHAR(100)    NULL DEFAULT NULL,
  `descripcion` TEXT            NULL DEFAULT NULL,
  `ip`          VARCHAR(45)     NULL DEFAULT NULL,
  `user_agent`  VARCHAR(255)    NULL DEFAULT NULL,
  `created_at`  TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_al_usuario` (`usuario_id`),
  KEY `idx_al_created` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ─────────────────────────────────────────
-- 23. error_logs
-- ─────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `error_logs` (
  `id`         BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `nivel`      ENUM('error','warning','info') NOT NULL DEFAULT 'error',
  `mensaje`    TEXT            NOT NULL,
  `archivo`    VARCHAR(255)    NULL DEFAULT NULL,
  `linea`      INT             NULL DEFAULT NULL,
  `trace`      TEXT            NULL DEFAULT NULL,
  `created_at` TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_el_nivel`   (`nivel`),
  KEY `idx_el_created` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ─────────────────────────────────────────
-- 24. dispositivos_hikvision
-- ─────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `dispositivos_hikvision` (
  `id`           INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `nombre`       VARCHAR(100) NOT NULL,
  `ip`           VARCHAR(45)  NOT NULL,
  `puerto`       INT          NOT NULL DEFAULT 80,
  `usuario`      VARCHAR(100) NULL DEFAULT NULL,
  `password_enc` VARCHAR(255) NULL DEFAULT NULL,
  `canal`        INT          NOT NULL DEFAULT 1,
  `tipo`         VARCHAR(50)  NOT NULL DEFAULT 'camara',
  `activo`       TINYINT(1)   NOT NULL DEFAULT 1,
  `ubicacion`    VARCHAR(200) NULL DEFAULT NULL,
  `created_at`   TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ─────────────────────────────────────────
-- 25. dispositivos_shelly
-- ─────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `dispositivos_shelly` (
  `id`            INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `nombre`        VARCHAR(100) NOT NULL,
  `device_id`     VARCHAR(100) NOT NULL,
  `auth_key`      VARCHAR(255) NULL DEFAULT NULL,
  `tipo`          VARCHAR(50)  NOT NULL DEFAULT 'relay',
  `ubicacion`     VARCHAR(200) NULL DEFAULT NULL,
  `activo`        TINYINT(1)   NOT NULL DEFAULT 1,
  `estado_actual` VARCHAR(50)  NULL DEFAULT NULL,
  `updated_at`    TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ─────────────────────────────────────────
-- ALTER para instalaciones existentes (MySQL 5.7)
-- Ejecutar solo si la BD ya existía antes de esta versión
-- ─────────────────────────────────────────
-- ALTER TABLE `usuarios` ADD COLUMN `apellido_paterno` VARCHAR(100) NOT NULL DEFAULT '' AFTER `nombre`;
-- ALTER TABLE `usuarios` ADD COLUMN `apellido_materno` VARCHAR(100) NULL DEFAULT NULL AFTER `apellido_paterno`;

SET foreign_key_checks = 1;

-- ─────────────────────────────────────────
-- Seed action log inicial
-- ─────────────────────────────────────────
INSERT INTO `action_logs` (`usuario_id`, `accion`, `modulo`, `descripcion`, `ip`) VALUES
(1, 'Sistema instalado', 'sistema', 'Instalación inicial de CarniHub', '127.0.0.1');
