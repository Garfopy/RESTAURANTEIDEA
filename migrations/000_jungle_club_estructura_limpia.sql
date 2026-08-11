-- Migración de estructura limpia
-- Origen: jungle_club_estructura.sql
-- Generada sin registros, sin CREATE DATABASE y sin USE.
-- Objetos: 93 tablas, 194 bloques ALTER, 9 triggers.

SET SQL_MODE = 'NO_AUTO_VALUE_ON_ZERO';
SET time_zone = '+00:00';
SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- ============================================================
-- TABLAS
-- ============================================================

CREATE TABLE `action_logs` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `usuario_id` int(10) UNSIGNED DEFAULT NULL,
  `rol` varchar(30) DEFAULT NULL,
  `empresa_id` int(10) UNSIGNED DEFAULT NULL,
  `accion` varchar(100) DEFAULT NULL,
  `modulo` varchar(50) DEFAULT NULL,
  `descripcion` text,
  `ip` varchar(45) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE `jungle_branch_menu_modifiers` (
  `branch_id` int(10) UNSIGNED NOT NULL,
  `platillo_external_id` int(10) UNSIGNED NOT NULL,
  `payload_json` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `jungle_points_transactions` (
  `id` int(10) UNSIGNED NOT NULL,
  `usuario_id` int(10) UNSIGNED NOT NULL,
  `tipo` enum('purchase','reward_redemption','adjustment') COLLATE utf8mb4_unicode_ci NOT NULL,
  `puntos` int(11) NOT NULL,
  `saldo_resultante` int(11) NOT NULL,
  `referencia_tipo` varchar(40) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `referencia_id` varchar(120) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `descripcion` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `metadata_json` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `jungle_wallets` (
  `id` int(10) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED NOT NULL,
  `usuario_id` int(10) UNSIGNED DEFAULT NULL,
  `balance_mxn` decimal(12,2) NOT NULL DEFAULT '0.00',
  `purchased_balance_mxn` decimal(12,2) NOT NULL DEFAULT '0.00',
  `promotional_balance_mxn` decimal(12,2) NOT NULL DEFAULT '0.00',
  `points` int(10) UNSIGNED NOT NULL DEFAULT '0',
  `simulated_balance` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `jungle_wallet_topups` (
  `id` int(10) UNSIGNED NOT NULL,
  `usuario_id` int(10) UNSIGNED NOT NULL,
  `payment_intent_id` varchar(120) COLLATE utf8mb4_unicode_ci NOT NULL,
  `stripe_customer_id` varchar(120) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `requested_amount` decimal(10,2) NOT NULL,
  `amount_received` decimal(10,2) DEFAULT NULL,
  `currency` char(3) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'MXN',
  `status` enum('pending','confirmed','failed','canceled') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending',
  `metadata_json` text COLLATE utf8mb4_unicode_ci,
  `confirmed_at` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `jungle_wallet_transactions` (
  `id` int(10) UNSIGNED NOT NULL,
  `wallet_id` int(10) UNSIGNED NOT NULL DEFAULT '0',
  `user_id` int(10) UNSIGNED NOT NULL DEFAULT '0',
  `type` varchar(40) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'wallet_payment',
  `funding_type` varchar(24) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `context` varchar(30) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `reference_type` varchar(40) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `reference_id` int(10) UNSIGNED DEFAULT NULL,
  `amount_mxn` decimal(12,2) NOT NULL DEFAULT '0.00',
  `points_delta` int(11) NOT NULL DEFAULT '0',
  `balance_after_mxn` decimal(12,2) NOT NULL DEFAULT '0.00',
  `points_after` int(10) UNSIGNED NOT NULL DEFAULT '0',
  `description` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `usuario_id` int(10) UNSIGNED NOT NULL,
  `tipo` enum('topup','purchase','reward_redemption','adjustment') COLLATE utf8mb4_unicode_ci NOT NULL,
  `monto` decimal(10,2) NOT NULL,
  `saldo_resultante` decimal(10,2) NOT NULL,
  `referencia_tipo` varchar(40) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `referencia_id` varchar(120) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `descripcion` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `metadata_json` text COLLATE utf8mb4_unicode_ci,
  `external_reference` varchar(120) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `api_rate_limits` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `bucket_key` char(64) COLLATE utf8mb4_unicode_ci NOT NULL,
  `scope` varchar(80) COLLATE utf8mb4_unicode_ci NOT NULL,
  `attempts` int(10) UNSIGNED NOT NULL DEFAULT '1',
  `window_started_at` datetime NOT NULL,
  `updated_at` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `api_tokens` (
  `id` int(10) UNSIGNED NOT NULL,
  `empresa_id` int(10) UNSIGNED NOT NULL,
  `comprador_id` int(10) UNSIGNED NOT NULL,
  `nombre` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `token` char(64) COLLATE utf8mb4_unicode_ci NOT NULL,
  `scopes` json DEFAULT NULL,
  `webhook_url` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `webhook_secret` varchar(128) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `activo` tinyint(1) NOT NULL DEFAULT '1',
  `ultimo_uso` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `api_access_log` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `token_id` int(10) UNSIGNED NOT NULL,
  `endpoint` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `metodo` varchar(10) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `ip` varchar(45) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status` smallint(5) UNSIGNED DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `app_clientes` (
  `id` int(10) UNSIGNED NOT NULL,
  `google_id` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `apple_id` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `nombre` varchar(120) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(120) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `telefono` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `foto_url` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `password_hash` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `activo` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `app_modificadores` (
  `id` int(10) UNSIGNED NOT NULL,
  `restaurante_id` int(10) UNSIGNED NOT NULL,
  `nombre` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL,
  `tipo` enum('radio','checkbox') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'radio',
  `requerido` tinyint(1) NOT NULL DEFAULT '0',
  `min_selecciones` tinyint(4) NOT NULL DEFAULT '0',
  `max_selecciones` tinyint(4) NOT NULL DEFAULT '1',
  `activo` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `app_opciones_modificador` (
  `id` int(10) UNSIGNED NOT NULL,
  `modificador_id` int(10) UNSIGNED NOT NULL,
  `nombre` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL,
  `precio_extra` decimal(8,2) NOT NULL DEFAULT '0.00',
  `activo` tinyint(1) NOT NULL DEFAULT '1'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `app_platillo_modificadores` (
  `platillo_id` int(10) UNSIGNED NOT NULL,
  `modificador_id` int(10) UNSIGNED NOT NULL,
  `orden` tinyint(4) NOT NULL DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `app_tokens` (
  `id` int(10) UNSIGNED NOT NULL,
  `cliente_id` int(10) UNSIGNED NOT NULL,
  `token_hash` varchar(64) COLLATE utf8mb4_unicode_ci NOT NULL,
  `refresh_hash` varchar(64) COLLATE utf8mb4_unicode_ci NOT NULL,
  `expires_at` datetime NOT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `carnihub_api_config` (
  `id` int(10) UNSIGNED NOT NULL,
  `restaurante_id` int(10) UNSIGNED NOT NULL,
  `carnihub_url` varchar(255) NOT NULL,
  `api_key` varchar(128) NOT NULL,
  `carnihub_empresa_id` int(10) UNSIGNED DEFAULT NULL,
  `nombre_distribuidor` varchar(200) DEFAULT NULL,
  `webhook_secret` varchar(64) DEFAULT NULL,
  `webhook_url` varchar(255) DEFAULT NULL,
  `activo` tinyint(1) NOT NULL DEFAULT '1',
  `ultima_sincronizacion` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  `metodo_pago` enum('stripe','paypal','transferencia') NOT NULL DEFAULT 'transferencia',
  `instrucciones_transferencia` text,
  `stripe_customer_id` varchar(64) DEFAULT NULL,
  `stripe_payment_method_id` varchar(64) DEFAULT NULL,
  `stripe_card_last4` varchar(4) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE `direcciones` (
  `id` int(11) NOT NULL,
  `usuario_id` int(11) NOT NULL,
  `alias` varchar(100) COLLATE utf8_unicode_ci DEFAULT NULL,
  `calle` varchar(200) COLLATE utf8_unicode_ci DEFAULT NULL,
  `numero` varchar(20) COLLATE utf8_unicode_ci DEFAULT NULL,
  `colonia` varchar(100) COLLATE utf8_unicode_ci DEFAULT NULL,
  `ciudad` varchar(100) COLLATE utf8_unicode_ci DEFAULT NULL,
  `lat` decimal(10,7) DEFAULT NULL,
  `lng` decimal(10,7) DEFAULT NULL,
  `cp` varchar(10) COLLATE utf8_unicode_ci DEFAULT NULL,
  `es_principal` tinyint(4) DEFAULT '0',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

CREATE TABLE `empresas` (
  `id` int(10) UNSIGNED NOT NULL,
  `razon_social` varchar(200) NOT NULL,
  `rfc` varchar(15) DEFAULT NULL,
  `tipo_negocio` enum('taqueria','carniceria','restaurante','comedor','otro') DEFAULT NULL,
  `email` varchar(150) DEFAULT NULL,
  `telefono` varchar(20) DEFAULT NULL,
  `direccion_fiscal` text,
  `activo` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE `facturacion_solicitudes` (
  `id` int(11) NOT NULL,
  `restaurante_id` int(11) NOT NULL,
  `pedido_id` int(11) DEFAULT NULL,
  `consumo_id` varchar(64) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `mesa_id` int(11) DEFAULT NULL,
  `division_id` int(11) DEFAULT NULL,
  `division_cuenta_id` int(11) DEFAULT NULL,
  `mobile_usuario_id` int(11) DEFAULT NULL,
  `solicitado_por_usuario_id` int(11) DEFAULT NULL,
  `origen` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'cliente',
  `scope` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pedido',
  `monto` decimal(10,2) NOT NULL DEFAULT '0.00',
  `metodo_pago` varchar(40) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `estado` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pendiente',
  `receptor_rfc` varchar(13) COLLATE utf8mb4_unicode_ci NOT NULL,
  `receptor_nombre` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `receptor_regimen_fiscal` varchar(10) COLLATE utf8mb4_unicode_ci NOT NULL,
  `receptor_codigo_postal` varchar(10) COLLATE utf8mb4_unicode_ci NOT NULL,
  `uso_cfdi` varchar(10) COLLATE utf8mb4_unicode_ci NOT NULL,
  `receptor_email` varchar(190) COLLATE utf8mb4_unicode_ci NOT NULL,
  `facturapi_invoice_id` varchar(80) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `facturapi_status` varchar(40) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `facturapi_livemode` tinyint(1) DEFAULT NULL,
  `cfdi_uuid` varchar(80) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `pdf_url` text COLLATE utf8mb4_unicode_ci,
  `xml_url` text COLLATE utf8mb4_unicode_ci,
  `notas` text COLLATE utf8mb4_unicode_ci,
  `facturada_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `favoritos` (
  `id` int(11) NOT NULL,
  `usuario_id` int(11) NOT NULL,
  `platillo_id` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

CREATE TABLE `global_settings` (
  `clave` varchar(100) NOT NULL,
  `valor` text,
  `tipo` enum('text','number','boolean','json','color','password') NOT NULL DEFAULT 'text',
  `grupo` varchar(50) DEFAULT NULL,
  `etiqueta` varchar(150) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE `login_intentos` (
  `id` int(10) UNSIGNED NOT NULL,
  `ip` varchar(45) NOT NULL,
  `email` varchar(150) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE `mobile_datos_fiscales` (
  `id` int(11) NOT NULL,
  `usuario_id` int(11) NOT NULL,
  `rfc` varchar(13) COLLATE utf8mb4_unicode_ci NOT NULL,
  `nombre_fiscal` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `regimen_fiscal` varchar(10) COLLATE utf8mb4_unicode_ci NOT NULL,
  `codigo_postal` varchar(10) COLLATE utf8mb4_unicode_ci NOT NULL,
  `uso_cfdi` varchar(10) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(190) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `mobile_direcciones` (
  `id` int(10) UNSIGNED NOT NULL,
  `usuario_id` int(10) UNSIGNED NOT NULL,
  `alias` varchar(80) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'Casa',
  `calle` varchar(300) COLLATE utf8mb4_unicode_ci NOT NULL,
  `numero` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `colonia` varchar(150) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `ciudad` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL,
  `estado_provincia` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `cp` varchar(10) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `lat` decimal(10,8) DEFAULT NULL,
  `lng` decimal(11,8) DEFAULT NULL,
  `instrucciones` text COLLATE utf8mb4_unicode_ci,
  `es_principal` tinyint(1) NOT NULL DEFAULT '0',
  `activo` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `mobile_favoritos` (
  `id` int(10) UNSIGNED NOT NULL,
  `usuario_id` int(10) UNSIGNED NOT NULL,
  `platillo_id` int(10) UNSIGNED NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `mobile_notification_logs` (
  `id` int(10) UNSIGNED NOT NULL,
  `promotion_id` int(10) UNSIGNED DEFAULT NULL,
  `usuario_id` int(10) UNSIGNED NOT NULL,
  `fcm_token_id` int(10) UNSIGNED DEFAULT NULL,
  `fcm_token` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `provider` varchar(40) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'fcm',
  `status` enum('pending','sent','failed','skipped') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending',
  `title` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `body` text COLLATE utf8mb4_unicode_ci,
  `response` text COLLATE utf8mb4_unicode_ci,
  `error` text COLLATE utf8mb4_unicode_ci,
  `sent_at` datetime DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `mobile_promociones` (
  `id` int(10) UNSIGNED NOT NULL,
  `usuario_id` int(10) UNSIGNED NOT NULL,
  `producto_id` int(10) UNSIGNED DEFAULT NULL,
  `platillo_id` int(11) DEFAULT NULL,
  `titulo` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `descripcion` text COLLATE utf8mb4_unicode_ci,
  `imagen` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `deep_link` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `code` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `discount_type` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `discount_value` decimal(10,2) DEFAULT NULL,
  `activo` tinyint(1) NOT NULL DEFAULT '1',
  `expires_at` datetime DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `created_by` int(11) DEFAULT NULL,
  `updated_by` int(11) DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  `tipo_descuento` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'porcentaje',
  `valor_descuento` decimal(10,2) NOT NULL DEFAULT '0.00',
  `scope_tipo` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'all',
  `scope_ids` text COLLATE utf8mb4_unicode_ci,
  `buy_qty` int(10) UNSIGNED DEFAULT NULL,
  `pay_qty` int(10) UNSIGNED DEFAULT NULL,
  `min_subtotal` decimal(10,2) NOT NULL DEFAULT '0.00',
  `max_uses` int(10) UNSIGNED DEFAULT NULL,
  `combinable` tinyint(1) NOT NULL DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `mobile_promocion_usos` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `promocion_id` int(11) NOT NULL,
  `usuario_id` int(11) NOT NULL,
  `pedido_id` int(11) DEFAULT NULL,
  `codigo` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `descuento_mxn` decimal(10,2) NOT NULL DEFAULT '0.00',
  `estado` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'usado',
  `usado_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `mobile_push_tokens` (
  `id` int(11) NOT NULL,
  `usuario_id` int(11) NOT NULL,
  `fcm_token` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `platform` varchar(30) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `device_id` varchar(190) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `enabled` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `last_seen_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `mobile_sesiones` (
  `id` int(10) UNSIGNED NOT NULL,
  `usuario_id` int(10) UNSIGNED NOT NULL,
  `token_hash` char(64) COLLATE utf8mb4_unicode_ci NOT NULL,
  `device_info` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `platform` enum('ios','android','web') COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `activo` tinyint(1) NOT NULL DEFAULT '1',
  `ultimo_uso` timestamp NULL DEFAULT NULL,
  `expires_at` datetime NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `mobile_usuarios` (
  `id` int(10) UNSIGNED NOT NULL,
  `nombre` varchar(200) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(200) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `rol` enum('user','admin','mesero','hostess') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'user',
  `telefono` varchar(30) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `fecha_nacimiento` date DEFAULT NULL,
  `onboarding_completed_at` datetime DEFAULT NULL,
  `terms_accepted_at` datetime DEFAULT NULL,
  `marketing_opt_in` tinyint(1) NOT NULL DEFAULT '0',
  `foto_url` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `social_photos_json` text COLLATE utf8mb4_unicode_ci,
  `google_id` varchar(120) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `apple_id` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `stripe_customer_id` varchar(120) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `password_hash` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `password_reset_code_hash` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `password_reset_expires_at` datetime DEFAULT NULL,
  `password_reset_requested_at` datetime DEFAULT NULL,
  `jungle_saldo` decimal(10,2) NOT NULL DEFAULT '0.00',
  `jungle_puntos` int(11) NOT NULL DEFAULT '0',
  `activo` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `is_social_active` tinyint(1) NOT NULL DEFAULT '0',
  `current_restaurante_id` int(10) UNSIGNED DEFAULT NULL,
  `mesa` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `social_updated_at` datetime DEFAULT NULL,
  `social_consent_accepted_at` datetime DEFAULT NULL,
  `social_consent_version` varchar(40) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `edad` int(11) DEFAULT NULL,
  `sexualidad` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `genero` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `descripcion` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `intereses` varchar(1000) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `que_busca` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `redes_sociales` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `moderation_actions` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `action` varchar(80) COLLATE utf8mb4_unicode_ci NOT NULL,
  `target_type` varchar(40) COLLATE utf8mb4_unicode_ci NOT NULL,
  `target_id` bigint(20) UNSIGNED DEFAULT NULL,
  `user_id` int(10) UNSIGNED DEFAULT NULL,
  `photo_id` bigint(20) UNSIGNED DEFAULT NULL,
  `photo_url` varchar(600) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `decision` varchar(30) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `notes` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `moderator_id` bigint(20) UNSIGNED DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `rest_alertas` (
  `id` int(10) UNSIGNED NOT NULL,
  `restaurante_id` int(10) UNSIGNED NOT NULL,
  `tipo` enum('mesero','cuenta') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'mesero',
  `mesa_id` int(10) UNSIGNED DEFAULT NULL,
  `visita_id` int(10) UNSIGNED DEFAULT NULL,
  `atendida` tinyint(1) NOT NULL DEFAULT '0',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `rest_categorias_menu` (
  `id` int(10) UNSIGNED NOT NULL,
  `restaurante_id` int(10) UNSIGNED NOT NULL,
  `nombre` varchar(100) NOT NULL,
  `descripcion` varchar(255) DEFAULT NULL,
  `imagen` varchar(255) DEFAULT NULL,
  `orden` tinyint(4) NOT NULL DEFAULT '0',
  `activo` tinyint(1) NOT NULL DEFAULT '1'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE `rest_comensales` (
  `id` int(10) UNSIGNED NOT NULL,
  `restaurante_id` int(10) UNSIGNED NOT NULL,
  `nombre` varchar(200) DEFAULT NULL,
  `telefono` varchar(20) DEFAULT NULL,
  `email` varchar(150) DEFAULT NULL,
  `total_visitas` int(10) UNSIGNED NOT NULL DEFAULT '0',
  `total_gastado` decimal(12,2) NOT NULL DEFAULT '0.00',
  `ultima_visita` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE `rest_configuracion` (
  `id` int(11) NOT NULL,
  `restaurante_id` int(11) NOT NULL,
  `metodos_pago` json NOT NULL,
  `tipos_entrega` json NOT NULL,
  `costo_envio` decimal(10,2) DEFAULT '0.00',
  `pedido_minimo` decimal(10,2) DEFAULT '0.00',
  `exclusiones_habilitadas` tinyint(1) NOT NULL DEFAULT '1',
  `extras_habilitados` tinyint(1) NOT NULL DEFAULT '1',
  `config_version` bigint(20) UNSIGNED NOT NULL DEFAULT '1',
  `activo` tinyint(1) DEFAULT '1',
  `facturacion_habilitada` tinyint(1) NOT NULL DEFAULT '0',
  `facturacion_emisor_json` json DEFAULT NULL,
  `facturacion_email_notificacion` varchar(190) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `rest_cortes` (
  `id` int(10) UNSIGNED NOT NULL,
  `restaurante_id` int(10) UNSIGNED NOT NULL,
  `turno` varchar(50) NOT NULL DEFAULT 'General',
  `usuario_id` int(10) UNSIGNED NOT NULL,
  `ingresos` decimal(12,2) NOT NULL DEFAULT '0.00',
  `gastos` decimal(12,2) NOT NULL DEFAULT '0.00',
  `retiros` decimal(12,2) NOT NULL DEFAULT '0.00',
  `propinas` decimal(12,2) NOT NULL DEFAULT '0.00',
  `utilidad_neta` decimal(12,2) NOT NULL DEFAULT '0.00',
  `notas` text,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE `rest_cuenta_divisiones` (
  `id` int(10) UNSIGNED NOT NULL,
  `restaurante_id` int(10) UNSIGNED NOT NULL,
  `mesa_id` int(10) UNSIGNED NOT NULL,
  `estado` enum('activa','pagada','cancelada') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'activa',
  `creado_por_usuario_id` int(10) UNSIGNED NOT NULL,
  `creado_por_nombre` varchar(120) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT NULL,
  `completed_at` datetime DEFAULT NULL,
  `cancelled_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `rest_cuenta_division_cuentas` (
  `id` int(10) UNSIGNED NOT NULL,
  `division_id` int(10) UNSIGNED NOT NULL,
  `numero` smallint(5) UNSIGNED NOT NULL,
  `nombre` varchar(60) COLLATE utf8mb4_unicode_ci NOT NULL,
  `total` decimal(10,2) NOT NULL DEFAULT '0.00',
  `estado` enum('pendiente','pagada') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pendiente',
  `metodo_pago` varchar(30) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `pagado_por_usuario_id` int(10) UNSIGNED DEFAULT NULL,
  `pagado_por_nombre` varchar(120) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `pagado_at` datetime DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `rest_cuenta_division_items` (
  `id` int(10) UNSIGNED NOT NULL,
  `cuenta_id` int(10) UNSIGNED NOT NULL,
  `pedido_item_id` int(10) UNSIGNED NOT NULL,
  `cantidad` int(10) UNSIGNED NOT NULL,
  `precio_unit` decimal(10,2) NOT NULL,
  `subtotal` decimal(10,2) NOT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `rest_gastos` (
  `id` int(10) UNSIGNED NOT NULL,
  `restaurante_id` int(10) UNSIGNED NOT NULL,
  `categoria` enum('personal','suministros','mantenimiento','servicios','propinas','devolucion','marketing','otros') NOT NULL DEFAULT 'otros',
  `descripcion` varchar(255) NOT NULL,
  `monto` decimal(10,2) NOT NULL,
  `fecha` date NOT NULL,
  `comprobante` varchar(255) DEFAULT NULL,
  `usuario_id` int(10) UNSIGNED NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE `rest_ingredientes` (
  `id` int(10) UNSIGNED NOT NULL,
  `codigo` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `tipo` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'otro',
  `restaurante_id` int(10) UNSIGNED NOT NULL,
  `nombre` varchar(200) COLLATE utf8mb4_unicode_ci NOT NULL,
  `unidad_principal` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'kg',
  `unidad_compra` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `equivalencia` decimal(10,4) NOT NULL DEFAULT '1.0000',
  `costo_unitario` decimal(10,2) NOT NULL DEFAULT '0.00',
  `stock` decimal(10,3) NOT NULL DEFAULT '0.000',
  `stock_minimo` decimal(10,3) NOT NULL DEFAULT '0.000',
  `categoria` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `carnihub_producto_id` int(10) UNSIGNED DEFAULT NULL,
  `proveedor_carnihub` tinyint(1) NOT NULL DEFAULT '0',
  `proveedor_nombre` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `activo` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `rest_liberaciones_pedido_programador` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `restaurante_id` int(10) UNSIGNED NOT NULL,
  `pedido_id` int(10) UNSIGNED NOT NULL,
  `folio` varchar(80) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `cliente_referencia` varchar(200) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `estado_anterior` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `estado_nuevo` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'entregado',
  `estados_items_anteriores` text COLLATE utf8mb4_unicode_ci,
  `motivo` varchar(500) COLLATE utf8mb4_unicode_ci NOT NULL,
  `usuario_id` int(10) UNSIGNED DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `rest_mesas` (
  `id` int(10) UNSIGNED NOT NULL,
  `restaurante_id` int(10) UNSIGNED NOT NULL,
  `zona_id` int(10) UNSIGNED DEFAULT NULL,
  `nombre` varchar(50) NOT NULL,
  `capacidad` tinyint(4) NOT NULL DEFAULT '4',
  `qr_codigo` varchar(64) NOT NULL,
  `posicion_x` int(11) NOT NULL DEFAULT '0',
  `posicion_y` int(11) NOT NULL DEFAULT '0',
  `estado` enum('disponible','ocupada','reservada','pagando') NOT NULL DEFAULT 'disponible',
  `activo` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `mesero_usuario_id` int(10) UNSIGNED DEFAULT NULL,
  `mesero_nombre` varchar(120) DEFAULT NULL,
  `cliente_nombre` varchar(120) DEFAULT NULL,
  `reclamada_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE `rest_mesero_turno` (
  `id` int(10) UNSIGNED NOT NULL,
  `restaurante_id` int(10) UNSIGNED NOT NULL,
  `usuario_id` int(10) UNSIGNED NOT NULL,
  `zona_id` int(10) UNSIGNED NOT NULL,
  `turno_fecha` date NOT NULL,
  `activo` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `rest_modificadores` (
  `id` int(10) UNSIGNED NOT NULL,
  `restaurante_id` int(10) UNSIGNED NOT NULL,
  `ingrediente_id` int(10) UNSIGNED DEFAULT NULL,
  `nombre` varchar(120) COLLATE utf8mb4_unicode_ci NOT NULL,
  `descripcion` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `tipo` enum('extra','sin','opcion') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'opcion',
  `alcance` enum('platillo','restaurante') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'platillo',
  `precio_extra` decimal(10,2) NOT NULL DEFAULT '0.00',
  `cantidad_unidad` decimal(12,3) NOT NULL DEFAULT '1.000',
  `unidad` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pza',
  `max_seleccion_global` smallint(5) UNSIGNED NOT NULL DEFAULT '1',
  `activo` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `rest_movimientos_inventario` (
  `id` int(10) UNSIGNED NOT NULL,
  `restaurante_id` int(10) UNSIGNED NOT NULL,
  `ingrediente_id` int(10) UNSIGNED NOT NULL,
  `tipo` enum('entrada','salida','merma','ajuste') NOT NULL,
  `cantidad` decimal(10,3) NOT NULL,
  `stock_antes` decimal(10,3) NOT NULL,
  `stock_despues` decimal(10,3) NOT NULL,
  `motivo` varchar(255) DEFAULT NULL,
  `referencia` varchar(100) DEFAULT NULL,
  `usuario_id` int(10) UNSIGNED DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE `rest_pasos_preparacion` (
  `id` int(10) UNSIGNED NOT NULL,
  `restaurante_id` int(10) UNSIGNED NOT NULL,
  `platillo_id` int(10) UNSIGNED NOT NULL,
  `orden_paso` int(10) UNSIGNED NOT NULL,
  `descripcion` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `activo` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `rest_pedidos` (
  `id` int(10) UNSIGNED NOT NULL,
  `restaurante_id` int(10) UNSIGNED NOT NULL,
  `mesa_id` int(10) UNSIGNED DEFAULT NULL,
  `visita_id` int(10) UNSIGNED DEFAULT NULL,
  `consumo_id` varchar(40) DEFAULT NULL,
  `cuenta_abierta` tinyint(1) NOT NULL DEFAULT '0',
  `mesero_id` int(10) UNSIGNED DEFAULT NULL,
  `reclamado_por` int(10) UNSIGNED DEFAULT NULL,
  `reclamado_at` timestamp NULL DEFAULT NULL,
  `folio` varchar(20) NOT NULL,
  `estado` enum('pendiente','en_preparacion','listo','en_camino','entregado','cancelado') NOT NULL DEFAULT 'pendiente',
  `notas` text,
  `subtotal` decimal(10,2) NOT NULL DEFAULT '0.00',
  `descuento` decimal(10,2) NOT NULL DEFAULT '0.00',
  `promo_code` varchar(50) DEFAULT NULL,
  `total` decimal(10,2) NOT NULL DEFAULT '0.00',
  `jungle_wallet_used_mxn` decimal(12,2) DEFAULT NULL,
  `jungle_discount_mxn` decimal(12,2) DEFAULT NULL,
  `jungle_points_redeemed` int(10) UNSIGNED DEFAULT NULL,
  `tipo_pedido` enum('dine_in','take_out','delivery','pickup','eat_in') NOT NULL DEFAULT 'dine_in',
  `tipo_entrega` varchar(30) DEFAULT NULL,
  `pedido_origen` varchar(20) NOT NULL DEFAULT 'cliente',
  `mesero_usuario_id` int(10) UNSIGNED DEFAULT NULL,
  `mesero_nombre` varchar(120) DEFAULT NULL,
  `cliente_nombre` varchar(120) DEFAULT NULL,
  `comprador_telefono` varchar(30) DEFAULT NULL,
  `tipo_origen` varchar(20) NOT NULL DEFAULT 'menu',
  `direccion_entrega` text,
  `pickup_at` datetime DEFAULT NULL,
  `app_cliente_id` int(10) UNSIGNED DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `actualizado_at` datetime DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  `mobile_usuario_id` int(10) UNSIGNED DEFAULT NULL,
  `stripe_payment_intent_id` varchar(100) DEFAULT NULL,
  `metodo_pago` varchar(30) DEFAULT NULL,
  `pagado_at` datetime DEFAULT NULL,
  `stripe_payment_status` varchar(30) DEFAULT NULL,
  `stripe_payment_error` varchar(500) DEFAULT NULL,
  `stripe_refunded_cents` bigint(20) UNSIGNED NOT NULL DEFAULT '0',
  `stripe_disputed_at` datetime DEFAULT NULL,
  `app_order_id` varchar(80) DEFAULT NULL,
  `jungle_wallet_amount_applied` decimal(10,2) NOT NULL DEFAULT '0.00',
  `jungle_points_earned` int(11) NOT NULL DEFAULT '0',
  `cerrado_por_mesero_usuario_id` int(10) UNSIGNED DEFAULT NULL,
  `cerrado_por_mesero_nombre` varchar(120) DEFAULT NULL,
  `cerrado_at` datetime DEFAULT NULL,
  `salida_token` varchar(96) DEFAULT NULL,
  `salida_qr_generado_at` datetime DEFAULT NULL,
  `salida_validado_at` datetime DEFAULT NULL,
  `salida_validado_por` int(10) UNSIGNED DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE `rest_pedidos_sugeridos` (
  `id` int(10) UNSIGNED NOT NULL,
  `restaurante_id` int(10) UNSIGNED NOT NULL,
  `carnihub_empresa_id` int(10) UNSIGNED NOT NULL,
  `estado` enum('borrador','sugerido','aprobado','rechazado','convertido') NOT NULL DEFAULT 'sugerido',
  `total_estimado` decimal(12,2) NOT NULL DEFAULT '0.00',
  `notas` text,
  `usuario_id` int(10) UNSIGNED DEFAULT NULL,
  `pedido_carnihub_id` int(10) UNSIGNED DEFAULT NULL,
  `estado_carnihub` varchar(40) DEFAULT NULL,
  `ultima_sync_carnihub` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `aprobado_at` datetime DEFAULT NULL,
  `monto_total` decimal(10,2) DEFAULT NULL,
  `metodo_pago` varchar(30) DEFAULT NULL,
  `estado_pago` enum('pendiente','procesando','pagado','fallido') NOT NULL DEFAULT 'pendiente',
  `pago_referencia` varchar(255) DEFAULT NULL,
  `pagado_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE `rest_pedido_items` (
  `id` int(10) UNSIGNED NOT NULL,
  `pedido_id` int(10) UNSIGNED NOT NULL,
  `platillo_id` int(10) UNSIGNED NOT NULL,
  `origen` varchar(20) NOT NULL DEFAULT 'menu',
  `cantidad` tinyint(4) NOT NULL DEFAULT '1',
  `precio_unit` decimal(10,2) NOT NULL,
  `subtotal` decimal(10,2) NOT NULL,
  `notas` varchar(255) DEFAULT NULL,
  `extras_json` text,
  `exclusiones` text,
  `estado` enum('pendiente','en_preparacion','listo','entregado') NOT NULL DEFAULT 'pendiente',
  `extras` text
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE `rest_pedido_item_modificadores` (
  `id` int(10) UNSIGNED NOT NULL,
  `pedido_item_id` int(10) UNSIGNED NOT NULL,
  `modificador_id` int(10) UNSIGNED NOT NULL,
  `cantidad` smallint(5) UNSIGNED NOT NULL DEFAULT '1',
  `precio_extra` decimal(10,2) NOT NULL DEFAULT '0.00',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `rest_pedido_sugerido_items` (
  `id` int(10) UNSIGNED NOT NULL,
  `pedido_sugerido_id` int(10) UNSIGNED NOT NULL,
  `ingrediente_id` int(10) UNSIGNED NOT NULL,
  `carnihub_producto_id` int(10) UNSIGNED DEFAULT NULL,
  `cantidad_sugerida` decimal(10,3) NOT NULL,
  `cantidad_aprobada` decimal(10,3) DEFAULT NULL,
  `unidad` varchar(20) NOT NULL DEFAULT 'kg',
  `precio_unit_estimado` decimal(10,4) NOT NULL DEFAULT '0.0000',
  `subtotal_estimado` decimal(12,2) NOT NULL DEFAULT '0.00'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE `rest_platillos` (
  `id` int(10) UNSIGNED NOT NULL,
  `codigo` varchar(20) DEFAULT NULL,
  `es_armado` tinyint(1) NOT NULL DEFAULT '0',
  `restaurante_id` int(10) UNSIGNED NOT NULL,
  `categoria_id` int(10) UNSIGNED DEFAULT NULL,
  `nombre` varchar(200) NOT NULL,
  `descripcion` text,
  `alergenos` varchar(500) DEFAULT NULL,
  `contiene` text,
  `precio` decimal(10,2) NOT NULL DEFAULT '0.00',
  `imagen` varchar(255) DEFAULT NULL,
  `tiempo_preparacion_min` tinyint(4) NOT NULL DEFAULT '15',
  `disponible` tinyint(1) NOT NULL DEFAULT '1',
  `activo` tinyint(1) NOT NULL DEFAULT '1',
  `modificadores_sincronizados_at` datetime DEFAULT NULL,
  `ingrediente_directo_id` int(10) UNSIGNED DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE `rest_platillo_armado` (
  `id` int(10) UNSIGNED NOT NULL,
  `restaurante_id` int(10) UNSIGNED NOT NULL,
  `platillo_id` int(10) UNSIGNED NOT NULL,
  `orden_paso` int(10) UNSIGNED NOT NULL DEFAULT '1',
  `tipo` enum('ingrediente','guarnicion','accion') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'accion',
  `referencia_id` int(10) UNSIGNED DEFAULT NULL,
  `descripcion` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `obligatorio` tinyint(1) NOT NULL DEFAULT '1',
  `activo` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `rest_platillo_modificador` (
  `platillo_id` int(10) UNSIGNED NOT NULL,
  `modificador_id` int(10) UNSIGNED NOT NULL,
  `obligatorio` tinyint(1) NOT NULL DEFAULT '0',
  `max_seleccion` smallint(5) UNSIGNED DEFAULT '1'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `rest_platillo_modificadores` (
  `id` int(10) UNSIGNED NOT NULL,
  `restaurante_id` int(10) UNSIGNED NOT NULL,
  `platillo_id` int(10) UNSIGNED NOT NULL,
  `tipo` enum('exclusion','extra') COLLATE utf8mb4_unicode_ci NOT NULL,
  `nombre` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL,
  `ingrediente_id` int(10) UNSIGNED DEFAULT NULL,
  `cantidad_unidad` decimal(12,3) NOT NULL DEFAULT '0.000',
  `unidad` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `precio_unitario` decimal(10,2) NOT NULL DEFAULT '0.00',
  `max_cantidad` int(10) UNSIGNED NOT NULL DEFAULT '1',
  `activo` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `rest_promociones` (
  `id` int(10) UNSIGNED NOT NULL,
  `restaurante_id` int(10) UNSIGNED NOT NULL,
  `usuario_id` int(10) UNSIGNED DEFAULT NULL,
  `titulo` varchar(200) COLLATE utf8mb4_unicode_ci NOT NULL,
  `descripcion` text COLLATE utf8mb4_unicode_ci,
  `code` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `tipo` enum('porcentaje','monto_fijo','envio_gratis') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'porcentaje',
  `valor_descuento` decimal(10,2) NOT NULL DEFAULT '0.00',
  `fecha_inicio` date NOT NULL,
  `fecha_fin` date NOT NULL,
  `activo` tinyint(1) NOT NULL DEFAULT '1',
  `expires_at` datetime DEFAULT NULL,
  `imagen` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `deep_link` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `rest_promocion_comensales` (
  `id` int(10) UNSIGNED NOT NULL,
  `promocion_id` int(10) UNSIGNED NOT NULL,
  `comensal_id` int(10) UNSIGNED NOT NULL,
  `usado` tinyint(1) NOT NULL DEFAULT '0',
  `fecha_uso` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `rest_promocion_envios` (
  `id` int(10) UNSIGNED NOT NULL,
  `restaurante_id` int(10) UNSIGNED NOT NULL,
  `mobile_usuario_id` int(10) UNSIGNED NOT NULL,
  `comensal_id` int(10) UNSIGNED DEFAULT NULL,
  `promocion_remota_id` int(10) UNSIGNED DEFAULT NULL,
  `code` varchar(80) COLLATE utf8mb4_unicode_ci NOT NULL,
  `motivo` varchar(40) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'manual',
  `periodo` char(7) COLLATE utf8mb4_unicode_ci NOT NULL,
  `payload_json` text COLLATE utf8mb4_unicode_ci,
  `enviado_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `rest_recetas` (
  `id` int(10) UNSIGNED NOT NULL,
  `platillo_id` int(10) UNSIGNED NOT NULL,
  `porciones_base` tinyint(4) NOT NULL DEFAULT '1',
  `notas` text
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE `rest_receta_ingredientes` (
  `id` int(10) UNSIGNED NOT NULL,
  `receta_id` int(10) UNSIGNED NOT NULL,
  `ingrediente_id` int(10) UNSIGNED NOT NULL,
  `cantidad` decimal(10,3) NOT NULL,
  `unidad` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'kg',
  `notas` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `es_informativo` tinyint(1) NOT NULL DEFAULT '0',
  `precio_extra` decimal(10,2) NOT NULL DEFAULT '0.00',
  `tipo_componente` varchar(30) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `codigo_display` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `rest_regularizaciones_adeudo` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `restaurante_id` int(10) UNSIGNED NOT NULL,
  `tipo_registro` enum('ticket','pedido_app') COLLATE utf8mb4_unicode_ci NOT NULL,
  `registro_id` int(10) UNSIGNED NOT NULL,
  `folio` varchar(80) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `cliente_referencia` varchar(200) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `monto` decimal(12,2) NOT NULL DEFAULT '0.00',
  `estado_anterior` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `metodo_pago` enum('paypal','tarjeta','transferencia','efectivo') COLLATE utf8mb4_unicode_ci NOT NULL,
  `motivo` varchar(500) COLLATE utf8mb4_unicode_ci NOT NULL,
  `usuario_id` int(10) UNSIGNED DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `rest_reservaciones` (
  `id` int(10) UNSIGNED NOT NULL,
  `restaurante_id` int(10) UNSIGNED NOT NULL,
  `mesa_id` int(10) UNSIGNED DEFAULT NULL,
  `comensal_id` int(10) UNSIGNED DEFAULT NULL,
  `mesero_id` int(10) UNSIGNED DEFAULT NULL,
  `nombre` varchar(200) NOT NULL,
  `telefono` varchar(20) DEFAULT NULL,
  `email` varchar(150) DEFAULT NULL,
  `fecha` date NOT NULL,
  `hora` time NOT NULL,
  `personas` tinyint(4) NOT NULL DEFAULT '2',
  `estado` enum('pendiente','confirmada','cancelada','completada') NOT NULL DEFAULT 'pendiente',
  `origen` enum('restaurante','comensal') NOT NULL DEFAULT 'restaurante',
  `canal_reserva` enum('web','movil') NOT NULL DEFAULT 'web',
  `confirmacion_enviada` tinyint(1) NOT NULL DEFAULT '0',
  `recordatorio_enviado` tinyint(1) NOT NULL DEFAULT '0',
  `notas` text,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE `rest_restaurantes` (
  `id` int(10) UNSIGNED NOT NULL,
  `empresa_id` int(10) UNSIGNED NOT NULL,
  `comprador_id` int(10) UNSIGNED NOT NULL,
  `nombre` varchar(200) NOT NULL,
  `slug` varchar(100) NOT NULL,
  `logo` varchar(255) DEFAULT NULL,
  `imagen_banner` varchar(255) DEFAULT NULL,
  `color_primario` varchar(7) NOT NULL DEFAULT '#C8102E',
  `color_secundario` varchar(7) NOT NULL DEFAULT '#1f2937',
  `descripcion` text,
  `telefono` varchar(20) DEFAULT NULL,
  `direccion` text,
  `lat` decimal(10,8) DEFAULT NULL,
  `lng` decimal(11,8) DEFAULT NULL,
  `horario_apertura` time DEFAULT NULL,
  `horario_cierre` time DEFAULT NULL,
  `activo` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `mesas_habilitadas` tinyint(1) NOT NULL DEFAULT '1',
  `reservas_habilitadas` tinyint(1) NOT NULL DEFAULT '1',
  `app_movil_habilitada` tinyint(1) NOT NULL DEFAULT '0',
  `portero_habilitado` tinyint(1) NOT NULL DEFAULT '1',
  `propinas_sugeridas` varchar(40) NOT NULL DEFAULT '0,10,15,20',
  `requiere_login_comensal` tinyint(1) NOT NULL DEFAULT '0',
  `exclusiones_app_habilitadas` tinyint(1) NOT NULL DEFAULT '0',
  `extras_app_habilitados` tinyint(1) NOT NULL DEFAULT '0',
  `horarios_json` text,
  `sucursal_id` int(10) UNSIGNED DEFAULT NULL,
  `menu_principal` tinyint(1) NOT NULL DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE `rest_retiros` (
  `id` int(10) UNSIGNED NOT NULL,
  `restaurante_id` int(10) UNSIGNED NOT NULL,
  `descripcion` varchar(255) NOT NULL,
  `monto` decimal(10,2) NOT NULL,
  `usuario_id` int(10) UNSIGNED NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE `rest_staff` (
  `id` int(10) UNSIGNED NOT NULL,
  `restaurante_id` int(10) UNSIGNED NOT NULL,
  `usuario_id` int(10) UNSIGNED NOT NULL,
  `codigo` varchar(10) NOT NULL,
  `rol_slug` varchar(20) NOT NULL,
  `activo` tinyint(1) NOT NULL DEFAULT '1',
  `fecha_ingreso` date DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE `rest_staff_restaurantes` (
  `id` int(10) UNSIGNED NOT NULL,
  `usuario_id` int(10) UNSIGNED NOT NULL,
  `restaurante_id` int(10) UNSIGNED NOT NULL,
  `rol_operativo` varchar(40) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'mesero',
  `activo` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `rest_tickets` (
  `id` int(10) UNSIGNED NOT NULL,
  `restaurante_id` int(10) UNSIGNED NOT NULL,
  `visita_id` int(10) UNSIGNED NOT NULL,
  `mesa_id` int(10) UNSIGNED DEFAULT NULL,
  `folio` varchar(20) NOT NULL,
  `subtotal` decimal(10,2) NOT NULL DEFAULT '0.00',
  `propina` decimal(10,2) NOT NULL DEFAULT '0.00',
  `total` decimal(10,2) NOT NULL DEFAULT '0.00',
  `estado` enum('pendiente','pagado','cancelado') NOT NULL DEFAULT 'pendiente',
  `metodo_pago` enum('paypal','tarjeta','transferencia','efectivo','social_cover') DEFAULT NULL,
  `paypal_order_id` varchar(100) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `pagado_at` datetime DEFAULT NULL,
  `mesero_id` int(11) DEFAULT NULL,
  `propina_entregada` tinyint(1) NOT NULL DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE `rest_validaciones_salida_programador` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `restaurante_id` int(10) UNSIGNED NOT NULL,
  `visita_id` int(10) UNSIGNED NOT NULL,
  `ticket_folio` varchar(80) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `cliente_referencia` varchar(200) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `mesa_referencia` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `pagada_at` datetime DEFAULT NULL,
  `salida_registrada_at` datetime NOT NULL,
  `motivo` varchar(500) COLLATE utf8mb4_unicode_ci NOT NULL,
  `usuario_id` int(10) UNSIGNED DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `rest_visibilidad_financiera` (
  `restaurante_id` int(10) UNSIGNED NOT NULL,
  `activo` tinyint(1) NOT NULL DEFAULT '0',
  `ocultar_hasta` date DEFAULT NULL,
  `actualizado_por` int(10) UNSIGNED DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `rest_visibilidad_financiera_historial` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `restaurante_id` int(10) UNSIGNED NOT NULL,
  `accion` enum('ocultar','restaurar') COLLATE utf8mb4_unicode_ci NOT NULL,
  `ocultar_hasta` date DEFAULT NULL,
  `usuario_id` int(10) UNSIGNED DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `rest_visitas` (
  `id` int(10) UNSIGNED NOT NULL,
  `restaurante_id` int(10) UNSIGNED NOT NULL,
  `tipo` enum('presencial','mobile') DEFAULT 'presencial',
  `mesa_id` int(10) UNSIGNED DEFAULT NULL,
  `comensal_id` int(10) UNSIGNED DEFAULT NULL,
  `qr_code` varchar(128) NOT NULL,
  `estado` enum('activa','pagando','pagada','cancelada') NOT NULL DEFAULT 'activa',
  `notas` text,
  `subtotal` decimal(10,2) NOT NULL DEFAULT '0.00',
  `propina` decimal(10,2) NOT NULL DEFAULT '0.00',
  `total` decimal(10,2) NOT NULL DEFAULT '0.00',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `pagada_at` datetime DEFAULT NULL,
  `salida_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE `rest_zonas` (
  `id` int(10) UNSIGNED NOT NULL,
  `restaurante_id` int(10) UNSIGNED NOT NULL,
  `nombre` varchar(100) NOT NULL,
  `descripcion` varchar(255) DEFAULT NULL,
  `activo` tinyint(1) NOT NULL DEFAULT '1'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE `rest_zonas_delivery` (
  `id` int(10) UNSIGNED NOT NULL,
  `restaurante_id` int(10) UNSIGNED NOT NULL,
  `nombre` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `radio_km` decimal(5,2) NOT NULL DEFAULT '5.00',
  `costo_envio` decimal(8,2) NOT NULL DEFAULT '0.00',
  `activa` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `roles` (
  `id` tinyint(3) UNSIGNED NOT NULL,
  `nombre` varchar(50) NOT NULL,
  `slug` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE `social_account_covers` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `restaurante_id` bigint(20) UNSIGNED NOT NULL,
  `payer_user_id` bigint(20) UNSIGNED NOT NULL,
  `covered_user_id` bigint(20) UNSIGNED NOT NULL,
  `payer_mesa_id` bigint(20) UNSIGNED DEFAULT NULL,
  `covered_mesa_id` bigint(20) UNSIGNED DEFAULT NULL,
  `payer_mesa` varchar(80) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `covered_mesa` varchar(80) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `covered_consumo_id` varchar(80) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `payer_pedido_id` bigint(20) UNSIGNED DEFAULT NULL,
  `payment_mode` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'account',
  `status` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending',
  `amount_mxn` decimal(10,2) NOT NULL DEFAULT '0.00',
  `items_count` int(11) NOT NULL DEFAULT '0',
  `payment_request_key` varchar(80) COLLATE utf8mb4_unicode_ci NOT NULL,
  `stripe_payment_intent_id` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `message` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `paid_at` datetime DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `social_account_notifications` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `user_id` bigint(20) UNSIGNED NOT NULL,
  `actor_user_id` bigint(20) UNSIGNED DEFAULT NULL,
  `type` varchar(60) COLLATE utf8mb4_unicode_ci NOT NULL,
  `title` varchar(120) COLLATE utf8mb4_unicode_ci NOT NULL,
  `body` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `payload_json` text COLLATE utf8mb4_unicode_ci,
  `read_at` datetime DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `social_blocks` (
  `id` int(10) UNSIGNED NOT NULL,
  `blocker_user_id` int(10) UNSIGNED NOT NULL,
  `blocked_user_id` int(10) UNSIGNED NOT NULL,
  `reason` varchar(80) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `social_gift_account_products` (
  `id` int(10) UNSIGNED NOT NULL,
  `restaurante_id` int(10) UNSIGNED NOT NULL,
  `gift_product_id` int(10) UNSIGNED NOT NULL,
  `platillo_id` int(10) UNSIGNED NOT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `social_gift_orders` (
  `id` int(10) UNSIGNED NOT NULL,
  `folio` varchar(40) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `restaurante_id` int(10) UNSIGNED NOT NULL,
  `mesa_id` int(10) UNSIGNED NOT NULL,
  `sender_mesa_id` int(10) UNSIGNED DEFAULT NULL,
  `gift_product_id` int(10) UNSIGNED NOT NULL,
  `sender_user_id` int(10) UNSIGNED NOT NULL,
  `recipient_user_id` int(10) UNSIGNED NOT NULL,
  `pedido_id` int(10) UNSIGNED DEFAULT NULL,
  `pedido_item_id` int(10) UNSIGNED DEFAULT NULL,
  `consumo_id` varchar(40) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `sender_nombre` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL,
  `recipient_nombre` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL,
  `sender_mesa` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `recipient_mesa` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `gift_nombre` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL,
  `gift_descripcion` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `gift_precio` decimal(10,2) NOT NULL DEFAULT '0.00',
  `jungle_wallet_used_mxn` decimal(12,2) DEFAULT NULL,
  `jungle_discount_mxn` decimal(12,2) DEFAULT NULL,
  `jungle_points_redeemed` int(10) UNSIGNED DEFAULT NULL,
  `jungle_points_earned` int(10) UNSIGNED DEFAULT NULL,
  `stripe_payment_intent_id` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `moneda` char(3) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'MXN',
  `payment_request_key` varchar(64) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `pagado_at` datetime DEFAULT NULL,
  `cargado_cuenta_at` datetime DEFAULT NULL,
  `gift_imagen` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status` enum('pendiente_pago','pago_fallido','pendiente_aceptacion','listo','reclamado','entregado','rechazado','cancelado') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pendiente_pago',
  `acceptance_status` enum('pendiente','aceptado','rechazado') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pendiente',
  `accepted_at` datetime DEFAULT NULL,
  `rejected_at` datetime DEFAULT NULL,
  `rejection_resolution` enum('pendiente','saldo_50','entregar_remitente') COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `rejection_resolved_at` datetime DEFAULT NULL,
  `refund_amount_mxn` decimal(10,2) DEFAULT NULL,
  `refund_wallet_applied_at` datetime DEFAULT NULL,
  `reclamado_por` int(10) UNSIGNED DEFAULT NULL,
  `reclamado_at` datetime DEFAULT NULL,
  `entregado_por` int(10) UNSIGNED DEFAULT NULL,
  `entregado_at` datetime DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `social_gift_products` (
  `id` int(11) NOT NULL,
  `nombre` varchar(200) NOT NULL,
  `stock` decimal(10,2) NOT NULL DEFAULT '0.00',
  `descripcion` varchar(500) DEFAULT NULL,
  `precio` decimal(10,2) NOT NULL DEFAULT '0.00',
  `icono` varchar(100) NOT NULL DEFAULT 'gift-outline',
  `color` varchar(20) NOT NULL DEFAULT '#1976D2',
  `activo` tinyint(1) NOT NULL DEFAULT '1',
  `orden` int(11) NOT NULL DEFAULT '0',
  `es_regalo` tinyint(1) NOT NULL DEFAULT '1',
  `categoria` varchar(100) DEFAULT NULL,
  `imagen` varchar(500) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE `social_likes` (
  `id` int(10) UNSIGNED NOT NULL,
  `liker_user_id` int(10) UNSIGNED NOT NULL,
  `liked_user_id` int(10) UNSIGNED NOT NULL,
  `restaurante_id` int(10) UNSIGNED DEFAULT NULL,
  `matched_at` datetime DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `social_photo_moderation` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED NOT NULL,
  `photo_url` varchar(600) COLLATE utf8mb4_unicode_ci NOT NULL,
  `status` enum('pending','approved','rejected') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending',
  `review_notes` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `reviewed_by` bigint(20) UNSIGNED DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `reviewed_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `social_reports` (
  `id` int(10) UNSIGNED NOT NULL,
  `reporter_user_id` int(10) UNSIGNED NOT NULL,
  `reported_user_id` int(10) UNSIGNED NOT NULL,
  `reason` varchar(80) COLLATE utf8mb4_unicode_ci NOT NULL,
  `details` text COLLATE utf8mb4_unicode_ci,
  `status` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'open',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `reviewed_at` datetime DEFAULT NULL,
  `reviewed_by` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `store_categorias` (
  `id` int(11) NOT NULL,
  `nombre` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `descripcion` text COLLATE utf8mb4_unicode_ci,
  `imagen` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `activo` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `store_productos` (
  `id` int(11) NOT NULL,
  `categoria_id` int(11) NOT NULL,
  `nombre` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `descripcion` text COLLATE utf8mb4_unicode_ci,
  `tipo_producto` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'fisico',
  `presentacion` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `precio` decimal(10,2) NOT NULL,
  `imagen` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `stock` int(11) NOT NULL DEFAULT '0',
  `activo` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `stripe_charge_refund_state` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `stripe_charge_id` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `payment_intent_id` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `user_id` int(10) UNSIGNED DEFAULT NULL,
  `refunded_cents` bigint(20) UNSIGNED NOT NULL DEFAULT '0',
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `stripe_payment_incidents` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `incident_type` varchar(80) COLLATE utf8mb4_unicode_ci NOT NULL,
  `stripe_object_id` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `payment_intent_id` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `details_json` text COLLATE utf8mb4_unicode_ci,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `stripe_pending_invoice_requests` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `order_id` int(10) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED NOT NULL,
  `request_json` json NOT NULL,
  `status` varchar(24) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending',
  `invoice_request_id` int(10) UNSIGNED DEFAULT NULL,
  `last_error` text COLLATE utf8mb4_unicode_ci,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT NULL,
  `processed_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `stripe_refund_audit` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `stripe_refund_id` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `payment_intent_id` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `user_id` int(10) UNSIGNED NOT NULL,
  `admin_user_id` int(10) UNSIGNED DEFAULT NULL,
  `request_key` varchar(120) COLLATE utf8mb4_unicode_ci NOT NULL,
  `amount_mxn` decimal(12,2) NOT NULL,
  `reason` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `status` varchar(40) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `stripe_webhook_events` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `stripe_event_id` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `event_type` varchar(120) COLLATE utf8mb4_unicode_ci NOT NULL,
  `object_id` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status` varchar(24) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'processing',
  `attempts` int(10) UNSIGNED NOT NULL DEFAULT '1',
  `last_error` text COLLATE utf8mb4_unicode_ci,
  `received_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `processed_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `sucursales` (
  `id` int(10) UNSIGNED NOT NULL,
  `empresa_id` int(10) UNSIGNED NOT NULL,
  `nombre` varchar(150) NOT NULL,
  `direccion` text NOT NULL,
  `lat` decimal(10,8) DEFAULT NULL,
  `lng` decimal(11,8) DEFAULT NULL,
  `responsable` varchar(100) DEFAULT NULL,
  `telefono` varchar(20) DEFAULT NULL,
  `activo` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `modificadores_config` text
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE `usuarios` (
  `id` int(10) UNSIGNED NOT NULL,
  `nombre` varchar(100) NOT NULL,
  `apellido_paterno` varchar(100) NOT NULL,
  `apellido_materno` varchar(100) DEFAULT NULL,
  `email` varchar(150) NOT NULL,
  `email_verificado` tinyint(1) NOT NULL DEFAULT '0',
  `token_verificacion` varchar(64) DEFAULT NULL,
  `primer_login_completado` tinyint(1) NOT NULL DEFAULT '0',
  `password` varchar(255) NOT NULL,
  `rol_id` tinyint(3) UNSIGNED NOT NULL,
  `empresa_id` int(10) UNSIGNED DEFAULT NULL,
  `restaurante_id` int(10) UNSIGNED DEFAULT NULL,
  `restaurante_activo` tinyint(1) NOT NULL DEFAULT '0',
  `activo` tinyint(1) NOT NULL DEFAULT '1',
  `avatar` varchar(255) DEFAULT NULL,
  `telefono` varchar(20) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `token_expira` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================
-- ÍNDICES, AUTO_INCREMENT Y CLAVES FORÁNEAS
-- ============================================================

ALTER TABLE `action_logs`
  ADD PRIMARY KEY (`id`);

ALTER TABLE `jungle_branch_menu_modifiers`
  ADD PRIMARY KEY (`branch_id`,`platillo_external_id`);

ALTER TABLE `jungle_points_transactions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_jungle_points_tx_user_created` (`usuario_id`,`created_at`);

ALTER TABLE `jungle_wallets`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_jungle_wallet_user` (`user_id`),
  ADD KEY `idx_jungle_wallet_user` (`user_id`);

ALTER TABLE `jungle_wallet_topups`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_jungle_wallet_topups_intent` (`payment_intent_id`),
  ADD KEY `idx_jungle_wallet_topups_user_status` (`usuario_id`,`status`,`created_at`);

ALTER TABLE `jungle_wallet_transactions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_jungle_wallet_tx_user_created` (`usuario_id`,`created_at`),
  ADD KEY `idx_wallet_transactions_external_reference` (`external_reference`);

ALTER TABLE `api_rate_limits`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_api_rate_limits_bucket` (`bucket_key`),
  ADD KEY `idx_api_rate_limits_cleanup` (`updated_at`);

ALTER TABLE `api_tokens`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_api_tokens_token` (`token`),
  ADD KEY `idx_api_tokens_empresa` (`empresa_id`),
  ADD KEY `idx_api_tokens_comprador` (`comprador_id`),
  ADD KEY `idx_api_tokens_activo` (`activo`);

ALTER TABLE `api_access_log`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_api_access_token` (`token_id`),
  ADD KEY `idx_api_access_created` (`created_at`);

ALTER TABLE `app_clientes`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_google_id` (`google_id`),
  ADD UNIQUE KEY `uq_apple_id` (`apple_id`),
  ADD KEY `idx_email` (`email`);

ALTER TABLE `app_modificadores`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_appmod_rest` (`restaurante_id`);

ALTER TABLE `app_opciones_modificador`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_appopmod_mod` (`modificador_id`);

ALTER TABLE `app_platillo_modificadores`
  ADD PRIMARY KEY (`platillo_id`,`modificador_id`),
  ADD KEY `idx_appplatmod_mod` (`modificador_id`);

ALTER TABLE `app_tokens`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_token_hash` (`token_hash`),
  ADD KEY `idx_refresh_hash` (`refresh_hash`),
  ADD KEY `idx_expires` (`expires_at`),
  ADD KEY `fk_app_tokens_cliente` (`cliente_id`);

ALTER TABLE `carnihub_api_config`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_api_restaurante` (`restaurante_id`);

ALTER TABLE `direcciones`
  ADD PRIMARY KEY (`id`);

ALTER TABLE `empresas`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_rfc` (`rfc`);

ALTER TABLE `facturacion_solicitudes`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_facturacion_restaurante_estado` (`restaurante_id`,`estado`),
  ADD KEY `idx_facturacion_pedido` (`pedido_id`),
  ADD KEY `idx_facturacion_division_cuenta` (`division_cuenta_id`),
  ADD KEY `idx_facturacion_mobile_usuario` (`mobile_usuario_id`),
  ADD KEY `idx_facturacion_created_at` (`created_at`),
  ADD KEY `idx_facturacion_facturapi_invoice` (`facturapi_invoice_id`);

ALTER TABLE `favoritos`
  ADD PRIMARY KEY (`id`);

ALTER TABLE `global_settings`
  ADD PRIMARY KEY (`clave`);

ALTER TABLE `login_intentos`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_ip` (`ip`),
  ADD KEY `idx_email` (`email`),
  ADD KEY `idx_created_at` (`created_at`);

ALTER TABLE `mobile_datos_fiscales`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uniq_mobile_datos_fiscales_usuario` (`usuario_id`),
  ADD KEY `idx_mobile_datos_fiscales_usuario` (`usuario_id`);

ALTER TABLE `mobile_direcciones`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_usuario` (`usuario_id`);

ALTER TABLE `mobile_favoritos`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_fav` (`usuario_id`,`platillo_id`),
  ADD KEY `idx_usuario` (`usuario_id`),
  ADD KEY `idx_platillo` (`platillo_id`);

ALTER TABLE `mobile_notification_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_mobile_notification_promotion` (`promotion_id`),
  ADD KEY `idx_mobile_notification_usuario` (`usuario_id`),
  ADD KEY `idx_mobile_notification_status` (`status`,`created_at`);

ALTER TABLE `mobile_promociones`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_usuario` (`usuario_id`),
  ADD KEY `idx_activo` (`activo`),
  ADD KEY `idx_activo_expires` (`activo`,`expires_at`),
  ADD KEY `idx_platillo_id` (`platillo_id`),
  ADD KEY `idx_producto_id` (`producto_id`);

ALTER TABLE `mobile_promocion_usos`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_mobile_promo_usage_user_promotion` (`usuario_id`,`promocion_id`),
  ADD UNIQUE KEY `uq_mobile_promo_usage_order_promotion` (`pedido_id`,`promocion_id`),
  ADD KEY `idx_mobile_promo_usage_user_date` (`usuario_id`,`usado_at`),
  ADD KEY `idx_mobile_promo_usage_promotion` (`promocion_id`);

ALTER TABLE `mobile_push_tokens`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uniq_mobile_push_token` (`fcm_token`),
  ADD UNIQUE KEY `uniq_mobile_push_token_fcm` (`fcm_token`),
  ADD UNIQUE KEY `uniq_mobile_push_device` (`device_id`),
  ADD KEY `idx_mobile_push_tokens_usuario` (`usuario_id`,`enabled`),
  ADD KEY `idx_mobile_push_tokens_device` (`device_id`);

ALTER TABLE `mobile_sesiones`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_token_hash` (`token_hash`),
  ADD KEY `idx_usuario` (`usuario_id`);

ALTER TABLE `mobile_usuarios`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_email` (`email`),
  ADD UNIQUE KEY `uq_google_id` (`google_id`),
  ADD UNIQUE KEY `uq_mobile_usuarios_telefono` (`telefono`),
  ADD UNIQUE KEY `uq_mobile_usuarios_apple_id` (`apple_id`),
  ADD KEY `idx_mu_social` (`current_restaurante_id`,`is_social_active`),
  ADD KEY `idx_mobile_usuarios_fecha_nacimiento` (`fecha_nacimiento`);

ALTER TABLE `moderation_actions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_moderation_actions_target` (`target_type`,`target_id`,`created_at`),
  ADD KEY `idx_moderation_actions_user` (`user_id`,`created_at`),
  ADD KEY `idx_moderation_actions_photo` (`photo_id`,`created_at`),
  ADD KEY `idx_moderation_actions_moderator` (`moderator_id`,`created_at`);

ALTER TABLE `rest_alertas`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_restaurante` (`restaurante_id`),
  ADD KEY `idx_atendida` (`restaurante_id`,`atendida`),
  ADD KEY `idx_visita` (`visita_id`);

ALTER TABLE `rest_categorias_menu`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_rcat_rest` (`restaurante_id`);

ALTER TABLE `rest_comensales`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_rcom_rest` (`restaurante_id`);

ALTER TABLE `rest_configuracion`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `restaurante_id` (`restaurante_id`);

ALTER TABLE `rest_cortes`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_rcorte_rest` (`restaurante_id`),
  ADD KEY `fk_rcorte_usr` (`usuario_id`);

ALTER TABLE `rest_cuenta_divisiones`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_cuenta_division_mesa_estado` (`restaurante_id`,`mesa_id`,`estado`);

ALTER TABLE `rest_cuenta_division_cuentas`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_cuenta_division_numero` (`division_id`,`numero`),
  ADD KEY `idx_cuenta_division_estado` (`division_id`,`estado`);

ALTER TABLE `rest_cuenta_division_items`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_cuenta_item` (`cuenta_id`,`pedido_item_id`),
  ADD KEY `idx_division_item_origen` (`pedido_item_id`);

ALTER TABLE `rest_gastos`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_rgasto_rest` (`restaurante_id`),
  ADD KEY `idx_rgasto_fecha` (`fecha`),
  ADD KEY `fk_rgasto_usr` (`usuario_id`);

ALTER TABLE `rest_ingredientes`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uk_ring_rest_codigo` (`restaurante_id`,`codigo`),
  ADD KEY `idx_ring_carnihub_producto` (`carnihub_producto_id`);

ALTER TABLE `rest_liberaciones_pedido_programador`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_rlpp_rest_fecha` (`restaurante_id`,`created_at`),
  ADD KEY `idx_rlpp_pedido` (`pedido_id`),
  ADD KEY `idx_rlpp_usuario` (`usuario_id`);

ALTER TABLE `rest_mesas`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_mesa_qr` (`qr_codigo`),
  ADD KEY `fk_rmesa_rest` (`restaurante_id`),
  ADD KEY `fk_rmesa_zona` (`zona_id`),
  ADD KEY `idx_rest_mesas_mesero` (`mesero_usuario_id`);

ALTER TABLE `rest_mesero_turno`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_turno_rest_fecha` (`restaurante_id`,`turno_fecha`),
  ADD KEY `idx_turno_user` (`usuario_id`),
  ADD KEY `fk_mturno_zona` (`zona_id`);

ALTER TABLE `rest_modificadores`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_rm_restaurante` (`restaurante_id`),
  ADD KEY `idx_rm_ingrediente` (`ingrediente_id`),
  ADD KEY `idx_rm_catalogo` (`restaurante_id`,`alcance`,`tipo`,`activo`);

ALTER TABLE `rest_movimientos_inventario`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_rmov_rest` (`restaurante_id`),
  ADD KEY `idx_rmov_ing` (`ingrediente_id`);

ALTER TABLE `rest_pasos_preparacion`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_rpp_platillo` (`platillo_id`),
  ADD KEY `fk_rpp_restaurante` (`restaurante_id`);

ALTER TABLE `rest_pedidos`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `idx_rest_pedidos_salida_token` (`salida_token`),
  ADD UNIQUE KEY `uniq_rest_pedidos_app_order` (`restaurante_id`,`app_order_id`),
  ADD KEY `idx_rped_rest` (`restaurante_id`),
  ADD KEY `idx_rped_mesa` (`mesa_id`),
  ADD KEY `idx_rped_vis` (`visita_id`),
  ADD KEY `idx_rped_est` (`estado`),
  ADD KEY `fk_rped_mes` (`mesero_id`),
  ADD KEY `idx_rped_reclamado` (`reclamado_por`),
  ADD KEY `idx_app_cliente` (`app_cliente_id`),
  ADD KEY `idx_tipo_origen` (`tipo_origen`),
  ADD KEY `idx_rest_pedidos_consumo` (`consumo_id`),
  ADD KEY `idx_rest_pedidos_cuenta_abierta` (`restaurante_id`,`mesa_id`,`mobile_usuario_id`,`tipo_pedido`,`cuenta_abierta`),
  ADD KEY `idx_rest_pedidos_mesero` (`mesero_usuario_id`,`pedido_origen`),
  ADD KEY `idx_rest_pedidos_cierre_mesero` (`cerrado_por_mesero_usuario_id`,`cerrado_at`),
  ADD KEY `idx_rest_pedidos_mobile` (`restaurante_id`,`mobile_usuario_id`,`created_at`),
  ADD KEY `idx_rest_pedidos_tipo_app` (`restaurante_id`,`tipo_origen`,`tipo_pedido`,`estado`),
  ADD KEY `idx_rest_pedidos_stripe_status` (`stripe_payment_status`);

ALTER TABLE `rest_pedidos_sugeridos`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_rps_restaurante` (`restaurante_id`),
  ADD KEY `idx_rps_estado` (`estado`),
  ADD KEY `fk_rps_usuario` (`usuario_id`);

ALTER TABLE `rest_pedido_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_ritem_ped` (`pedido_id`),
  ADD KEY `fk_ritem_plat` (`platillo_id`),
  ADD KEY `idx_origen` (`origen`);

ALTER TABLE `rest_pedido_item_modificadores`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_rpim_item` (`pedido_item_id`),
  ADD KEY `fk_rpim_modificador` (`modificador_id`);

ALTER TABLE `rest_pedido_sugerido_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_rpsi_pedido` (`pedido_sugerido_id`),
  ADD KEY `idx_rpsi_ingrediente` (`ingrediente_id`);

ALTER TABLE `rest_platillos`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_rplat_rest` (`restaurante_id`),
  ADD KEY `fk_rplat_cat` (`categoria_id`),
  ADD KEY `idx_rplat_ing_directo` (`ingrediente_directo_id`);

ALTER TABLE `rest_platillo_armado`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_rpa_restaurante` (`restaurante_id`),
  ADD KEY `idx_rpa_platillo` (`platillo_id`),
  ADD KEY `fk_rpa_ingrediente` (`referencia_id`);

ALTER TABLE `rest_platillo_modificador`
  ADD PRIMARY KEY (`platillo_id`,`modificador_id`),
  ADD KEY `fk_rpm_modificador` (`modificador_id`);

ALTER TABLE `rest_platillo_modificadores`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_rpm_platillo` (`restaurante_id`,`platillo_id`,`activo`),
  ADD KEY `idx_rpm_ingrediente` (`ingrediente_id`);

ALTER TABLE `rest_promociones`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_rest_prom_code` (`code`),
  ADD KEY `idx_rest_prom_rest` (`restaurante_id`),
  ADD KEY `idx_rest_prom_activo` (`restaurante_id`,`activo`,`fecha_inicio`,`fecha_fin`),
  ADD KEY `idx_rest_prom_code` (`code`),
  ADD KEY `fk_rest_prom_usuario` (`usuario_id`);

ALTER TABLE `rest_promocion_comensales`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_prom_comensal` (`promocion_id`,`comensal_id`),
  ADD KEY `idx_prom_com_com` (`comensal_id`);

ALTER TABLE `rest_promocion_envios`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uniq_envio_periodo` (`restaurante_id`,`mobile_usuario_id`,`motivo`,`periodo`),
  ADD KEY `idx_envio_mobile` (`mobile_usuario_id`),
  ADD KEY `idx_envio_restaurante` (`restaurante_id`);

ALTER TABLE `rest_recetas`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_receta_platillo` (`platillo_id`);

ALTER TABLE `rest_receta_ingredientes`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_rri_rec` (`receta_id`),
  ADD KEY `fk_rri_ing` (`ingrediente_id`);

ALTER TABLE `rest_regularizaciones_adeudo`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_rra_rest_fecha` (`restaurante_id`,`created_at`),
  ADD KEY `idx_rra_registro` (`tipo_registro`,`registro_id`),
  ADD KEY `idx_rra_usuario` (`usuario_id`);

ALTER TABLE `rest_reservaciones`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_rres_rest` (`restaurante_id`),
  ADD KEY `idx_rres_fecha` (`fecha`),
  ADD KEY `fk_rres_mesa` (`mesa_id`),
  ADD KEY `fk_rres_com` (`comensal_id`),
  ADD KEY `idx_recordatorio` (`fecha`,`estado`,`recordatorio_enviado`),
  ADD KEY `idx_reservaciones_mesa_fecha_hora` (`restaurante_id`,`mesa_id`,`fecha`,`hora`,`estado`),
  ADD KEY `idx_reservaciones_rest_fecha_hora` (`restaurante_id`,`fecha`,`hora`),
  ADD KEY `idx_rest_reservaciones_canal` (`restaurante_id`,`canal_reserva`);

ALTER TABLE `rest_restaurantes`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_slug` (`slug`),
  ADD KEY `fk_rrest_empresa` (`empresa_id`),
  ADD KEY `fk_rrest_comprador` (`comprador_id`),
  ADD KEY `idx_rest_restaurantes_sucursal` (`sucursal_id`),
  ADD KEY `idx_rest_menu_principal` (`empresa_id`,`menu_principal`);

ALTER TABLE `rest_retiros`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_rret_rest` (`restaurante_id`),
  ADD KEY `fk_rret_usr` (`usuario_id`);

ALTER TABLE `rest_staff`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_staff_codigo` (`restaurante_id`,`codigo`),
  ADD KEY `fk_rstaff_usr` (`usuario_id`),
  ADD KEY `idx_rest_staff_mesero_lookup` (`usuario_id`,`restaurante_id`,`rol_slug`,`activo`);

ALTER TABLE `rest_staff_restaurantes`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uniq_rsr_usuario_restaurante_rol` (`usuario_id`,`restaurante_id`,`rol_operativo`),
  ADD KEY `idx_rsr_usuario_activo` (`usuario_id`,`activo`),
  ADD KEY `idx_rsr_restaurante_activo` (`restaurante_id`,`activo`);

ALTER TABLE `rest_tickets`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_rtick_rest` (`restaurante_id`),
  ADD KEY `idx_rtick_vis` (`visita_id`),
  ADD KEY `fk_rtick_mesa` (`mesa_id`),
  ADD KEY `idx_tickets_mesero` (`mesero_id`),
  ADD KEY `idx_tickets_propina_entregada` (`propina_entregada`);

ALTER TABLE `rest_validaciones_salida_programador`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_rvsp_rest_fecha` (`restaurante_id`,`created_at`),
  ADD KEY `idx_rvsp_visita` (`visita_id`),
  ADD KEY `idx_rvsp_usuario` (`usuario_id`);

ALTER TABLE `rest_visibilidad_financiera`
  ADD PRIMARY KEY (`restaurante_id`),
  ADD KEY `idx_rvf_activo_fecha` (`activo`,`ocultar_hasta`),
  ADD KEY `idx_rvf_usuario` (`actualizado_por`);

ALTER TABLE `rest_visibilidad_financiera_historial`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_rvfh_rest_fecha` (`restaurante_id`,`created_at`),
  ADD KEY `idx_rvfh_usuario` (`usuario_id`);

ALTER TABLE `rest_visitas`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_visita_qr` (`qr_code`),
  ADD KEY `idx_visita_rest` (`restaurante_id`),
  ADD KEY `fk_rvis_mesa` (`mesa_id`),
  ADD KEY `fk_rvis_com` (`comensal_id`);

ALTER TABLE `rest_zonas`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_rzona_rest` (`restaurante_id`);

ALTER TABLE `rest_zonas_delivery`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_rest_activa` (`restaurante_id`,`activa`);

ALTER TABLE `roles`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_slug` (`slug`);

ALTER TABLE `social_account_covers`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_social_account_cover_request` (`payer_user_id`,`payment_request_key`),
  ADD KEY `idx_social_account_cover_covered` (`covered_user_id`,`status`),
  ADD KEY `idx_social_account_cover_restaurant` (`restaurante_id`,`created_at`),
  ADD KEY `idx_social_account_cover_consumo_status` (`restaurante_id`,`covered_user_id`,`covered_consumo_id`,`status`);

ALTER TABLE `social_account_notifications`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_social_account_notifications_user` (`user_id`,`read_at`,`created_at`);

ALTER TABLE `social_blocks`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_social_blocks_pair` (`blocker_user_id`,`blocked_user_id`),
  ADD KEY `idx_social_blocks_blocker` (`blocker_user_id`,`created_at`),
  ADD KEY `idx_social_blocks_blocked` (`blocked_user_id`,`created_at`);

ALTER TABLE `social_gift_account_products`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_social_gift_account_product` (`restaurante_id`,`gift_product_id`),
  ADD UNIQUE KEY `uq_social_gift_account_dish` (`platillo_id`);

ALTER TABLE `social_gift_orders`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_sgo_payment_request` (`sender_user_id`,`payment_request_key`),
  ADD UNIQUE KEY `uq_sgo_payment_intent` (`stripe_payment_intent_id`),
  ADD KEY `idx_sgo_rest_status` (`restaurante_id`,`status`),
  ADD KEY `idx_sgo_mesa_status` (`mesa_id`,`status`),
  ADD KEY `idx_sgo_sender` (`sender_user_id`),
  ADD KEY `idx_sgo_recipient` (`recipient_user_id`),
  ADD KEY `idx_sgo_pedido` (`pedido_id`),
  ADD KEY `idx_sgo_consumo` (`consumo_id`),
  ADD KEY `idx_sgo_sender_account` (`sender_mesa_id`,`pedido_id`);

ALTER TABLE `social_gift_products`
  ADD PRIMARY KEY (`id`);

ALTER TABLE `social_likes`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uniq_social_likes_pair` (`liker_user_id`,`liked_user_id`),
  ADD KEY `idx_social_likes_liker` (`liker_user_id`,`matched_at`),
  ADD KEY `idx_social_likes_liked` (`liked_user_id`,`matched_at`),
  ADD KEY `idx_social_likes_restaurante` (`restaurante_id`);

ALTER TABLE `social_photo_moderation`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_social_photo_url` (`photo_url`),
  ADD KEY `idx_social_photo_queue` (`status`,`created_at`),
  ADD KEY `idx_social_photo_user` (`user_id`,`status`);

ALTER TABLE `social_reports`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_social_reports_status` (`status`,`created_at`),
  ADD KEY `idx_social_reports_reported` (`reported_user_id`,`created_at`),
  ADD KEY `idx_social_reports_reporter` (`reporter_user_id`,`created_at`);

ALTER TABLE `store_categorias`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_activo` (`activo`);

ALTER TABLE `store_productos`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_categoria` (`categoria_id`),
  ADD KEY `idx_activo` (`activo`),
  ADD KEY `idx_nombre` (`nombre`),
  ADD KEY `idx_tipo_producto` (`tipo_producto`);

ALTER TABLE `stripe_charge_refund_state`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_stripe_refund_charge` (`stripe_charge_id`),
  ADD KEY `idx_stripe_refund_user` (`user_id`,`updated_at`);

ALTER TABLE `stripe_payment_incidents`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_stripe_payment_incident` (`incident_type`,`stripe_object_id`),
  ADD KEY `idx_stripe_payment_incident_intent` (`payment_intent_id`),
  ADD KEY `idx_stripe_payment_incident_created` (`created_at`);

ALTER TABLE `stripe_pending_invoice_requests`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_stripe_pending_invoice_order` (`order_id`),
  ADD KEY `idx_stripe_pending_invoice_status` (`status`,`updated_at`);

ALTER TABLE `stripe_refund_audit`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_stripe_refund_audit_refund` (`stripe_refund_id`),
  ADD KEY `idx_stripe_refund_audit_user` (`user_id`,`created_at`),
  ADD KEY `idx_stripe_refund_audit_request` (`request_key`);

ALTER TABLE `stripe_webhook_events`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_stripe_webhook_event` (`stripe_event_id`),
  ADD KEY `idx_stripe_webhook_status` (`status`,`updated_at`),
  ADD KEY `idx_stripe_webhook_object` (`object_id`);

ALTER TABLE `sucursales`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_sucursal_empresa` (`empresa_id`);

ALTER TABLE `usuarios`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_email` (`email`),
  ADD KEY `idx_usuario_restaurante` (`restaurante_id`),
  ADD KEY `fk_usuario_rol` (`rol_id`),
  ADD KEY `fk_usuario_empresa` (`empresa_id`);

ALTER TABLE `action_logs`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

ALTER TABLE `jungle_points_transactions`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

ALTER TABLE `jungle_wallets`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

ALTER TABLE `jungle_wallet_topups`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

ALTER TABLE `jungle_wallet_transactions`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

ALTER TABLE `api_rate_limits`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

ALTER TABLE `api_tokens`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

ALTER TABLE `api_access_log`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

ALTER TABLE `app_clientes`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

ALTER TABLE `app_modificadores`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

ALTER TABLE `app_opciones_modificador`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

ALTER TABLE `app_tokens`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

ALTER TABLE `carnihub_api_config`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

ALTER TABLE `direcciones`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

ALTER TABLE `empresas`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

ALTER TABLE `facturacion_solicitudes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

ALTER TABLE `favoritos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

ALTER TABLE `login_intentos`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

ALTER TABLE `mobile_datos_fiscales`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

ALTER TABLE `mobile_direcciones`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

ALTER TABLE `mobile_favoritos`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

ALTER TABLE `mobile_notification_logs`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

ALTER TABLE `mobile_promociones`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

ALTER TABLE `mobile_promocion_usos`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

ALTER TABLE `mobile_push_tokens`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

ALTER TABLE `mobile_sesiones`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

ALTER TABLE `mobile_usuarios`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

ALTER TABLE `moderation_actions`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

ALTER TABLE `rest_alertas`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

ALTER TABLE `rest_categorias_menu`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

ALTER TABLE `rest_comensales`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

ALTER TABLE `rest_configuracion`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

ALTER TABLE `rest_cortes`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

ALTER TABLE `rest_cuenta_divisiones`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

ALTER TABLE `rest_cuenta_division_cuentas`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

ALTER TABLE `rest_cuenta_division_items`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

ALTER TABLE `rest_gastos`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

ALTER TABLE `rest_ingredientes`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

ALTER TABLE `rest_liberaciones_pedido_programador`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

ALTER TABLE `rest_mesas`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

ALTER TABLE `rest_mesero_turno`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

ALTER TABLE `rest_modificadores`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

ALTER TABLE `rest_movimientos_inventario`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

ALTER TABLE `rest_pasos_preparacion`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

ALTER TABLE `rest_pedidos`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

ALTER TABLE `rest_pedidos_sugeridos`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

ALTER TABLE `rest_pedido_items`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

ALTER TABLE `rest_pedido_item_modificadores`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

ALTER TABLE `rest_pedido_sugerido_items`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

ALTER TABLE `rest_platillos`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

ALTER TABLE `rest_platillo_armado`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

ALTER TABLE `rest_platillo_modificadores`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

ALTER TABLE `rest_promociones`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

ALTER TABLE `rest_promocion_comensales`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

ALTER TABLE `rest_promocion_envios`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

ALTER TABLE `rest_recetas`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

ALTER TABLE `rest_receta_ingredientes`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

ALTER TABLE `rest_regularizaciones_adeudo`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

ALTER TABLE `rest_reservaciones`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

ALTER TABLE `rest_restaurantes`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

ALTER TABLE `rest_retiros`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

ALTER TABLE `rest_staff`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

ALTER TABLE `rest_staff_restaurantes`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

ALTER TABLE `rest_tickets`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

ALTER TABLE `rest_validaciones_salida_programador`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

ALTER TABLE `rest_visibilidad_financiera_historial`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

ALTER TABLE `rest_visitas`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

ALTER TABLE `rest_zonas`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

ALTER TABLE `rest_zonas_delivery`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

ALTER TABLE `social_account_covers`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

ALTER TABLE `social_account_notifications`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

ALTER TABLE `social_blocks`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

ALTER TABLE `social_gift_account_products`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

ALTER TABLE `social_gift_orders`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

ALTER TABLE `social_gift_products`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

ALTER TABLE `social_likes`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

ALTER TABLE `social_photo_moderation`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

ALTER TABLE `social_reports`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

ALTER TABLE `store_categorias`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

ALTER TABLE `store_productos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

ALTER TABLE `stripe_charge_refund_state`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

ALTER TABLE `stripe_payment_incidents`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

ALTER TABLE `stripe_pending_invoice_requests`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

ALTER TABLE `stripe_refund_audit`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

ALTER TABLE `stripe_webhook_events`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

ALTER TABLE `sucursales`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

ALTER TABLE `usuarios`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

ALTER TABLE `jungle_points_transactions`
  ADD CONSTRAINT `fk_jungle_points_tx_user` FOREIGN KEY (`usuario_id`) REFERENCES `mobile_usuarios` (`id`) ON DELETE CASCADE;

ALTER TABLE `jungle_wallet_topups`
  ADD CONSTRAINT `fk_jungle_wallet_topups_user` FOREIGN KEY (`usuario_id`) REFERENCES `mobile_usuarios` (`id`) ON DELETE CASCADE;

ALTER TABLE `jungle_wallet_transactions`
  ADD CONSTRAINT `fk_jungle_wallet_tx_user` FOREIGN KEY (`usuario_id`) REFERENCES `mobile_usuarios` (`id`) ON DELETE CASCADE;

ALTER TABLE `api_tokens`
  ADD CONSTRAINT `fk_api_tokens_empresa` FOREIGN KEY (`empresa_id`) REFERENCES `empresas` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_api_tokens_comprador` FOREIGN KEY (`comprador_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE;

ALTER TABLE `api_access_log`
  ADD CONSTRAINT `fk_api_access_token` FOREIGN KEY (`token_id`) REFERENCES `api_tokens` (`id`) ON DELETE CASCADE;

ALTER TABLE `rest_modificadores`
  ADD CONSTRAINT `fk_rm_ingrediente` FOREIGN KEY (`ingrediente_id`) REFERENCES `rest_ingredientes` (`id`);

ALTER TABLE `rest_liberaciones_pedido_programador`
  ADD CONSTRAINT `fk_rlpp_restaurante` FOREIGN KEY (`restaurante_id`) REFERENCES `rest_restaurantes` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_rlpp_pedido` FOREIGN KEY (`pedido_id`) REFERENCES `rest_pedidos` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_rlpp_usuario` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE SET NULL;

ALTER TABLE `rest_regularizaciones_adeudo`
  ADD CONSTRAINT `fk_rra_restaurante` FOREIGN KEY (`restaurante_id`) REFERENCES `rest_restaurantes` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_rra_usuario` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE SET NULL;

ALTER TABLE `rest_validaciones_salida_programador`
  ADD CONSTRAINT `fk_rvsp_restaurante` FOREIGN KEY (`restaurante_id`) REFERENCES `rest_restaurantes` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_rvsp_usuario` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_rvsp_visita` FOREIGN KEY (`visita_id`) REFERENCES `rest_visitas` (`id`) ON DELETE CASCADE;

ALTER TABLE `rest_visibilidad_financiera`
  ADD CONSTRAINT `fk_rvf_restaurante` FOREIGN KEY (`restaurante_id`) REFERENCES `rest_restaurantes` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_rvf_usuario` FOREIGN KEY (`actualizado_por`) REFERENCES `usuarios` (`id`) ON DELETE SET NULL;

ALTER TABLE `rest_visibilidad_financiera_historial`
  ADD CONSTRAINT `fk_rvfh_restaurante` FOREIGN KEY (`restaurante_id`) REFERENCES `rest_restaurantes` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_rvfh_usuario` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE SET NULL;

ALTER TABLE `social_blocks`
  ADD CONSTRAINT `fk_social_blocks_blocked` FOREIGN KEY (`blocked_user_id`) REFERENCES `mobile_usuarios` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_social_blocks_blocker` FOREIGN KEY (`blocker_user_id`) REFERENCES `mobile_usuarios` (`id`) ON DELETE CASCADE;

ALTER TABLE `social_photo_moderation`
  ADD CONSTRAINT `fk_social_photo_user` FOREIGN KEY (`user_id`) REFERENCES `mobile_usuarios` (`id`) ON DELETE CASCADE;

ALTER TABLE `social_reports`
  ADD CONSTRAINT `fk_social_reports_reported` FOREIGN KEY (`reported_user_id`) REFERENCES `mobile_usuarios` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_social_reports_reporter` FOREIGN KEY (`reporter_user_id`) REFERENCES `mobile_usuarios` (`id`) ON DELETE CASCADE;

-- ============================================================
-- TRIGGERS
-- ============================================================

DELIMITER $$
CREATE TRIGGER `trg_config_version_modifier_delete` AFTER DELETE ON `rest_platillo_modificadores` FOR EACH ROW BEGIN
  UPDATE rest_configuracion SET config_version=config_version+1, updated_at=NOW() WHERE restaurante_id=OLD.restaurante_id;
END
$$
DELIMITER ;

DELIMITER $$
CREATE TRIGGER `trg_config_version_modifier_insert` AFTER INSERT ON `rest_platillo_modificadores` FOR EACH ROW BEGIN
  UPDATE rest_configuracion SET config_version=config_version+1, updated_at=NOW() WHERE restaurante_id=NEW.restaurante_id;
END
$$
DELIMITER ;

DELIMITER $$
CREATE TRIGGER `trg_config_version_modifier_update` AFTER UPDATE ON `rest_platillo_modificadores` FOR EACH ROW BEGIN
  UPDATE rest_configuracion SET config_version=config_version+1, updated_at=NOW() WHERE restaurante_id=NEW.restaurante_id;
END
$$
DELIMITER ;

DELIMITER $$
CREATE TRIGGER `trg_config_version_recipe_delete` AFTER DELETE ON `rest_recetas` FOR EACH ROW BEGIN
  UPDATE rest_configuracion c JOIN rest_platillos p ON p.restaurante_id=c.restaurante_id
  SET c.config_version=c.config_version+1, c.updated_at=NOW() WHERE p.id=OLD.platillo_id;
END
$$
DELIMITER ;

DELIMITER $$
CREATE TRIGGER `trg_config_version_recipe_insert` AFTER INSERT ON `rest_recetas` FOR EACH ROW BEGIN
  UPDATE rest_configuracion c JOIN rest_platillos p ON p.restaurante_id=c.restaurante_id
  SET c.config_version=c.config_version+1, c.updated_at=NOW() WHERE p.id=NEW.platillo_id;
END
$$
DELIMITER ;

DELIMITER $$
CREATE TRIGGER `trg_config_version_recipe_update` AFTER UPDATE ON `rest_recetas` FOR EACH ROW BEGIN
  UPDATE rest_configuracion c JOIN rest_platillos p ON p.restaurante_id=c.restaurante_id
  SET c.config_version=c.config_version+1, c.updated_at=NOW() WHERE p.id=NEW.platillo_id;
END
$$
DELIMITER ;

DELIMITER $$
CREATE TRIGGER `trg_config_version_recipe_item_delete` AFTER DELETE ON `rest_receta_ingredientes` FOR EACH ROW BEGIN
  UPDATE rest_configuracion c
  JOIN rest_platillos p ON p.restaurante_id=c.restaurante_id
  JOIN rest_recetas r ON r.platillo_id=p.id
  SET c.config_version=c.config_version+1, c.updated_at=NOW() WHERE r.id=OLD.receta_id;
END
$$
DELIMITER ;

DELIMITER $$
CREATE TRIGGER `trg_config_version_recipe_item_insert` AFTER INSERT ON `rest_receta_ingredientes` FOR EACH ROW BEGIN
  UPDATE rest_configuracion c
  JOIN rest_platillos p ON p.restaurante_id=c.restaurante_id
  JOIN rest_recetas r ON r.platillo_id=p.id
  SET c.config_version=c.config_version+1, c.updated_at=NOW() WHERE r.id=NEW.receta_id;
END
$$
DELIMITER ;

DELIMITER $$
CREATE TRIGGER `trg_config_version_recipe_item_update` AFTER UPDATE ON `rest_receta_ingredientes` FOR EACH ROW BEGIN
  UPDATE rest_configuracion c
  JOIN rest_platillos p ON p.restaurante_id=c.restaurante_id
  JOIN rest_recetas r ON r.platillo_id=p.id
  SET c.config_version=c.config_version+1, c.updated_at=NOW() WHERE r.id=NEW.receta_id;
END
$$
DELIMITER ;

SET FOREIGN_KEY_CHECKS = 1;
