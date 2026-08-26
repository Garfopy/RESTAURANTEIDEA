-- ============================================================
-- 002_cajero_pos.sql — Módulo Cajero (POS)
--
-- Base: esquema `idactivo_cafeteq` (MySQL 5.7.23).
-- Contexto y decisiones: ver plan-web-cajero.md en la raíz del repo.
--
-- Es IDEMPOTENTE: se puede correr varias veces sin romper nada.
-- Usa un procedimiento auxiliar porque MySQL 5.7 no soporta
-- ADD COLUMN IF NOT EXISTS. Si tu usuario de BD no tiene permiso
-- CREATE ROUTINE, ver el "Plan B" al final del archivo.
-- ============================================================

-- ── 0. Helpers idempotentes ──────────────────────────────────
DROP PROCEDURE IF EXISTS pos_add_column;
DROP PROCEDURE IF EXISTS pos_add_index;

DELIMITER $$

-- Las comparaciones van con BINARY a propósito: information_schema usa su
-- propia collation (utf8_general_ci en MySQL 5.7) que no coincide con la de
-- las tablas de la app (utf8mb4_*), y mezclarlas revienta con
-- "Illegal mix of collations". BINARY compara byte a byte y evita el choque
-- por completo (los nombres de tabla/columna aquí siempre son ASCII).
CREATE PROCEDURE pos_add_column(IN p_tabla VARCHAR(64), IN p_col VARCHAR(64), IN p_ddl TEXT)
BEGIN
  IF (SELECT COUNT(*) FROM information_schema.COLUMNS
        WHERE TABLE_SCHEMA = DATABASE() AND BINARY TABLE_NAME = BINARY p_tabla AND BINARY COLUMN_NAME = BINARY p_col) = 0
     AND (SELECT COUNT(*) FROM information_schema.TABLES
        WHERE TABLE_SCHEMA = DATABASE() AND BINARY TABLE_NAME = BINARY p_tabla) = 1 THEN
    SET @q = CONCAT('ALTER TABLE `', p_tabla, '` ADD COLUMN ', p_ddl);
    PREPARE st FROM @q; EXECUTE st; DEALLOCATE PREPARE st;
  END IF;
END$$

CREATE PROCEDURE pos_add_index(IN p_tabla VARCHAR(64), IN p_idx VARCHAR(64), IN p_ddl TEXT)
BEGIN
  IF (SELECT COUNT(*) FROM information_schema.STATISTICS
        WHERE TABLE_SCHEMA = DATABASE() AND BINARY TABLE_NAME = BINARY p_tabla AND BINARY INDEX_NAME = BINARY p_idx) = 0
     AND (SELECT COUNT(*) FROM information_schema.TABLES
        WHERE TABLE_SCHEMA = DATABASE() AND BINARY TABLE_NAME = BINARY p_tabla) = 1 THEN
    SET @q = CONCAT('ALTER TABLE `', p_tabla, '` ADD ', p_ddl);
    PREPARE st FROM @q; EXECUTE st; DEALLOCATE PREPARE st;
  END IF;
END$$

DELIMITER ;


-- ── 1. Rol ───────────────────────────────────────────────────
-- `roles` tiene UNIQUE(slug), así que esto no duplica aunque el rol
-- ya exista con otro id. El código SIEMPRE resuelve por slug.
INSERT IGNORE INTO `roles` (`id`, `nombre`, `slug`) VALUES (3, 'Cajero', 'cajero');


-- ── 2. usuarios: PIN corto ───────────────────────────────────
-- Aplica a cajeros (operar la caja) y a admins (autorizar descuentos).
CALL pos_add_column('usuarios','pin_hash',
  '`pin_hash` varchar(255) DEFAULT NULL COMMENT ''password_hash del PIN; nunca el PIN en claro''');
CALL pos_add_column('usuarios','pin_intentos_fallidos',
  '`pin_intentos_fallidos` tinyint(3) UNSIGNED NOT NULL DEFAULT 0');
CALL pos_add_column('usuarios','pin_bloqueado_hasta',
  '`pin_bloqueado_hasta` datetime DEFAULT NULL');
CALL pos_add_column('usuarios','pin_actualizado_at',
  '`pin_actualizado_at` datetime DEFAULT NULL');


-- ── 3. rest_restaurantes ─────────────────────────────────────
CALL pos_add_column('rest_restaurantes','pos_habilitado',
  '`pos_habilitado` tinyint(1) NOT NULL DEFAULT 1 COMMENT ''El módulo de caja es opcional por negocio''');
-- El esquema recortado perdió esta columna del sistema anterior y el POS
-- la usa para los botones de propina sugerida.
CALL pos_add_column('rest_restaurantes','propinas_sugeridas',
  '`propinas_sugeridas` varchar(40) NOT NULL DEFAULT ''0,10,15,20''');


-- ── 4. rest_configuracion: parámetros del POS ────────────────
CALL pos_add_column('rest_configuracion','descuento_max_cajero_pct',
  '`descuento_max_cajero_pct` decimal(5,2) NOT NULL DEFAULT 10.00');
CALL pos_add_column('rest_configuracion','impresora_ancho_ticket',
  '`impresora_ancho_ticket` enum(''58mm'',''80mm'') NOT NULL DEFAULT ''80mm''');
CALL pos_add_column('rest_configuracion','iva_habilitado',
  '`iva_habilitado` tinyint(1) NOT NULL DEFAULT 0 COMMENT ''Solo desglose informativo: los precios YA incluyen IVA''');
CALL pos_add_column('rest_configuracion','iva_porcentaje',
  '`iva_porcentaje` decimal(5,2) NOT NULL DEFAULT 16.00');
CALL pos_add_column('rest_configuracion','propinas_pos_habilitadas',
  '`propinas_pos_habilitadas` tinyint(1) NOT NULL DEFAULT 1');
CALL pos_add_column('rest_configuracion','diferencia_caja_alerta_mxn',
  '`diferencia_caja_alerta_mxn` decimal(10,2) NOT NULL DEFAULT 20.00');
CALL pos_add_column('rest_configuracion','pin_intentos_max',
  '`pin_intentos_max` tinyint(3) UNSIGNED NOT NULL DEFAULT 5');
CALL pos_add_column('rest_configuracion','pin_bloqueo_minutos',
  '`pin_bloqueo_minutos` smallint(5) UNSIGNED NOT NULL DEFAULT 5');
CALL pos_add_column('rest_configuracion','pos_polling_segundos',
  '`pos_polling_segundos` smallint(5) UNSIGNED NOT NULL DEFAULT 15');
CALL pos_add_column('rest_configuracion','ticket_leyenda',
  '`ticket_leyenda` varchar(255) DEFAULT NULL');


-- ── 5. rest_pedidos: columnas del POS ────────────────────────
-- Ya existían y NO se tocan: pedido_origen, tipo_origen, metodo_pago,
-- pagado_at, descuento, promo_code, estado, folio.
CALL pos_add_column('rest_pedidos','turno_caja_id',
  '`turno_caja_id` int(10) UNSIGNED DEFAULT NULL COMMENT ''NULL = pedido de app que ningún cajero ha tomado''');
CALL pos_add_column('rest_pedidos','cajero_id',
  '`cajero_id` int(10) UNSIGNED DEFAULT NULL');
CALL pos_add_column('rest_pedidos','propina_mxn',
  '`propina_mxn` decimal(10,2) NOT NULL DEFAULT 0.00');
CALL pos_add_column('rest_pedidos','iva_mxn',
  '`iva_mxn` decimal(10,2) NOT NULL DEFAULT 0.00 COMMENT ''Informativo: IVA contenido en un total que ya lo incluye''');
CALL pos_add_column('rest_pedidos','motivo_cancelacion',
  '`motivo_cancelacion` varchar(255) DEFAULT NULL');
CALL pos_add_column('rest_pedidos','cancelado_por_id',
  '`cancelado_por_id` int(10) UNSIGNED DEFAULT NULL');
CALL pos_add_column('rest_pedidos','cancelado_at',
  '`cancelado_at` datetime DEFAULT NULL');
CALL pos_add_column('rest_pedidos','reembolso_pendiente',
  '`reembolso_pendiente` tinyint(1) NOT NULL DEFAULT 0 COMMENT ''Cancelado con pago wallet/stripe: lo resuelve el Admin''');
CALL pos_add_column('rest_pedidos','pos_client_uuid',
  '`pos_client_uuid` char(36) DEFAULT NULL COMMENT ''Idempotencia del cobro: evita doble venta por doble clic''');

CALL pos_add_index('rest_pedidos','uq_pedidos_pos_uuid',
  'UNIQUE KEY `uq_pedidos_pos_uuid` (`pos_client_uuid`)');
CALL pos_add_index('rest_pedidos','idx_pedidos_turno',
  'KEY `idx_pedidos_turno` (`turno_caja_id`)');
CALL pos_add_index('rest_pedidos','idx_pedidos_cola_caja',
  'KEY `idx_pedidos_cola_caja` (`restaurante_id`,`turno_caja_id`,`estado`)');


-- ── 6. turnos_caja ───────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `turnos_caja` (
  `id`                    int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `restaurante_id`        int(10) UNSIGNED NOT NULL,
  `cajero_id`             int(10) UNSIGNED NOT NULL,
  `terminal_usuario_id`   int(10) UNSIGNED DEFAULT NULL COMMENT 'Cuenta que abrió la terminal, si es distinta del cajero',
  `fondo_inicial`         decimal(10,2) NOT NULL DEFAULT 0.00,

  -- Totales congelados AL CERRAR (se calculan desde rest_pedido_pagos).
  `total_efectivo`        decimal(10,2) NOT NULL DEFAULT 0.00,
  `total_tarjeta`         decimal(10,2) NOT NULL DEFAULT 0.00,
  `total_wallet`          decimal(10,2) NOT NULL DEFAULT 0.00,
  `total_transferencia`   decimal(10,2) NOT NULL DEFAULT 0.00,
  `total_prepagado_app`   decimal(10,2) NOT NULL DEFAULT 0.00 COMMENT 'Informativo: no entra al efectivo esperado',
  `total_propinas`        decimal(10,2) NOT NULL DEFAULT 0.00,
  `total_descuentos`      decimal(10,2) NOT NULL DEFAULT 0.00,
  `total_cancelado`       decimal(10,2) NOT NULL DEFAULT 0.00,
  `total_retiros`         decimal(10,2) NOT NULL DEFAULT 0.00,
  `total_ingresos_extra`  decimal(10,2) NOT NULL DEFAULT 0.00,
  `pedidos_vendidos`      int(10) UNSIGNED NOT NULL DEFAULT 0,
  `pedidos_pendientes_al_cierre` int(10) UNSIGNED NOT NULL DEFAULT 0,

  `efectivo_esperado`     decimal(10,2) DEFAULT NULL,
  `efectivo_contado`      decimal(10,2) DEFAULT NULL,
  `diferencia`            decimal(10,2) DEFAULT NULL,
  `alerta_diferencia`     tinyint(1) NOT NULL DEFAULT 0,
  `denominaciones_json`   text COMMENT 'Desglose opcional del conteo',

  `estado`                enum('abierto','cerrado') NOT NULL DEFAULT 'abierto',
  `abierto_at`            datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `cerrado_at`            datetime DEFAULT NULL,
  `notas`                 text,

  -- Un cajero no puede tener dos turnos abiertos: la columna vale
  -- cajero_id mientras está abierto y NULL al cerrar (MySQL permite
  -- muchos NULL dentro de un índice UNIQUE).
  `cajero_abierto_uk`     int(10) UNSIGNED
      GENERATED ALWAYS AS (IF(`estado` = 'abierto', `cajero_id`, NULL)) STORED,

  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_turno_cajero_abierto` (`cajero_abierto_uk`),
  KEY `idx_turno_rest_estado` (`restaurante_id`,`estado`),
  KEY `idx_turno_cajero_fecha` (`cajero_id`,`abierto_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


-- ── 7. turno_caja_movimientos (retiros / ingresos de efectivo) ─
CREATE TABLE IF NOT EXISTS `turno_caja_movimientos` (
  `id`             int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `turno_caja_id`  int(10) UNSIGNED NOT NULL,
  `restaurante_id` int(10) UNSIGNED NOT NULL,
  `cajero_id`      int(10) UNSIGNED NOT NULL,
  `tipo`           enum('retiro','ingreso') NOT NULL,
  `monto`          decimal(10,2) NOT NULL COMMENT 'Siempre positivo; el signo lo da tipo',
  `motivo`         varchar(255) NOT NULL,
  `espejo_tabla`   varchar(20) DEFAULT NULL COMMENT 'rest_retiros | rest_gastos',
  `espejo_id`      int(10) UNSIGNED DEFAULT NULL,
  `created_at`     timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_mov_turno` (`turno_caja_id`,`tipo`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


-- ── 8. rest_pedido_pagos (pago mixto y devoluciones) ─────────
CREATE TABLE IF NOT EXISTS `rest_pedido_pagos` (
  `id`            int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `pedido_id`     int(10) UNSIGNED NOT NULL,
  `restaurante_id` int(10) UNSIGNED NOT NULL,
  `turno_caja_id` int(10) UNSIGNED DEFAULT NULL COMMENT 'Turno de ESTE movimiento: una devolución puede caer en otro turno',
  `cajero_id`     int(10) UNSIGNED DEFAULT NULL,
  `tipo`          enum('cobro','devolucion') NOT NULL DEFAULT 'cobro',
  `metodo`        enum('efectivo','tarjeta','wallet','transferencia','stripe_app','otro') NOT NULL,
  `monto`         decimal(10,2) NOT NULL COMMENT 'Siempre positivo; el signo lo da tipo',
  `recibido`      decimal(10,2) DEFAULT NULL COMMENT 'Solo efectivo',
  `cambio`        decimal(10,2) DEFAULT NULL COMMENT 'Solo efectivo, calculado en servidor',
  `referencia`    varchar(120) DEFAULT NULL,
  `created_at`    timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_pedido_pagos_pedido` (`pedido_id`),
  KEY `idx_pedido_pagos_turno` (`turno_caja_id`,`metodo`,`tipo`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


-- ── 9. rest_descuentos_log (auditoría de descuentos manuales) ─
CREATE TABLE IF NOT EXISTS `rest_descuentos_log` (
  `id`                    int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `pedido_id`             int(10) UNSIGNED NOT NULL,
  `restaurante_id`        int(10) UNSIGNED NOT NULL,
  `cajero_id`             int(10) UNSIGNED NOT NULL,
  `tipo`                  enum('porcentaje','monto_fijo') NOT NULL,
  `valor`                 decimal(10,2) NOT NULL,
  `monto_aplicado`        decimal(10,2) NOT NULL,
  `motivo`                varchar(255) DEFAULT NULL,
  `requirio_autorizacion` tinyint(1) NOT NULL DEFAULT 0,
  `autorizado_por_id`     int(10) UNSIGNED DEFAULT NULL,
  `created_at`            timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_desc_pedido` (`pedido_id`),
  KEY `idx_desc_rest_fecha` (`restaurante_id`,`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


-- ── 10. Limpieza de helpers ──────────────────────────────────
DROP PROCEDURE IF EXISTS pos_add_column;
DROP PROCEDURE IF EXISTS pos_add_index;


-- ============================================================
-- VERIFICACIÓN (correr a mano después)
-- ============================================================
-- SELECT id, nombre, slug FROM roles WHERE slug = 'cajero';
-- SHOW COLUMNS FROM usuarios LIKE 'pin_%';
-- SHOW COLUMNS FROM rest_pedidos LIKE '%caj%';
-- SHOW CREATE TABLE turnos_caja;          -- debe traer uq_turno_cajero_abierto
--
-- Un cajero no puede tener dos turnos abiertos (debe fallar el 2º):
--   INSERT INTO turnos_caja (restaurante_id, cajero_id, fondo_inicial) VALUES (1, 99, 0);
--   INSERT INTO turnos_caja (restaurante_id, cajero_id, fondo_inicial) VALUES (1, 99, 0);
--   DELETE FROM turnos_caja WHERE cajero_id = 99;

-- ============================================================
-- ASIGNAR UN PIN A MANO (solo para probar antes de que Admin
-- tenga su pantalla de PINs).
-- ============================================================
-- 1) Genera el hash del PIN (no pegues el PIN en claro en la BD):
--      php -r "echo password_hash('1234', PASSWORD_DEFAULT), PHP_EOL;"
-- 2) Pega el hash que imprimió:
--      UPDATE usuarios
--         SET pin_hash = '<pega-aquí-el-hash>',
--             pin_intentos_fallidos = 0, pin_bloqueado_hasta = NULL, pin_actualizado_at = NOW()
--       WHERE email = 'cajero@tu-negocio.com';
--
-- NOTA: no hace falta. Si un cajero entra a la terminal con su email y
-- password y todavía no tiene PIN, el POS le pide crear uno en ese momento.

-- ============================================================
-- PLAN B — si tu usuario de MySQL no puede crear procedimientos
-- ============================================================
-- Corre a mano solo los ALTER que falten, sin los CALL. Ejemplo:
--   ALTER TABLE usuarios ADD COLUMN pin_hash varchar(255) DEFAULT NULL;
-- Si MySQL responde "Duplicate column name", esa columna ya existía:
-- ignóralo y sigue con la siguiente. Los CREATE TABLE IF NOT EXISTS y
-- el INSERT IGNORE de arriba se pueden correr tal cual.

-- ============================================================
-- ROLLBACK
-- ============================================================
-- DROP TABLE IF EXISTS turno_caja_movimientos, rest_descuentos_log, rest_pedido_pagos, turnos_caja;
-- ALTER TABLE rest_pedidos
--   DROP INDEX uq_pedidos_pos_uuid, DROP INDEX idx_pedidos_turno, DROP INDEX idx_pedidos_cola_caja,
--   DROP COLUMN turno_caja_id, DROP COLUMN cajero_id, DROP COLUMN propina_mxn, DROP COLUMN iva_mxn,
--   DROP COLUMN motivo_cancelacion, DROP COLUMN cancelado_por_id, DROP COLUMN cancelado_at,
--   DROP COLUMN reembolso_pendiente, DROP COLUMN pos_client_uuid;
-- ALTER TABLE rest_configuracion
--   DROP COLUMN descuento_max_cajero_pct, DROP COLUMN impresora_ancho_ticket, DROP COLUMN iva_habilitado,
--   DROP COLUMN iva_porcentaje, DROP COLUMN propinas_pos_habilitadas, DROP COLUMN diferencia_caja_alerta_mxn,
--   DROP COLUMN pin_intentos_max, DROP COLUMN pin_bloqueo_minutos, DROP COLUMN pos_polling_segundos,
--   DROP COLUMN ticket_leyenda;
-- ALTER TABLE rest_restaurantes DROP COLUMN pos_habilitado, DROP COLUMN propinas_sugeridas;
-- ALTER TABLE usuarios DROP COLUMN pin_hash, DROP COLUMN pin_intentos_fallidos,
--   DROP COLUMN pin_bloqueado_hasta, DROP COLUMN pin_actualizado_at;
-- (el rol `cajero` se deja: borrarlo dejaría usuarios huérfanos)
