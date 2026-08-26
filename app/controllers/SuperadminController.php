<?php
require_once ROOT_PATH . '/app/controllers/BaseController.php';

class SuperadminController extends BaseController
{
    private RestauranteModel $restModel;
    private LogModel $logModel;
    private PuntoReferenciaModel $puntoModel;

    public function __construct()
    {
        parent::__construct();
        $this->requireSuperAdmin();
        $this->restModel  = new RestauranteModel();
        $this->logModel   = new LogModel();
        $this->puntoModel = new PuntoReferenciaModel();
    }

    public function index(?string $p = null): void
    {
        $this->redirect('superadmin/dashboard');
    }

    public function dashboard(?string $p = null): void
    {
        $resumen    = $this->restModel->getResumenPlataforma();
        $flash      = $this->getFlash();
        $pageTitle  = 'Dashboard';
        $activeMenu = 'sa_dashboard';
        $this->render('superadmin/dashboard/index', compact('resumen', 'flash', 'pageTitle', 'activeMenu'));
    }

    public function negocios(?string $p = null): void
    {
        $negocios   = $this->restModel->getAllParaSuperadmin();
        $flash      = $this->getFlash();
        $pageTitle  = 'Negocios';
        $activeMenu = 'sa_negocios';
        $this->render('superadmin/negocios/index', compact('negocios', 'flash', 'pageTitle', 'activeMenu'));
    }

    public function aprobar(?string $id = null): void
    {
        if (!$this->isPost()) {
            $this->redirect('superadmin/negocios');
        }

        $restauranteId = (int)$id;
        $negocio = $this->restModel->find($restauranteId);
        if (!$negocio) {
            $this->flash('error', 'Negocio no encontrado.');
            $this->redirect('superadmin/negocios');
        }

        $this->restModel->setEstadoPlataforma($restauranteId, 'activo', 1);
        $this->logModel->registrar(
            $this->usuarioId(),
            'superadmin',
            null,
            'Aprobar negocio',
            'superadmin',
            "Negocio #{$restauranteId} ({$negocio['nombre']}) pasó a estado activo"
        );
        $this->flash('success', "Negocio \"{$negocio['nombre']}\" aprobado y activo.");
        $this->redirect('superadmin/negocios');
    }

    public function suspender(?string $id = null): void
    {
        if (!$this->isPost()) {
            $this->redirect('superadmin/negocios');
        }

        $restauranteId = (int)$id;
        $motivo = trim((string)$this->post('motivo', ''));
        $negocio = $this->restModel->find($restauranteId);
        if (!$negocio) {
            $this->flash('error', 'Negocio no encontrado.');
            $this->redirect('superadmin/negocios');
        }

        $this->restModel->setEstadoPlataforma($restauranteId, 'suspendido');
        $this->logModel->registrar(
            $this->usuarioId(),
            'superadmin',
            null,
            'Suspender negocio',
            'superadmin',
            "Negocio #{$restauranteId} ({$negocio['nombre']}) suspendido. Motivo: " . ($motivo !== '' ? $motivo : '(sin motivo)')
        );
        $this->flash('success', "Negocio \"{$negocio['nombre']}\" suspendido.");
        $this->redirect('superadmin/negocios');
    }

    public function puntosReferencia(?string $p = null): void
    {
        $puntos     = $this->puntoModel->getAllConConteo();
        $flash      = $this->getFlash();
        $pageTitle  = 'Puntos de referencia';
        $activeMenu = 'sa_puntos_referencia';
        $this->render('superadmin/puntos-referencia/index', compact('puntos', 'flash', 'pageTitle', 'activeMenu'));
    }

    public function puntoReferenciaGuardar(?string $id = null): void
    {
        if (!$this->isPost()) {
            $this->redirect('superadmin/puntosReferencia');
        }

        $nombre  = trim((string)$this->post('nombre', ''));
        $ciudad  = trim((string)$this->post('ciudad', ''));
        $lat     = $this->post('lat');
        $lng     = $this->post('lng');
        $radioKm = $this->post('radio_km', '2.00');

        if ($nombre === '' || !is_numeric($lat) || !is_numeric($lng)) {
            $this->flash('error', 'Nombre, latitud y longitud son requeridos.');
            $this->redirect('superadmin/puntosReferencia');
        }

        $data = [
            'nombre'   => $nombre,
            'ciudad'   => $ciudad !== '' ? $ciudad : null,
            'lat'      => (float)$lat,
            'lng'      => (float)$lng,
            'radio_km' => is_numeric($radioKm) ? (float)$radioKm : 2.00,
        ];

        $puntoId = (int)$id;
        if ($puntoId > 0) {
            $this->puntoModel->update($puntoId, $data);
            $accion = 'Editar punto de referencia';
        } else {
            $puntoId = $this->puntoModel->insert($data);
            $accion = 'Crear punto de referencia';
        }

        $this->puntoModel->recalcularParaPunto($puntoId);
        $this->logModel->registrar(
            $this->usuarioId(),
            'superadmin',
            null,
            $accion,
            'superadmin',
            "Punto de referencia #{$puntoId} ({$nombre})"
        );
        $this->flash('success', "Punto de referencia \"{$nombre}\" guardado.");
        $this->redirect('superadmin/puntosReferencia');
    }

    public function puntoReferenciaToggle(?string $id = null): void
    {
        if (!$this->isPost()) {
            $this->redirect('superadmin/puntosReferencia');
        }

        $puntoId = (int)$id;
        $punto = $this->puntoModel->find($puntoId);
        if (!$punto) {
            $this->flash('error', 'Punto de referencia no encontrado.');
            $this->redirect('superadmin/puntosReferencia');
        }

        $nuevoEstado = $punto['activo'] ? 0 : 1;
        $this->puntoModel->update($puntoId, ['activo' => $nuevoEstado]);
        $this->puntoModel->recalcularParaPunto($puntoId);
        $this->flash('success', $nuevoEstado ? 'Punto reactivado.' : 'Punto desactivado.');
        $this->redirect('superadmin/puntosReferencia');
    }

    public function usuarios(?string $p = null): void
    {
        $filtros = [
            'restaurante_id' => (int)$this->get('restaurante_id', 0) ?: null,
            'rol_slug'       => (string)$this->get('rol_slug', '') ?: null,
            'buscar'         => trim((string)$this->get('buscar', '')) ?: null,
        ];
        $page = max(1, (int)$this->get('page', 1));

        $usuarioModel = new UsuarioModel();
        $resultado    = $usuarioModel->listadoParaSuperadmin($filtros, $page);
        $negocios     = $this->restModel->getAllConEmpresa();
        $roles        = Database::getInstance()->query('SELECT * FROM roles ORDER BY id')->fetchAll(PDO::FETCH_ASSOC);

        $flash      = $this->getFlash();
        $pageTitle  = 'Usuarios';
        $activeMenu = 'sa_usuarios';
        $this->render('superadmin/usuarios/index', compact('resultado', 'negocios', 'roles', 'filtros', 'flash', 'pageTitle', 'activeMenu'));
    }

    public function usuarioCrear(?string $p = null): void
    {
        if (!$this->isPost()) {
            $this->redirect('superadmin/usuarios');
        }

        $nombre        = trim((string)$this->post('nombre', ''));
        $email         = strtolower(trim((string)$this->post('email', '')));
        $rolSlug       = (string)$this->post('rol_slug', '');
        $restauranteId = (int)$this->post('restaurante_id', 0) ?: null;

        if ($nombre === '' || $email === '' || $rolSlug === '') {
            $this->flash('error', 'Nombre, correo y rol son requeridos.');
            $this->redirect('superadmin/usuarios');
        }

        $usuarioModel = new UsuarioModel();
        if ($usuarioModel->existeEmail($email)) {
            $this->flash('error', 'Ya existe un usuario con ese correo.');
            $this->redirect('superadmin/usuarios');
        }

        $rol = $usuarioModel->getRolPorSlug($rolSlug);
        if (!$rol) {
            $this->flash('error', 'Rol inválido.');
            $this->redirect('superadmin/usuarios');
        }

        if (in_array($rolSlug, ['admin_restaurante', 'cajero', 'cocina'], true) && !$restauranteId) {
            $this->flash('error', 'Selecciona el negocio para este rol.');
            $this->redirect('superadmin/usuarios');
        }

        $negocio   = $restauranteId ? $this->restModel->find($restauranteId) : null;
        $empresaId = $negocio['empresa_id'] ?? null;

        $partes       = preg_split('/\s+/', $nombre, 3);
        $primerNombre = $partes[0] ?? $nombre;
        $apPaterno    = $partes[1] ?? '';
        $apMaterno    = $partes[2] ?? null;

        $passwordTemporal = bin2hex(random_bytes(5));
        $usuarioId = $usuarioModel->crear([
            'nombre'                  => $primerNombre,
            'apellido_paterno'        => $apPaterno,
            'apellido_materno'        => $apMaterno,
            'email'                   => $email,
            'email_verificado'        => 1,
            'primer_login_completado' => 0,
            'rol_id'                  => $rol['id'],
            'empresa_id'              => $empresaId,
            'restaurante_id'          => $restauranteId,
            'restaurante_activo'      => $restauranteId ? 1 : 0,
            'activo'                  => 1,
        ], $passwordTemporal);

        $this->logModel->registrar(
            $this->usuarioId(),
            'superadmin',
            $empresaId,
            'Crear usuario',
            'superadmin',
            "Usuario #{$usuarioId} ({$email}, rol {$rolSlug})"
        );
        $this->flash('success', "Usuario creado: {$email} · Password temporal: {$passwordTemporal}");
        $this->redirect('superadmin/usuarios');
    }

    public function usuarioToggle(?string $id = null): void
    {
        if (!$this->isPost()) {
            $this->redirect('superadmin/usuarios');
        }

        $usuarioModel = new UsuarioModel();
        $usuario = $usuarioModel->find((int)$id);
        if (!$usuario) {
            $this->flash('error', 'Usuario no encontrado.');
            $this->redirect('superadmin/usuarios');
        }

        $nuevoEstado = $usuario['activo'] ? 0 : 1;
        $usuarioModel->update((int)$id, ['activo' => $nuevoEstado]);
        $this->logModel->registrar(
            $this->usuarioId(),
            'superadmin',
            $usuario['empresa_id'],
            $nuevoEstado ? 'Reactivar usuario' : 'Desactivar usuario',
            'superadmin',
            "Usuario #{$id} ({$usuario['email']})"
        );
        $this->flash('success', $nuevoEstado ? 'Usuario reactivado.' : 'Usuario desactivado.');
        $this->redirect('superadmin/usuarios');
    }

    public function usuarioResetPassword(?string $id = null): void
    {
        if (!$this->isPost()) {
            $this->redirect('superadmin/usuarios');
        }

        $usuarioModel = new UsuarioModel();
        $usuario = $usuarioModel->find((int)$id);
        if (!$usuario) {
            $this->flash('error', 'Usuario no encontrado.');
            $this->redirect('superadmin/usuarios');
        }

        $passwordTemporal = bin2hex(random_bytes(5));
        $usuarioModel->actualizarPassword((int)$id, $passwordTemporal);
        $this->logModel->registrar(
            $this->usuarioId(),
            'superadmin',
            $usuario['empresa_id'],
            'Resetear password',
            'superadmin',
            "Usuario #{$id} ({$usuario['email']})"
        );
        $this->flash('success', "Password restablecida para {$usuario['email']}: {$passwordTemporal}");
        $this->redirect('superadmin/usuarios');
    }
}
