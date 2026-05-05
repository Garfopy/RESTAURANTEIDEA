-- Migración 009: Combos de productos por comprador
-- Sprint 4C-1 — Combos predefinidos que el admin asigna a compradores específicos

CREATE TABLE combos (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    empresa_id  INT NOT NULL,
    nombre      VARCHAR(150) NOT NULL,
    descripcion TEXT NULL,
    activo      TINYINT(1) NOT NULL DEFAULT 1,
    created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_combo_empresa FOREIGN KEY (empresa_id) REFERENCES empresas(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE combo_items (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    combo_id    INT NOT NULL,
    producto_id INT NOT NULL,
    cantidad    DECIMAL(10,2) NOT NULL,
    CONSTRAINT fk_ci_combo    FOREIGN KEY (combo_id)    REFERENCES combos(id)    ON DELETE CASCADE,
    CONSTRAINT fk_ci_producto FOREIGN KEY (producto_id) REFERENCES productos(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE combo_compradores (
    id           INT AUTO_INCREMENT PRIMARY KEY,
    combo_id     INT NOT NULL,
    comprador_id INT NOT NULL,
    UNIQUE KEY uq_combo_comprador (combo_id, comprador_id),
    CONSTRAINT fk_cc_combo     FOREIGN KEY (combo_id)     REFERENCES combos(id)   ON DELETE CASCADE,
    CONSTRAINT fk_cc_comprador FOREIGN KEY (comprador_id) REFERENCES usuarios(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
