<?php
require_once ROOT_PATH . '/app/controllers/BaseController.php';

class RestModeracionController extends BaseController
{
    private RestSocialModeracionModel $model;

    public function __construct()
    {
        parent::__construct();
        $this->requireRestaurante();
        $this->requireAppMovil();
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
        $tab = in_array((string)$this->get('tab', 'reportes'), ['reportes', 'fotos'], true)
            ? (string)$this->get('tab', 'reportes')
            : 'reportes';
        $fotoStatus = in_array((string)$this->get('status', 'pending'), ['pending', 'approved', 'rejected'], true)
            ? (string)$this->get('status', 'pending')
            : 'pending';
        $fotoPage = max(1, (int)$this->get('page', 1));
        $fotoPerPage = min(100, max(1, (int)$this->get('per_page', 25)));
        $fotoSearch = trim((string)$this->get('search', ''));
        $fotosResultado = $this->model->gestionFotos($restauranteId, $fotoStatus, $fotoPage, $fotoPerPage, $fotoSearch);
        $csrfToken = $this->csrfToken();
        $flash = $this->getFlash();
        $pageTitle = 'Reportes de App';
        $activeMenu = 'rest_moderacion';

        $this->render('restaurante/moderacion/index', array_merge(
            $resultado,
            compact('flash', 'pageTitle', 'activeMenu', 'tab', 'fotoStatus', 'fotoSearch', 'fotosResultado', 'csrfToken')
        ));
    }

    public function desactivar(?string $id = null): void
    {
        if (!$this->isPost()) {
            $this->redirect('rest-moderacion/index');
        }
        if (!$this->validCsrf()) {
            $this->flash('error', 'Sesion expirada. Intenta de nuevo.');
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
        if (!$this->validCsrf()) {
            $this->flash('error', 'Sesion expirada. Intenta de nuevo.');
            $this->redirect('rest-moderacion/index');
        }

        $ok = $this->model->reactivarUsuario((int)$id, $this->restauranteId(), $this->usuarioId());
        $this->flash($ok ? 'success' : 'error', $ok ? 'Cuenta reactivada.' : 'No se pudo reactivar la cuenta.');
        $this->redirect('rest-moderacion/index');
    }

    public function foto(?string $id = null): void
    {
        if (!$this->isPost()) {
            $this->redirect('rest-moderacion/index?tab=fotos');
        }
        if (!$this->validCsrf()) {
            $this->flash('error', 'Sesion expirada. Intenta de nuevo.');
            $this->redirect('rest-moderacion/index?tab=fotos');
        }

        $decision = (string)$this->post('decision', '');
        $photoId = (int)$id;
        if ($decision === 'approved') {
            $result = $this->model->aprobarFoto($photoId, $this->restauranteId(), $this->usuarioId());
            if (($result['status'] ?? '') === 'conflict') {
                $this->flash('error', 'Otro moderador ya decidio esta fotografia. Se actualizo la cola.');
            } else {
                $this->flash($result['ok'] ? 'success' : 'error', $result['ok'] ? 'Fotografia aprobada.' : 'No se pudo aprobar la fotografia.');
            }
            $this->redirect('rest-moderacion/index?tab=fotos');
        }

        if ($decision === 'rejected') {
            $notes = trim((string)$this->post('notes', ''));
            $confirm = (string)$this->post('confirm_suspend', '');
            if ($confirm !== '1') {
                $this->flash('error', 'Confirma la suspension antes de retirar la fotografia.');
                $this->redirect('rest-moderacion/index?tab=fotos');
            }

            $result = $this->model->rechazarFoto($photoId, $this->restauranteId(), $notes, $this->usuarioId());
            if (($result['status'] ?? '') === 'validation') {
                $this->flash('error', $result['message'] ?? 'El motivo no es valido.');
            } elseif (($result['status'] ?? '') === 'conflict') {
                $this->flash('error', 'Otro moderador ya decidio esta fotografia. Se actualizo la cola.');
            } else {
                $this->flash($result['ok'] ? 'success' : 'error', $result['ok'] ? 'Fotografia retirada y cuenta suspendida.' : 'No se pudo rechazar la fotografia.');
            }
            $this->redirect('rest-moderacion/index?tab=fotos');
        }

        $this->flash('error', 'Decision no valida.');
        $this->redirect('rest-moderacion/index?tab=fotos');
    }

    public function reporte(?string $id = null): void
    {
        if (!$this->isPost()) {
            $this->redirect('rest-moderacion/index');
        }
        if (!$this->validCsrf()) {
            $this->flash('error', 'Sesion expirada. Intenta de nuevo.');
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

    private function csrfToken(): string
    {
        if (empty($_SESSION['rest_moderacion_csrf'])) {
            $_SESSION['rest_moderacion_csrf'] = bin2hex(random_bytes(32));
        }

        return (string)$_SESSION['rest_moderacion_csrf'];
    }

    private function validCsrf(): bool
    {
        $expected = (string)($_SESSION['rest_moderacion_csrf'] ?? '');
        $received = (string)$this->post('_csrf', '');
        return $expected !== '' && $received !== '' && hash_equals($expected, $received);
    }
}
