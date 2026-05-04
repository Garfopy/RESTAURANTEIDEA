<?php
require_once ROOT_PATH . '/app/controllers/BaseController.php';

/**
 * PedidoController — Gestión de pedidos desde el portal empresa.
 *
 * Todos los roles empresa pueden ver pedidos.
 * Solo comprador/admin_empresa pueden crear (via CarritoController).
 * Solo supervisor/admin_empresa pueden aprobar.
 */
class PedidoController extends BaseController
{
    private PedidoModel $model;

    public function __construct()
    {
        parent::__construct();
        $this->requireEmpresa();
        $this->model = new PedidoModel();
    }

    // ── Historial de pedidos ──────────────────────────────────────
    public function index(?string $p = null): void
    {
        $filtros = [
            'estado' => $this->get('estado', ''),
            'buscar' => $this->get('buscar', ''),
        ];
        $page = max(1, (int)$this->get('page', 1));

        $resultado  = $this->model->listadoEmpresa($this->empresaId(), $filtros, $page);
        $pedidos    = $resultado['data'];
        $paginacion = $resultado;

        $flash      = $this->getFlash();
        $pageTitle  = 'Mis pedidos';
        $activeMenu = 'pedidos';

        ob_start();
        require ROOT_PATH . '/app/views/empresa/pedidos/index.php';
        $content = ob_get_clean();
        require ROOT_PATH . '/app/views/empresa/layouts/main.php';
    }

    // ── Detalle de un pedido ──────────────────────────────────────
    public function detalle(?string $id = null): void
    {
        $pedidoId = (int)$id;
        if (!$pedidoId || !$this->model->verificarPertenece($pedidoId, $this->empresaId())) {
            $this->flash('error', 'Pedido no encontrado.');
            $this->redirect('pedido/index');
        }

        $pedido = $this->model->conDetalle($pedidoId);

        $flash      = $this->getFlash();
        $pageTitle  = 'Pedido ' . $pedido['folio'];
        $activeMenu = 'pedidos';

        ob_start();
        require ROOT_PATH . '/app/views/empresa/pedidos/detalle.php';
        $content = ob_get_clean();
        require ROOT_PATH . '/app/views/empresa/layouts/main.php';
    }

    // ── Vista de aprobación (supervisor + admin_empresa) ──────────
    public function aprobacion(?string $p = null): void
    {
        $this->requireSupervisor();

        $pendientes = $this->model->pendientesAprobacion($this->empresaId());

        $flash      = $this->getFlash();
        $pageTitle  = 'Aprobaciones pendientes';
        $activeMenu = 'aprobacion';

        ob_start();
        require ROOT_PATH . '/app/views/empresa/pedidos/aprobacion.php';
        $content = ob_get_clean();
        require ROOT_PATH . '/app/views/empresa/layouts/main.php';
    }

    // ── Aprobar pedido ────────────────────────────────────────────
    public function aprobar(?string $id = null): void
    {
        $this->requireSupervisor();

        $pedidoId = (int)$id;
        if (!$pedidoId || !$this->model->verificarPertenece($pedidoId, $this->empresaId())) {
            $this->flash('error', 'Pedido no encontrado.');
            $this->redirect('pedido/aprobacion');
        }

        $ok = $this->model->aprobar($pedidoId, $this->usuarioId());
        if ($ok) {
            $pedido = $this->model->find($pedidoId);
            $this->log('aprobar_pedido', 'pedidos', "Aprobado {$pedido['folio']}");
            $this->flash('success', 'Pedido aprobado correctamente.');
        } else {
            $this->flash('error', 'No se pudo aprobar. Verifica que el pedido esté pendiente.');
        }

        $this->redirect('pedido/aprobacion');
    }

    // ── Rechazar pedido ───────────────────────────────────────────
    public function rechazar(?string $id = null): void
    {
        $this->requireSupervisor();

        if (!$this->isPost()) {
            $this->redirect('pedido/aprobacion');
        }

        $pedidoId = (int)$id;
        $motivo   = trim($this->post('motivo', ''));

        if (!$pedidoId || !$this->model->verificarPertenece($pedidoId, $this->empresaId())) {
            $this->flash('error', 'Pedido no encontrado.');
            $this->redirect('pedido/aprobacion');
        }

        if (empty($motivo)) {
            $this->flash('error', 'Debes indicar el motivo del rechazo.');
            $this->redirect('pedido/aprobacion');
        }

        $ok = $this->model->rechazar($pedidoId, $this->usuarioId(), $motivo);
        if ($ok) {
            $pedido = $this->model->find($pedidoId);
            $this->log('rechazar_pedido', 'pedidos', "Rechazado {$pedido['folio']}: {$motivo}");
            $this->flash('success', 'Pedido rechazado.');
        } else {
            $this->flash('error', 'No se pudo rechazar el pedido.');
        }

        $this->redirect('pedido/aprobacion');
    }

    // ── Tracking GPS en tiempo real ───────────────────────────────
    public function tracking(?string $id = null): void
    {
        $pedidoId = (int)$id;
        if (!$pedidoId || !$this->model->verificarPertenece($pedidoId, $this->empresaId())) {
            $this->flash('error', 'Pedido no encontrado.');
            $this->redirect('pedido/index');
        }

        $pedido   = $this->model->conDetalle($pedidoId);
        $tracking = $this->model->getTrackingActivo($pedidoId);

        $flash      = $this->getFlash();
        $pageTitle  = 'Seguimiento — ' . $pedido['folio'];
        $activeMenu = 'pedidos';

        ob_start();
        require ROOT_PATH . '/app/views/empresa/pedidos/tracking.php';
        $content = ob_get_clean();
        require ROOT_PATH . '/app/views/empresa/layouts/main.php';
    }

    // ── Cancelar pedido (solo comprador, solo si está pendiente) ──
    public function cancelar(?string $id = null): void
    {
        $this->requireComprador();

        $pedidoId = (int)$id;
        if (!$pedidoId || !$this->model->verificarPertenece($pedidoId, $this->empresaId())) {
            $this->flash('error', 'Pedido no encontrado.');
            $this->redirect('pedido/index');
        }

        $ok = $this->model->cancelar($pedidoId, $this->usuarioId());
        $this->flash($ok ? 'success' : 'error', $ok ? 'Pedido cancelado.' : 'No se puede cancelar este pedido.');
        $this->redirect('pedido/detalle/' . $pedidoId);
    }
}
