<?php
/**
 * CarniHub — Base Controller v2.0
 */
abstract class BaseController
{
    protected array $session;

    public function __construct()
    {
        $this->session = $_SESSION;
    }

    // ── Render ────────────────────────────────────────────────────
    protected function render(string $view, array $data = []): void
    {
        extract($data);
        $viewFile = ROOT_PATH . '/app/views/' . $view . '.php';
        if (!file_exists($viewFile)) {
            http_response_code(500);
            die("View not found: $view");
        }
        require $viewFile;
    }

    protected function redirect(string $path): void
    {
        header('Location: ' . BASE_URL . ltrim($path, '/'));
        exit;
    }

    protected function json(mixed $data, int $code = 200): void
    {
        http_response_code($code);
        header('Content-Type: application/json');
        echo json_encode($data);
        exit;
    }

    // ── Redirección por rol ───────────────────────────────────────
    protected function redirectSegunRol(string $rol): void
    {
        match (true) {
            $rol === 'superadmin'                                      => $this->redirect('superadmin/dashboard'),
            $rol === 'admin_restaurante', $rol === 'comprador',
            $rol === 'admin_local'                                     => $this->redirect('restaurante/dashboard'),
            $rol === 'cocina'                                          => $this->redirect('rest-cocina/index'),
            $rol === 'cajero'                                          => $this->redirect('rest-caja/venta'),
            // mesero/chef/barra/portero: roles retirados (modelo marketplace
            // pickup/delivery, sin servicio en mesa) — caen al default.
            // Evita un ciclo /auth/login -> /auth/login cuando una sesion
            // contiene un rol nuevo, eliminado o todavia no desplegado.
            default                                                    => $this->redirect('auth/logout'),
        };
    }

    // ── Protección de acceso ──────────────────────────────────────

    /** Solo superadmin (panel de plataforma) */
    protected function requireAdmin(): void
    {
        $this->requireRole(['superadmin']);
    }

    /** Solo superadmin (configuración global, crear admins) */
    protected function requireSuperAdmin(): void
    {
        $this->requireRole(['superadmin']);
    }

    /** Roles del portal empresa: admin_empresa, supervisor, comprador */
    protected function requireEmpresa(): void
    {
        $this->requireRole(['admin_empresa', 'supervisor', 'comprador']);
    }

    /** Puede hacer pedidos: admin_empresa y comprador */
    protected function requireComprador(): void
    {
        $this->requireRole(['admin_empresa', 'comprador']);
    }

    /** Puede aprobar pedidos: admin_empresa y supervisor */
    protected function requireSupervisor(): void
    {
        $this->requireRole(['admin_empresa', 'supervisor']);
    }

    /** Solo admin_empresa (gestión de su empresa) */
    protected function requireAdminEmpresa(): void
    {
        $this->requireRole(['admin_empresa']);
    }

    /** Solo repartidor */
    protected function requireRepartidor(): void
    {
        $this->requireRole(['repartidor']);
    }

    /** Admin del restaurante (comprador con restaurante activo o admin_restaurante) */
    protected function requireRestaurante(): void
    {
        $this->requireRole(['comprador', 'admin_restaurante', 'admin_local']);
        $rol = $this->rolActual();

        // admin_restaurante: auto-seleccionar el primer restaurante de su empresa
        if ($rol === 'admin_restaurante' && empty($_SESSION['restaurante_activo_id'])) {
            $empresaId = $this->empresaId();
            if ($empresaId) {
                $restModel = new RestauranteModel();
                $rests     = $restModel->getByEmpresa($empresaId);
                if (!empty($rests)) {
                    $_SESSION['restaurante_activo_id'] = (int)$rests[0]['id'];
                }
            }
        }

        // admin_local: auto-seleccionar el restaurante asignado a su usuario
        if ($rol === 'admin_local' && empty($_SESSION['restaurante_activo_id'])) {
            $restId = $_SESSION['usuario']['restaurante_id'] ?? null;
            if ($restId) {
                $_SESSION['restaurante_activo_id'] = (int)$restId;
            }
        }

        $restauranteId = $_SESSION['restaurante_activo_id'] ?? null;
        if (!$restauranteId) {
            if ($rol === 'superadmin') {
                $this->redirect('restaurante/seleccionar');
            }
            if (in_array($rol, ['admin_restaurante', 'admin_local'], true)) {
                // No hay restaurante asociado: cerrar sesión con mensaje.
                $this->flash('error', 'Tu cuenta no tiene ningún restaurante asignado. Contacta al administrador.');
                session_destroy();
                $this->redirect('auth/login');
            }
            $this->redirect('restaurante/seleccionar');
        }
    }

    /** Fija restaurante_activo_id desde el usuario logueado (staff: cajero/cocina). */
    private function autoSeleccionarRestauranteStaff(): void
    {
        if (empty($_SESSION['restaurante_activo_id']) && !empty($_SESSION['usuario']['restaurante_id'])) {
            $_SESSION['restaurante_activo_id'] = (int)$_SESSION['usuario']['restaurante_id'];
        }
    }

    /** Staff: cajero (POS) */
    protected function requireCajero(): void
    {
        $this->requireRole(['cajero', 'admin_restaurante', 'comprador']);
        $this->autoSeleccionarRestauranteStaff();
    }

    /** Staff: cocina (KDS web) */
    protected function requireCocina(): void
    {
        $this->requireRole(['cocina', 'admin_restaurante', 'comprador']);
        $this->autoSeleccionarRestauranteStaff();
    }

    /** Cualquier usuario autenticado */
    protected function requireAuth(): void
    {
        if (empty($_SESSION['usuario'])) {
            $this->redirect('auth/login');
        }
    }

    protected function requireRole(array $roles): void
    {
        if (empty($_SESSION['usuario'])) {
            $this->redirect('auth/login');
        }
        $userRole = $_SESSION['usuario']['rol_slug'] ?? '';
        if (
            $userRole === 'superadmin'
            && count(array_intersect($roles, ['comprador', 'admin_restaurante', 'admin_local'])) > 0
        ) {
            return;
        }
        if (!in_array($userRole, $roles, true)) {
            // Redirigir a su portal correspondiente, no a una 403 genérica
            $this->redirectSegunRol($userRole);
        }
    }

    // ── Helpers de sesión ─────────────────────────────────────────
    protected function rolActual(): string
    {
        return $_SESSION['usuario']['rol_slug'] ?? '';
    }

    protected function usuarioId(): ?int
    {
        return isset($_SESSION['usuario']['id']) ? (int)$_SESSION['usuario']['id'] : null;
    }

    protected function empresaId(): ?int
    {
        if ($this->esSuperAdmin() && isset($_SESSION['empresa_activa_id'])) {
            return (int)$_SESSION['empresa_activa_id'];
        }
        return isset($_SESSION['usuario']['empresa_id'])
            ? (int)$_SESSION['usuario']['empresa_id']
            : null;
    }

    protected function restauranteId(): ?int
    {
        return isset($_SESSION['restaurante_activo_id'])
            ? (int)$_SESSION['restaurante_activo_id']
            : null;
    }

    /**
     * Estado central del canal móvil. Si la migración todavía no fue aplicada,
     * RestauranteModel conserva el comportamiento anterior (habilitado).
     */
    protected function appMovilHabilitada(?int $restauranteId = null): bool
    {
        $restauranteId ??= $this->restauranteId();
        return $restauranteId
            ? (new RestauranteModel())->appMovilHabilitada((int)$restauranteId)
            : false;
    }

    /** Protege módulos web que solo existen para la app móvil. */
    protected function requireAppMovil(): void
    {
        if ($this->appMovilHabilitada()) {
            return;
        }

        $this->flash('warning', 'La app móvil está apagada para este restaurante. Puedes activarla desde Configuración.');
        $this->redirect('rest-config/index');
    }

    protected function esSuperAdmin(): bool
    {
        return $this->rolActual() === 'superadmin';
    }

    protected function esAdminEmpresa(): bool
    {
        return $this->rolActual() === 'admin_empresa';
    }

    /** Primera fecha visible para roles no superadmin; null significa sin filtro. */
    protected function fechaFinancieraVisibleDesde(?int $restauranteId = null): ?string
    {
        $restauranteId ??= $this->restauranteId();
        if (!$restauranteId) {
            return null;
        }

        return (new RestVisibilidadFinancieraModel())->fechaVisibleDesde(
            (int)$restauranteId,
            $this->rolActual()
        );
    }

    protected function ajustarRangoFinancieroVisible(
        string $desde,
        string $hasta,
        ?int $restauranteId = null
    ): array {
        $visibleDesde = $this->fechaFinancieraVisibleDesde($restauranteId);
        if ($visibleDesde && $desde < $visibleDesde) {
            $desde = $visibleDesde;
        }

        return ['desde' => $desde, 'hasta' => $hasta, 'visible_desde' => $visibleDesde];
    }

    // ── HTTP helpers ──────────────────────────────────────────────
    protected function isPost(): bool
    {
        return $_SERVER['REQUEST_METHOD'] === 'POST';
    }

    protected function post(string $key, mixed $default = null): mixed
    {
        return $_POST[$key] ?? $default;
    }

    protected function get(string $key, mixed $default = null): mixed
    {
        return $_GET[$key] ?? $default;
    }

    // ── CSRF ──────────────────────────────────────────────────────
    // Token por sesión, compartido por todos los formularios. Se manda en
    // el campo oculto `_csrf` o en la cabecera `X-CSRF-Token` (peticiones
    // fetch). Comparación con hash_equals para no filtrar el token por
    // diferencias de tiempo.

    protected function csrfToken(): string
    {
        if (empty($_SESSION['_csrf_token'])) {
            $_SESSION['_csrf_token'] = bin2hex(random_bytes(32));
        }
        return (string)$_SESSION['_csrf_token'];
    }

    protected function validarCsrf(?string $recibido = null): bool
    {
        $esperado = (string)($_SESSION['_csrf_token'] ?? '');
        if ($esperado === '') {
            return false;
        }

        $token = $recibido
            ?? $_POST['_csrf']
            ?? $_SERVER['HTTP_X_CSRF_TOKEN']
            ?? '';

        return is_string($token) && $token !== '' && hash_equals($esperado, $token);
    }

    /** Campo oculto listo para pegar dentro de un <form>. */
    protected function csrfInput(): string
    {
        return '<input type="hidden" name="_csrf" value="' . htmlspecialchars($this->csrfToken(), ENT_QUOTES) . '">';
    }

    // ── Flash messages ────────────────────────────────────────────
    protected function flash(string $type, string $message): void
    {
        $_SESSION['flash'] = compact('type', 'message');
    }

    protected function getFlash(): ?array
    {
        $flash = $_SESSION['flash'] ?? null;
        unset($_SESSION['flash']);
        return $flash;
    }

    // ── Auditoría ─────────────────────────────────────────────────
    protected function log(string $accion, string $modulo = '', string $desc = ''): void
    {
        $logModel = new LogModel();
        $logModel->registrar(
            $this->usuarioId(),
            $this->rolActual(),
            $this->empresaId(),
            $accion,
            $modulo,
            $desc
        );
    }
}
