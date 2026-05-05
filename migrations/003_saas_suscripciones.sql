-- ══════════════════════════════════════════════════════════════════════════════
-- CarniHub — Migration 003: Planes SaaS y Suscripciones
-- Ejecutar DESPUÉS de 001_schema_completo.sql y 002_seed_usuarios_prueba.sql
-- ══════════════════════════════════════════════════════════════════════════════

-- ── Planes SaaS ──────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `planes_saas` (
  `id`              TINYINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `slug`            VARCHAR(30)      NOT NULL,
  `nombre`          VARCHAR(80)      NOT NULL,
  `descripcion`     TEXT             NULL,
  `precio_mensual`  DECIMAL(8,2)     NOT NULL DEFAULT 0.00,
  `precio_anual`    DECIMAL(8,2)     NOT NULL DEFAULT 0.00,
  `max_usuarios`    SMALLINT         NOT NULL DEFAULT 5,    -- 0 = ilimitado
  `max_productos`   SMALLINT         NOT NULL DEFAULT 100,
  `max_pedidos_mes` SMALLINT         NOT NULL DEFAULT 200,
  `max_sucursales`  SMALLINT         NOT NULL DEFAULT 3,
  `features`        JSON             NULL,
  `paypal_plan_id`  VARCHAR(50)      NULL,
  `activo`          TINYINT(1)       NOT NULL DEFAULT 1,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_slug` (`slug`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO `planes_saas`
  (`id`, `slug`, `nombre`, `descripcion`,
   `precio_mensual`, `precio_anual`,
   `max_usuarios`, `max_productos`, `max_pedidos_mes`, `max_sucursales`,
   `features`, `activo`)
VALUES
(1, 'basico', 'Básico', 'Para carnicerías y taquerías pequeñas',
   2600.00, 26000.00, 5, 100, 200, 3,
   JSON_ARRAY('Catálogo de productos','Carrito y pedidos','GPS repartidor','Dashboard básico','Hasta 5 usuarios','Hasta 3 sucursales'),
   1),
(2, 'pro', 'Pro', 'Para operaciones medianas con equipo',
   3200.00, 32000.00, 20, 0, 0, 10,
   JSON_ARRAY('Todo lo del plan Básico','Hasta 20 usuarios','Hasta 10 sucursales','Productos ilimitados','Pedidos ilimitados','Notificaciones WhatsApp','Límites de compra','Pedidos recurrentes','Reportes completos'),
   1),
(3, 'empresa', 'Empresa', 'Operación sin límites con facturación CFDI',
   4000.00, 40000.00, 0, 0, 0, 0,
   JSON_ARRAY('Todo lo del plan Pro','Usuarios ilimitados','Sucursales ilimitadas','Facturación CFDI automática','Exportar Excel y PDF','Soporte prioritario'),
   1);

-- ── Suscripciones por empresa ─────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `suscripciones` (
  `id`                     INT UNSIGNED     NOT NULL AUTO_INCREMENT,
  `empresa_id`             INT UNSIGNED     NOT NULL,
  `plan_id`                TINYINT UNSIGNED NOT NULL,
  `estado`                 ENUM('pendiente_paypal','activo','suspendido','cancelado')
                             NOT NULL DEFAULT 'activo',
  `ciclo`                  ENUM('mensual','anual') NOT NULL DEFAULT 'mensual',
  `fecha_inicio`           DATE             NOT NULL,
  `fecha_vencimiento`      DATE             NULL,
  `paypal_subscription_id` VARCHAR(50)      NULL,
  `paypal_status`          VARCHAR(30)      NULL,
  `notas`                  TEXT             NULL,
  `created_by`             INT UNSIGNED     NULL,
  `created_at`             TIMESTAMP        NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_empresa` (`empresa_id`),
  KEY `idx_paypal_sub` (`paypal_subscription_id`),
  CONSTRAINT `fk_sus_empresa` FOREIGN KEY (`empresa_id`) REFERENCES `empresas`(`id`),
  CONSTRAINT `fk_sus_plan`    FOREIGN KEY (`plan_id`)    REFERENCES `planes_saas`(`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ── Agregar estado de suscripción a empresas ──────────────────────────────────
ALTER TABLE `empresas`
  ADD COLUMN `suscripcion_estado`
    ENUM('activo','suspendido','sin_plan') NOT NULL DEFAULT 'sin_plan'
    AFTER `activo`;

-- ── Suscripción demo para empresa 1 (buensabor) ───────────────────────────────
-- El seed de 001 inserta la empresa directamente en BD, omitiendo EmpresaController
-- que normalmente crea el registro en suscripciones. Este INSERT lo repara.
INSERT IGNORE INTO `suscripciones`
  (`empresa_id`, `plan_id`, `estado`, `ciclo`, `fecha_inicio`, `created_by`)
SELECT 1, id, 'activo', 'mensual', CURDATE(), 1
FROM `planes_saas` WHERE `slug` = 'pro' LIMIT 1;

UPDATE `empresas` SET `suscripcion_estado` = 'activo' WHERE `id` = 1;
