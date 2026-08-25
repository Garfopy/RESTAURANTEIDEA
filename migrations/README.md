# migrations/

Las migraciones históricas del sistema anterior (Jungle Pizza / CarniHub) se quitaron el
2026-08-25 — quedan disponibles en el historial de git si se necesitan, pero ya no aplican:
el esquema actual vive como dump plano en `idactivo_cafeteq.sql`, no se reconstruye
corriendo estos archivos en orden.

A partir de aquí, esta carpeta guarda las migraciones **nuevas** del marketplace (ver
`plan-web-superadmin.md`, `plan-web-admin.md`, `plan-web-cajero.md` en la raíz del repo para
las tablas/columnas pendientes de cada rol).
