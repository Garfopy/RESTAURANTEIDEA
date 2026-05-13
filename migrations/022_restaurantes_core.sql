-- ============================================================
-- CarniHub v3.2 — Módulo Restaurantes (core)
-- Ejecutar DESPUÉS de 001–021
-- ============================================================

SET FOREIGN_KEY_CHECKS = 0;

-- ── Restaurantes ──────────────────────────────────────────────
CREATE TABLE `rest_restaurantes` (
  `id`                INT UNSIGNED  NOT NULL AUTO_INCREMENT,
  `empresa_id`        INT UNSIGNED  NOT NULL,
  `comprador_id`      INT UNSIGNED  NOT NULL,
  `nombre`            VARCHAR(200)  NOT NULL,
  `slug`              VARCHAR(100)  NOT NULL,
  `logo`              VARCHAR(255)  NULL,
  `color_primario`    VARCHAR(7)    NOT NULL DEFAULT '#C8102E',
  `color_secundario`  VARCHAR(7)    NOT NULL DEFAULT '#1f2937',
  `descripcion`       TEXT          NULL,
  `telefono`          VARCHAR(20)   NULL,
  `direccion`         TEXT          NULL,
  `lat`               DECIMAL(10,8) NULL,
  `lng`               DECIMAL(11,8) NULL,
  `horario_apertura`  TIME          NULL,
  `horario_cierre`    TIME          NULL,
  `activo`            TINYINT(1)    NOT NULL DEFAULT 1,
  `created_at`        TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_slug` (`slug`),
  CONSTRAINT `fk_rrest_empresa`    FOREIGN KEY (`empresa_id`)   REFERENCES `empresas`(`id`),
  CONSTRAINT `fk_rrest_comprador`  FOREIGN KEY (`comprador_id`) REFERENCES `usuarios`(`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ── Zonas del restaurante ──────────────────────────────────────
CREATE TABLE `rest_zonas` (
  `id`             INT UNSIGNED  NOT NULL AUTO_INCREMENT,
  `restaurante_id` INT UNSIGNED  NOT NULL,
  `nombre`         VARCHAR(100)  NOT NULL,
  `descripcion`    VARCHAR(255)  NULL,
  `activo`         TINYINT(1)    NOT NULL DEFAULT 1,
  PRIMARY KEY (`id`),
  CONSTRAINT `fk_rzona_rest` FOREIGN KEY (`restaurante_id`) REFERENCES `rest_restaurantes`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ── Mesas ─────────────────────────────────────────────────────
CREATE TABLE `rest_mesas` (
  `id`             INT UNSIGNED  NOT NULL AUTO_INCREMENT,
  `restaurante_id` INT UNSIGNED  NOT NULL,
  `zona_id`        INT UNSIGNED  NULL,
  `nombre`         VARCHAR(50)   NOT NULL,
  `capacidad`      TINYINT       NOT NULL DEFAULT 4,
  `qr_codigo`      VARCHAR(64)   NOT NULL,
  `posicion_x`     INT           NOT NULL DEFAULT 0,
  `posicion_y`     INT           NOT NULL DEFAULT 0,
  `estado`         ENUM('disponible','ocupada','reservada','pagando') NOT NULL DEFAULT 'disponible',
  `activo`         TINYINT(1)    NOT NULL DEFAULT 1,
  `created_at`     TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_mesa_qr` (`qr_codigo`),
  CONSTRAINT `fk_rmesa_rest` FOREIGN KEY (`restaurante_id`) REFERENCES `rest_restaurantes`(`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_rmesa_zona` FOREIGN KEY (`zona_id`)        REFERENCES `rest_zonas`(`id`)         ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ── Categorías del menú ────────────────────────────────────────
CREATE TABLE `rest_categorias_menu` (
  `id`             INT UNSIGNED  NOT NULL AUTO_INCREMENT,
  `restaurante_id` INT UNSIGNED  NOT NULL,
  `nombre`         VARCHAR(100)  NOT NULL,
  `descripcion`    VARCHAR(255)  NULL,
  `imagen`         VARCHAR(255)  NULL,
  `orden`          TINYINT       NOT NULL DEFAULT 0,
  `activo`         TINYINT(1)    NOT NULL DEFAULT 1,
  PRIMARY KEY (`id`),
  CONSTRAINT `fk_rcat_rest` FOREIGN KEY (`restaurante_id`) REFERENCES `rest_restaurantes`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ── Platillos ──────────────────────────────────────────────────
CREATE TABLE `rest_platillos` (
  `id`                     INT UNSIGNED   NOT NULL AUTO_INCREMENT,
  `restaurante_id`         INT UNSIGNED   NOT NULL,
  `categoria_id`           INT UNSIGNED   NULL,
  `nombre`                 VARCHAR(200)   NOT NULL,
  `descripcion`            TEXT           NULL,
  `precio`                 DECIMAL(10,2)  NOT NULL DEFAULT 0.00,
  `imagen`                 VARCHAR(255)   NULL,
  `tiempo_preparacion_min` TINYINT        NOT NULL DEFAULT 15,
  `disponible`             TINYINT(1)     NOT NULL DEFAULT 1,
  `activo`                 TINYINT(1)     NOT NULL DEFAULT 1,
  `created_at`             TIMESTAMP      NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  CONSTRAINT `fk_rplat_rest` FOREIGN KEY (`restaurante_id`) REFERENCES `rest_restaurantes`(`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_rplat_cat`  FOREIGN KEY (`categoria_id`)   REFERENCES `rest_categorias_menu`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ── Recetas ───────────────────────────────────────────────────
CREATE TABLE `rest_recetas` (
  `id`             INT UNSIGNED  NOT NULL AUTO_INCREMENT,
  `platillo_id`    INT UNSIGNED  NOT NULL,
  `porciones_base` TINYINT       NOT NULL DEFAULT 1,
  `notas`          TEXT          NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_receta_platillo` (`platillo_id`),
  CONSTRAINT `fk_rrec_plat` FOREIGN KEY (`platillo_id`) REFERENCES `rest_platillos`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ── Ingredientes (almacén del restaurante) ─────────────────────
CREATE TABLE `rest_ingredientes` (
  `id`                    INT UNSIGNED   NOT NULL AUTO_INCREMENT,
  `restaurante_id`        INT UNSIGNED   NOT NULL,
  `nombre`                VARCHAR(200)   NOT NULL,
  `unidad_principal`      VARCHAR(20)    NOT NULL DEFAULT 'kg',
  `unidad_compra`         VARCHAR(20)    NULL,
  `equivalencia`          DECIMAL(10,4)  NOT NULL DEFAULT 1.0000,
  `costo_unitario`        DECIMAL(10,2)  NOT NULL DEFAULT 0.00,
  `stock`                 DECIMAL(10,3)  NOT NULL DEFAULT 0.000,
  `stock_minimo`          DECIMAL(10,3)  NOT NULL DEFAULT 0.000,
  `categoria`             VARCHAR(100)   NULL,
  -- Integración CarniHub: si viene del distribuidor, link al producto
  `carnihub_producto_id`  INT UNSIGNED   NULL,
  `proveedor_carnihub`    TINYINT(1)     NOT NULL DEFAULT 0,
  -- Otros proveedores: SOLO nombre libre, sin tabla externa
  `proveedor_nombre`      VARCHAR(100)   NULL,
  `activo`                TINYINT(1)     NOT NULL DEFAULT 1,
  `created_at`            TIMESTAMP      NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  CONSTRAINT `fk_ring_rest` FOREIGN KEY (`restaurante_id`) REFERENCES `rest_restaurantes`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ── Ingredientes de receta ────────────────────────────────────
CREATE TABLE `rest_receta_ingredientes` (
  `id`             INT UNSIGNED   NOT NULL AUTO_INCREMENT,
  `receta_id`      INT UNSIGNED   NOT NULL,
  `ingrediente_id` INT UNSIGNED   NOT NULL,
  `cantidad`       DECIMAL(10,3)  NOT NULL,
  `unidad`         VARCHAR(20)    NOT NULL DEFAULT 'kg',
  `notas`          VARCHAR(255)   NULL,
  PRIMARY KEY (`id`),
  CONSTRAINT `fk_rri_rec`  FOREIGN KEY (`receta_id`)      REFERENCES `rest_recetas`(`id`)      ON DELETE CASCADE,
  CONSTRAINT `fk_rri_ing`  FOREIGN KEY (`ingrediente_id`) REFERENCES `rest_ingredientes`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ── Movimientos de inventario ─────────────────────────────────
CREATE TABLE `rest_movimientos_inventario` (
  `id`             INT UNSIGNED   NOT NULL AUTO_INCREMENT,
  `restaurante_id` INT UNSIGNED   NOT NULL,
  `ingrediente_id` INT UNSIGNED   NOT NULL,
  `tipo`           ENUM('entrada','salida','merma','ajuste') NOT NULL,
  `cantidad`       DECIMAL(10,3)  NOT NULL,
  `stock_antes`    DECIMAL(10,3)  NOT NULL,
  `stock_despues`  DECIMAL(10,3)  NOT NULL,
  `motivo`         VARCHAR(255)   NULL,
  `referencia`     VARCHAR(100)   NULL,
  `usuario_id`     INT UNSIGNED   NULL,
  `created_at`     TIMESTAMP      NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_rmov_rest` (`restaurante_id`),
  KEY `idx_rmov_ing`  (`ingrediente_id`),
  CONSTRAINT `fk_rmov_rest` FOREIGN KEY (`restaurante_id`) REFERENCES `rest_restaurantes`(`id`)   ON DELETE CASCADE,
  CONSTRAINT `fk_rmov_ing`  FOREIGN KEY (`ingrediente_id`) REFERENCES `rest_ingredientes`(`id`)   ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ── Comensales (clientes del restaurante) ─────────────────────
CREATE TABLE `rest_comensales` (
  `id`             INT UNSIGNED   NOT NULL AUTO_INCREMENT,
  `restaurante_id` INT UNSIGNED   NOT NULL,
  `nombre`         VARCHAR(200)   NULL,
  `telefono`       VARCHAR(20)    NULL,
  `email`          VARCHAR(150)   NULL,
  `total_visitas`  INT UNSIGNED   NOT NULL DEFAULT 0,
  `total_gastado`  DECIMAL(12,2)  NOT NULL DEFAULT 0.00,
  `ultima_visita`  DATETIME       NULL,
  `created_at`     TIMESTAMP      NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  CONSTRAINT `fk_rcom_rest` FOREIGN KEY (`restaurante_id`) REFERENCES `rest_restaurantes`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ── Visitas (sesión QR entrada/salida) ────────────────────────
CREATE TABLE `rest_visitas` (
  `id`             INT UNSIGNED   NOT NULL AUTO_INCREMENT,
  `restaurante_id` INT UNSIGNED   NOT NULL,
  `mesa_id`        INT UNSIGNED   NULL,
  `comensal_id`    INT UNSIGNED   NULL,
  `qr_code`        VARCHAR(128)   NOT NULL,
  `estado`         ENUM('activa','pagando','pagada','cancelada') NOT NULL DEFAULT 'activa',
  `notas`          TEXT           NULL,
  `subtotal`       DECIMAL(10,2)  NOT NULL DEFAULT 0.00,
  `propina`        DECIMAL(10,2)  NOT NULL DEFAULT 0.00,
  `total`          DECIMAL(10,2)  NOT NULL DEFAULT 0.00,
  `created_at`     TIMESTAMP      NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `pagada_at`      DATETIME       NULL,
  `salida_at`      DATETIME       NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_visita_qr` (`qr_code`),
  KEY `idx_visita_rest` (`restaurante_id`),
  CONSTRAINT `fk_rvis_rest`  FOREIGN KEY (`restaurante_id`) REFERENCES `rest_restaurantes`(`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_rvis_mesa`  FOREIGN KEY (`mesa_id`)        REFERENCES `rest_mesas`(`id`)          ON DELETE SET NULL,
  CONSTRAINT `fk_rvis_com`   FOREIGN KEY (`comensal_id`)    REFERENCES `rest_comensales`(`id`)     ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ── Pedidos ───────────────────────────────────────────────────
CREATE TABLE `rest_pedidos` (
  `id`             INT UNSIGNED   NOT NULL AUTO_INCREMENT,
  `restaurante_id` INT UNSIGNED   NOT NULL,
  `mesa_id`        INT UNSIGNED   NULL,
  `visita_id`      INT UNSIGNED   NULL,
  `mesero_id`      INT UNSIGNED   NULL,
  `folio`          VARCHAR(20)    NOT NULL,
  `estado`         ENUM('pendiente','en_preparacion','listo','entregado','cancelado') NOT NULL DEFAULT 'pendiente',
  `notas`          TEXT           NULL,
  `subtotal`       DECIMAL(10,2)  NOT NULL DEFAULT 0.00,
  `total`          DECIMAL(10,2)  NOT NULL DEFAULT 0.00,
  `created_at`     TIMESTAMP      NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `actualizado_at` DATETIME       NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_rped_rest`  (`restaurante_id`),
  KEY `idx_rped_mesa`  (`mesa_id`),
  KEY `idx_rped_vis`   (`visita_id`),
  KEY `idx_rped_est`   (`estado`),
  CONSTRAINT `fk_rped_rest`  FOREIGN KEY (`restaurante_id`) REFERENCES `rest_restaurantes`(`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_rped_mesa`  FOREIGN KEY (`mesa_id`)        REFERENCES `rest_mesas`(`id`)          ON DELETE SET NULL,
  CONSTRAINT `fk_rped_vis`   FOREIGN KEY (`visita_id`)      REFERENCES `rest_visitas`(`id`)         ON DELETE SET NULL,
  CONSTRAINT `fk_rped_mes`   FOREIGN KEY (`mesero_id`)      REFERENCES `usuarios`(`id`)             ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ── Items del pedido ──────────────────────────────────────────
CREATE TABLE `rest_pedido_items` (
  `id`           INT UNSIGNED   NOT NULL AUTO_INCREMENT,
  `pedido_id`    INT UNSIGNED   NOT NULL,
  `platillo_id`  INT UNSIGNED   NOT NULL,
  `cantidad`     TINYINT        NOT NULL DEFAULT 1,
  `precio_unit`  DECIMAL(10,2)  NOT NULL,
  `subtotal`     DECIMAL(10,2)  NOT NULL,
  `notas`        VARCHAR(255)   NULL,
  `estado`       ENUM('pendiente','en_preparacion','listo','entregado') NOT NULL DEFAULT 'pendiente',
  PRIMARY KEY (`id`),
  CONSTRAINT `fk_ritem_ped`  FOREIGN KEY (`pedido_id`)   REFERENCES `rest_pedidos`(`id`)   ON DELETE CASCADE,
  CONSTRAINT `fk_ritem_plat` FOREIGN KEY (`platillo_id`) REFERENCES `rest_platillos`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ── Tickets (cuenta final) ────────────────────────────────────
CREATE TABLE `rest_tickets` (
  `id`              INT UNSIGNED   NOT NULL AUTO_INCREMENT,
  `restaurante_id`  INT UNSIGNED   NOT NULL,
  `visita_id`       INT UNSIGNED   NOT NULL,
  `mesa_id`         INT UNSIGNED   NULL,
  `folio`           VARCHAR(20)    NOT NULL,
  `subtotal`        DECIMAL(10,2)  NOT NULL DEFAULT 0.00,
  `propina`         DECIMAL(10,2)  NOT NULL DEFAULT 0.00,
  `total`           DECIMAL(10,2)  NOT NULL DEFAULT 0.00,
  `estado`          ENUM('pendiente','pagado','cancelado') NOT NULL DEFAULT 'pendiente',
  `metodo_pago`     ENUM('paypal','tarjeta','transferencia','efectivo') NULL,
  `paypal_order_id` VARCHAR(100)   NULL,
  `created_at`      TIMESTAMP      NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `pagado_at`       DATETIME       NULL,
  PRIMARY KEY (`id`),
  KEY `idx_rtick_rest` (`restaurante_id`),
  KEY `idx_rtick_vis`  (`visita_id`),
  CONSTRAINT `fk_rtick_rest` FOREIGN KEY (`restaurante_id`) REFERENCES `rest_restaurantes`(`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_rtick_vis`  FOREIGN KEY (`visita_id`)      REFERENCES `rest_visitas`(`id`)       ON DELETE CASCADE,
  CONSTRAINT `fk_rtick_mesa` FOREIGN KEY (`mesa_id`)        REFERENCES `rest_mesas`(`id`)          ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ── Staff del restaurante (meseros, chefs, porteros) ──────────
CREATE TABLE `rest_staff` (
  `id`             INT UNSIGNED  NOT NULL AUTO_INCREMENT,
  `restaurante_id` INT UNSIGNED  NOT NULL,
  `usuario_id`     INT UNSIGNED  NOT NULL,
  `codigo`         VARCHAR(10)   NOT NULL,
  `rol_slug`       VARCHAR(20)   NOT NULL,
  `activo`         TINYINT(1)    NOT NULL DEFAULT 1,
  `fecha_ingreso`  DATE          NULL,
  `created_at`     TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_staff_codigo` (`restaurante_id`, `codigo`),
  CONSTRAINT `fk_rstaff_rest` FOREIGN KEY (`restaurante_id`) REFERENCES `rest_restaurantes`(`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_rstaff_usr`  FOREIGN KEY (`usuario_id`)     REFERENCES `usuarios`(`id`)           ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

SET FOREIGN_KEY_CHECKS = 1;
