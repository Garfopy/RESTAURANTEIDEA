-- ============================================================
-- Migración 020 — Favoritos del Comprador
-- Permite a un comprador marcar productos como favoritos para
-- acceso rápido desde una sección dedicada del portal.
-- ============================================================

CREATE TABLE IF NOT EXISTS `favoritos_comprador` (
  `id`           INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `usuario_id`   INT UNSIGNED NOT NULL,
  `producto_id`  INT UNSIGNED NOT NULL,
  `created_at`   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_usuario_producto` (`usuario_id`, `producto_id`),
  KEY `idx_usuario` (`usuario_id`),
  KEY `idx_producto` (`producto_id`),
  CONSTRAINT `fk_fav_usuario`
    FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_fav_producto`
    FOREIGN KEY (`producto_id`) REFERENCES `productos` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
