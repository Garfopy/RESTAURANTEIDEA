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
            $rol === 'repartidor'                                                              => $this->redirect('repartidor/inicio'),
            $rol === 'supervisor'                                                               => $this->redirect('supervisor/dashboard'),
            in_array($rol, ['comprador', 'admin_empresa'], true)                               => $this->redirect('empresa/dashboard'),
            in_array($rol, ['superadmin', 'admin'], true)                                     => $this->redirect('panel/dashboard'),
            default                                                                            => $this->redirect('auth/login'),
        };
    }

    // ── Protección de acceso ──────────────────────────────────────

    /** Solo superadmin y admin de plataforma */
    protected function requireAdmin(): void
    {
        $this->requireRole(['superadmin', 'admin']);
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
        return isset($_SESSION['usuario']['empresa_id'])
            ? (int)$_SESSION['usuario']['empresa_id']
            : null;
    }

    protected function esSuperAdmin(): bool
    {
        return $this->rolActual() === 'superadmin';
    }

    protected function esAdminEmpresa(): bool
    {
        return $this->rolActual() === 'admin_empresa';
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

    // ── Suscripción ───────────────────────────────────────────────

    protected function requireSuscripcionActiva(): void
    {
        $empresaId = $this->empresaId();
        if (!$empresaId) return;

        $stmt = Database::getInstance()->prepare(
            'SELECT suscripcion_estado FROM empresas WHERE id = ?'
        );
        $stmt->execute([$empresaId]);
        $estado = $stmt->fetchColumn();

        if ($estado !== 'activo') {
            $this->redirect('empresa-suscripcion/suspendida');
        }
    }

    protected function getPlanActual(): ?array
    {
        $empresaId = $this->empresaId();
        if (!$empresaId) return null;
        $model = new SuscripcionModel();
        return $model->getByEmpresa($empresaId);
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
