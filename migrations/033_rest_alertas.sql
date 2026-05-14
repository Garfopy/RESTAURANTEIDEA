-- =============================================================
-- 033_rest_alertas.sql
-- Tabla de alertas generadas por comensales hacia el staff
-- Tipos: 'mesero' (llamar al mesero), 'cuenta' (pedir la cuenta)
-- =============================================================

CREATE TABLE IF NOT EXISTS rest_alertas (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    restaurante_id  INT UNSIGNED NOT NULL,
    tipo            ENUM('mesero','cuenta') NOT NULL DEFAULT 'mesero',
    mesa_id         INT UNSIGNED NULL,
    visita_id       INT UNSIGNED NULL,
    atendida        TINYINT(1)   NOT NULL DEFAULT 0,
    created_at      DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,

    INDEX idx_restaurante (restaurante_id),
    INDEX idx_atendida    (restaurante_id, atendida),
    INDEX idx_visita      (visita_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
