# Despliegue de Jungle Pizza en `/sistema`

Destino: `https://junglepizzazihuatanejo.com.mx/sistema/`

## Requisitos del hosting

- Apache con `mod_rewrite` y `AllowOverride All`.
- PHP 8.0 o superior con `pdo_mysql`, `mbstring`, `openssl` y `curl`.
- MySQL o MariaDB con soporte para `utf8mb4`, JSON, claves foráneas y triggers.
- Certificado HTTPS activo.

## Instalación

1. Crea una base de datos vacía en cPanel con cotejamiento `utf8mb4_unicode_ci`.
2. Importa `migrations/000_jungle_club_estructura_limpia.sql` desde phpMyAdmin. Esta migración no inserta registros.
3. Extrae el ZIP de despliegue directamente en `public_html/sistema/`. En esa carpeta deben quedar `index.php`, `.htaccess`, `app/`, `base/`, `config/`, `public/` y `vendor/`.
4. Edita `public_html/sistema/config/database.php` y cambia `DB_NAME`, `DB_USER` y `DB_PASS`. Conserva la clave aleatoria de `JWT_SECRET` que incluye el paquete.
5. Verifica permisos de escritura para el usuario de PHP:
   - `storage/sessions/`: `775`
   - `storage/logs/`: `775`
   - `public/uploads/`: `775`
6. Desde Terminal de cPanel crea la configuración inicial. Sustituye el correo y la contraseña:

   ```bash
   cd ~/public_html/sistema
   php cron/bootstrap_jungle.php --email="admin@tudominio.com" --password="UNA_CLAVE_SEGURA_DE_12_O_MAS" --nombre="Administrador"
   ```

7. Abre `https://junglepizzazihuatanejo.com.mx/sistema/` y prueba una reservación. El acceso administrativo está en `https://junglepizzazihuatanejo.com.mx/sistema/auth/login`.

## Recordatorios de reservación

En Cron Jobs de cPanel configura una ejecución diaria, ajustando la ruta absoluta de tu cuenta:

```bash
0 9 * * * APP_ENV=production APP_URL=https://junglepizzazihuatanejo.com.mx/sistema /usr/local/bin/php /home/USUARIO/public_html/sistema/cron/recordatorio_reservas.php
```

## Comprobaciones finales

- La página principal muestra el formulario de reservaciones habilitado.
- Una reserva nueva aparece en `Reservaciones` del panel.
- `https://junglepizzazihuatanejo.com.mx/sistema/public/debug-auth.php` responde `403`.
- `https://junglepizzazihuatanejo.com.mx/sistema/migrations/000_jungle_club_estructura_limpia.sql` responde `403`.
- Las URLs internas conservan el prefijo `/sistema/` y usan HTTPS.

No subas archivos de credenciales de Firebase a una carpeta pública. Si se usan, colócalos fuera de `public_html` y configura su ruta por variable de entorno.
