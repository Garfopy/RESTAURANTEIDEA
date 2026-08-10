-- Cambia solamente el nombre visible del rol.
-- El slug interno permanece como "programador" para conservar compatibilidad.

UPDATE `roles`
   SET `nombre` = 'Macias'
 WHERE `slug` = 'programador';
