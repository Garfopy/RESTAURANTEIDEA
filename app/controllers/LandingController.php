<?php
require_once ROOT_PATH . '/app/controllers/BaseController.php';

class LandingController extends BaseController
{
    public function index(): void
    {
        $pageTitle = 'AMARE | Restaurant Connecting Club';
        $this->render('public/landing_amare', compact('pageTitle'));
    }
}
