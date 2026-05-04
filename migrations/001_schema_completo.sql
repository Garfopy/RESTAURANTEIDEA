-- ============================================================
-- CarniHub v2.0 — Schema completo desde cero
-- Ejecutar en base de datos limpia (DROP DATABASE / CREATE DATABASE primero)
-- ============================================================

SET FOREIGN_KEY_CHECKS = 0;

-- ── Roles ──────────────────────────────────────────────────────
CREATE TABLE `roles` (
  `id`     TINYINT UNSIGNED NOT NULL,
  `nombre` VARCHAR(50)      NOT NULL,
  `slug`   VARCHAR(50)      NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_slug` (`slug`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO `roles` VALUES
  (1, 'Super Admin',      'superadmin'),
  (2, 'Administrador',    'admin'),
  (3, 'Admin Empresa',    'admin_empresa'),
  (4, 'Supervisor',       'supervisor'),
  (5, 'Comprador',        'comprador'),
  (6, 'Repartidor',       'repartidor');

-- ── Empresas ───────────────────────────────────────────────────
CREATE TABLE `empresas` (
  `id`                    INT UNSIGNED     NOT NULL AUTO_INCREMENT,
  `razon_social`          VARCHAR(200)     NOT NULL,
  `rfc`                   VARCHAR(15)      NULL,
  `regimen_fiscal`        VARCHAR(100)     NULL,
  `tipo_negocio`          ENUM('taqueria','carniceria','restaurante','comedor','otro') NULL,
  `email`                 VARCHAR(150)     NULL,
  `telefono`              VARCHAR(20)      NULL,
  `direccion_fiscal`      TEXT             NULL,
  `metodo_pago_preferido` ENUM('transferencia','tarjeta','credito') DEFAULT 'transferencia',
  `credito_activo`        TINYINT(1)       NOT NULL DEFAULT 0,
  `limite_credito`        DECIMAL(12,2)    NOT NULL DEFAULT 0.00,
  `saldo_credito`         DECIMAL(12,2)    NOT NULL DEFAULT 0.00,
  `dias_credito`          TINYINT          NOT NULL DEFAULT 0,
  `activo`                TINYINT(1)       NOT NULL DEFAULT 1,
  `created_by`            INT UNSIGNED     NULL,
  `created_at`            TIMESTAMP        NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_rfc` (`rfc`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ── Usuarios ────────────────────────────────────────────────────
CREATE TABLE `usuarios` (
  `id`               INT UNSIGNED  NOT NULL AUTO_INCREMENT,
  `nombre`           VARCHAR(100)  NOT NULL,
  `apellido_paterno` VARCHAR(100)  NOT NULL,
  `apellido_materno` VARCHAR(100)  NULL,
  `email`            VARCHAR(150)  NOT NULL,
  `password`         VARCHAR(255)  NOT NULL,
  `rol_id`           TINYINT UNSIGNED NOT NULL,
  `empresa_id`       INT UNSIGNED  NULL,
  `activo`           TINYINT(1)    NOT NULL DEFAULT 1,
  `avatar`           VARCHAR(255)  NULL,
  `telefono`         VARCHAR(20)   NULL,
  `created_by`       INT UNSIGNED  NULL,
  `created_at`       TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_email` (`email`),
  CONSTRAINT `fk_usuario_rol`     FOREIGN KEY (`rol_id`)     REFERENCES `roles`(`id`),
  CONSTRAINT `fk_usuario_empresa` FOREIGN KEY (`empresa_id`) REFERENCES `empresas`(`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ── Sucursales ──────────────────────────────────────────────────
CREATE TABLE `sucursales` (
  `id`          INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `empresa_id`  INT UNSIGNED NOT NULL,
  `nombre`      VARCHAR(150) NOT NULL,
  `direccion`   TEXT         NOT NULL,
  `lat`         DECIMAL(10,8) NULL,
  `lng`         DECIMAL(11,8) NULL,
  `responsable` VARCHAR(100)  NULL,
  `telefono`    VARCHAR(20)   NULL,
  `activo`      TINYINT(1)    NOT NULL DEFAULT 1,
  `created_at`  TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  CONSTRAINT `fk_sucursal_empresa` FOREIGN KEY (`empresa_id`) REFERENCES `empresas`(`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ── Categorías ──────────────────────────────────────────────────
CREATE TABLE `categorias` (
  `id`     INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `nombre` VARCHAR(100) NOT NULL,
  `slug`   VARCHAR(100) NOT NULL,
  `activo` TINYINT(1)   NOT NULL DEFAULT 1,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_cat_slug` (`slug`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO `categorias` (`nombre`, `slug`) VALUES
  ('Res',    'res'),
  ('Cerdo',  'cerdo'),
  ('Pollo',  'pollo'),
  ('Mixtos', 'mixtos');

-- ── Productos ───────────────────────────────────────────────────
CREATE TABLE `productos` (
  `id`           INT UNSIGNED  NOT NULL AUTO_INCREMENT,
  `categoria_id` INT UNSIGNED  NOT NULL,
  `nombre`       VARCHAR(150)  NOT NULL,
  `descripcion`  TEXT          NULL,
  `presentacion` ENUM('kg','caja','pieza') NOT NULL DEFAULT 'kg',
  `imagen`       VARCHAR(255)  NULL,
  `precio_base`  DECIMAL(10,2) NOT NULL,
  `activo`       TINYINT(1)    NOT NULL DEFAULT 1,
  `created_at`   TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  CONSTRAINT `fk_prod_cat` FOREIGN KEY (`categoria_id`) REFERENCES `categorias`(`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ── Precios escalonados ─────────────────────────────────────────
CREATE TABLE `precios_escalonados` (
  `id`           INT UNSIGNED  NOT NULL AUTO_INCREMENT,
  `producto_id`  INT UNSIGNED  NOT NULL,
  `cantidad_min` DECIMAL(10,2) NOT NULL,
  `cantidad_max` DECIMAL(10,2) NULL,
  `precio`       DECIMAL(10,2) NOT NULL,
  PRIMARY KEY (`id`),
  CONSTRAINT `fk_pe_prod` FOREIGN KEY (`producto_id`) REFERENCES `productos`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ── Inventario ──────────────────────────────────────────────────
CREATE TABLE `inventario` (
  `id`            INT UNSIGNED  NOT NULL AUTO_INCREMENT,
  `producto_id`   INT UNSIGNED  NOT NULL,
  `stock`         DECIMAL(10,2) NOT NULL DEFAULT 0,
  `umbral_minimo` DECIMAL(10,2) NOT NULL DEFAULT 0,
  `updated_at`    TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_inv_prod` (`producto_id`),
  CONSTRAINT `fk_inv_prod` FOREIGN KEY (`producto_id`) REFERENCES `productos`(`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ── Pedidos ─────────────────────────────────────────────────────
CREATE TABLE `pedidos` (
  `id`                   INT UNSIGNED  NOT NULL AUTO_INCREMENT,
  `folio`                VARCHAR(20)   NOT NULL,
  `empresa_id`           INT UNSIGNED  NOT NULL,
  `comprador_id`         INT UNSIGNED  NOT NULL,
  `estado`               ENUM('pendiente','confirmado','en_preparacion','en_ruta','entregado','cancelado') NOT NULL DEFAULT 'pendiente',
  `requiere_aprobacion`  TINYINT(1)    NOT NULL DEFAULT 0,
  `aprobado_por`         INT UNSIGNED  NULL,
  `aprobado_at`          TIMESTAMP     NULL,
  `fecha_entrega`        DATE          NULL,
  `metodo_pago`          ENUM('transferencia','tarjeta','credito') NULL,
  `subtotal`             DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  `total`                DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  `notas`                TEXT          NULL,
  `created_at`           TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_folio` (`folio`),
  CONSTRAINT `fk_ped_empresa`   FOREIGN KEY (`empresa_id`)  REFERENCES `empresas`(`id`),
  CONSTRAINT `fk_ped_comprador` FOREIGN KEY (`comprador_id`) REFERENCES `usuarios`(`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ── Detalle de pedido ───────────────────────────────────────────
CREATE TABLE `pedido_detalle` (
  `id`          INT UNSIGNED  NOT NULL AUTO_INCREMENT,
  `pedido_id`   INT UNSIGNED  NOT NULL,
  `producto_id` INT UNSIGNED  NOT NULL,
  `cantidad`    DECIMAL(10,2) NOT NULL,
  `precio_unit` DECIMAL(10,2) NOT NULL,
  `subtotal`    DECIMAL(12,2) NOT NULL,
  PRIMARY KEY (`id`),
  CONSTRAINT `fk_pd_pedido`  FOREIGN KEY (`pedido_id`)  REFERENCES `pedidos`(`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_pd_prod`    FOREIGN KEY (`producto_id`) REFERENCES `productos`(`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ── Distribución por sucursal ───────────────────────────────────
CREATE TABLE `pedido_sucursal` (
  `id`          INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `pedido_id`   INT UNSIGNED NOT NULL,
  `sucursal_id` INT UNSIGNED NOT NULL,
  `estado`      ENUM('pendiente','entregado','parcial','rechazado') NOT NULL DEFAULT 'pendiente',
  `hora_entrega` DATETIME NULL,
  PRIMARY KEY (`id`),
  CONSTRAINT `fk_ps_ped` FOREIGN KEY (`pedido_id`)   REFERENCES `pedidos`(`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_ps_suc` FOREIGN KEY (`sucursal_id`) REFERENCES `sucursales`(`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ── Vehículos ───────────────────────────────────────────────────
CREATE TABLE `vehiculos` (
  `id`         INT UNSIGNED  NOT NULL AUTO_INCREMENT,
  `empresa_id` INT UNSIGNED  NOT NULL,
  `placa`      VARCHAR(20)   NOT NULL,
  `modelo`     VARCHAR(100)  NULL,
  `capacidad`  DECIMAL(10,2) NULL,
  `activo`     TINYINT(1)    NOT NULL DEFAULT 1,
  PRIMARY KEY (`id`),
  CONSTRAINT `fk_veh_empresa` FOREIGN KEY (`empresa_id`) REFERENCES `empresas`(`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ── Asignación repartidor–vehículo ──────────────────────────────
CREATE TABLE `repartidor_vehiculo` (
  `repartidor_id` INT UNSIGNED NOT NULL,
  `vehiculo_id`   INT UNSIGNED NOT NULL,
  `activo`        TINYINT(1)   NOT NULL DEFAULT 1,
  PRIMARY KEY (`repartidor_id`, `vehiculo_id`),
  CONSTRAINT `fk_rv_rep` FOREIGN KEY (`repartidor_id`) REFERENCES `usuarios`(`id`),
  CONSTRAINT `fk_rv_veh` FOREIGN KEY (`vehiculo_id`)   REFERENCES `vehiculos`(`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ── Rutas de entrega ────────────────────────────────────────────
CREATE TABLE `rutas` (
  `id`            INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `empresa_id`    INT UNSIGNED NOT NULL,
  `nombre`        VARCHAR(100) NULL,
  `fecha`         DATE         NOT NULL,
  `repartidor_id` INT UNSIGNED NULL,
  `vehiculo_id`   INT UNSIGNED NULL,
  `estado`        ENUM('planificada','en_curso','completada','cancelada') NOT NULL DEFAULT 'planificada',
  `created_at`    TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  CONSTRAINT `fk_ruta_empresa` FOREIGN KEY (`empresa_id`)    REFERENCES `empresas`(`id`),
  CONSTRAINT `fk_ruta_rep`     FOREIGN KEY (`repartidor_id`) REFERENCES `usuarios`(`id`),
  CONSTRAINT `fk_ruta_veh`     FOREIGN KEY (`vehiculo_id`)   REFERENCES `vehiculos`(`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ── Detalle de ruta (paradas) ───────────────────────────────────
CREATE TABLE `ruta_detalle` (
  `id`                INT UNSIGNED  NOT NULL AUTO_INCREMENT,
  `ruta_id`           INT UNSIGNED  NOT NULL,
  `pedido_id`         INT UNSIGNED  NOT NULL,
  `sucursal_id`       INT UNSIGNED  NOT NULL,
  `orden`             TINYINT UNSIGNED NOT NULL DEFAULT 1,
  `estado`            ENUM('pendiente','entregado','parcial','fallido') NOT NULL DEFAULT 'pendiente',
  `lat_actual`        DECIMAL(10,8) NULL,
  `lng_actual`        DECIMAL(11,8) NULL,
  `eta_minutos`       INT           NULL,
  `traccar_device_id` VARCHAR(50)   NULL,
  `tracking_activo`   TINYINT(1)    NOT NULL DEFAULT 0,
  `hora_entrega`      DATETIME      NULL,
  PRIMARY KEY (`id`),
  CONSTRAINT `fk_rd_ruta`     FOREIGN KEY (`ruta_id`)     REFERENCES `rutas`(`id`),
  CONSTRAINT `fk_rd_pedido`   FOREIGN KEY (`pedido_id`)   REFERENCES `pedidos`(`id`),
  CONSTRAINT `fk_rd_sucursal` FOREIGN KEY (`sucursal_id`) REFERENCES `sucursales`(`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ── Evidencias de entrega (POD) ─────────────────────────────────
CREATE TABLE `evidencias_entrega` (
  `id`               INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `ruta_detalle_id`  INT UNSIGNED NOT NULL,
  `nombre_receptor`  VARCHAR(100) NULL,
  `firma_path`       VARCHAR(255) NULL,
  `foto_path`        VARCHAR(255) NULL,
  `entregado_at`     DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  CONSTRAINT `fk_ev_rd` FOREIGN KEY (`ruta_detalle_id`) REFERENCES `ruta_detalle`(`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ── Pedidos recurrentes ─────────────────────────────────────────
CREATE TABLE `pedidos_recurrentes` (
  `id`            INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `empresa_id`    INT UNSIGNED NOT NULL,
  `nombre`        VARCHAR(150) NOT NULL,
  `frecuencia`    ENUM('diario','semanal','quincenal') NOT NULL,
  `dia_semana`    TINYINT      NULL,
  `activo`        TINYINT(1)   NOT NULL DEFAULT 1,
  `proximo_pedido` DATE        NULL,
  `created_by`    INT UNSIGNED NULL,
  `created_at`    TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  CONSTRAINT `fk_rec_empresa` FOREIGN KEY (`empresa_id`) REFERENCES `empresas`(`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ── Detalle plantilla recurrente ────────────────────────────────
CREATE TABLE `plantilla_recurrente_detalle` (
  `id`            INT UNSIGNED  NOT NULL AUTO_INCREMENT,
  `recurrente_id` INT UNSIGNED  NOT NULL,
  `producto_id`   INT UNSIGNED  NOT NULL,
  `sucursal_id`   INT UNSIGNED  NOT NULL,
  `cantidad`      DECIMAL(10,2) NOT NULL,
  PRIMARY KEY (`id`),
  CONSTRAINT `fk_prd_rec`  FOREIGN KEY (`recurrente_id`) REFERENCES `pedidos_recurrentes`(`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_prd_prod` FOREIGN KEY (`producto_id`)   REFERENCES `productos`(`id`),
  CONSTRAINT `fk_prd_suc`  FOREIGN KEY (`sucursal_id`)   REFERENCES `sucursales`(`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ── Límites de compra ───────────────────────────────────────────
CREATE TABLE `limites_compra` (
  `id`           INT UNSIGNED  NOT NULL AUTO_INCREMENT,
  `empresa_id`   INT UNSIGNED  NOT NULL,
  `sucursal_id`  INT UNSIGNED  NULL,
  `producto_id`  INT UNSIGNED  NULL,
  `limite_kg`    DECIMAL(10,2) NULL,
  `limite_monto` DECIMAL(12,2) NULL,
  `periodo`      ENUM('por_pedido','semanal','mensual') NOT NULL DEFAULT 'por_pedido',
  `activo`       TINYINT(1)    NOT NULL DEFAULT 1,
  `created_by`   INT UNSIGNED  NULL,
  PRIMARY KEY (`id`),
  CONSTRAINT `fk_lc_empresa` FOREIGN KEY (`empresa_id`) REFERENCES `empresas`(`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ── Pagos ───────────────────────────────────────────────────────
CREATE TABLE `pagos` (
  `id`             INT UNSIGNED  NOT NULL AUTO_INCREMENT,
  `pedido_id`      INT UNSIGNED  NOT NULL,
  `monto`          DECIMAL(12,2) NOT NULL,
  `metodo`         ENUM('transferencia','tarjeta','credito') NOT NULL,
  `estatus`        ENUM('pendiente','confirmado','rechazado') NOT NULL DEFAULT 'pendiente',
  `comprobante`    VARCHAR(255)  NULL,
  `confirmado_por` INT UNSIGNED  NULL,
  `created_at`     TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  CONSTRAINT `fk_pago_pedido` FOREIGN KEY (`pedido_id`) REFERENCES `pedidos`(`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ── Facturas CFDI ───────────────────────────────────────────────
CREATE TABLE `facturas` (
  `id`         INT UNSIGNED  NOT NULL AUTO_INCREMENT,
  `pedido_id`  INT UNSIGNED  NOT NULL,
  `uuid_cfdi`  VARCHAR(50)   NULL,
  `xml_path`   VARCHAR(255)  NULL,
  `pdf_path`   VARCHAR(255)  NULL,
  `serie`      VARCHAR(10)   NULL,
  `folio_fac`  VARCHAR(20)   NULL,
  `monto`      DECIMAL(12,2) NULL,
  `created_at` TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_uuid_cfdi` (`uuid_cfdi`),
  CONSTRAINT `fk_fac_pedido` FOREIGN KEY (`pedido_id`) REFERENCES `pedidos`(`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ── Configuración global ────────────────────────────────────────
CREATE TABLE `global_settings` (
  `clave`    VARCHAR(100) NOT NULL,
  `valor`    TEXT         NULL,
  `tipo`     ENUM('text','number','boolean','json','color','password') NOT NULL DEFAULT 'text',
  `grupo`    VARCHAR(50)  NULL,
  `etiqueta` VARCHAR(150) NULL,
  PRIMARY KEY (`clave`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO `global_settings` (`clave`, `valor`, `tipo`, `grupo`, `etiqueta`) VALUES
  ('app_name',           'CarniHub',           'text',     'general',        'Nombre del sitio'),
  ('app_logo',           '',                   'text',     'general',        'Logo del sitio (ruta o URL)'),
  ('color_primary',      '#C8102E',             'color',    'estilos',        'Color primario'),
  ('color_secondary',    '#1f2937',             'color',    'estilos',        'Color secundario'),
  ('smtp_host',          '',                   'text',     'correo',         'Servidor SMTP'),
  ('smtp_port',          '587',                'number',   'correo',         'Puerto SMTP'),
  ('smtp_user',          '',                   'text',     'correo',         'Usuario SMTP'),
  ('smtp_pass',          '',                   'password', 'correo',         'Contraseña SMTP'),
  ('smtp_from',          '',                   'text',     'correo',         'Correo remitente'),
  ('telefono_contacto',  '',                   'text',     'contacto',       'Teléfono de contacto'),
  ('horarios_atencion',  'Lun-Vie 8am-6pm',    'text',     'contacto',       'Horarios de atención'),
  ('paypal_client_id',   '',                   'text',     'pagos',          'PayPal Client ID'),
  ('paypal_secret',      '',                   'password', 'pagos',          'PayPal Secret'),
  ('paypal_mode',        'sandbox',            'text',     'pagos',          'PayPal Mode (sandbox/live)'),
  ('google_maps_key',    '',                   'password', 'apis',           'Google Maps API Key'),
  ('qr_api_url',         '',                   'text',     'apis',           'API QR Masivos URL'),
  ('qr_api_key',         '',                   'password', 'apis',           'API QR Key'),
  ('whatsapp_api_token', '',                   'password', 'notificaciones', 'WhatsApp API Token'),
  ('whatsapp_phone_id',  '',                   'text',     'notificaciones', 'WhatsApp Phone ID'),
  ('traccar_url',        '',                   'text',     'gps',            'Traccar URL'),
  ('traccar_user',       '',                   'text',     'gps',            'Traccar Usuario'),
  ('traccar_pass',       '',                   'password', 'gps',            'Traccar Contraseña'),
  ('facturalo_api_key',  '',                   'password', 'facturacion',    'Factura-lo API Key'),
  ('shelly_api_url',     '',                   'text',     'iot',            'Shelly Cloud URL'),
  ('shelly_auth_key',    '',                   'password', 'iot',            'Shelly Auth Key'),
  ('hikvision_host',     '',                   'text',     'iot',            'HikVision Host'),
  ('hikvision_user',     '',                   'text',     'iot',            'HikVision Usuario'),
  ('hikvision_pass',     '',                   'password', 'iot',            'HikVision Contraseña');

-- ── Bitácora de acciones ────────────────────────────────────────
CREATE TABLE `action_logs` (
  `id`          BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `usuario_id`  INT UNSIGNED    NULL,
  `rol`         VARCHAR(30)     NULL,
  `empresa_id`  INT UNSIGNED    NULL,
  `accion`      VARCHAR(100)    NULL,
  `modulo`      VARCHAR(50)     NULL,
  `descripcion` TEXT            NULL,
  `ip`          VARCHAR(45)     NULL,
  `created_at`  TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ── Registro de errores ─────────────────────────────────────────
CREATE TABLE `error_logs` (
  `id`         BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `nivel`      ENUM('error','warning','info') NOT NULL DEFAULT 'error',
  `mensaje`    TEXT            NULL,
  `archivo`    VARCHAR(255)    NULL,
  `linea`      INT             NULL,
  `contexto`   JSON            NULL,
  `created_at` TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ── Control brute-force login ───────────────────────────────────
CREATE TABLE `login_intentos` (
  `id`    INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `ip`    VARCHAR(45)  NOT NULL,
  `email` VARCHAR(150) NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_ip` (`ip`),
  KEY `idx_created` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

SET FOREIGN_KEY_CHECKS = 1;

-- ── Seed: SuperAdmin inicial ────────────────────────────────────
-- Contraseña: Admin2024! (cambiar en producción)
INSERT INTO `usuarios` (`nombre`, `apellido_paterno`, `email`, `password`, `rol_id`, `activo`)
VALUES ('Super', 'Admin', 'admin@carnihub.mx',
        '$2y$10$TKh8H1.PfcaZiGPBo1JsH.o7S3nGpXWMZuWtmE5tPk.yWn2bKxkuu', 1, 1);

-- ── Seed: Datos de demo ─────────────────────────────────────────
-- Empresa de prueba
INSERT INTO `empresas` (`razon_social`, `rfc`, `tipo_negocio`, `email`, `telefono`, `activo`)
VALUES ('Taquería El Buen Sabor SA de CV', 'TBS9001011K3', 'taqueria', 'contacto@buensabor.mx', '4421234567', 1);

-- Admin Empresa de prueba (contraseña: Admin2024!)
INSERT INTO `usuarios` (`nombre`, `apellido_paterno`, `email`, `password`, `rol_id`, `empresa_id`, `activo`)
VALUES ('Juan', 'Pérez', 'juan@buensabor.mx',
        '$2y$10$TKh8H1.PfcaZiGPBo1JsH.o7S3nGpXWMZuWtmE5tPk.yWn2bKxkuu', 3, 1, 1);

-- Sucursal de prueba
INSERT INTO `sucursales` (`empresa_id`, `nombre`, `direccion`, `activo`)
VALUES (1, 'Sucursal Centro', 'Centro Histórico, Querétaro', 1),
       (1, 'Sucursal Norte', 'Juriquilla, Querétaro', 1);

-- Productos de prueba
INSERT INTO `productos` (`categoria_id`, `nombre`, `descripcion`, `presentacion`, `precio_base`, `activo`) VALUES
  (1, 'Bistec de Res',     'Corte premium para tacos',    'kg',   185.00, 1),
  (1, 'Falda de Res',      'Ideal para tacos de canasta', 'kg',   165.00, 1),
  (1, 'Suadero',           'Corte tradicional taquero',   'kg',   170.00, 1),
  (2, 'Carne para pastor', 'Mezcla de cerdo marinada',    'kg',   145.00, 1),
  (3, 'Pechuga de Pollo',  'Filete entero sin hueso',     'kg',   120.00, 1);

-- Precios escalonados de ejemplo
INSERT INTO `precios_escalonados` (`producto_id`, `cantidad_min`, `cantidad_max`, `precio`) VALUES
  (1, 1, 9.99, 185.00), (1, 10, 49.99, 175.00), (1, 50, NULL, 160.00),
  (2, 1, 9.99, 165.00), (2, 10, 49.99, 155.00), (2, 50, NULL, 142.00),
  (3, 1, 9.99, 170.00), (3, 10, 49.99, 160.00), (3, 50, NULL, 148.00),
  (4, 1, 9.99, 145.00), (4, 10, 49.99, 138.00), (4, 50, NULL, 128.00),
  (5, 1, 9.99, 120.00), (5, 10, 49.99, 112.00), (5, 50, NULL, 105.00);

-- Inventario inicial
INSERT INTO `inventario` (`producto_id`, `stock`, `umbral_minimo`) VALUES
  (1, 500, 50), (2, 350, 40), (3, 420, 50), (4, 600, 60), (5, 800, 80);
