-- ============================================================
-- CarniHub v3.2 — Módulo Restaurantes (reservaciones)
-- Ejecutar DESPUÉS de 023_restaurantes_finanzas.sql
-- ============================================================

SET FOREIGN_KEY_CHECKS = 0;

CREATE TABLE `rest_reservaciones` (
  `id`             INT UNSIGNED  NOT NULL AUTO_INCREMENT,
  `restaurante_id` INT UNSIGNED  NOT NULL,
  `mesa_id`        INT UNSIGNED  NULL,
  `comensal_id`    INT UNSIGNED  NULL,
  `nombre`         VARCHAR(200)  NOT NULL,
  `telefono`       VARCHAR(20)   NULL,
  `email`          VARCHAR(150)  NULL,
  `fecha`          DATE          NOT NULL,
  `hora`           TIME          NOT NULL,
  `personas`       TINYINT       NOT NULL DEFAULT 2,
  `estado`         ENUM('pendiente','confirmada','cancelada','completada') NOT NULL DEFAULT 'pendiente',
  `notas`          TEXT          NULL,
  `created_at`     TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_rres_rest`  (`restaurante_id`),
  KEY `idx_rres_fecha` (`fecha`),
  CONSTRAINT `fk_rres_rest` FOREIGN KEY (`restaurante_id`) REFERENCES `rest_restaurantes`(`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_rres_mesa` FOREIGN KEY (`mesa_id`)        REFERENCES `rest_mesas`(`id`)          ON DELETE SET NULL,
  CONSTRAINT `fk_rres_com`  FOREIGN KEY (`comensal_id`)    REFERENCES `rest_comensales`(`id`)     ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

SET FOREIGN_KEY_CHECKS = 1;
