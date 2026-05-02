<?php
/**
 * CarniHub — Base Controller
 */
abstract class BaseController
{
    protected array $session;

    public function __construct()
    {
        $this->session = $_SESSION;
    }

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

    protected function requireRole(array $roles): void
    {
        $userRole = $_SESSION['usuario']['rol_slug'] ?? '';
        if (!in_array($userRole, $roles, true)) {
            $this->redirect('dashboard/index');
        }
    }

    protected function requireAdmin(): void
    {
        $this->requireRole(['superadmin', 'admin']);
    }

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

    protected function empresaIdActual(): ?int
    {
        return $_SESSION['usuario']['empresa_id'] ?? null;
    }

    protected function log(string $accion, string $modulo = '', string $desc = ''): void
    {
        $logModel = new LogModel();
        $logModel->registrar(
            $_SESSION['usuario']['id'] ?? null,
            $accion, $modulo, $desc
        );
    }
}
