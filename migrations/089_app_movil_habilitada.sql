-- Jungle Pizza
-- Control central de la app movil por restaurante.
-- Los restaurantes existentes conservan el canal activo; los nuevos nacen apagados.

ALTER TABLE `rest_restaurantes`
  ADD COLUMN IF NOT EXISTS `app_movil_habilitada` TINYINT(1) NOT NULL DEFAULT 1
  AFTER `reservas_habilitadas`;

ALTER TABLE `rest_restaurantes`
  ALTER COLUMN `app_movil_habilitada` SET DEFAULT 0;

