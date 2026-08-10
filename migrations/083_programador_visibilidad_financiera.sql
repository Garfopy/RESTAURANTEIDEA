-- Rol PROGRAMADOR y ocultamiento reversible de datos financieros historicos.
-- La regla solo afecta consultas del portal; nunca elimina ni modifica ventas,
-- tickets, puntos, movimientos de monedero o historial de clientes.

INSERT IGNORE INTO `roles` (`id`, `nombre`, `slug`)
VALUES (12, 'Programador', 'programador');

CREATE TABLE IF NOT EXISTS `rest_visibilidad_financiera` (
  `restaurante_id` INT UNSIGNED NOT NULL,
  `activo` TINYINT(1) NOT NULL DEFAULT 0,
  `ocultar_hasta` DATE NULL,
  `actualizado_por` INT UNSIGNED NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`restaurante_id`),
  KEY `idx_rvf_activo_fecha` (`activo`, `ocultar_hasta`),
  KEY `idx_rvf_usuario` (`actualizado_por`),
  CONSTRAINT `fk_rvf_restaurante`
    FOREIGN KEY (`restaurante_id`) REFERENCES `rest_restaurantes` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_rvf_usuario`
    FOREIGN KEY (`actualizado_por`) REFERENCES `usuarios` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `rest_visibilidad_financiera_historial` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `restaurante_id` INT UNSIGNED NOT NULL,
  `accion` ENUM('ocultar', 'restaurar') NOT NULL,
  `ocultar_hasta` DATE NULL,
  `usuario_id` INT UNSIGNED NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_rvfh_rest_fecha` (`restaurante_id`, `created_at`),
  KEY `idx_rvfh_usuario` (`usuario_id`),
  CONSTRAINT `fk_rvfh_restaurante`
    FOREIGN KEY (`restaurante_id`) REFERENCES `rest_restaurantes` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_rvfh_usuario`
    FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
