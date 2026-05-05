-- Migration 008: Flujo de pedido completo — entrega, costo envío, fotos, dirección comprador
-- Ejecutar en phpMyAdmin (cPanel) DESPUÉS de 006 y 007

-- Columnas en pedidos para el nuevo flujo de revisión y entrega
ALTER TABLE `pedidos`
  ADD COLUMN `tipo_entrega`          ENUM('pickup','repartidor') NULL          AFTER `tipo`,
  ADD COLUMN `repartidor_asignado_id` INT UNSIGNED               NULL          AFTER `tipo_entrega`,
  ADD COLUMN `costo_envio`           DECIMAL(10,2)  NOT NULL DEFAULT 0.00      AFTER `repartidor_asignado_id`,
  ADD COLUMN `nota_empresa`          TEXT           NULL                        AFTER `costo_envio`,
  ADD COLUMN `foto_comprobante_path` VARCHAR(255)   NULL                        AFTER `nota_empresa`,
  ADD COLUMN `foto_entrega_path`     VARCHAR(255)   NULL                        AFTER `foto_comprobante_path`,
  ADD CONSTRAINT `fk_ped_repartidor_asignado`
    FOREIGN KEY (`repartidor_asignado_id`) REFERENCES `usuarios`(`id`) ON DELETE SET NULL;

-- Columnas en usuarios para dirección de entrega del comprador (punto de entrega)
ALTER TABLE `usuarios`
  ADD COLUMN `direccion_entrega`  TEXT           NULL AFTER `telefono`,
  ADD COLUMN `referencia_entrega` VARCHAR(200)   NULL AFTER `direccion_entrega`,
  ADD COLUMN `lat_entrega`        DECIMAL(10,8)  NULL AFTER `referencia_entrega`,
  ADD COLUMN `lng_entrega`        DECIMAL(11,8)  NULL AFTER `lat_entrega`;

-- Directorio de uploads: crear en servidor /public/uploads/evidencias/ con permisos 755
-- mkdir -p /public/uploads/evidencias && chmod 755 /public/uploads/evidencias
