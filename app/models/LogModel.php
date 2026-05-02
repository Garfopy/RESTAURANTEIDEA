<?php
class LogModel extends BaseModel
{
    protected string $table = 'action_logs';

    public function registrar(
        ?int $usuarioId,
        string $accion,
        string $modulo = '',
        string $descripcion = ''
    ): void {
        $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
        $ua = $_SERVER['HTTP_USER_AGENT'] ?? '';
        $this->execute(
            'INSERT INTO action_logs (usuario_id, accion, modulo, descripcion, ip, user_agent)
             VALUES (?, ?, ?, ?, ?, ?)',
            [$usuarioId, $accion, $modulo, $descripcion, $ip, substr($ua, 0, 255)]
        );
    }

    public function registrarError(string $nivel, string $mensaje, string $archivo = '', int $linea = 0, string $trace = ''): void
    {
        $this->execute(
            'INSERT INTO error_logs (nivel, mensaje, archivo, linea, trace)
             VALUES (?, ?, ?, ?, ?)',
            [$nivel, $mensaje, $archivo, $linea, $trace]
        );
    }

    public function getBitacora(array $filtros = [], int $page = 1): array
    {
        $where  = ['1=1'];
        $params = [];

        if (!empty($filtros['modulo'])) {
            $where[]  = 'al.modulo = ?';
            $params[] = $filtros['modulo'];
        }
        if (!empty($filtros['usuario_id'])) {
            $where[]  = 'al.usuario_id = ?';
            $params[] = $filtros['usuario_id'];
        }
        if (!empty($filtros['fecha'])) {
            $where[]  = 'DATE(al.created_at) = ?';
            $params[] = $filtros['fecha'];
        }

        $sqlWhere = 'WHERE ' . implode(' AND ', $where);
        $sql = "SELECT al.*, u.nombre AS usuario_nombre, u.email
                  FROM action_logs al
             LEFT JOIN usuarios u ON u.id = al.usuario_id
                  $sqlWhere
              ORDER BY al.created_at DESC";
        return $this->paginate($sql, $params, $page);
    }

    public function getErrores(int $page = 1): array
    {
        $sql = 'SELECT * FROM error_logs ORDER BY created_at DESC';
        return $this->paginate($sql, [], $page);
    }
}
