# Plan de correccion para api_restaurante

## Objetivo

Hacer que `api_restaurante` lea y escriba modificadores usando la misma base de
datos y las mismas tablas oficiales que CarniHub:

- `rest_modificadores`
- `rest_platillo_modificador`
- `rest_ingredientes`
- `rest_platillos`

El flujo no debe leer ni escribir `amare_branch_menu_modifiers`.

## 1. Localizar el flujo actual

Buscar en el repositorio de `api_restaurante`:

```text
amare_branch_menu_modifiers
syncModifiers
menu-items
/modifiers
No se pudieron sincronizar los modificadores
MenuController
```

Revisar especialmente:

```text
src/Controllers/MenuController.php
src/Models/Menu.php
routes/api.php
```

## 2. Confirmar la ruta

Localizar la implementacion de:

```http
PUT /branches/{branchId}/menu-items/{menuItemId}/modifiers
GET /branches/{branchId}/menu-items/{menuItemId}/modifiers
```

Eliminar de esos endpoints cualquier dependencia de
`amare_branch_menu_modifiers`.

## 3. Resolver la sucursal

Obtener el restaurante relacionado con `branchId` mediante
`rest_restaurantes.sucursal_id` o `sucursal_carnihub_id`.

Validar despues el platillo:

```sql
SELECT id
FROM rest_platillos
WHERE id = ?
  AND restaurante_id = ?
LIMIT 1;
```

Si no existe, responder HTTP 404:

```json
{
  "success": false,
  "message": "El platillo no existe para esta sucursal."
}
```

## 4. Implementar la lectura oficial

Crear un metodo reutilizable en el modelo:

```sql
SELECT m.*,
       pm.max_seleccion,
       i.nombre AS ingrediente_nombre,
       i.unidad_principal AS ingrediente_unidad
FROM rest_platillo_modificador pm
JOIN rest_modificadores m ON m.id = pm.modificador_id
LEFT JOIN rest_ingredientes i ON i.id = m.ingrediente_id
WHERE pm.platillo_id = ?
  AND m.activo = 1
ORDER BY FIELD(m.tipo, 'sin', 'extra', 'opcion'), m.nombre;
```

Usar este metodo en:

- GET del menu completo.
- GET del detalle de un platillo.
- GET `/branches/{branchId}/menu-items/{menuItemId}/modifiers`.

## 5. Construir el selector unificado

Transformar los registros con `tipo='sin'` en `selector.incluidas` y los
registros con `tipo='extra'` en `selector.extras`.

```json
{
  "platillo_id": 42,
  "modificadores": [],
  "selector": {
    "tipo": "personalizacion_platillo",
    "titulo": "Personaliza tu platillo",
    "visible": true,
    "incluidas": [],
    "extras": []
  }
}
```

Reglas:

- Las incluidas aparecen visibles y seleccionadas por defecto.
- Desmarcar una incluida envia su modificador de exclusion.
- Los extras comienzan con cantidad cero.
- Si no existen incluidas, se muestran solamente los extras.
- Si ambas listas estan vacias, `visible` debe ser `false`.

## 6. Reescribir syncModifiers

Aceptar cualquiera de estos cuerpos:

```json
{"modifiers": []}
```

```json
{"modificadores": []}
```

```json
[]
```

Una lista vacia es valida y debe devolver HTTP 200 sin borrar opciones
existentes.

Para cada modificador:

1. Convertir `tipo='exclusion'` a `tipo='sin'`.
2. Validar que el ingrediente pertenezca al restaurante.
3. Si incluye `id`, validar que el modificador pertenezca al restaurante.
4. Sin `id`, buscar por restaurante, ingrediente, tipo, alcance y nombre.
5. Actualizar el registro existente o insertarlo en `rest_modificadores`.
6. Crear o actualizar la relacion en `rest_platillo_modificador`.

```sql
INSERT INTO rest_platillo_modificador
  (platillo_id, modificador_id, obligatorio, max_seleccion)
VALUES (?, ?, 0, ?)
ON DUPLICATE KEY UPDATE
  max_seleccion = VALUES(max_seleccion);
```

Si `alcance='restaurante'`, asociar el modificador con todos los platillos
activos del restaurante.

No desactivar modificadores omitidos en el payload. CarniHub puede excluirlos
temporalmente cuando los interruptores globales estan apagados.

## 7. Mantener compatibilidad e idempotencia

Confirmar que `rest_platillo_modificador` tenga una llave unica o primaria en:

```text
(platillo_id, modificador_id)
```

Ejecutar dos veces el mismo PUT no debe crear registros duplicados.

La tabla `amare_branch_menu_modifiers` puede permanecer por compatibilidad
historica, pero ningun endpoint nuevo debe utilizarla.

## 8. Eliminar la sincronizacion redundante de la web

CarniHub ya materializa los modificadores directamente en las tablas
compartidas. No debe ejecutar un PUT por cada platillo despues de guardar.

La API debe limitarse a leer los datos actuales. El PUT se conserva solamente
para clientes anteriores.

## 9. Evitar cache obsoleta

Agregar a los GET de configuracion y menu:

```http
Cache-Control: no-store, no-cache, must-revalidate, max-age=0
Pragma: no-cache
```

La aplicacion movil debe volver a solicitar configuracion y menu:

- Al iniciar sesion.
- Al cambiar de sucursal.
- Al regresar a primer plano.
- Al ejecutar pull-to-refresh.

No debe conservar los modificadores solamente en el estado creado durante el
inicio de sesion.

## 10. Manejo de errores

Registrar el detalle internamente:

```php
error_log('[MenuController::syncModifiers] ' . $e->getMessage());
error_log('[MenuController::syncModifiers TRACE] ' . $e->getTraceAsString());
```

Responder en produccion:

```json
{
  "success": false,
  "message": "No se pudieron sincronizar los modificadores."
}
```

Incluir el mensaje tecnico solamente cuando `APP_ENV` no sea `production`.

## 11. Pruebas minimas

### Lista vacia

```http
PUT /branches/1/menu-items/94/modifiers
```

```json
{"modifiers": []}
```

Debe responder HTTP 200 y `count: 0`.

### Exclusion

```json
{
  "modifiers": [
    {
      "ingrediente_id": 10,
      "nombre": "Sin cebolla",
      "tipo": "sin",
      "alcance": "platillo",
      "precio_extra": 0,
      "cantidad_unidad": 1,
      "unidad": "pza",
      "max_seleccion": 1
    }
  ]
}
```

Debe crear o actualizar el modificador y su relacion.

### Idempotencia

Ejecutar dos veces el mismo cuerpo. No debe duplicar registros ni relaciones.

### Seguridad

- Platillo de otra sucursal: HTTP 404.
- Ingrediente de otro restaurante: HTTP 422.
- Modificador de otro restaurante: HTTP 422.

### Lectura

- Platillo con incluida y extras: devuelve ambos grupos.
- Platillo sin incluida: devuelve extras globales.
- Platillo sin opciones: devuelve selector oculto.
- Cambios de la web aparecen sin cerrar sesion.

## 12. Consultas de validacion

```sql
SELECT
  p.id AS platillo_id,
  p.nombre AS platillo,
  m.id AS modificador_id,
  m.nombre AS modificador,
  m.tipo,
  m.alcance,
  m.ingrediente_id,
  m.precio_extra,
  m.cantidad_unidad,
  m.unidad,
  pm.max_seleccion,
  m.activo
FROM rest_platillos p
LEFT JOIN rest_platillo_modificador pm ON pm.platillo_id = p.id
LEFT JOIN rest_modificadores m ON m.id = pm.modificador_id
WHERE p.id IN (94, 95)
ORDER BY p.id, m.tipo, m.nombre;
```

```sql
SELECT id, nombre, restaurante_id, activo
FROM rest_platillos
WHERE id IN (94, 95);
```

## Resultado esperado

- No aparece el HTTP 500 relacionado con `amare_branch_menu_modifiers`.
- Web y app usan una sola fuente de datos.
- Los platillos sin modificadores son validos.
- No existen relaciones duplicadas.
- Los extras globales aparecen en todos los platillos activos.
- Las guarniciones incluidas pueden omitirse sin desaparecer del menu.
- La app recibe cambios sin cerrar sesion.
