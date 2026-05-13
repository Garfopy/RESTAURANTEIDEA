-- ============================================================
-- CarniHub v3.2 — Módulo Restaurantes (finanzas)
-- Ejecutar DESPUÉS de 022_restaurantes_core.sql
-- ============================================================

SET FOREIGN_KEY_CHECKS = 0;

-- ── Gastos del restaurante ────────────────────────────────────
CREATE TABLE `rest_gastos` (
  `id`             INT UNSIGNED   NOT NULL AUTO_INCREMENT,
  `restaurante_id` INT UNSIGNED   NOT NULL,
  `categoria`      ENUM('personal','suministros','mantenimiento','servicios','propinas','devolucion','marketing','otros') NOT NULL DEFAULT 'otros',
  `descripcion`    VARCHAR(255)   NOT NULL,
  `monto`          DECIMAL(10,2)  NOT NULL,
  `fecha`          DATE           NOT NULL,
  `comprobante`    VARCHAR(255)   NULL,
  `usuario_id`     INT UNSIGNED   NOT NULL,
  `created_at`     TIMESTAMP      NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_rgasto_rest`  (`restaurante_id`),
  KEY `idx_rgasto_fecha` (`fecha`),
  CONSTRAINT `fk_rgasto_rest` FOREIGN KEY (`restaurante_id`) REFERENCES `rest_restaurantes`(`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_rgasto_usr`  FOREIGN KEY (`usuario_id`)     REFERENCES `usuarios`(`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ── Retiros de caja ───────────────────────────────────────────
CREATE TABLE `rest_retiros` (
  `id`             INT UNSIGNED   NOT NULL AUTO_INCREMENT,
  `restaurante_id` INT UNSIGNED   NOT NULL,
  `descripcion`    VARCHAR(255)   NOT NULL,
  `monto`          DECIMAL(10,2)  NOT NULL,
  `usuario_id`     INT UNSIGNED   NOT NULL,
  `created_at`     TIMESTAMP      NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_rret_rest` (`restaurante_id`),
  CONSTRAINT `fk_rret_rest` FOREIGN KEY (`restaurante_id`) REFERENCES `rest_restaurantes`(`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_rret_usr`  FOREIGN KEY (`usuario_id`)     REFERENCES `usuarios`(`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ── Cortes de caja ────────────────────────────────────────────
CREATE TABLE `rest_cortes` (
  `id`             INT UNSIGNED   NOT NULL AUTO_INCREMENT,
  `restaurante_id` INT UNSIGNED   NOT NULL,
  `turno`          VARCHAR(50)    NOT NULL DEFAULT 'General',
  `usuario_id`     INT UNSIGNED   NOT NULL,
  `ingresos`       DECIMAL(12,2)  NOT NULL DEFAULT 0.00,
  `gastos`         DECIMAL(12,2)  NOT NULL DEFAULT 0.00,
  `retiros`        DECIMAL(12,2)  NOT NULL DEFAULT 0.00,
  `propinas`       DECIMAL(12,2)  NOT NULL DEFAULT 0.00,
  `utilidad_neta`  DECIMAL(12,2)  NOT NULL DEFAULT 0.00,
  `notas`          TEXT           NULL,
  `created_at`     TIMESTAMP      NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_rcorte_rest` (`restaurante_id`),
  CONSTRAINT `fk_rcorte_rest` FOREIGN KEY (`restaurante_id`) REFERENCES `rest_restaurantes`(`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_rcorte_usr`  FOREIGN KEY (`usuario_id`)     REFERENCES `usuarios`(`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

SET FOREIGN_KEY_CHECKS = 1;
