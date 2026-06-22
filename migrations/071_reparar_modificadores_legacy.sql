-- Repara datos legacy del selector y asegura el almacenamiento de Amare-App.
-- Idempotente para MySQL 5.7+; requiere 069 y 070.

CREATE TABLE IF NOT EXISTS amare_branch_menu_modifiers (
  branch_id INT UNSIGNED NOT NULL,
  platillo_external_id INT UNSIGNED NOT NULL,
  payload_json TEXT NOT NULL,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (branch_id, platillo_external_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Una guarnicion incluida legacy tiene precio cero, participa en inventario y
-- su ingrediente ya estaba clasificado como guarnicion. Las opciones con
-- precio_extra mayor a cero son extras y no se convierten en exclusiones.
UPDATE rest_receta_ingredientes ri
JOIN rest_recetas r ON r.id=ri.receta_id
JOIN rest_platillos p ON p.id=r.platillo_id
JOIN rest_ingredientes i ON i.id=ri.ingrediente_id
SET ri.tipo_componente='guarnicion'
WHERE p.activo=1 AND i.restaurante_id=p.restaurante_id
  AND i.tipo='guarnicion'
  AND COALESCE(ri.precio_extra, 0)=0
  AND COALESCE(ri.es_informativo, 0)=0
  AND COALESCE(ri.tipo_componente, 'materia_prima')<>'guarnicion';

-- Convierte el catalogo de extras guardado historicamente en las recetas.
-- Si un ingrediente tenia varios precios se conserva el mayor.
INSERT INTO rest_modificadores
  (restaurante_id, ingrediente_id, nombre, tipo, alcance, precio_extra,
   cantidad_unidad, unidad, max_seleccion_global, activo)
SELECT p.restaurante_id, ri.ingrediente_id, CONCAT('Extra ', MAX(i.nombre)),
       'extra', 'restaurante', MAX(ri.precio_extra), MAX(ri.cantidad),
       MAX(ri.unidad), 1, 1
FROM rest_receta_ingredientes ri
JOIN rest_recetas r ON r.id=ri.receta_id
JOIN rest_platillos p ON p.id=r.platillo_id
JOIN rest_ingredientes i ON i.id=ri.ingrediente_id
WHERE p.activo=1 AND i.restaurante_id=p.restaurante_id
  AND COALESCE(ri.precio_extra, 0)>0
  AND NOT EXISTS (
    SELECT 1 FROM rest_modificadores m
    WHERE m.restaurante_id=p.restaurante_id
      AND m.ingrediente_id=ri.ingrediente_id
      AND m.tipo='extra' AND m.alcance='restaurante'
  )
GROUP BY p.restaurante_id, ri.ingrediente_id;

INSERT IGNORE INTO rest_platillo_modificador
  (platillo_id, modificador_id, obligatorio, max_seleccion)
SELECT p.id, m.id, 0, GREATEST(1, m.max_seleccion_global)
FROM rest_platillos p
JOIN rest_modificadores m ON m.restaurante_id=p.restaurante_id
WHERE p.activo=1 AND m.activo=1
  AND m.tipo='extra' AND m.alcance='restaurante';
