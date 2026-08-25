-- Repara datos legacy del selector usando las tablas oficiales compartidas.
-- Idempotente para MySQL 5.7+; requiere 069 y 070.

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

-- Crea automaticamente una ficha de extra para cada guarnicion incluida.
-- El precio se obtiene del mayor precio extra historico del ingrediente.
INSERT INTO rest_modificadores
  (restaurante_id, ingrediente_id, nombre, tipo, alcance, precio_extra,
   cantidad_unidad, unidad, max_seleccion_global, activo)
SELECT p.restaurante_id, ri.ingrediente_id, CONCAT('Extra ', MAX(i.nombre)),
       'extra', 'restaurante', COALESCE(MAX(precios.precio_extra), 0), MAX(ri.cantidad),
       MAX(ri.unidad), 1, 1
FROM rest_receta_ingredientes ri
JOIN rest_recetas r ON r.id=ri.receta_id
JOIN rest_platillos p ON p.id=r.platillo_id
JOIN rest_ingredientes i ON i.id=ri.ingrediente_id
LEFT JOIN (
  SELECT hp.restaurante_id, hri.ingrediente_id, MAX(hri.precio_extra) AS precio_extra
  FROM rest_receta_ingredientes hri
  JOIN rest_recetas hr ON hr.id=hri.receta_id
  JOIN rest_platillos hp ON hp.id=hr.platillo_id
  WHERE COALESCE(hri.precio_extra, 0)>0
  GROUP BY hp.restaurante_id, hri.ingrediente_id
) precios ON precios.restaurante_id=p.restaurante_id
         AND precios.ingrediente_id=ri.ingrediente_id
WHERE p.activo=1 AND i.restaurante_id=p.restaurante_id
  AND COALESCE(ri.precio_extra, 0)=0
  AND (ri.tipo_componente='guarnicion'
       OR (i.tipo='guarnicion' AND COALESCE(ri.es_informativo, 0)=0))
  AND NOT EXISTS (
    SELECT 1 FROM rest_modificadores m
    WHERE m.restaurante_id=p.restaurante_id
      AND m.ingrediente_id=ri.ingrediente_id
      AND m.tipo='extra' AND m.alcance='restaurante'
  )
GROUP BY p.restaurante_id, ri.ingrediente_id;

-- Elimina asociaciones globales antiguas en platillos que no incluyen esa
-- guarnicion. No elimina el modificador ni afecta pedidos historicos.
DELETE pm FROM rest_platillo_modificador pm
JOIN rest_platillos p ON p.id=pm.platillo_id
JOIN rest_modificadores m ON m.id=pm.modificador_id
WHERE m.tipo='extra' AND m.alcance='restaurante'
  AND NOT EXISTS (
    SELECT 1
    FROM rest_recetas r
    JOIN rest_receta_ingredientes ri ON ri.receta_id=r.id
    JOIN rest_ingredientes i ON i.id=ri.ingrediente_id
    WHERE r.platillo_id=p.id
      AND ri.ingrediente_id=m.ingrediente_id
      AND COALESCE(ri.precio_extra, 0)=0
      AND (ri.tipo_componente='guarnicion'
           OR (i.tipo='guarnicion' AND COALESCE(ri.es_informativo, 0)=0))
  );

INSERT IGNORE INTO rest_platillo_modificador
  (platillo_id, modificador_id, obligatorio, max_seleccion)
SELECT DISTINCT p.id, m.id, 0, GREATEST(1, m.max_seleccion_global)
FROM rest_platillos p
JOIN rest_recetas r ON r.platillo_id=p.id
JOIN rest_receta_ingredientes ri ON ri.receta_id=r.id
JOIN rest_ingredientes i ON i.id=ri.ingrediente_id
JOIN rest_modificadores m ON m.restaurante_id=p.restaurante_id
  AND m.ingrediente_id=ri.ingrediente_id
WHERE p.activo=1 AND m.activo=1
  AND m.tipo='extra' AND m.alcance='restaurante'
  AND COALESCE(ri.precio_extra, 0)=0
  AND (ri.tipo_componente='guarnicion'
       OR (i.tipo='guarnicion' AND COALESCE(ri.es_informativo, 0)=0));
