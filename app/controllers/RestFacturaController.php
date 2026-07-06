<?php
require_once ROOT_PATH . '/app/controllers/BaseController.php';

class RestFacturaController extends BaseController
{
    private RestFacturaSolicitudModel $model;

    public function __construct()
    {
        parent::__construct();
        $this->requireRestaurante();
        $this->model = new RestFacturaSolicitudModel();
    }

    public function index(?string $p = null): void
    {
        $restauranteId = (int)$this->restauranteId();
        $restaurante = (new RestauranteModel())->find($restauranteId);
        $filtros = [
            'estado' => $this->get('estado', ''),
            'from' => $this->get('from', ''),
            'to' => $this->get('to', ''),
            'page' => $this->get('page', 1),
            'per_page' => $this->get('per_page', 20),
        ];
        $solicitudes = $this->model->listar($restauranteId, $filtros);
        $flash = $this->getFlash();
        $pageTitle = 'Solicitudes de factura';
        $activeMenu = 'rest_facturas';

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
        $restauranteId = (int)$this->restauranteId();
        $restaurante = (new RestauranteModel())->find($restauranteId);
        $solicitud = $this->model->buscarParaRestaurante((int)$id, $restauranteId);
        if (!$solicitud) {
            $this->flash('error', 'Solicitud de factura no encontrada.');
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
        if (!$this->isPost()) {
            $this->redirect('rest-factura/detalle/' . (int)$id);
        }

        $restauranteId = (int)$this->restauranteId();
        $id = (int)$id;

        try {
            $this->model->actualizarEstado($id, $restauranteId, [
                'estado' => $this->post('estado', 'pendiente'),
                'cfdi_uuid' => $this->post('cfdi_uuid', ''),
                'pdf_url' => $this->post('pdf_url', ''),
                'xml_url' => $this->post('xml_url', ''),
                'notas' => $this->post('notas', ''),
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
        if (!$this->isPost()) {
            $this->redirect('rest-factura/detalle/' . (int)$id);
        }

        $id = (int)$id;
        try {
            $actual = $this->model->buscarParaRestaurante($id, (int)$this->restauranteId());
            $this->model->actualizarEstado($id, (int)$this->restauranteId(), [
                'estado' => 'en_proceso',
                'cfdi_uuid' => $actual['cfdi_uuid'] ?? '',
                'pdf_url' => $actual['pdf_url'] ?? '',
                'xml_url' => $actual['xml_url'] ?? '',
                'notas' => $this->post('notas', $actual['notas'] ?? ''),
            ]);
            $this->flash('success', 'Solicitud marcada en proceso.');
        } catch (\Throwable $e) {
            $this->flash('error', 'No se pudo marcar en proceso.');
        }

        $this->redirect('rest-factura/detalle/' . $id);
    }

    public function timbrarFacturapi(?string $id = null): void
    {
        if (!$this->isPost()) {
            $this->redirect('rest-factura/detalle/' . (int)$id);
        }

        $id = (int)$id;
        $restauranteId = (int)$this->restauranteId();
        try {
            $actual = $this->model->buscarParaRestaurante($id, $restauranteId);
            if (!$actual) {
                throw new \RuntimeException('Solicitud de factura no encontrada.');
            }

            $restaurante = (new RestauranteModel())->find($restauranteId);
            (new FacturApiService())->stampInvoiceRequest($id, (int)($restaurante['empresa_id'] ?? 0), [
                'payment_form' => $this->post('payment_form', ''),
                'use' => $this->post('use', ''),
                'description' => $this->post('description', 'Consumo en restaurante'),
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
