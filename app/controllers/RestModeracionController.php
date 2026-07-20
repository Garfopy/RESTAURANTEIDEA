<?php
require_once ROOT_PATH . '/app/controllers/BaseController.php';

class RestModeracionController extends BaseController
{
    private RestSocialModeracionModel $model;

    public function __construct()
    {
        parent::__construct();
        $this->requireRestaurante();
        $this->model = new RestSocialModeracionModel();
    }

    public function index(?string $p = null): void
    {
        $restauranteId = $this->restauranteId();
        $autoBaneados = $this->model->autoBanPorReportes($restauranteId, 3, $this->usuarioId());
        if ($autoBaneados > 0) {
            $this->flash('success', $autoBaneados . ' cuenta(s) fueron desactivadas automaticamente por acumular 3 reportes.');
        }

        $resultado = $this->model->gestionReportes($restauranteId);
        $flash = $this->getFlash();
        $pageTitle = 'Reportes de App';
        $activeMenu = 'rest_moderacion';

        $this->render('restaurante/moderacion/index', array_merge(
            $resultado,
            compact('flash', 'pageTitle', 'activeMenu')
        ));
    }

    public function desactivar(?string $id = null): void
    {
        if (!$this->isPost()) {
            $this->redirect('rest-moderacion/index');
        }

        $ok = $this->model->desactivarUsuario((int)$id, $this->restauranteId(), $this->usuarioId());
        $this->flash($ok ? 'success' : 'error', $ok ? 'Cuenta desactivada y reportes marcados.' : 'No se pudo desactivar la cuenta.');
        $this->redirect('rest-moderacion/index');
    }

    public function reactivar(?string $id = null): void
    {
        if (!$this->isPost()) {
            $this->redirect('rest-moderacion/index');
        }

        $ok = $this->model->reactivarUsuario((int)$id, $this->restauranteId());
        $this->flash($ok ? 'success' : 'error', $ok ? 'Cuenta reactivada.' : 'No se pudo reactivar la cuenta.');
        $this->redirect('rest-moderacion/index');
    }

    public function reporte(?string $id = null): void
    {
        if (!$this->isPost()) {
            $this->redirect('rest-moderacion/index');
        }

        $accion = (string)$this->post('accion', 'reviewed');
        $status = match ($accion) {
            'descartar' => 'dismissed',
            'reabrir' => 'open',
            default => 'reviewed',
        };

        $ok = $this->model->cambiarEstadoReporte((int)$id, $this->restauranteId(), $status, $this->usuarioId());
        $this->flash($ok ? 'success' : 'error', $ok ? 'Reporte actualizado.' : 'No se pudo actualizar el reporte.');
        $this->redirect('rest-moderacion/index');
    }
}
