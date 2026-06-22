# Modificadores de platillos para Amare-App

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

CarniHub sincroniza `PUT /branches/{branchId}/menu-items/{platilloId}/modifiers`:

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

Durante la transicion se conserva `modificadores` y se agrega el selector unificado:

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
        "nombre": "Sin ensalada",
        "seleccionada_por_defecto": true,
        "accion_al_desmarcar": "excluir"
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

La app muestra ambas listas dentro de un solo bloque. Si `incluidas` esta vacio muestra solo extras; si ambas listas estan vacias usa `visible=false` y oculta el selector.

Tambien puede consultarse un solo platillo con `GET /branches/{branchId}/menu-items/{platilloId}/modifiers`.

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
