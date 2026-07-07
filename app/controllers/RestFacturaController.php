<?php
require_once ROOT_PATH . '/app/controllers/BaseController.php';

class RestFacturaController extends BaseController
{
    private ?object $model = null;

    public function __construct()
    {
        parent::__construct();
        $this->requireRestaurante();

        if (class_exists('RestFacturaSolicitudModel')) {
            $this->model = new RestFacturaSolicitudModel();
        }
    }

    public function index(?string $p = null): void
    {
        $flash = $this->getFlash();
        $pageTitle = 'Facturas';
        $activeMenu = 'rest_facturas';

        if (!$this->model) {
            $this->render('restaurante/facturas/index', compact('flash', 'pageTitle', 'activeMenu'));
            return;
        }

        $restauranteId = (int)$this->restauranteId();
        $restaurante = (new RestauranteModel())->find($restauranteId);
        $filtros = [
            'estado' => trim((string)$this->get('estado', '')),
            'from' => trim((string)$this->get('from', '')),
            'to' => trim((string)$this->get('to', '')),
            'page' => max(1, (int)$this->get('page', 1)),
            'per_page' => max(1, min(100, (int)$this->get('per_page', 20))),
        ];
        $solicitudes = $this->model->listar($restauranteId, $filtros);
        $pageTitle = 'Solicitudes de factura';

        $this->render('restaurante/facturas/index', compact(
            'restaurante',
            'solicitudes',
            'filtros',
            'flash',
            'pageTitle',
            'activeMenu'
        ));
    }

    public function detalle(?string $id = null): void
    {
        if (!$this->model) {
            $this->flash('error', 'El modulo de solicitudes de factura aun no esta instalado.');
            $this->redirect('rest-factura/index');
        }

        $restauranteId = (int)$this->restauranteId();
        $solicitudId = (int)$id;
        if ($solicitudId <= 0) {
            $this->flash('error', 'Solicitud de factura no valida.');
            $this->redirect('rest-factura/index');
        }

        $restaurante = (new RestauranteModel())->find($restauranteId);
        $solicitud = $this->model->buscarParaRestaurante($solicitudId, $restauranteId);
        if (!$solicitud) {
            $this->flash('error', 'Solicitud de factura no encontrada.');
            $this->redirect('rest-factura/index');
        }

        $viewFile = ROOT_PATH . '/app/views/restaurante/facturas/detalle.php';
        if (!file_exists($viewFile)) {
            $this->flash('error', 'La vista de detalle de facturas no esta instalada.');
            $this->redirect('rest-factura/index');
        }

        $flash = $this->getFlash();
        $pageTitle = 'Solicitud de factura #' . (int)$solicitud['id'];
        $activeMenu = 'rest_facturas';

        $this->render('restaurante/facturas/detalle', compact(
            'restaurante',
            'solicitud',
            'flash',
            'pageTitle',
            'activeMenu'
        ));
    }

    public function actualizar(?string $id = null): void
    {
        $id = (int)$id;
        if (!$this->isPost()) {
            $this->redirect('rest-factura/detalle/' . $id);
        }

        if (!$this->model) {
            $this->flash('error', 'El modulo de solicitudes de factura aun no esta instalado.');
            $this->redirect('rest-factura/index');
        }

        if ($id <= 0) {
            $this->flash('error', 'Solicitud de factura no valida.');
            $this->redirect('rest-factura/index');
        }

        $restauranteId = (int)$this->restauranteId();

        try {
            $this->model->actualizarEstado($id, $restauranteId, [
                'estado' => trim((string)$this->post('estado', 'pendiente')),
                'cfdi_uuid' => trim((string)$this->post('cfdi_uuid', '')),
                'pdf_url' => trim((string)$this->post('pdf_url', '')),
                'xml_url' => trim((string)$this->post('xml_url', '')),
                'notas' => trim((string)$this->post('notas', '')),
            ]);
            $this->flash('success', 'Solicitud actualizada.');
        } catch (\InvalidArgumentException $e) {
            $this->flash('error', $e->getMessage());
        } catch (\Throwable $e) {
            error_log('[RestFacturaController] ' . $e->getMessage());
            $this->flash('error', 'No se pudo actualizar la solicitud.');
        }

        $this->redirect('rest-factura/detalle/' . $id);
    }

    public function marcarProceso(?string $id = null): void
    {
        $id = (int)$id;
        if (!$this->isPost()) {
            $this->redirect('rest-factura/detalle/' . $id);
        }

        if (!$this->model) {
            $this->flash('error', 'El modulo de solicitudes de factura aun no esta instalado.');
            $this->redirect('rest-factura/index');
        }

        if ($id <= 0) {
            $this->flash('error', 'Solicitud de factura no valida.');
            $this->redirect('rest-factura/index');
        }

        $restauranteId = (int)$this->restauranteId();

        try {
            $actual = $this->model->buscarParaRestaurante($id, $restauranteId);
            if (!$actual) {
                throw new \InvalidArgumentException('Solicitud de factura no encontrada.');
            }

            $this->model->actualizarEstado($id, $restauranteId, [
                'estado' => 'en_proceso',
                'cfdi_uuid' => $actual['cfdi_uuid'] ?? '',
                'pdf_url' => $actual['pdf_url'] ?? '',
                'xml_url' => $actual['xml_url'] ?? '',
                'notas' => trim((string)$this->post('notas', $actual['notas'] ?? '')),
            ]);
            $this->flash('success', 'Solicitud marcada en proceso.');
        } catch (\InvalidArgumentException $e) {
            $this->flash('error', $e->getMessage());
        } catch (\Throwable $e) {
            error_log('[RestFacturaController::marcarProceso] ' . $e->getMessage());
            $this->flash('error', 'No se pudo marcar en proceso.');
        }

        $this->redirect('rest-factura/detalle/' . $id);
    }

    public function timbrarFacturapi(?string $id = null): void
    {
        $id = (int)$id;
        if (!$this->isPost()) {
            $this->redirect('rest-factura/detalle/' . $id);
        }

        if (!$this->model) {
            $this->flash('error', 'El modulo de solicitudes de factura aun no esta instalado.');
            $this->redirect('rest-factura/index');
        }

        if ($id <= 0) {
            $this->flash('error', 'Solicitud de factura no valida.');
            $this->redirect('rest-factura/index');
        }

        $restauranteId = (int)$this->restauranteId();

        try {
            $actual = $this->model->buscarParaRestaurante($id, $restauranteId);
            if (!$actual) {
                throw new \RuntimeException('Solicitud de factura no encontrada.');
            }

            $restaurante = (new RestauranteModel())->find($restauranteId);
            (new FacturApiService())->stampInvoiceRequest($id, (int)($restaurante['empresa_id'] ?? 0), [
                'payment_form' => trim((string)$this->post('payment_form', '')),
                'use' => trim((string)$this->post('use', '')),
                'description' => trim((string)$this->post('description', 'Consumo en restaurante')),
                'tax_rate' => $this->post('tax_rate', FACTURAPI_TAX_RATE),
                'tax_included' => $this->post('tax_included', '1') ? true : false,
            ]);
            $this->flash('success', 'Factura timbrada con FacturAPI.');
        } catch (\Throwable $e) {
            error_log('[RestFactura FacturAPI] ' . $e->getMessage());
            try {
                $actual = $this->model->buscarParaRestaurante($id, $restauranteId);
                if ($actual) {
                    $this->model->actualizarEstado($id, $restauranteId, [
                        'estado' => 'en_proceso',
                        'cfdi_uuid' => $actual['cfdi_uuid'] ?? '',
                        'pdf_url' => $actual['pdf_url'] ?? '',
                        'xml_url' => $actual['xml_url'] ?? '',
                        'notas' => 'Error FacturAPI: ' . mb_substr($e->getMessage(), 0, 500),
                    ]);
                }
            } catch (\Throwable $ignored) {
                // El error principal se muestra abajo.
            }
            $this->flash('error', 'No se pudo timbrar con FacturAPI: ' . $e->getMessage());
        }

        $this->redirect('rest-factura/detalle/' . $id);
    }
}
