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
