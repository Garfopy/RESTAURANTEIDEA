-- =============================================================
-- 034_paypal_sandbox_carnihub.sql
-- Credenciales PayPal Sandbox para CarniHub
-- App: Paypal Carnihub (sandbox)
-- Ejecutar en phpMyAdmin o CLI: mysql -u user -p db < 034_...sql
-- =============================================================

INSERT INTO global_settings (clave, valor, grupo) VALUES
  ('paypal_client_id_sandbox', 'AalQGM8Ez-mXtJpDBayzANY2sPNFNcojryEiE-esBkDHWRo8eJv2-qAgIKpWA8wU4XNhV0nikreArAQi', 'apis'),
  ('paypal_secret_sandbox',    'EIfn70EfQR2lN6RHQ9defZW1ShU_zPztJNWPi8rdLSRWxzurEhQKz7XTLlB1vERkCpX1D7zfcGM26Mqs', 'apis'),
  ('paypal_mode',              'sandbox', 'apis')
ON DUPLICATE KEY UPDATE valor = VALUES(valor);
