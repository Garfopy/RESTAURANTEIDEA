<?php
require_once ROOT_PATH . '/app/controllers/BaseController.php';

class CompradorController extends BaseController
{
    public function __construct()
    {
        parent::__construct();
        $this->requireRole(['comprador']);
    }

    public function inicio(?string $p = null): void
    {
        $usuario     = $_SESSION['usuario'] ?? [];
        $empresaId   = (int)($usuario['empresa_id'] ?? 0);
        $compradorId = (int)($usuario['id'] ?? 0);
        $pedidoModel = new PedidoModel();

        $ultimosPedidos = $pedidoModel->getUltimosPedidosComprador($compradorId, $empresaId, 5);
        $enRuta         = $pedidoModel->getPedidosEnRuta($compradorId, $empresaId, 3);

        $productoModel  = new ProductoModel();
        $destacados     = $productoModel->listadoConPrecio(['activo' => 1], 1)['data'] ?? [];

        // ── KPIs del Comprador ─────────────────────────────────────────────
        $hoy   = date('Y-m-d');
        $desde = date('Y-m-d', strtotime('-29 days'));
        $hasta = $hoy;

        $kgMes              = $pedidoModel->kgTotalesMes($compradorId, $empresaId);
        $gastoMes           = $pedidoModel->gastoMesComprador($compradorId, $empresaId);
        $enTransitoGps      = $pedidoModel->pedidosEnTransitoConGps($compradorId, $empresaId);
        $ahorroVolumen      = $pedidoModel->ahorroPorVolumen($compradorId, $empresaId, $desde, $hasta);
        $recurrentesActivos = $pedidoModel->recurrentesActivos($empresaId);
        $proximaEntrega     = $pedidoModel->proximaEntregaComprador($compradorId, $empresaId);
        $topProducto        = $pedidoModel->topProductoComprador($compradorId, $empresaId, $desde, $hasta);
        $consumoCategoria   = $pedidoModel->consumoPorCategoriaComprador($compradorId, $empresaId, $desde, $hasta);
        $gastoSemanal       = $pedidoModel->gastoSemanalComprador($compradorId, $empresaId, 8);

        // Presupuesto = suma de límites mensuales activos de la empresa (referencia)
        $presupuesto = 0.0;
        try {
            $db = Database::getInstance();
            $stmt = $db->prepare(
                "SELECT COALESCE(SUM(limite_monto),0) AS p FROM limites_compra
                  WHERE empresa_id = ? AND activo = 1 AND periodo = 'mensual'"
            );
            $stmt->execute([$empresaId]);
            $presupuesto = (float)$stmt->fetchColumn();
        } catch (\Throwable $e) {
            $presupuesto = 0.0;
        }

        $flash      = $this->getFlash();
        $pageTitle  = 'Bienvenido';
        $activeMenu = 'comprador_inicio';

        ob_start();
        require ROOT_PATH . '/app/views/comprador/inicio.php';
        $content = ob_get_clean();
        require ROOT_PATH . '/app/views/empresa/layouts/main.php';
    }
}
