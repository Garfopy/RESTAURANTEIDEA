<?php
require_once ROOT_PATH . '/app/controllers/BaseController.php';

class RestStaffController extends BaseController
{
    private RestauranteModel $restModel;

    public function __construct()
    {
        parent::__construct();
        $this->requireRestaurante();
        $this->restModel = new RestauranteModel();
    }

    public function index(?string $p = null): void
    {
        $restauranteId = $this->restauranteId();
        $db            = Database::getInstance();

        $staff = $db->query(
            "SELECT u.id, u.nombre, u.email, u.activo,
                    r.nombre AS rol_nombre, r.slug AS rol_slug,
                    rs.codigo, rs.fecha_ingreso, rs.activo AS staff_activo
             FROM rest_staff rs
             JOIN usuarios u ON u.id = rs.usuario_id
             JOIN roles r ON r.id = u.rol_id
             WHERE rs.restaurante_id = ?
             ORDER BY r.slug, u.nombre",
            [$restauranteId]
        );

        $restaurante = $this->restModel->find($restauranteId);
        $flash       = $this->getFlash();
        $pageTitle   = 'Gestión de Staff';
        $activeMenu  = 'rest_staff';
        $this->render('restaurante/staff/index', compact('staff','restaurante','flash','pageTitle','activeMenu'));
    }

    public function crear(?string $p = null): void
    {
        if (!$this->isPost()) $this->redirect('rest-staff/index');

        $restauranteId = $this->restauranteId();
        $db            = Database::getInstance();

        $nombre   = trim($this->post('nombre', ''));
        $email    = strtolower(trim($this->post('email', '')));
        $password = $this->post('password', '');
        $rolSlug  = $this->post('rol_slug', 'mesero');
        $codigo   = strtoupper(trim($this->post('codigo', '')));

        if (!$nombre || !$email || !$password) {
            $this->flash('error', 'Nombre, email y contraseña son requeridos.');
            $this->redirect('rest-staff/index');
        }

        // Verificar que el rol sea válido
        if (!in_array($rolSlug, ['mesero','chef','portero'], true)) {
            $this->flash('error', 'Rol inválido.');
            $this->redirect('rest-staff/index');
        }

        // Verificar que el email no exista
        $existe = $db->queryOne("SELECT id FROM usuarios WHERE email = ?", [$email]);
        if ($existe) {
            $this->flash('error', 'Ya existe un usuario con ese correo.');
            $this->redirect('rest-staff/index');
        }

        $restaurante = $this->restModel->find($restauranteId);
        $empresaId   = $restaurante['empresa_id'] ?? null;

        // Obtener rol_id
        $rol = $db->queryOne("SELECT id FROM roles WHERE slug = ?", [$rolSlug]);
        if (!$rol) {
            $this->flash('error', 'Rol no encontrado. Asegúrate de correr migration 025.');
            $this->redirect('rest-staff/index');
        }

        // Crear usuario
        $hash   = password_hash($password, PASSWORD_DEFAULT);
        $db->execute(
            "INSERT INTO usuarios (nombre, email, password, rol_id, empresa_id, restaurante_id, activo, created_at)
             VALUES (?,?,?,?,?,?,1,NOW())",
            [$nombre, $email, $hash, $rol['id'], $empresaId, $restauranteId]
        );
        $usuarioId = (int)$db->lastInsertId();

        // Crear entrada en rest_staff
        if (!$codigo) {
            $prefix = ['mesero'=>'ME','chef'=>'CH','portero'=>'PT'][$rolSlug] ?? 'ST';
            $codigo = $prefix . str_pad($usuarioId, 3, '0', STR_PAD_LEFT);
        }
        $db->execute(
            "INSERT INTO rest_staff (restaurante_id, usuario_id, codigo, rol_slug, activo, fecha_ingreso, created_at)
             VALUES (?,?,?,?,1,CURDATE(),NOW())",
            [$restauranteId, $usuarioId, $codigo, $rolSlug]
        );

        $this->flash('success', "Staff creado: $nombre ($rolSlug). Código: $codigo");
        $this->redirect('rest-staff/index');
    }

    public function desactivar(?string $id = null): void
    {
        $db = Database::getInstance();
        $db->execute("UPDATE rest_staff SET activo = 0 WHERE usuario_id = ?", [(int)$id]);
        $db->execute("UPDATE usuarios SET activo = 0 WHERE id = ?", [(int)$id]);
        $this->flash('success', 'Staff desactivado.');
        $this->redirect('rest-staff/index');
    }
}
