<?php
require_once ROOT_PATH . '/app/controllers/BaseController.php';
require_once ROOT_PATH . '/app/services/PayPalSuscripcionService.php';

class SuscripcionController extends BaseController
{
    public function __construct()
    {
        parent::__construct();
        $this->requireAdmin();
    }

    // GET suscripcion/index
    public function index(?string $p = null): void
    {
        $model    = new SuscripcionModel();
        $filtros  = [
            'buscar'  => $this->get('buscar', ''),
            'plan_id' => $this->get('plan_id', ''),
            'estado'  => $this->get('estado', ''),
        ];
        $page      = max(1, (int)$this->get('page', 1));
        $resultado = $model->listado($filtros, $page);
        $planes    = $model->getPlanesActivos();

        $flash      = $this->getFlash();
        $pageTitle  = 'Suscripciones';
        $activeMenu = 'suscripciones';
        ob_start();
        require ROOT_PATH . '/app/views/panel/suscripciones/index.php';
        $content = ob_get_clean();
        require ROOT_PATH . '/app/views/panel/layouts/main.php';
    }

    // GET suscripcion/configurar
    public function configurar(?string $p = null): void
    {
        $model  = new SuscripcionModel();
        $planes = $model->getPlanesActivos();

        $flash      = $this->getFlash();
        $pageTitle  = 'Configurar PayPal';
        $activeMenu = 'suscripciones';
        ob_start();
        require ROOT_PATH . '/app/views/panel/suscripciones/configurar.php';
        $content = ob_get_clean();
        require ROOT_PATH . '/app/views/panel/layouts/main.php';
    }

    // POST suscripcion/guardarConfig
    public function guardarConfig(?string $p = null): void
    {
        if (!$this->isPost()) $this->redirect('suscripcion/configurar');
        if (!$this->esSuperAdmin()) {
            $this->flash('error', 'Solo el superadmin puede configurar PayPal.');
            $this->redirect('suscripcion/configurar');
        }
        $model = new SuscripcionModel();
        $planes = $model->getPlanesActivos();
        foreach ($planes as $plan) {
            $planId = $this->post('paypal_plan_' . $plan['id'], '');
            if ($planId !== '') {
                $model->guardarPaypalPlanId($plan['id'], trim($planId));
            }
        }
        $this->log('Configurar PayPal planes', 'suscripcion');
        $this->flash('success', 'IDs de PayPal guardados.');
        $this->redirect('suscripcion/configurar');
    }

    // POST suscripcion/cambiarPlan
    public function cambiarPlan(?string $p = null): void
    {
        if (!$this->isPost()) $this->redirect('suscripcion/index');
        $susId  = (int)$this->post('suscripcion_id');
        $planId = (int)$this->post('plan_id');
        if ($susId && $planId) {
            $model = new SuscripcionModel();
            $model->cambiarPlan($susId, $planId);
            $this->log('Cambiar plan', 'suscripcion', "sus_id=$susId plan_id=$planId");
            $this->flash('success', 'Plan actualizado.');
        }
        $this->redirect('suscripcion/index');
    }

    // POST suscripcion/suspender
    public function suspender(?string $p = null): void
    {
        if (!$this->isPost()) $this->redirect('suscripcion/index');
        $susId = (int)$this->post('suscripcion_id');
        if ($susId) {
            $model = new SuscripcionModel();
            $model->cambiarEstado($susId, 'suspendido');
            $this->log('Suspender suscripción', 'suscripcion', "sus_id=$susId");
            $this->flash('success', 'Suscripción suspendida.');
        }
        $this->redirect('suscripcion/index');
    }

    // POST suscripcion/activar
    public function activar(?string $p = null): void
    {
        if (!$this->isPost()) $this->redirect('suscripcion/index');
        $susId = (int)$this->post('suscripcion_id');
        if ($susId) {
            $model = new SuscripcionModel();
            $model->cambiarEstado($susId, 'activo');
            $this->log('Activar suscripción', 'suscripcion', "sus_id=$susId");
            $this->flash('success', 'Suscripción activada.');
        }
        $this->redirect('suscripcion/index');
    }

    // POST suscripcion/webhook  ← sin autenticación de sesión
    public function webhook(?string $p = null): void
    {
        $rawBody = file_get_contents('php://input');
        $headers = PayPalSuscripcionService::getRequestHeaders();

        $configModel = new ConfigModel();
        $webhookId   = $configModel->get('paypal_webhook_id', '');

        $paypal = new PayPalSuscripcionService();
        if ($webhookId && !$paypal->verificarWebhook($headers, $rawBody, $webhookId)) {
            http_response_code(401);
            echo json_encode(['error' => 'Invalid signature']);
            exit;
        }

        $event   = json_decode($rawBody, true);
        $tipo    = $event['event_type'] ?? '';
        $model   = new SuscripcionModel();

        switch ($tipo) {
            case 'BILLING.SUBSCRIPTION.ACTIVATED':
                $paypalSubId = $event['resource']['id'] ?? '';
                if ($paypalSubId) $model->activarDesdePaypal($paypalSubId);
                break;

            case 'PAYMENT.SALE.COMPLETED':
                $paypalSubId = $event['resource']['billing_agreement_id'] ?? '';
                if ($paypalSubId) {
                    $sus = $model->getByPaypalId($paypalSubId);
                    if ($sus) {
                        $dias    = ($sus['ciclo'] === 'anual') ? 365 : 30;
                        $base    = $sus['fecha_vencimiento'] ?? date('Y-m-d');
                        $nueva   = date('Y-m-d', strtotime($base . " +$dias days"));
                        $model->renovar($sus['id'], $nueva);
                    }
                }
                break;

            case 'BILLING.SUBSCRIPTION.SUSPENDED':
                $paypalSubId = $event['resource']['id'] ?? '';
                if ($paypalSubId) {
                    $sus = $model->getByPaypalId($paypalSubId);
                    if ($sus) $model->cambiarEstado($sus['id'], 'suspendido');
                }
                break;

            case 'BILLING.SUBSCRIPTION.CANCELLED':
                $paypalSubId = $event['resource']['id'] ?? '';
                if ($paypalSubId) {
                    $sus = $model->getByPaypalId($paypalSubId);
                    if ($sus) $model->cambiarEstado($sus['id'], 'cancelado');
                }
                break;
        }

        http_response_code(200);
        echo json_encode(['status' => 'ok']);
        exit;
    }
}
