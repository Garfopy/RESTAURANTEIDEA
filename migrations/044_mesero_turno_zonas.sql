-- ============================================================
-- 044 — Mesero turno-zonas + estado reclamado en pedidos
-- Dependencias: rest_restaurantes, rest_zonas, rest_pedidos, usuarios
-- ============================================================
-- Implementa el modelo "pool con reclamación por zona":
--   1. rest_mesero_turno  → asigna zonas a meseros por turno/día
--   2. ALTER rest_pedidos → agrega estado 'reclamado' (entre listo y entregado)
--      + columnas reclamado_at, reclamado_por
-- ============================================================

SET FOREIGN_KEY_CHECKS = 0;

-- ── 1. Tabla de turnos: mesero ↔ zona por día ─────────────────
CREATE TABLE IF NOT EXISTS `rest_mesero_turno` (
  `id`             INT UNSIGNED  NOT NULL AUTO_INCREMENT,
  `restaurante_id` INT UNSIGNED  NOT NULL,
  `usuario_id`     INT UNSIGNED  NOT NULL,
  `zona_id`        INT UNSIGNED  NOT NULL,
  `turno_fecha`    DATE          NOT NULL,
  `activo`         TINYINT(1)    NOT NULL DEFAULT 1,
  `created_at`     TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_turno_rest_fecha` (`restaurante_id`, `turno_fecha`),
  KEY `idx_turno_user`       (`usuario_id`),
  CONSTRAINT `fk_mturno_rest` FOREIGN KEY (`restaurante_id`) REFERENCES `rest_restaurantes`(`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_mturno_user` FOREIGN KEY (`usuario_id`)     REFERENCES `usuarios`(`id`)          ON DELETE CASCADE,
  CONSTRAINT `fk_mturno_zona` FOREIGN KEY (`zona_id`)        REFERENCES `rest_zonas`(`id`)        ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── 2. Agregar estado 'reclamado' entre 'listo' y 'entregado' ──
ALTER TABLE `rest_pedidos`
  MODIFY COLUMN `estado`
    ENUM('pendiente','en_preparacion','listo','reclamado','entregado','cancelado')
    NOT NULL DEFAULT 'pendiente';

-- ── 3. Columnas de reclamación en rest_pedidos ─────────────────
ALTER TABLE `rest_pedidos`
  ADD COLUMN IF NOT EXISTS `reclamado_at`  TIMESTAMP    NULL DEFAULT NULL AFTER `mesero_id`,
  ADD COLUMN IF NOT EXISTS `reclamado_por` INT UNSIGNED NULL DEFAULT NULL AFTER `reclamado_at`;

ALTER TABLE `rest_pedidos`
  ADD CONSTRAINT `fk_rpedido_reclamado_por`
    FOREIGN KEY (`reclamado_por`) REFERENCES `usuarios`(`id`) ON DELETE SET NULL;

-- ── 4. Igual para rest_pedido_items (ya incluía 'listo') ────────
--  Solo extender si el ENUM actual no tiene 'reclamado'
ALTER TABLE `rest_pedido_items`
  MODIFY COLUMN `estado`
    ENUM('pendiente','en_preparacion','listo','reclamado','entregado','cancelado')
    NOT NULL DEFAULT 'pendiente';

SET FOREIGN_KEY_CHECKS = 1;
