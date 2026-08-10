-- Validaciones manuales de salida realizadas por PROGRAMADOR.
-- Conserva una bitacora independiente sin modificar el historial financiero.

CREATE TABLE IF NOT EXISTS `rest_validaciones_salida_programador` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `restaurante_id` INT UNSIGNED NOT NULL,
  `visita_id` INT UNSIGNED NOT NULL,
  `ticket_folio` VARCHAR(80) NULL,
  `cliente_referencia` VARCHAR(200) NULL,
  `mesa_referencia` VARCHAR(100) NULL,
  `pagada_at` DATETIME NULL,
  `salida_registrada_at` DATETIME NOT NULL,
  `motivo` VARCHAR(500) NOT NULL,
  `usuario_id` INT UNSIGNED NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_rvsp_rest_fecha` (`restaurante_id`, `created_at`),
  KEY `idx_rvsp_visita` (`visita_id`),
  KEY `idx_rvsp_usuario` (`usuario_id`),
  CONSTRAINT `fk_rvsp_restaurante`
    FOREIGN KEY (`restaurante_id`) REFERENCES `rest_restaurantes` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_rvsp_visita`
    FOREIGN KEY (`visita_id`) REFERENCES `rest_visitas` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_rvsp_usuario`
    FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
