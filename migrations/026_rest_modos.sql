-- 026_rest_modos.sql
-- Toggles de modo de operación por sucursal del restaurante.
-- Permite que cada sucursal sea: restaurante con mesas, taquería de banqueta,
-- take-away, con o sin portero, con o sin reservas, etc.

ALTER TABLE rest_restaurantes
  ADD COLUMN mesas_habilitadas       TINYINT(1)   NOT NULL DEFAULT 1
    COMMENT '1 = sucursal con mesas. 0 = take-away o banqueta sin mesas',
  ADD COLUMN reservas_habilitadas    TINYINT(1)   NOT NULL DEFAULT 1
    COMMENT '1 = acepta reservaciones. 0 = lugar de paso',
  ADD COLUMN portero_habilitado      TINYINT(1)   NOT NULL DEFAULT 1
    COMMENT '1 = checador verifica pago al salir. 0 = self-service',
  ADD COLUMN propinas_sugeridas      VARCHAR(40)  NOT NULL DEFAULT '0,10,15,20'
    COMMENT 'CSV de porcentajes de propina mostrados al comensal',
  ADD COLUMN requiere_login_comensal TINYINT(1)   NOT NULL DEFAULT 0
    COMMENT '1 = exige Google login o nombre+telefono antes de ordenar';
