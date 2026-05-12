<?php
require_once ROOT_PATH . '/app/controllers/BaseController.php';
require_once ROOT_PATH . '/app/services/FacturaloService.php';

class EmpresaFacturaController extends BaseController
{
    private PDO $db;

    public function __construct()
    {
        parent::__construct();
        $this->requireSupervisor(); // admin_empresa + supervisor
        $this->db = Database::getInstance();
    }

    // ── Lista de facturas de la empresa ──────────────────────────────────────
    public function index(?string $p = null): void
    {
        $empresaId = $this->empresaId();
        $page      = max(1, (int)$this->get('page', 1));
        $perPage   = 20;
        $offset    = ($page - 1) * $perPage;

        $stTotal = $this->db->prepare('SELECT COUNT(*) FROM facturas WHERE empresa_id = ?');
        $stTotal->execute([$empresaId]);
        $total = (int)$stTotal->fetchColumn();

        $stmt = $this->db->prepare(
            'SELECT f.*, p.folio AS pedido_folio
               FROM facturas f
               JOIN pedidos p ON p.id = f.pedido_id
              WHERE f.empresa_id = ?
              ORDER BY f.fecha_emision DESC
              LIMIT ? OFFSET ?'
        );
        $stmt->execute([$empresaId, $perPage, $offset]);
        $facturas = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Pedidos entregados sin factura (para generar desde aquí)
        $stPedidos = $this->db->prepare(
            'SELECT p.id, p.folio, p.total, p.created_at
               FROM pedidos p
              WHERE p.empresa_id = ?
                AND p.estado = "entregado"
                AND NOT EXISTS (SELECT 1 FROM facturas f WHERE f.pedido_id = p.id)
              ORDER BY p.created_at DESC
              LIMIT 50'
        );
        $stPedidos->execute([$empresaId]);
        $pedidosSinFactura = $stPedidos->fetchAll(PDO::FETCH_ASSOC);

        $totalPages = $total > 0 ? (int)ceil($total / $perPage) : 1;
        $flash      = $this->getFlash();
        $pageTitle  = 'Facturas';
        $activeMenu = 'facturas';

        ob_start();
        require ROOT_PATH . '/app/views/empresa/facturas/index.php';
        $content = ob_get_clean();
        require ROOT_PATH . '/app/views/empresa/layouts/main.php';
    }

    // ── Generar CFDI para un pedido ──────────────────────────────────────────
    public function generar(?string $pedidoId = null): void
    {
        $this->requireAdminEmpresa();

        $pedidoId  = (int)$pedidoId;
        $empresaId = $this->empresaId();

        if (!$pedidoId) {
            $this->flash('error', 'Pedido no especificado.');
            $this->redirect('empresa-factura/index');
        }

        // Verificar que el pedido pertenece a esta empresa y está entregado
        $stPedido = $this->db->prepare(
            'SELECT id FROM pedidos WHERE id = ? AND empresa_id = ? AND estado = "entregado"'
        );
        $stPedido->execute([$pedidoId, $empresaId]);
        if (!$stPedido->fetch()) {
            $this->flash('error', 'El pedido no existe, no pertenece a tu empresa o no está entregado.');
            $this->redirect('empresa-factura/index');
        }

        // Verificar que no tenga factura ya
        $stExiste = $this->db->prepare('SELECT id FROM facturas WHERE pedido_id = ?');
        $stExiste->execute([$pedidoId]);
        if ($stExiste->fetch()) {
            $this->flash('error', 'Este pedido ya tiene una factura generada.');
            $this->redirect('empresa-factura/index');
        }

        $service  = new FacturaloService();
        $resultado = $service->generarCFDI($pedidoId);

        if (!$resultado['ok']) {
            $this->flash('error', 'Error al timbrar: ' . ($resultado['error'] ?? 'desconocido'));
        } else {
            $this->log('Factura generada', 'facturas', 'Pedido #' . $pedidoId . ' UUID: ' . $resultado['uuid']);
            $this->flash('success', 'Factura timbrada correctamente. UUID: ' . $resultado['uuid']);
        }

        $this->redirect('empresa-factura/index');
    }

    // ── Cancelar CFDI ────────────────────────────────────────────────────────
    public function cancelar(?string $uuid = null): void
    {
        $this->requireAdminEmpresa();

        $uuid      = preg_replace('/[^a-f0-9\-]/i', '', $uuid ?? '');
        $empresaId = $this->empresaId();

        if (!$uuid) {
            $this->flash('error', 'UUID no válido.');
            $this->redirect('empresa-factura/index');
        }

        // Verificar que la factura pertenece a esta empresa
        $stFact = $this->db->prepare(
            'SELECT id FROM facturas WHERE uuid_cfdi = ? AND empresa_id = ?'
        );
        $stFact->execute([$uuid, $empresaId]);
        if (!$stFact->fetch()) {
            $this->flash('error', 'Factura no encontrada.');
            $this->redirect('empresa-factura/index');
        }

        $service = new FacturaloService();
        $ok      = $service->cancelarCFDI($uuid);

        if ($ok) {
            $this->db->prepare(
                'UPDATE facturas SET estado = "cancelada" WHERE uuid_cfdi = ?'
            )->execute([$uuid]);
            $this->log('Factura cancelada', 'facturas', 'UUID: ' . $uuid);
            $this->flash('success', 'Factura cancelada.');
        } else {
            $this->flash('error', 'No se pudo cancelar la factura. Verifica el token de API.');
        }

        $this->redirect('empresa-factura/index');
    }
}
