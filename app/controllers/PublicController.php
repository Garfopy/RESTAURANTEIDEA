<?php
require_once ROOT_PATH . '/app/controllers/BaseController.php';

class PublicController extends BaseController
{
    public function __construct()
    {
        parent::__construct();
        // Sin restricción — página pública
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
