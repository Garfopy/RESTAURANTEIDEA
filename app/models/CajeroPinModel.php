<?php
/**
 * CajeroPinModel — acceso rápido por PIN dentro de la terminal.
 *
 * Modelo de acceso (decisión D8 de plan-web-cajero.md):
 *   La terminal se abre UNA vez con email+password. Después cada cajero
 *   se selecciona de una lista y teclea su PIN. El PIN nunca identifica
 *   por sí solo a nadie: siempre se verifica contra el usuario elegido.
 */
class CajeroPinModel extends BaseModel
{
    protected string $table = 'usuarios';

    private const PIN_MIN = 4;
    private const PIN_MAX = 6;

    /** Cajeros activos del negocio, para pintar la lista de selección. */
    public function cajerosActivos(int $restauranteId): array
    {
        return $this->query(
            "SELECT u.id, u.nombre, u.apellido_paterno, u.email,
                    (u.pin_hash IS NOT NULL) AS tiene_pin,
                    u.pin_bloqueado_hasta,
                    (u.pin_bloqueado_hasta IS NOT NULL AND u.pin_bloqueado_hasta > NOW()) AS bloqueado
               FROM usuarios u
               JOIN roles r ON r.id = u.rol_id
              WHERE u.restaurante_id = ? AND u.activo = 1 AND r.slug = 'cajero'
              ORDER BY u.nombre",
            [$restauranteId]
        );
    }

    public function cajero(int $usuarioId, int $restauranteId): ?array
    {
        return $this->queryOne(
            "SELECT u.*, r.slug AS rol_slug
               FROM usuarios u
               JOIN roles r ON r.id = u.rol_id
              WHERE u.id = ? AND u.restaurante_id = ? AND u.activo = 1 AND r.slug = 'cajero'
              LIMIT 1",
            [$usuarioId, $restauranteId]
        );
    }

    /** Segundos que faltan para que se libere el bloqueo, 0 si no está bloqueado. */
    public function segundosBloqueo(array $usuario): int
    {
        $hasta = $usuario['pin_bloqueado_hasta'] ?? null;
        if (!$hasta) return 0;
        return max(0, strtotime((string)$hasta) - time());
    }

    /**
     * Verifica el PIN de un usuario concreto.
     * Cuenta intentos fallidos y bloquea temporalmente al llegar al máximo.
     *
     * @return array{ok:bool, error?:string, espera?:int}
     */
    public function verificar(int $usuarioId, string $pin, int $intentosMax, int $bloqueoMinutos): array
    {
        $u = $this->queryOne(
            "SELECT id, pin_hash, pin_intentos_fallidos, pin_bloqueado_hasta, activo
               FROM usuarios WHERE id = ? LIMIT 1",
            [$usuarioId]
        );

        if (!$u || !(int)$u['activo']) {
            return ['ok' => false, 'error' => 'Esta cuenta no está activa.'];
        }

        $espera = $this->segundosBloqueo($u);
        if ($espera > 0) {
            return ['ok' => false, 'error' => 'PIN bloqueado temporalmente.', 'espera' => $espera];
        }

        if (empty($u['pin_hash'])) {
            return ['ok' => false, 'error' => 'Este cajero todavía no tiene PIN asignado.'];
        }

        if (!password_verify($pin, (string)$u['pin_hash'])) {
            $fallidos = (int)$u['pin_intentos_fallidos'] + 1;
            if ($fallidos >= $intentosMax) {
                $this->execute(
                    "UPDATE usuarios
                        SET pin_intentos_fallidos = 0,
                            pin_bloqueado_hasta = DATE_ADD(NOW(), INTERVAL ? MINUTE)
                      WHERE id = ?",
                    [$bloqueoMinutos, $usuarioId]
                );
                return [
                    'ok'     => false,
                    'error'  => "Demasiados intentos. PIN bloqueado {$bloqueoMinutos} min.",
                    'espera' => $bloqueoMinutos * 60,
                ];
            }

            $this->execute(
                "UPDATE usuarios SET pin_intentos_fallidos = ? WHERE id = ?",
                [$fallidos, $usuarioId]
            );
            $restantes = $intentosMax - $fallidos;
            return ['ok' => false, 'error' => "PIN incorrecto. Te quedan {$restantes} intento(s)."];
        }

        $this->execute(
            "UPDATE usuarios SET pin_intentos_fallidos = 0, pin_bloqueado_hasta = NULL WHERE id = ?",
            [$usuarioId]
        );
        return ['ok' => true];
    }

    /**
     * Verifica el PIN de CUALQUIER admin del negocio (autorización de
     * descuentos, decisión D9). Devuelve el id del admin que autorizó.
     *
     * Recorre los admins porque el cajero no elige de quién es el PIN:
     * el admin llega, teclea y se va. Son pocos usuarios por negocio.
     */
    public function verificarAdmin(int $restauranteId, string $pin): ?int
    {
        $admins = $this->query(
            "SELECT u.id, u.pin_hash
               FROM usuarios u
               JOIN roles r ON r.id = u.rol_id
              WHERE u.restaurante_id = ? AND u.activo = 1
                AND u.pin_hash IS NOT NULL
                AND r.slug IN ('admin_restaurante','admin_local','superadmin')",
            [$restauranteId]
        );

        foreach ($admins as $admin) {
            if (password_verify($pin, (string)$admin['pin_hash'])) {
                return (int)$admin['id'];
            }
        }
        return null;
    }

    /** ¿Hay al menos un admin con PIN? Si no, no tiene caso ofrecer el modal. */
    public function hayAdminConPin(int $restauranteId): bool
    {
        $row = $this->queryOne(
            "SELECT COUNT(*) AS c
               FROM usuarios u JOIN roles r ON r.id = u.rol_id
              WHERE u.restaurante_id = ? AND u.activo = 1 AND u.pin_hash IS NOT NULL
                AND r.slug IN ('admin_restaurante','admin_local','superadmin')",
            [$restauranteId]
        );
        return (int)($row['c'] ?? 0) > 0;
    }

    /** @return string|null Mensaje de error, o null si el PIN es aceptable. */
    public function validarFormato(string $pin): ?string
    {
        if (!preg_match('/^\d+$/', $pin)) {
            return 'El PIN solo puede tener números.';
        }
        $len = strlen($pin);
        if ($len < self::PIN_MIN || $len > self::PIN_MAX) {
            return 'El PIN debe tener entre ' . self::PIN_MIN . ' y ' . self::PIN_MAX . ' dígitos.';
        }
        if (preg_match('/^(\d)\1+$/', $pin)) {
            return 'Elige un PIN menos obvio (no todos los dígitos iguales).';
        }
        return null;
    }

    public function asignar(int $usuarioId, string $pin): void
    {
        $this->execute(
            "UPDATE usuarios
                SET pin_hash = ?, pin_intentos_fallidos = 0,
                    pin_bloqueado_hasta = NULL, pin_actualizado_at = NOW()
              WHERE id = ?",
            [password_hash($pin, PASSWORD_DEFAULT), $usuarioId]
        );
    }
}
