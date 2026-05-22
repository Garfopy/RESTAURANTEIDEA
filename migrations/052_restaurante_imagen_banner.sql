-- Compatibilidad: agrega imagen_banner para instalaciones previas
ALTER TABLE rest_restaurantes
  ADD COLUMN IF NOT EXISTS imagen_banner VARCHAR(255) NULL AFTER logo;