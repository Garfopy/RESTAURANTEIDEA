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

        $stmt = $db->prepare(
            "SELECT u.id, u.nombre, u.email, u.activo,
                    r.nombre AS rol_nombre, r.slug AS rol_slug,
                    rs.codigo, rs.fecha_ingreso, rs.activo AS staff_activo
             FROM rest_staff rs
             JOIN usuarios u ON u.id = rs.usuario_id
             JOIN roles r    ON r.id = u.rol_id
             WHERE rs.restaurante_id = ?
             ORDER BY r.slug, u.nombre"
        );
        $stmt->execute([$restauranteId]);
        $staff = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $restaurante = $this->restModel->find($restauranteId);
        $linkAcceso  = BASE_URL . 'acceso/' . $restaurante['slug'];
        $flash       = $this->getFlash();
        $pageTitle   = 'Gestión de Staff';
        $activeMenu  = 'rest_staff';
        $this->render('restaurante/staff/index',
            compact('staff','restaurante','linkAcceso','flash','pageTitle','activeMenu'));
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
        if (!in_array($rolSlug, ['mesero','chef','portero'], true)) {
            $this->flash('error', 'Rol inválido.');
            $this->redirect('rest-staff/index');
        }

        $st = $db->prepare("SELECT id FROM usuarios WHERE email = ? LIMIT 1");
        $st->execute([$email]);
        if ($st->fetch()) {
            $this->flash('error', 'Ya existe un usuario con ese correo.');
            $this->redirect('rest-staff/index');
        }

        $restaurante = $this->restModel->find($restauranteId);
        $empresaId   = $restaurante['empresa_id'] ?? null;

        $st = $db->prepare("SELECT id FROM roles WHERE slug = ? LIMIT 1");
        $st->execute([$rolSlug]);
        $rol = $st->fetch(PDO::FETCH_ASSOC);
        if (!$rol) {
            $this->flash('error', 'Rol no encontrado. Asegúrate de correr migration 025.');
            $this->redirect('rest-staff/index');
        }

        $hash = password_hash($password, PASSWORD_DEFAULT);
        $partes = preg_split('/\s+/', $nombre, 3);
        $primerNombre = $partes[0] ?? $nombre;
        $apPat = $partes[1] ?? 'Staff';
        $apMat = $partes[2] ?? null;

        $ins = $db->prepare(
            "INSERT INTO usuarios
               (nombre, apellido_paterno, apellido_materno, email, email_verificado,
                primer_login_completado, password, rol_id, empresa_id, restaurante_id, activo, created_at)
             VALUES (?,?,?,?,1,1,?,?,?,?,1,NOW())"
        );
        $ins->execute([$primerNombre, $apPat, $apMat, $email, $hash, $rol['id'], $empresaId, $restauranteId]);
        $usuarioId = (int)$db->lastInsertId();

        if (!$codigo) {
            $prefix = ['mesero'=>'ME','chef'=>'CH','portero'=>'PT'][$rolSlug] ?? 'ST';
            $codigo = $prefix . str_pad((string)$usuarioId, 3, '0', STR_PAD_LEFT);
        }
        $ins2 = $db->prepare(
            "INSERT INTO rest_staff
               (restaurante_id, usuario_id, codigo, rol_slug, activo, fecha_ingreso, created_at)
             VALUES (?,?,?,?,1,CURDATE(),NOW())"
        );
        $ins2->execute([$restauranteId, $usuarioId, $codigo, $rolSlug]);

        $this->flash('success', "Staff creado: $primerNombre ($rolSlug). Código: $codigo · Email: $email");
        $this->redirect('rest-staff/index');
    }

    public function desactivar(?string $id = null): void
    {
        $db  = Database::getInstance();
        $uid = (int)$id;
        $db->prepare("UPDATE rest_staff SET activo = 0 WHERE usuario_id = ?")->execute([$uid]);
        $db->prepare("UPDATE usuarios   SET activo = 0 WHERE id         = ?")->execute([$uid]);
        $this->flash('success', 'Staff desactivado.');
        $this->redirect('rest-staff/index');
    }
}
