-- Terminos y condiciones consultables desde web y app.
-- El contenido completo puede editarse cambiando legal_terms_html en global_settings.

INSERT INTO global_settings (clave, valor, tipo, grupo, etiqueta) VALUES
  ('legal_terms_title', 'Terminos y condiciones', 'text', 'legal', 'Titulo de terminos y condiciones'),
  ('legal_terms_version', '2026-07-14', 'text', 'legal', 'Version de terminos y condiciones'),
  ('legal_terms_updated_at', '2026-07-14', 'text', 'legal', 'Fecha de actualizacion de terminos'),
  ('legal_terms_html', '', 'text', 'legal', 'Contenido HTML de terminos y condiciones')
ON DUPLICATE KEY UPDATE
  valor = VALUES(valor),
  tipo = VALUES(tipo),
  grupo = VALUES(grupo),
  etiqueta = VALUES(etiqueta);
