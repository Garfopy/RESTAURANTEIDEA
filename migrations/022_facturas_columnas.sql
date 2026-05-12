-- ============================================================
-- Migración 022 — Agregar columnas faltantes en tabla facturas
-- Ejecutar si da error "Unknown column" en facturas
-- Cada ALTER es independiente; ignora el error 1060 si la
-- columna ya existe (puedes ejecutarlas una por una en phpMyAdmin)
-- ============================================================

ALTER TABLE `facturas` ADD COLUMN `fecha_emision` DATETIME NULL AFTER `total`;
ALTER TABLE `facturas` ADD COLUMN `estado` ENUM('timbrada','cancelada') NOT NULL DEFAULT 'timbrada' AFTER `fecha_emision`;
ALTER TABLE `facturas` ADD COLUMN `xml_url` TEXT NULL AFTER `uuid_cfdi`;
ALTER TABLE `facturas` ADD COLUMN `pdf_url` TEXT NULL AFTER `xml_url`;

-- Rellena fecha_emision con created_at donde sea NULL
UPDATE `facturas` SET `fecha_emision` = `created_at` WHERE `fecha_emision` IS NULL;

-- Hace la columna NOT NULL ahora que está poblada
ALTER TABLE `facturas` MODIFY COLUMN `fecha_emision` DATETIME NOT NULL;
