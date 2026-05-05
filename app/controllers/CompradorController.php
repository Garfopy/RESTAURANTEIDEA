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
        $empresaId   = $usuario['empresa_id'] ?? 0;
        $compradorId = $usuario['id'] ?? 0;
        $pedidoModel = new PedidoModel();

        $ultimosPedidos = $pedidoModel->query(
            "SELECT p.id, p.folio, p.total, p.estado, p.created_at
               FROM pedidos p
              WHERE p.comprador_id = ? AND p.empresa_id = ?
              ORDER BY p.created_at DESC LIMIT 5",
            [$compradorId, $empresaId]
        );

        $enRuta = $pedidoModel->query(
            "SELECT p.id, p.folio, p.estado
               FROM pedidos p
              WHERE p.comprador_id = ? AND p.empresa_id = ? AND p.estado = 'en_ruta'
              ORDER BY p.created_at DESC LIMIT 3",
            [$compradorId, $empresaId]
        );

        $productoModel  = new ProductoModel();
        $destacados     = $productoModel->listadoConPrecio(['activo' => 1], 1)['data'] ?? [];

        $flash      = $this->getFlash();
        $pageTitle  = 'Bienvenido';
        $activeMenu = 'comprador_inicio';

        ob_start();
        require ROOT_PATH . '/app/views/comprador/inicio.php';
        $content = ob_get_clean();
        require ROOT_PATH . '/app/views/empresa/layouts/main.php';
    }
}
