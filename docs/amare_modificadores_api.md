# Modificadores de platillos para Amare-App

## Despliegue de base de datos

Ejecutar en orden `069_modificadores_app.sql`, `070_selector_unificado_guarniciones.sql`
y `071_reparar_modificadores_legacy.sql`. Web y Amare-App usan como fuente
oficial `rest_modificadores` y `rest_platillo_modificador`; el flujo no lee ni
escribe `amare_branch_menu_modifiers`.

## Configuracion de sucursal

`PUT /branches/{branchId}/config` admite:

```json
{
  "modificadores": {
    "exclusiones_habilitadas": true,
    "extras_habilitados": true
  }
}
```

La app puede recuperar la configuracion y todos los modificadores ya sincronizados con:

`GET /branches/{branchId}/config`

La respuesta incluye `data.modificadores` y `data.platillos_modificadores`, indexados por ID de platillo.

## Catalogo por platillo

La app consulta `GET /branches/{branchId}/menu-items/{platilloId}/modifiers`.
La respuesta se genera directamente desde las tablas oficiales compartidas:

```json
{
  "platillo_id": 42,
  "modificadores": [
    {
      "id": 12,
      "tipo": "extra",
      "nombre": "Extra aguacate",
      "ingrediente_id": 88,
      "cantidad_unidad": 50,
      "unidad": "g",
      "precio_unitario": 25,
      "max_cantidad": 3
    }
  ]
}
```

Se conserva `modificadores` y se agrega el selector unificado:

```json
{
  "selector": {
    "tipo": "personalizacion_platillo",
    "titulo": "Personaliza tu platillo",
    "visible": true,
    "incluidas": [
      {
        "id": 21,
        "tipo": "exclusion",
        "nombre": "Ensalada",
        "incluida": true,
        "visible": true,
        "puede_omitirse": true,
        "omitida_por_defecto": false,
        "seleccionada_por_defecto": true,
        "accion_al_desmarcar": "enviar_exclusion"
      }
    ],
    "extras": [
      {
        "id": 12,
        "tipo": "extra",
        "nombre": "Extra aguacate",
        "precio_unitario": 25,
        "cantidad_inicial": 0,
        "max_cantidad": 3
      }
    ]
  }
}
```

La app muestra cada elemento de `incluidas` visible y marcado por defecto. Desmarcarlo no lo elimina del catalogo: agrega su `id` a los modificadores enviados para omitirlo solamente en esa partida. Si `incluidas` esta vacio muestra solo extras; si ambas listas estan vacias usa `visible=false` y oculta el selector.

Cada platillo recibe solamente las guarniciones incluidas en su receta. Esas
mismas guarniciones aparecen como incluidas removibles y como extras; la ficha
compartida conserva automaticamente precio y porcion, pero no hace que el extra
aparezca en otros platillos. En `incluidas.nombre` se envia el nombre del
ingrediente (por ejemplo, `Arroz Rojo`), no el prefijo interno `Sin`.

El `PUT` se mantiene por compatibilidad con clientes anteriores. Acepta
`modifiers`, `modificadores` o una lista JSON directa, incluida una lista vacia,
y actualiza idempotentemente las mismas tablas oficiales. La web no necesita
invocarlo porque ya escribe en la base compartida.

`tipo` puede ser `exclusion` o `extra`. Una exclusion siempre tiene precio cero y cantidad maxima uno.

## Seleccion en pedidos

Cada partida puede devolver:

```json
{
  "platillo_id": 42,
  "cantidad": 2,
  "modificadores": [
    {"modificador_id": 12, "cantidad": 2}
  ]
}
```

CarniHub ignora precios enviados por el cliente, valida la asociacion y el maximo, y calcula el precio desde su base de datos. Una partida sin `modificadores` se procesa como platillo base.
