# migrations/

Las migraciones históricas del sistema anterior (Jungle Pizza / CarniHub) se quitaron el
2026-08-25 — quedan disponibles en el historial de git si se necesitan, pero ya no aplican:
el esquema actual vive como dump plano en `idactivo_cafeteq.sql`, no se reconstruye
corriendo estos archivos en orden.

A partir de aquí, esta carpeta guarda las migraciones **nuevas** del marketplace (ver
`plan-web-superadmin.md`, `plan-web-admin.md`, `plan-web-cajero.md` en la raíz del repo para
las tablas/columnas pendientes de cada rol).

## Migraciones nuevas

- `002_cajero_pos.sql`: estructura requerida por Caja/POS.
- `003_rest_cortes.sql`: columnas y tablas auxiliares para cortes del restaurante.
- `004_flujo_caja_cocina.sql`: agrega `requiere_preparacion` para separar productos que pasan por Cocina de productos listos para entregar, y `ingrediente_directo_cantidad` para descontar X unidades en bebidas/productos directos.

Ejecuta únicamente las migraciones que todavía no estén aplicadas en la base destino. Las
migraciones nuevas son idempotentes y no cambian los nombres usados por la aplicación móvil.
