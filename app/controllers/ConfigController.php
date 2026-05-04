<?php
require_once ROOT_PATH . '/app/controllers/BaseController.php';

class ConfigController extends BaseController
{
    private ConfigModel $model;

    public function __construct()
    {
        parent::__construct();
        $this->requireAdmin();
        $this->model = new ConfigModel();
    }

    public function general(?string $p = null): void
    {
        $config    = $this->model->getGrupo('general');
        $flash     = $this->getFlash();
        $pageTitle = 'Configuración — General';
        $ctrlSlug  = 'config';
        $this->render('admin/configuracion/general', compact('config','flash','pageTitle','ctrlSlug'));
    }

    public function apis(?string $p = null): void
    {
        $config    = $this->model->getGrupo('apis');
        $flash     = $this->getFlash();
        $pageTitle = 'Configuración — APIs';
        $ctrlSlug  = 'config';
        $this->render('admin/configuracion/apis', compact('config','flash','pageTitle','ctrlSlug'));
    }

    public function dispositivos(?string $p = null): void
    {
        $disModel    = new DispositivoModel();
        $hikvision   = $disModel->getHikvision();
        $shelly      = $disModel->getShelly();
        $flash       = $this->getFlash();
        $pageTitle   = 'Dispositivos IoT';
        $ctrlSlug    = 'config';
        $this->render('admin/configuracion/dispositivos', compact('hikvision','shelly','flash','pageTitle','ctrlSlug'));
    }

    public function guardar(?string $p = null): void
    {
        if (!$this->isPost()) { $this->redirect('config/general'); }
        $grupo  = $this->post('grupo', 'general');
        $campos = $_POST;
        unset($campos['grupo']);

        // Manejar subida de logo
        if (!empty($_FILES['app_logo']['tmp_name']) && $_FILES['app_logo']['error'] === UPLOAD_ERR_OK) {
            $allowedExts = ['png', 'jpg', 'jpeg', 'webp', 'svg'];
            $ext = strtolower(pathinfo($_FILES['app_logo']['name'], PATHINFO_EXTENSION));
            if (in_array($ext, $allowedExts, true) && $_FILES['app_logo']['size'] <= 2097152) {
                $dir = ROOT_PATH . '/public/uploads/logos/';
                if (!is_dir($dir)) mkdir($dir, 0755, true);
                $filename = 'logo.' . $ext;
                if (move_uploaded_file($_FILES['app_logo']['tmp_name'], $dir . $filename)) {
                    $this->model->set('app_logo', 'uploads/logos/' . $filename);
                }
            }
        }
        unset($campos['app_logo']); // nunca guardar el campo file vacío

        // Borrar logo si se solicitó
        if (!empty($campos['app_logo_borrar']) && $campos['app_logo_borrar'] === '1') {
            $currentLogo = $this->model->get('app_logo', '');
            if ($currentLogo) {
                $logoFile = ROOT_PATH . '/public/' . $currentLogo;
                if (file_exists($logoFile)) @unlink($logoFile);
                $this->model->set('app_logo', '');
            }
        }
        unset($campos['app_logo_borrar']);

        foreach ($campos as $k => $v) {
            $this->model->set($k, (string)$v);
        }

        $this->log("Configuración guardada (grupo: $grupo)", 'configuracion');
        $this->flash('success', 'Configuración guardada correctamente.');
        $this->redirect("config/$grupo");
    }

    public function guardarDispositivo(?string $p = null): void
    {
        if (!$this->isPost()) { $this->json(['ok'=>false]); }
        $tipo    = $this->post('tipo_dispositivo', 'hikvision');
        $disModel = new DispositivoModel();
        $data    = $_POST;
        unset($data['tipo_dispositivo']);

        if ($tipo === 'hikvision') {
            $id = $disModel->guardarHikvision($data);
        } else {
            $id = $disModel->guardarShelly($data);
        }

        $this->log("Dispositivo $tipo guardado #$id", 'dispositivos');
        $this->json(['ok' => true, 'id' => $id]);
    }

    public function eliminarDispositivo(?string $id = null): void
    {
        $tipo    = $this->post('tipo', 'hikvision');
        $disModel = new DispositivoModel();
        if ($tipo === 'hikvision') {
            $disModel->eliminarHikvision((int)$id);
        } else {
            $disModel->eliminarShelly((int)$id);
        }
        $this->log("Dispositivo $tipo eliminado #$id", 'dispositivos');
        $this->json(['ok' => true]);
    }

    public function bitacora(?string $p = null): void
    {
        $logModel  = new LogModel();
        $filtros   = ['modulo' => $this->get('modulo',''), 'fecha' => $this->get('fecha','')];
        $page      = max(1,(int)$this->get('page',1));
        $logs      = $logModel->getBitacora($filtros, $page);
        $errores   = $logModel->getErrores(1);
        $pageTitle = 'Bitácora';
        $ctrlSlug  = 'config';
        $this->render('admin/configuracion/bitacora', compact('logs','errores','filtros','pageTitle','ctrlSlug'));
    }

    public function index(?string $p = null): void
    {
        $this->redirect('config/general');
    }
}
