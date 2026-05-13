<?php
require_once ROOT_PATH . '/app/controllers/BaseController.php';

class RestInventarioController extends BaseController
{
    private RestInventarioModel $model;

    public function __construct()
    {
        parent::__construct();
        $this->requireRestaurante();
        $this->model = new RestInventarioModel();
    }

    public function index(?string $p = null): void
    {
        $restauranteId = $this->restauranteId();
        $ingredientes  = $this->model->getByRestaurante($restauranteId);
        $alertas       = $this->model->alertasStockBajo($restauranteId);
        $flash         = $this->getFlash();
        $pageTitle     = 'Inventario';
        $activeMenu    = 'rest_inventario';
        $this->render('restaurante/inventario/index', compact('ingredientes','alertas','flash','pageTitle','activeMenu'));
    }

    public function guardar(?string $p = null): void
    {
        if (!$this->isPost()) $this->redirect('rest-inventario/index');
        $restauranteId = $this->restauranteId();

        $id   = (int)$this->post('id');
        $data = [
            'restaurante_id'   => $restauranteId,
            'nombre'           => trim($this->post('nombre', '')),
            'unidad_principal' => $this->post('unidad_principal', 'kg'),
            'unidad_compra'    => $this->post('unidad_compra') ?: null,
            'equivalencia'     => (float)$this->post('equivalencia', 1),
            'costo_unitario'   => (float)$this->post('costo_unitario', 0),
            'stock_minimo'     => (float)$this->post('stock_minimo', 0),
            'categoria'        => $this->post('categoria') ?: null,
            'proveedor_nombre' => $this->post('proveedor_nombre') ?: null,
        ];

        if ($id) {
            $this->model->update($id, array_diff_key($data, ['restaurante_id' => '']));
        } else {
            $this->model->insert($data);
        }

        $this->flash('success', 'Ingrediente guardado.');
        $this->redirect('rest-inventario/index');
    }

    public function movimiento(?string $p = null): void
    {
        if (!$this->isPost()) $this->redirect('rest-inventario/index');

        $ingredienteId = (int)$this->post('ingrediente_id');
        $tipo          = $this->post('tipo', 'entrada');
        $cantidad      = abs((float)$this->post('cantidad', 0));
        $delta         = in_array($tipo, ['salida','merma']) ? -$cantidad : $cantidad;

        $this->model->ajustarStock(
            $ingredienteId,
            $delta,
            $tipo,
            $this->post('motivo', ''),
            null,
            $this->restauranteId(),
            $this->usuarioId()
        );

        $this->flash('success', 'Movimiento registrado.');
        $this->redirect('rest-inventario/index');
    }

    public function movimientos(?string $p = null): void
    {
        $restauranteId = $this->restauranteId();
        $page          = (int)($this->get('page', 1));
        $resultado     = $this->model->getMovimientos($restauranteId, $page);
        $flash         = $this->getFlash();
        $pageTitle     = 'Movimientos de Inventario';
        $activeMenu    = 'rest_inventario';
        $this->render('restaurante/inventario/movimientos', array_merge($resultado, compact('flash','pageTitle','activeMenu')));
    }

    public function eliminar(?string $id = null): void
    {
        $this->model->update((int)$id, ['activo' => 0]);
        $this->flash('success', 'Ingrediente desactivado.');
        $this->redirect('rest-inventario/index');
    }
}
