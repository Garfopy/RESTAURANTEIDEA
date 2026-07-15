-- Promotion discount rules for mobile promotions.
-- Safe to run more than once.

SET @sql := (
  SELECT IF(COUNT(*) = 0,
    "ALTER TABLE `mobile_promociones` ADD COLUMN `tipo_descuento` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'porcentaje'",
    "SELECT 1"
  )
  FROM information_schema.columns
  WHERE table_schema = DATABASE() AND table_name = 'mobile_promociones' AND column_name = 'tipo_descuento'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql := (
  SELECT IF(COUNT(*) = 0,
    "ALTER TABLE `mobile_promociones` ADD COLUMN `valor_descuento` decimal(10,2) NOT NULL DEFAULT 0.00",
    "SELECT 1"
  )
  FROM information_schema.columns
  WHERE table_schema = DATABASE() AND table_name = 'mobile_promociones' AND column_name = 'valor_descuento'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql := (
  SELECT IF(COUNT(*) = 0,
    "ALTER TABLE `mobile_promociones` ADD COLUMN `scope_tipo` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'all'",
    "SELECT 1"
  )
  FROM information_schema.columns
  WHERE table_schema = DATABASE() AND table_name = 'mobile_promociones' AND column_name = 'scope_tipo'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql := (
  SELECT IF(COUNT(*) = 0,
    "ALTER TABLE `mobile_promociones` ADD COLUMN `scope_ids` text COLLATE utf8mb4_unicode_ci DEFAULT NULL",
    "SELECT 1"
  )
  FROM information_schema.columns
  WHERE table_schema = DATABASE() AND table_name = 'mobile_promociones' AND column_name = 'scope_ids'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql := (
  SELECT IF(COUNT(*) = 0,
    "ALTER TABLE `mobile_promociones` ADD COLUMN `buy_qty` int(10) UNSIGNED DEFAULT NULL",
    "SELECT 1"
  )
  FROM information_schema.columns
  WHERE table_schema = DATABASE() AND table_name = 'mobile_promociones' AND column_name = 'buy_qty'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql := (
  SELECT IF(COUNT(*) = 0,
    "ALTER TABLE `mobile_promociones` ADD COLUMN `pay_qty` int(10) UNSIGNED DEFAULT NULL",
    "SELECT 1"
  )
  FROM information_schema.columns
  WHERE table_schema = DATABASE() AND table_name = 'mobile_promociones' AND column_name = 'pay_qty'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql := (
  SELECT IF(COUNT(*) = 0,
    "ALTER TABLE `mobile_promociones` ADD COLUMN `min_subtotal` decimal(10,2) NOT NULL DEFAULT 0.00",
    "SELECT 1"
  )
  FROM information_schema.columns
  WHERE table_schema = DATABASE() AND table_name = 'mobile_promociones' AND column_name = 'min_subtotal'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql := (
  SELECT IF(COUNT(*) = 0,
    "ALTER TABLE `mobile_promociones` ADD COLUMN `max_uses` int(10) UNSIGNED DEFAULT NULL",
    "SELECT 1"
  )
  FROM information_schema.columns
  WHERE table_schema = DATABASE() AND table_name = 'mobile_promociones' AND column_name = 'max_uses'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql := (
  SELECT IF(COUNT(*) = 0,
    "ALTER TABLE `mobile_promociones` ADD COLUMN `combinable` tinyint(1) NOT NULL DEFAULT 0",
    "SELECT 1"
  )
  FROM information_schema.columns
  WHERE table_schema = DATABASE() AND table_name = 'mobile_promociones' AND column_name = 'combinable'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;
