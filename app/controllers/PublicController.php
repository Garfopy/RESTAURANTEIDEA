<?php
require_once ROOT_PATH . '/app/controllers/BaseController.php';

class PublicController extends BaseController
{
    public function __construct()
    {
        parent::__construct();
        // Sin restricción — página pública
    }

    // GET / → landing pública
    public function landing(?string $p = null): void
    {
        // Si ya hay sesión activa, redirigir al portal correspondiente
        if (isset($_SESSION['usuario'])) {
            $this->redirectSegunRol($_SESSION['usuario']['rol_slug'] ?? '');
        }

        $config       = new ConfigModel();
        $appName      = $config->get('app_name',     APP_NAME);
        $appLogo      = $config->get('app_logo',      '');
        $colorPrimary = $config->get('color_primary', '#C8102E');
        $contactEmail = $config->get('smtp_user',     'contacto@carnihub.mx');

        require ROOT_PATH . '/app/views/public/landing.php';
    }

    // GET planes/index
    public function index(?string $p = null): void
    {
        $model   = new SuscripcionModel();
        $planes  = $model->getPlanesActivos();
        $config  = new ConfigModel();
        $appName = $config->get('app_name', APP_NAME);
        $appLogo = $config->get('app_logo', '');
        $colorPrimary = $config->get('color_primary', '#C8102E');
        $whatsapp = $config->get('whatsapp_api_token', '') ? $config->get('whatsapp_phone_id', '') : '';
        $contactEmail = $config->get('smtp_user', 'contacto@carnihub.mx');

        require ROOT_PATH . '/app/views/public/planes.php';
    }
}
