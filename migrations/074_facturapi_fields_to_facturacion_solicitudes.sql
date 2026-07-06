-- Campos de timbrado FacturAPI para la tabla ya existente de solicitudes.
-- No crea tablas nuevas: extiende facturacion_solicitudes del dump actual.

ALTER TABLE facturacion_solicitudes
  ADD COLUMN facturapi_invoice_id varchar(80) NULL AFTER xml_url,
  ADD COLUMN facturapi_status varchar(40) NULL AFTER facturapi_invoice_id,
  ADD COLUMN facturapi_livemode tinyint(1) NOT NULL DEFAULT 0 AFTER facturapi_status;

