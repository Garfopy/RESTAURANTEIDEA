<?php
require_once ROOT_PATH . '/app/controllers/RestChefController.php';

class RestBarController extends RestChefController
{
    protected string $kdsArea = 'barra';
    protected string $kdsBaseRoute = 'rest-bar';
    protected string $kdsLogoutRol = 'barra';
    protected string $kdsTitle = 'KDS - Barra';
    protected string $kdsBrand = 'KDS Barra';
    protected string $kdsIcon = 'Barra';

    protected function authorize(): void
    {
        $this->requireBar();
    }
}
