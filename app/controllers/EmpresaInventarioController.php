<?php
require_once ROOT_PATH . '/app/controllers/BaseController.php';

class EmpresaInventarioController extends BaseController
{
    public function __construct()
    {
        parent::__construct();
        $this->requireAdminEmpresa();
    }

    public function index(?string $p = null): void
    {
        $filtros = [
            'empresa_id' => $this->empresaId(),
            'buscar'     => $this->get('buscar', ''),
            'stock_bajo' => $this->get('stock_bajo', ''),
        ];
        $page = max(1, (int)$this->get('page', 1));

        $productoModel = new ProductoModel();
        $resultado     = $productoModel->listadoInventario($filtros, $page);
        $items         = $resultado['data'];
        $paginacion    = $resultado;
        $flash         = $this->getFlash();
        $pageTitle     = 'Inventario';
        $activeMenu    = 'inventario';

        ob_start();
        require ROOT_PATH . '/app/views/empresa/inventario/index.php';
        $content = ob_get_clean();
        require ROOT_PATH . '/app/views/empresa/layouts/main.php';
    }

    public function ajustar(?string $p = null): void
    {
        if (!$this->isPost()) {
            $this->redirect('empresa-inventario/index');
        }

        $productoId = (int)$this->post('producto_id');
        $tipo       = $this->post('tipo');
        $cantidad   = (float)$this->post('cantidad');
        $notas      = trim($this->post('notas', ''));

        if ($productoId <= 0 || $cantidad <= 0) {
            $this->flash('error', 'Datos inválidos para el ajuste.');
            $this->redirect('empresa-inventario/index');
        }

        $productoModel = new ProductoModel();
        $productoModel->ajustarStock($productoId, $tipo, $cantidad);

        $this->log('Ajuste inventario', 'inventario', "$tipo $cantidad uds — producto $productoId — $notas");
        $this->flash('success', 'Inventario actualizado correctamente.');
        $this->redirect('empresa-inventario/index');
    }
}
