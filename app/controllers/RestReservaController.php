<?php
require_once ROOT_PATH . '/app/controllers/BaseController.php';

class RestReservaController extends BaseController
{
    private RestReservaModel $model;

    public function __construct()
    {
        parent::__construct();
        $this->requireMesero();
        $this->model = new RestReservaModel();
    }

    public function index(?string $p = null): void
    {
        $restauranteId = $this->restauranteId();
        $estado    = $this->get('estado', '');
        $page      = (int)$this->get('page', 1);
        $resultado = $this->model->getByRestaurante($restauranteId, $page, $estado ?: null);
        $proximas  = $this->model->getProximas($restauranteId);
        $flash     = $this->getFlash();
        $pageTitle  = 'Reservaciones';
        $activeMenu = 'rest_reservas';
        $this->render('restaurante/reservas/index', array_merge($resultado, compact('proximas','flash','pageTitle','activeMenu','estado')));
    }

    public function guardar(?string $p = null): void
    {
        if (!$this->isPost()) $this->redirect('rest-reserva/index');
        $id = (int)$this->post('id');
        $data = [
            'restaurante_id' => $this->restauranteId(),
            'mesa_id'        => $this->post('mesa_id') ?: null,
            'nombre'         => trim($this->post('nombre', '')),
            'telefono'       => $this->post('telefono') ?: null,
            'email'          => $this->post('email') ?: null,
            'fecha'          => $this->post('fecha'),
            'hora'           => $this->post('hora'),
            'personas'       => (int)$this->post('personas', 2),
            'notas'          => $this->post('notas') ?: null,
        ];
        if ($id) {
            $this->model->update($id, $data);
        } else {
            $this->model->insert($data);
        }
        $this->flash('success', 'Reservación guardada.');
        $this->redirect('rest-reserva/index');
    }

    public function cambiarEstado(?string $id = null): void
    {
        $estado = $this->post('estado') ?? $this->get('estado');
        $this->model->cambiarEstado((int)$id, $estado);
        $this->flash('success', 'Estado actualizado.');
        $this->redirect('rest-reserva/index');
    }

    public function eliminar(?string $id = null): void
    {
        $this->model->delete((int)$id);
        $this->flash('success', 'Reservación eliminada.');
        $this->redirect('rest-reserva/index');
    }
}
