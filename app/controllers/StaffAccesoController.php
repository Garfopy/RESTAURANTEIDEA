<?php
require_once ROOT_PATH . '/app/controllers/BaseController.php';

class StaffAccesoController extends BaseController
{
    private RestauranteModel $restModel;

    public function __construct()
    {
        parent::__construct();
        $this->restModel = new RestauranteModel();
    }

    // GET /acceso/{slug}
    public function index(?string $slug = null): void
    {
        if (isset($_SESSION['usuario'])) {
            $rol = $_SESSION['usuario']['rol_slug'] ?? '';
            // Solo redirigir automáticamente a staff — comprador/admin puede ver la página para compartirla
            if (in_array($rol, ['mesero', 'chef', 'portero'])) {
                $this->redirectSegunRol($rol);
            }
        }
        $restaurante = $slug ? $this->restModel->getBySlug($slug) : null;
        $flash       = $this->getFlash();
        $pageTitle   = 'Acceso Staff — ' . ($restaurante['nombre'] ?? 'CarniHub');
        $this->render('staff/login', compact('restaurante', 'flash', 'pageTitle', 'slug'));
    }

    // POST /acceso/{slug}
    // POST /acceso/{slug}/entrarComensal
    public function entrarComensal(?string $slug = null): void
    {
        if (!$this->isPost()) {
            $this->redirect('acceso/' . $slug);
        }
        $restaurante = $slug ? $this->restModel->getBySlug($slug) : null;
        if (!$restaurante) { $this->redirect('acceso/' . $slug); }

        $nombre   = trim($this->post('nombre', ''));
        $telefono = trim($this->post('telefono', '')) ?: null;
        if (!$nombre) {
            $this->flash('error', 'Ingresa tu nombre para continuar.');
            $this->redirect('acceso/' . $slug);
        }

        $clienteModel = new RestClienteModel();
        $comensalId   = $clienteModel->buscarOCrear((int)$restaurante['id'], $nombre, $telefono, null);

        setcookie(
            'comensal_' . $restaurante['id'],
            json_encode(['id' => $comensalId, 'nombre' => $nombre]),
            time() + 4 * 3600, '/'
        );

        $this->redirect('menu/' . $slug);
    }

    public function login(?string $slug = null): void
    {
        if (!$this->isPost()) {
            $this->redirect('acceso/' . $slug);
        }

        $email    = trim($this->post('email', ''));
        $password = $this->post('password', '');
        $restaurante = $slug ? $this->restModel->getBySlug($slug) : null;

        if (!$email || !$password) {
            $this->flash('error', 'Completa todos los campos.');
            $this->redirect('acceso/' . $slug);
        }

        $db   = Database::getInstance();
        $stmt = $db->prepare(
            "SELECT u.*, r.slug AS rol_slug, r.nombre AS rol_nombre
             FROM usuarios u
             JOIN roles r ON r.id = u.rol_id
             JOIN rest_staff rs ON rs.usuario_id = u.id
             WHERE u.email = ? AND u.activo = 1
               AND r.slug IN ('mesero','chef','portero')
               AND rs.activo = 1
               AND rs.restaurante_id = ?"
        );
        $stmt->execute([$email, (int)($restaurante['id'] ?? 0)]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC) ?: null;

        if (!$user || !password_verify($password, $user['password'])) {
            $this->flash('error', 'Credenciales incorrectas o no tienes acceso a este restaurante.');
            $this->redirect('acceso/' . $slug);
        }

        $_SESSION['usuario'] = [
            'id'           => $user['id'],
            'nombre'       => $user['nombre'],
            'email'        => $user['email'],
            'rol_id'       => $user['rol_id'],
            'rol_slug'     => $user['rol_slug'],
            'empresa_id'   => $user['empresa_id'] ?? null,
            'restaurante_id' => $restaurante['id'] ?? null,
        ];
        $_SESSION['restaurante_activo_id'] = $restaurante['id'] ?? null;

        $this->redirectSegunRol($user['rol_slug']);
    }
}
