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

    // GET /acceso/{slug} - formulario comensal (nombre + email)
    public function index(?string $slug = null): void
    {
        $restaurante = $slug ? $this->restModel->getBySlug($slug) : null;
        $flash       = $this->getFlash();
        $pageTitle   = ($restaurante['nombre'] ?? 'CarniHub') . ' - Identificacion';
        $returnParam = trim($this->get('return', ''));
        $this->render('staff/login', compact('restaurante', 'flash', 'pageTitle', 'slug', 'returnParam'));
    }

    // GET /acceso/{slug}/staff - compatibilidad: staff usa el login unico.
    public function staff(?string $slug = null): void
    {
        $this->redirect('auth/login');
    }

    // POST /acceso/{slug}
    // POST /acceso/{slug}/entrarComensal
    public function entrarComensal(?string $slug = null): void
    {
        if (!$this->isPost()) {
            $this->redirect('acceso/' . $slug);
        }

        $restaurante = $slug ? $this->restModel->getBySlug($slug) : null;
        if (!$restaurante) {
            $this->redirect('acceso/' . $slug);
        }

        $nombre = trim($this->post('nombre', ''));
        $email  = mb_strtolower(trim($this->post('email', '')));

        if (!$nombre || !$email) {
            $this->flash('error', 'Ingresa tu nombre y correo para continuar.');
            $this->redirect('acceso/' . $slug);
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->flash('error', 'Por favor ingresa un correo valido.');
            $this->redirect('acceso/' . $slug);
        }

        $clienteModel = new RestClienteModel();
        $comensalId   = $clienteModel->buscarOCrear((int)$restaurante['id'], $nombre, null, $email);

        setcookie(
            'comensal_' . $restaurante['id'],
            json_encode(['id' => $comensalId, 'nombre' => $nombre, 'email' => $email]),
            time() + 30 * 24 * 3600,
            '/'
        );

        $return = trim($this->post('return_url', ''));
        if ($return && str_starts_with($return, 'menu/')) {
            $this->redirect($return);
            return;
        }

        $this->redirect('menu/' . $slug);
    }

    // POST /acceso/{slug}/login - compatibilidad con formularios viejos.
    public function login(?string $slug = null): void
    {
        $this->redirect('auth/login');
    }
}
