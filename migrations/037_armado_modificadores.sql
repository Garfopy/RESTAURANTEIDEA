-- ============================================================
-- Migration 037: Armado de platillos, modificadores y pasos de preparación
-- ============================================================
-- Corrige los 5 conflictos FK de la propuesta original:
--   · FK platillo_id   → rest_platillos (no a tabla de productos B2B)
--   · FK restaurante_id→ rest_restaurantes (no empresa_id)
--   · FK pedido_item_id→ rest_pedido_items (no rest_pedido_detalle)
--   · FK ingrediente_id→ rest_ingredientes
-- ============================================================

-- ────────────────────────────────────────────────────────────
-- 1. Añadir tipo_componente y codigo_display a rest_receta_ingredientes
--    (es_informativo y precio_extra ya existen desde migrations 030 y 036)
-- ────────────────────────────────────────────────────────────
ALTER TABLE `rest_receta_ingredientes`
  ADD COLUMN `tipo_componente`
      ENUM('materia_prima','guarnicion','salsa','extra','accion')
      NOT NULL DEFAULT 'materia_prima'
      COMMENT 'Categoría visual para el KDS'
      AFTER `notas`,
  ADD COLUMN `codigo_display`
      VARCHAR(10) NULL
      COMMENT 'Identificador manual asignado por el admin: MP1, G1, SA2…'
      AFTER `tipo_componente`;

-- ────────────────────────────────────────────────────────────
-- 2. Pasos de armado por platillo (G1/MP1/SA…)
-- ────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `rest_platillo_armado` (
  `id`            INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `restaurante_id` INT UNSIGNED NOT NULL,
  `platillo_id`   INT UNSIGNED NOT NULL,
  `orden_paso`    INT UNSIGNED NOT NULL DEFAULT 1,
  `tipo`          ENUM('ingrediente','guarnicion','accion') NOT NULL DEFAULT 'accion',
  `referencia_id` INT UNSIGNED DEFAULT NULL COMMENT 'FK opcional → rest_ingredientes',
  `descripcion`   VARCHAR(255) DEFAULT NULL,
  `obligatorio`   TINYINT(1)   NOT NULL DEFAULT 1,
  `activo`        TINYINT(1)   NOT NULL DEFAULT 1,
  `created_at`    TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_rpa_restaurante` (`restaurante_id`),
  KEY `idx_rpa_platillo`    (`platillo_id`),
  CONSTRAINT `fk_rpa_restaurante` FOREIGN KEY (`restaurante_id`)
      REFERENCES `rest_restaurantes` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_rpa_platillo`    FOREIGN KEY (`platillo_id`)
      REFERENCES `rest_platillos`    (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_rpa_ingrediente` FOREIGN KEY (`referencia_id`)
      REFERENCES `rest_ingredientes` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ────────────────────────────────────────────────────────────
-- 3. Modificadores (extras, sin, opciones) — coexisten con guarniciones
-- ────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `rest_modificadores` (
  `id`            INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `restaurante_id` INT UNSIGNED NOT NULL,
  `nombre`        VARCHAR(120)  NOT NULL,
  `descripcion`   VARCHAR(255)  DEFAULT NULL,
  `tipo`          ENUM('extra','sin','opcion') NOT NULL DEFAULT 'opcion',
  `precio_extra`  DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `activo`        TINYINT(1)   NOT NULL DEFAULT 1,
  `created_at`    TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_rm_restaurante` (`restaurante_id`),
  CONSTRAINT `fk_rm_restaurante` FOREIGN KEY (`restaurante_id`)
      REFERENCES `rest_restaurantes` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ────────────────────────────────────────────────────────────
-- 4. Relación platillo ↔ modificadores disponibles
-- ────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `rest_platillo_modificador` (
  `platillo_id`    INT UNSIGNED   NOT NULL,
  `modificador_id` INT UNSIGNED   NOT NULL,
  `obligatorio`    TINYINT(1)     NOT NULL DEFAULT 0,
  `max_seleccion`  SMALLINT UNSIGNED DEFAULT 1,
  PRIMARY KEY (`platillo_id`, `modificador_id`),
  CONSTRAINT `fk_rpm_platillo`    FOREIGN KEY (`platillo_id`)
      REFERENCES `rest_platillos`   (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_rpm_modificador` FOREIGN KEY (`modificador_id`)
      REFERENCES `rest_modificadores` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ────────────────────────────────────────────────────────────
-- 5. Modificadores elegidos por el cliente en cada ítem del pedido
-- ────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `rest_pedido_item_modificadores` (
  `id`             INT UNSIGNED   NOT NULL AUTO_INCREMENT,
  `pedido_item_id` INT UNSIGNED   NOT NULL,
  `modificador_id` INT UNSIGNED   NOT NULL,
  `cantidad`       SMALLINT UNSIGNED NOT NULL DEFAULT 1,
  `precio_extra`   DECIMAL(10,2)  NOT NULL DEFAULT 0.00,
  `created_at`     TIMESTAMP      NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_rpim_item` (`pedido_item_id`),
  CONSTRAINT `fk_rpim_item`       FOREIGN KEY (`pedido_item_id`)
      REFERENCES `rest_pedido_items`  (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_rpim_modificador` FOREIGN KEY (`modificador_id`)
      REFERENCES `rest_modificadores` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ────────────────────────────────────────────────────────────
-- 6. Pasos de preparación (visibles en KDS cuando estado = en_preparacion)
-- ────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `rest_pasos_preparacion` (
  `id`            INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `restaurante_id` INT UNSIGNED NOT NULL,
  `platillo_id`   INT UNSIGNED NOT NULL,
  `orden_paso`    INT UNSIGNED NOT NULL,
  `descripcion`   TEXT         NOT NULL,
  `activo`        TINYINT(1)   NOT NULL DEFAULT 1,
  `created_at`    TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_rpp_platillo` (`platillo_id`),
  CONSTRAINT `fk_rpp_restaurante` FOREIGN KEY (`restaurante_id`)
      REFERENCES `rest_restaurantes` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_rpp_platillo`    FOREIGN KEY (`platillo_id`)
      REFERENCES `rest_platillos`    (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
