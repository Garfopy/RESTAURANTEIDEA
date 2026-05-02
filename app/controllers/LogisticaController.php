<?php
require_once ROOT_PATH . '/app/controllers/BaseController.php';

class LogisticaController extends BaseController
{
    private RutaModel $model;

    public function __construct()
    {
        parent::__construct();
        $this->requireAdmin();
        $this->model = new RutaModel();
    }

    public function rutas(?string $p = null): void
    {
        $fecha  = $this->get('fecha', date('Y-m-d'));
        $rutas  = $this->model->getDelDia($fecha);
        $flash  = $this->getFlash();
        $pageTitle = 'Logística — Rutas del día';
        $ctrlSlug  = 'logistica';
        $this->render('admin/logistica/rutas', compact('rutas','fecha','flash','pageTitle','ctrlSlug'));
    }

    public function detalle(?string $id = null): void
    {
        $ruta = $this->model->getConDetalle((int)$id);
        if (!$ruta) { $this->redirect('logistica/rutas'); }
        $pageTitle = 'Ruta: ' . $ruta['nombre'];
        $ctrlSlug  = 'logistica';
        $this->render('admin/logistica/mapa', compact('ruta','pageTitle','ctrlSlug'));
    }

    public function choferes(?string $p = null): void
    {
        $usuarioModel = new UsuarioModel();
        $choferes     = $usuarioModel->getChoferes();
        $pageTitle    = 'Choferes';
        $ctrlSlug     = 'logistica';
        $this->render('admin/logistica/choferes', compact('choferes','pageTitle','ctrlSlug'));
    }

    public function crearRuta(?string $p = null): void
    {
        if (!$this->isPost()) { $this->redirect('logistica/rutas'); }
        $data = [
            'nombre'         => $this->post('nombre'),
            'fecha'          => $this->post('fecha', date('Y-m-d')),
            'chofer_id'      => $this->post('chofer_id') ?: null,
            'vehiculo_id'    => $this->post('vehiculo_id') ?: null,
            'estado'         => 'pendiente',
            'total_entregas' => (int)$this->post('total_entregas', 0),
            'km_estimados'   => $this->post('km_estimados') ?: null,
        ];
        $id = $this->model->insert($data);
        $this->log("Ruta creada #$id: {$data['nombre']}", 'logistica');
        $this->flash('success', 'Ruta creada.');
        $this->redirect("logistica/detalle/$id");
    }

    public function cambiarEstado(?string $id = null): void
    {
        $estado  = $this->post('estado','');
        $estados = ['pendiente','en_preparacion','en_ruta','completada'];
        if (!in_array($estado, $estados)) { $this->json(['ok'=>false]); }
        $this->model->update((int)$id, ['estado' => $estado]);
        $this->log("Ruta #$id → $estado", 'logistica');
        $this->json(['ok'=>true]);
    }
}
