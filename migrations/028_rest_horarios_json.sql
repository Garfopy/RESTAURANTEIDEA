-- 028_rest_horarios_json.sql
-- Almacena horarios por día de la semana como JSON
-- Reemplaza los campos TIME simples horario_apertura / horario_cierre

ALTER TABLE rest_restaurantes
  ADD COLUMN horarios_json TEXT NULL
    COMMENT 'JSON: {"lun":{"abre":"09:00","cierra":"22:00","cerrado":0},...}';
