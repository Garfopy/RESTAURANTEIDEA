# migrations/

Las migraciones históricas del sistema anterior (Jungle Pizza / CarniHub) se quitaron el
2026-08-25 — quedan disponibles en el historial de git si se necesitan, pero ya no aplican:
el esquema actual vive como dump plano, no se reconstruye corriendo estos archivos en orden.

A partir de aquí, esta carpeta guarda las migraciones **nuevas** del marketplace (ver
`plan-web-superadmin.md`, `plan-web-admin.md`, `plan-web-cajero.md` en la raíz del repo para
las tablas/columnas pendientes de cada rol).

## Referencia del esquema

- **`idactivo_cafeteq.schema.sql`** (raíz del repo, versionado) — solo estructura: tablas,
  índices, FKs y triggers. Es la referencia para escribir queries y migraciones nuevas.
- `idactivo_cafeteq.sql` (dump completo con datos) está en `.gitignore` **a propósito**:
  este repo es público y el dump trae correos y hashes de password reales. Si necesitas los
  datos, pide el dump por canal privado.

## ⚠️ Nunca commitear credenciales

Este repo es público. Las migraciones que crean cuentas (como `004_superadmin_seed.sql`) van
con placeholder — el hash real se genera localmente y se comparte fuera del repo:

```bash
php -r "echo password_hash('TU_PASSWORD', PASSWORD_DEFAULT), PHP_EOL;"
```

## Estado de aplicación (base de producción)

| Migración | Aplicada |
|---|---|
| `001_roles_cajero_cocina.sql` | ✅ |
| `002_rest_cortes.sql` | ✅ |
| `003_superadmin_universidades_planes.sql` | ✅ 2026-08-26 |
| `004_superadmin_seed.sql` | ✅ 2026-08-26 (cuenta creada, credencial fuera del repo) |
| `005_eliminar_modo_social.sql` | ⬜ **pendiente de correr** |
