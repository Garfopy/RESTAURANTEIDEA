<?php
require_once ROOT_PATH . '/app/controllers/BaseController.php';

class RecurrenteController extends BaseController
{
    private RecurrenteModel $model;

    public function __construct()
    {
        parent::__construct();
        $this->requireRole(['comprador','supervisor','admin','superadmin']);
        $this->model = new RecurrenteModel();
    }

    public function index(?string $p = null): void
    {
        $empresaId  = $this->empresaIdActual();
        $recurrentes = $empresaId ? $this->model->getByEmpresa($empresaId) : [];
        $flash      = $this->getFlash();
        $pageTitle  = 'Pedidos Recurrentes';
        $ctrlSlug   = 'recurrente';
        $this->render('cliente/recurrentes/index', compact('recurrentes','flash','pageTitle','ctrlSlug'));
    }

    public function detalle(?string $id = null): void
    {
        $rec = $this->model->getConDetalle((int)$id);
        if (!$rec) { $this->redirect('recurrente/index'); }
        $pageTitle = $rec['nombre'];
        $ctrlSlug  = 'recurrente';
        $this->render('cliente/recurrentes/detalle', compact('rec','pageTitle','ctrlSlug'));
    }

    public function guardar(?string $p = null): void
    {
        if (!$this->isPost()) { $this->redirect('recurrente/index'); }
        $empresaId = $this->empresaIdActual();
        $id = (int)$this->post('id', 0);
        $data = [
            'empresa_id'      => $empresaId,
            'nombre'          => $this->post('nombre'),
            'frecuencia'      => $this->post('frecuencia', 'semanal'),
            'proximo_pedido'  => $this->post('proximo_pedido') ?: null,
        ];
        if ($id > 0) {
            $this->model->update($id, $data);
        } else {
            $id = $this->model->insert($data);
        }

        // Save items
        $items = json_decode($this->post('items_json', '[]'), true) ?: [];
        if (!empty($items)) {
            $this->model->guardarDetalle($id, $items);
        }

        $this->log("Recurrente guardado #$id", 'pedidos_recurrentes');
        $this->flash('success', 'Plantilla guardada.');
        $this->redirect("recurrente/detalle/$id");
    }

    public function pausar(?string $id = null): void
    {
        $this->model->togglePausado((int)$id, 1);
        $this->json(['ok' => true, 'estado' => 'pausado']);
    }

    public function activar(?string $id = null): void
    {
        $this->model->togglePausado((int)$id, 0);
        $this->json(['ok' => true, 'estado' => 'activo']);
    }

    public function confirmarAhora(?string $id = null): void
    {
        $rec = $this->model->getConDetalle((int)$id);
        if (!$rec || $rec['pausado']) { $this->json(['ok'=>false]); }

        // Build cart from template
        foreach ($rec['items'] as $item) {
            $pId = $item['producto_id'];
            if (!isset($_SESSION['carrito'][$pId])) {
                $_SESSION['carrito'][$pId] = ['producto_id'=>$pId,'sucursales'=>[]];
            }
            $_SESSION['carrito'][$pId]['sucursales'][$item['sucursal_id']] =
                ($_SESSION['carrito'][$pId]['sucursales'][$item['sucursal_id']] ?? 0) + $item['cantidad'];
        }

        // Update next order date
        $freq = $rec['frecuencia'];
        $dias = match($freq) { 'diario'=>1, 'quincenal'=>15, default=>7 };
        $next = date('Y-m-d', strtotime("+$dias days"));
        $this->model->update((int)$id, ['ultimo_pedido'=>date('Y-m-d'), 'proximo_pedido'=>$next]);

        $this->log("Recurrente #$id confirmado manualmente", 'pedidos_recurrentes');
        $this->json(['ok' => true, 'redirect' => BASE_URL . 'carrito/index']);
    }
}
