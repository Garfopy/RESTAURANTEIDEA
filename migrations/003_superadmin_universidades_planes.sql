-- Tablas base del panel Superadmin (ver plan-web-superadmin.md §3.1-3.3).
-- No incluye rest_categorias_negocio todavia: con 1 solo negocio en produccion y el enum
-- empresas.tipo_negocio cubriendo los casos actuales, se difiere hasta que haga falta de verdad.
-- planes_negocio trae comision_pct Y cuota_mensual porque el modelo de comision (%, cuota fija
-- o hibrido) sigue sin decidirse por el equipo (plan-web-marketplace.md §11) — el plan "Basico"
-- que se inserta abajo arranca en 0/0 hasta que se resuelva.
--
-- `puntos_referencia` (antes "universidades"): se generaliza el nombre a proposito. El primer
-- caso de uso es efectivamente universidades (UTEQ), pero la tabla no debe quedar amarrada a ese
-- concepto — manana puede ser un hospital, una plaza, un fraccionamiento. Configurable solo por
-- Superadmin (igual que se penso originalmente), el texto visible en pantalla puede seguir
-- diciendo "Universidad" si el negocio lo pide, pero el esquema/codigo usan el nombre generico.

START TRANSACTION;

CREATE TABLE IF NOT EXISTS `puntos_referencia` (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `nombre` varchar(200) NOT NULL,
  `ciudad` varchar(100) DEFAULT NULL,
  `lat` decimal(10,8) NOT NULL,
  `lng` decimal(11,8) NOT NULL,
  `radio_km` decimal(5,2) NOT NULL DEFAULT '2.00',
  `activo` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `rest_puntos_referencia` (
  `restaurante_id` int(10) UNSIGNED NOT NULL,
  `punto_referencia_id` int(10) UNSIGNED NOT NULL,
  `distancia_km` decimal(6,2) DEFAULT NULL,
  `destacado_manual` tinyint(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`restaurante_id`,`punto_referencia_id`),
  KEY `idx_rpr_punto` (`punto_referencia_id`),
  CONSTRAINT `fk_rpr_restaurante` FOREIGN KEY (`restaurante_id`) REFERENCES `rest_restaurantes` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_rpr_punto` FOREIGN KEY (`punto_referencia_id`) REFERENCES `puntos_referencia` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `planes_negocio` (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `nombre` varchar(80) NOT NULL,
  `comision_pct` decimal(5,2) NOT NULL DEFAULT '0.00',
  `cuota_mensual` decimal(10,2) NOT NULL DEFAULT '0.00',
  `beneficios_json` text,
  `activo` tinyint(1) NOT NULL DEFAULT '1',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO `planes_negocio` (`id`, `nombre`, `comision_pct`, `cuota_mensual`, `activo`)
VALUES (1, 'Básico', 0.00, 0.00, 1)
ON DUPLICATE KEY UPDATE `nombre` = VALUES(`nombre`);

-- rest_restaurantes.activo (tinyint) sigue siendo el toggle tecnico on/off que ya usa el resto
-- del sistema (menu publico, dashboard, etc.) — estado_plataforma es la maquina de estados de
-- negocio que solo Superadmin puede mover (plan-web-admin.md §3.1).
ALTER TABLE `rest_restaurantes`
  ADD COLUMN `plan_id` int(10) UNSIGNED DEFAULT NULL AFTER `empresa_id`,
  ADD COLUMN `estado_plataforma` enum('pendiente','activo','suspendido','baja') NOT NULL DEFAULT 'pendiente' AFTER `activo`;

ALTER TABLE `rest_restaurantes`
  ADD CONSTRAINT `fk_rrest_plan` FOREIGN KEY (`plan_id`) REFERENCES `planes_negocio` (`id`) ON DELETE SET NULL;

-- Backfill: sin esto, UTEQ Cafeteria (activo=1, la unica sucursal real hoy) caeria en
-- estado_plataforma='pendiente' por el DEFAULT y desaparecería de cualquier listado/filtro
-- que a futuro use estado_plataforma='activo' en vez de activo=1.
UPDATE `rest_restaurantes` SET `estado_plataforma` = 'activo', `plan_id` = 1 WHERE `activo` = 1;

COMMIT;
