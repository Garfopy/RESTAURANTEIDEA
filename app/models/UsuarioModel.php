<?php
class UsuarioModel extends BaseModel
{
    protected string $table = 'usuarios';

    public function getByEmail(string $email): ?array
    {
        return $this->queryOne(
            'SELECT u.*, r.slug AS rol_slug, r.nombre AS rol_nombre
               FROM usuarios u
               JOIN roles r ON r.id = u.rol_id
              WHERE u.email = ? AND u.activo = 1',
            [$email]
        );
    }

    public function getByRol(int $rolId): array
    {
        return $this->query(
            'SELECT * FROM usuarios WHERE rol_id = ? AND activo = 1 ORDER BY nombre',
            [$rolId]
        );
    }

    public function getChoferes(): array
    {
        return $this->query(
            'SELECT u.*, c.id AS chofer_id, c.calificacion, v.placa, v.marca, v.modelo
               FROM usuarios u
               JOIN roles r    ON r.id = u.rol_id AND r.slug = "repartidor"
          LEFT JOIN choferes c ON c.usuario_id = u.id
          LEFT JOIN vehiculos v ON v.id = c.vehiculo_id
              WHERE u.activo = 1
           ORDER BY u.nombre'
        );
    }

    public function getAll(int $page = 1): array
    {
        $sql = 'SELECT u.*, r.nombre AS rol_nombre, e.razon_social AS empresa_nombre
                  FROM usuarios u
                  JOIN roles r ON r.id = u.rol_id
             LEFT JOIN empresas e ON e.id = u.empresa_id
              ORDER BY u.created_at DESC';
        return $this->paginate($sql, [], $page);
    }

    public function search(string $q, int $page = 1): array
    {
        $like = "%$q%";
        $sql  = 'SELECT u.*, r.nombre AS rol_nombre, e.razon_social AS empresa_nombre
                   FROM usuarios u
                   JOIN roles r ON r.id = u.rol_id
              LEFT JOIN empresas e ON e.id = u.empresa_id
                  WHERE u.nombre LIKE ? OR u.email LIKE ?
               ORDER BY u.created_at DESC';
        return $this->paginate($sql, [$like, $like], $page);
    }

    public function updatePassword(int $id, string $hash): bool
    {
        return $this->execute('UPDATE usuarios SET password = ? WHERE id = ?', [$hash, $id]);
    }
}
