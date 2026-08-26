-- Tabla rest_cortes: no existe en idactivo_cafeteq.sql pero el módulo de
-- "Corte de Caja" (RestFinanzasController::cortes/guardarCorte) ya la usa.
-- Nota: es el corte simple/manual actual. Cuando se construya el módulo de
-- Cajero (ver plan-web-cajero.md), turnos_caja será la fuente real de verdad
-- por turno; esta tabla queda como registro manual mientras tanto.

CREATE TABLE IF NOT EXISTS `rest_cortes` (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `restaurante_id` int(10) UNSIGNED NOT NULL,
  `turno` varchar(50) NOT NULL DEFAULT 'General',
  `usuario_id` int(10) UNSIGNED NOT NULL,
  `ingresos` decimal(12,2) NOT NULL DEFAULT 0.00,
  `gastos` decimal(12,2) NOT NULL DEFAULT 0.00,
  `retiros` decimal(12,2) NOT NULL DEFAULT 0.00,
  `propinas` decimal(12,2) NOT NULL DEFAULT 0.00,
  `utilidad_neta` decimal(12,2) NOT NULL DEFAULT 0.00,
  `notas` text,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_rcortes_rest` (`restaurante_id`),
  KEY `fk_rcortes_usr` (`usuario_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
