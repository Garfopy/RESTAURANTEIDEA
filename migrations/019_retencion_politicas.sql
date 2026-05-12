-- ============================================================
-- Migración 019 — Políticas de Retención de Archivos
-- Reemplaza la lógica estática de 60 días con configuración
-- dinámica desde global_settings. Las filas en BD nunca se
-- borran — solo se purgan los archivos físicos y se nullifican
-- los _path correspondientes para liberar espacio en disco.
-- ============================================================

-- 1. Configuraciones de retención (días antes de purgar archivo físico)
INSERT INTO `global_settings` (`clave`, `valor`, `tipo`, `grupo`, `etiqueta`) VALUES
('retencion_fotos_evidencias_dias', '90',  'number', 'retencion', 'Días retención fotos/firmas de evidencias'),
('retencion_fotos_pedidos_dias',    '90',  'number', 'retencion', 'Días retención fotos de pedidos y comprobantes'),
('retencion_logs_dias',             '365', 'number', 'retencion', 'Días retención de registros action_logs')
ON DUPLICATE KEY UPDATE `valor` = VALUES(`valor`);

-- 2. Columna de control: cuándo se purgaron las imágenes (NULL = aún tiene archivos)
ALTER TABLE `evidencias_entrega`
  ADD COLUMN `imagenes_purgadas_at` datetime NULL DEFAULT NULL
    COMMENT 'Fecha en que firma_path y foto_path fueron borrados del disco';

ALTER TABLE `pedidos`
  ADD COLUMN `imagenes_purgadas_at` datetime NULL DEFAULT NULL
    COMMENT 'Fecha en que foto_comprobante_path y foto_entrega_path fueron borrados del disco';

ALTER TABLE `pedido_sucursal`
  ADD COLUMN `imagen_purgada_at` datetime NULL DEFAULT NULL
    COMMENT 'Fecha en que foto_entrega_path fue borrado del disco';
