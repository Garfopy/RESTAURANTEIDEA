-- ============================================================
-- 042 — Ingrediente directo para platillos sin receta
-- Dependencias: rest_platillos, rest_ingredientes
-- ============================================================
-- Permite vincular un platillo (bebida, dulce, postre, etc.)
-- directamente a un ingrediente del inventario para descontar
-- stock al marcar el ítem como "en preparación" en el KDS,
-- sin necesidad de definir una receta completa.
-- ============================================================

ALTER TABLE `rest_platillos`
  ADD COLUMN `ingrediente_directo_id` INT UNSIGNED NULL DEFAULT NULL
    COMMENT 'Ingrediente a descontar directamente del inventario (para bebidas/postres sin receta)'
    AFTER `activo`,
  ADD KEY `idx_rplat_ing_directo` (`ingrediente_directo_id`),
  ADD CONSTRAINT `fk_rplat_ing_directo`
    FOREIGN KEY (`ingrediente_directo_id`) REFERENCES `rest_ingredientes` (`id`)
    ON DELETE SET NULL;
