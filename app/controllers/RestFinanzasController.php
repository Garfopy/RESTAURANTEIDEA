<?php
require_once ROOT_PATH . '/app/controllers/BaseController.php';

class RestFinanzasController extends BaseController
{
    private RestFinanzasModel $model;

    public function __construct()
    {
        parent::__construct();
        $this->requireRestaurante();
        $this->model = new RestFinanzasModel();
    }

    public function dashboard(?string $p = null): void
    {
        $restauranteId = $this->restauranteId();
        $desde  = $this->get('desde', date('Y-m-01'));
        $hasta  = $this->get('hasta', date('Y-m-d'));

        $kpis   = $this->model->kpisDashboard($restauranteId, $desde, $hasta);
        $grafica = $this->model->ingresosVsEgresosGrafica($restauranteId, $desde, $hasta);
        $catGastos = $this->model->gastosPorCategoria($restauranteId, $desde, $hasta);
        $metodos = $this->model->metodosPago($restauranteId, $desde, $hasta);
        $reciente = $this->model->actividadReciente($restauranteId);

        $flash  = $this->getFlash();
        $pageTitle  = 'Financiero';
        $activeMenu = 'rest_finanzas';
        $this->render('restaurante/finanzas/dashboard', compact(
            'kpis','grafica','catGastos','metodos','reciente','desde','hasta','flash','pageTitle','activeMenu'
        ));
    }

    public function visibilidad(?string $p = null): void
    {
        $this->requireProgramador();
        $restauranteId = (int)$this->restauranteId();
        $visibilidadModel = new RestVisibilidadFinancieraModel();
        $configuracion = $visibilidadModel->getConfiguracion($restauranteId);
        $historial = $visibilidadModel->getHistorial($restauranteId);
        $flash = $this->getFlash();
        $pageTitle = 'Visibilidad de datos financieros';
        $activeMenu = 'rest_visibilidad_financiera';

        $this->render('restaurante/finanzas/visibilidad', compact(
            'configuracion', 'historial', 'flash', 'pageTitle', 'activeMenu'
        ));
    }

    public function guardarVisibilidad(?string $p = null): void
    {
        $this->requireProgramador();
        if (!$this->isPost()) {
            $this->redirect('rest-finanzas/visibilidad');
        }

        $fecha = trim((string)$this->post('ocultar_hasta', ''));
        try {
            (new RestVisibilidadFinancieraModel())->guardarOcultamiento(
                (int)$this->restauranteId(),
                $fecha,
                (int)$this->usuarioId()
            );
            $this->log(
                'Ocultamiento financiero activado',
                'finanzas',
                'Registros con fecha hasta ' . $fecha . ' ocultos para roles no programador.'
            );
            $this->flash('success', 'La informacion con fecha hasta ' . $fecha . ' quedo oculta para los demas niveles.');
        } catch (InvalidArgumentException $e) {
            $this->flash('error', $e->getMessage());
        } catch (Throwable $e) {
            error_log('[RestFinanzasController::guardarVisibilidad] ' . $e->getMessage());
            $this->flash('error', 'No se pudo actualizar la visibilidad financiera.');
        }

        $this->redirect('rest-finanzas/visibilidad');
    }

    public function restaurarVisibilidad(?string $p = null): void
    {
        $this->requireProgramador();
        if (!$this->isPost()) {
            $this->redirect('rest-finanzas/visibilidad');
        }

        try {
            (new RestVisibilidadFinancieraModel())->restaurarVisibilidad(
                (int)$this->restauranteId(),
                (int)$this->usuarioId()
            );
            $this->log('Ocultamiento financiero revertido', 'finanzas');
            $this->flash('success', 'La visibilidad historica fue restaurada para todos los niveles.');
        } catch (Throwable $e) {
            error_log('[RestFinanzasController::restaurarVisibilidad] ' . $e->getMessage());
            $this->flash('error', 'No se pudo restaurar la visibilidad financiera.');
        }

        $this->redirect('rest-finanzas/visibilidad');
    }

    public function cuentasPendientes(?string $p = null): void
    {
        $this->requireProgramador();
        $restauranteId = (int)$this->restauranteId();
        $cuentasModel = new RestCuentaPendienteModel();
        $pendientes = $cuentasModel->listarPendientes($restauranteId);
        $salidasPendientes = $cuentasModel->listarSalidasPendientes($restauranteId);
        $historial = $cuentasModel->getHistorial($restauranteId);
        $historialSalidas = $cuentasModel->getHistorialSalidas($restauranteId);
        $flash = $this->getFlash();
        $pageTitle = 'Cuentas pendientes';
        $activeMenu = 'rest_cuentas_pendientes';

        $this->render('restaurante/finanzas/cuentas_pendientes', compact(
            'pendientes', 'salidasPendientes', 'historial', 'historialSalidas',
            'flash', 'pageTitle', 'activeMenu'
        ));
    }

    public function regularizarAdeudo(?string $p = null): void
    {
        $this->requireProgramador();
        if (!$this->isPost()) {
            $this->redirect('rest-finanzas/cuentasPendientes');
        }

        $tipo = trim((string)$this->post('tipo_registro', ''));
        $registroId = (int)$this->post('registro_id', 0);
        $metodoPago = trim((string)$this->post('metodo_pago', ''));
        $motivo = trim((string)$this->post('motivo', ''));

        try {
            $resultado = (new RestCuentaPendienteModel())->regularizar(
                (int)$this->restauranteId(),
                $tipo,
                $registroId,
                $metodoPago,
                $motivo,
                (int)$this->usuarioId()
            );
            $folio = (string)($resultado['folio'] ?? ('#' . $registroId));
            $this->log(
                'Adeudo regularizado por PROGRAMADOR',
                'finanzas',
                $tipo . ' ' . $folio . '. Motivo: ' . $motivo
            );
            $this->flash('success', 'El adeudo de ' . $folio . ' fue retirado y sus registros quedaron sincronizados.');
        } catch (InvalidArgumentException | DomainException $e) {
            $this->flash('error', $e->getMessage());
        } catch (Throwable $e) {
            error_log('[RestFinanzasController::regularizarAdeudo] ' . $e->getMessage());
            $detalle = preg_replace('/\s+/', ' ', trim($e->getMessage()));
            $this->flash(
                'error',
                'No se pudo regularizar el adeudo. Detalle tecnico: ' . ($detalle ?: get_class($e))
            );
        }

        $this->redirect('rest-finanzas/cuentasPendientes');
    }

    public function validarSalidaManual(?string $p = null): void
    {
        $this->requireProgramador();
        if (!$this->isPost()) {
            $this->redirect('rest-finanzas/cuentasPendientes');
        }

        $visitaId = (int)$this->post('visita_id', 0);
        $motivo = trim((string)$this->post('motivo', ''));

        try {
            $resultado = (new RestCuentaPendienteModel())->validarSalidaManual(
                (int)$this->restauranteId(),
                $visitaId,
                $motivo,
                (int)$this->usuarioId()
            );
            $referencia = ($resultado['ticket_folio'] ?? '') ?: ('Visita #' . $visitaId);
            $this->log(
                'Salida validada por PROGRAMADOR',
                'finanzas',
                $referencia . '. Motivo: ' . $motivo
            );
            $this->flash('success', 'La salida de ' . $referencia . ' fue validada y la mesa se liberó correctamente.');
        } catch (InvalidArgumentException | DomainException $e) {
            $this->flash('error', $e->getMessage());
        } catch (Throwable $e) {
            error_log('[RestFinanzasController::validarSalidaManual] ' . $e->getMessage());
            $detalle = preg_replace('/\s+/', ' ', trim($e->getMessage()));
            $this->flash(
                'error',
                'No se pudo validar la salida. Detalle técnico: ' . ($detalle ?: get_class($e))
            );
        }

        $this->redirect('rest-finanzas/cuentasPendientes');
    }

    public function gastos(?string $p = null): void
    {
        $this->redirect('rest-finanzas/egresos?tab=gastos');
    }

    public function ventas(?string $p = null): void
    {
        $restauranteId = $this->restauranteId();
        $periodoParam = (string)$this->get('periodo', '');
        $periodosValidos = ['hoy', 'semana', 'mes', 'trimestre', 'rango'];
        $periodo = in_array($periodoParam, $periodosValidos, true)
            ? $periodoParam
            : (($this->get('desde') || $this->get('hasta')) ? 'rango' : 'mes');
        $fechas = $this->ventasPeriodoFechas($periodo);
        $desde = $periodo === 'rango' ? $this->get('desde', $fechas['desde']) : $fechas['desde'];
        $hasta = $periodo === 'rango' ? $this->get('hasta', $fechas['hasta']) : $fechas['hasta'];
        $ordenProductos = strtolower((string)$this->get('orden', 'desc'));
        $ordenProductos = in_array($ordenProductos, ['desc', 'asc'], true) ? $ordenProductos : 'desc';
        $limiteProductos = max(5, min(100, (int)$this->get('limite', 20)));
        $limiteProductos = (int)(ceil($limiteProductos / 5) * 5);
        $estacionProductos = strtolower((string)$this->get('estacion', 'todas'));
        $estacionProductos = in_array($estacionProductos, ['todas', 'primavera', 'verano', 'otono', 'invierno'], true)
            ? $estacionProductos
            : 'todas';
        $ventas = $this->model->ventasDashboard(
            $restauranteId,
            $desde,
            $hasta,
            $ordenProductos,
            $limiteProductos,
            $estacionProductos
        );

        $flash = $this->getFlash();
        $pageTitle = 'Ventas';
        $activeMenu = 'rest_ventas_finanzas';
        $this->render('restaurante/finanzas/ventas', compact(
            'ventas',
            'desde',
            'hasta',
            'periodo',
            'ordenProductos',
            'limiteProductos',
            'estacionProductos',
            'flash',
            'pageTitle',
            'activeMenu'
        ));
    }

    private function ventasPeriodoFechas(string $periodo): array
    {
        $hoy = date('Y-m-d');

        return match ($periodo) {
            'hoy' => ['desde' => $hoy, 'hasta' => $hoy],
            'semana' => ['desde' => date('Y-m-d', strtotime('monday this week')), 'hasta' => $hoy],
            'trimestre' => ['desde' => date('Y-m-d', strtotime('-3 months')), 'hasta' => $hoy],
            'rango' => ['desde' => date('Y-m-01'), 'hasta' => $hoy],
            default => ['desde' => date('Y-m-01'), 'hasta' => $hoy],
        };
    }

    public function retiros(?string $p = null): void
    {
        $this->redirect('rest-finanzas/egresos?tab=retiros');
    }

    public function egresos(?string $p = null): void
    {
        $restauranteId = $this->restauranteId();
        $tab      = $this->get('tab', 'gastos');
        $tab      = in_array($tab, ['gastos','retiros'], true) ? $tab : 'gastos';
        $page     = (int)$this->get('page', 1);

        $resGastos  = $this->model->getGastos($restauranteId, $page);
        $resRetiros = $this->model->getRetiros($restauranteId, $page);

        $flash     = $this->getFlash();
        $pageTitle = 'Gastos y Retiros';
        $activeMenu = 'rest_egresos';
        $this->render('restaurante/finanzas/egresos', compact(
            'resGastos','resRetiros','tab','flash','pageTitle','activeMenu'
        ));
    }

    public function guardarGasto(?string $p = null): void
    {
        if (!$this->isPost()) $this->redirect('rest-finanzas/egresos?tab=gastos');
        $this->model->insertGasto([
            'restaurante_id' => $this->restauranteId(),
            'categoria'      => $this->post('categoria', 'otros'),
            'descripcion'    => trim($this->post('descripcion', '')),
            'monto'          => (float)$this->post('monto', 0),
            'fecha'          => $this->post('fecha', date('Y-m-d')),
            'comprobante'    => $this->post('comprobante') ?: null,
            'usuario_id'     => $this->usuarioId(),
        ]);
        $this->flash('success', 'Gasto registrado.');
        $this->redirect('rest-finanzas/egresos?tab=gastos');
    }

    public function guardarRetiro(?string $p = null): void
    {
        if (!$this->isPost()) $this->redirect('rest-finanzas/egresos?tab=retiros');
        $this->model->insertRetiro([
            'restaurante_id' => $this->restauranteId(),
            'descripcion'    => trim($this->post('descripcion', '')),
            'monto'          => (float)$this->post('monto', 0),
            'usuario_id'     => $this->usuarioId(),
        ]);
        $this->flash('success', 'Retiro registrado.');
        $this->redirect('rest-finanzas/egresos?tab=retiros');
    }

    public function cortes(?string $p = null): void
    {
        $restauranteId = $this->restauranteId();
        $page     = (int)$this->get('page', 1);
        $resultado = $this->model->getCortes($restauranteId, $page);
        $flash    = $this->getFlash();
        $pageTitle = 'Cortes de Caja';
        $activeMenu = 'rest_cortes';
        $this->render('restaurante/finanzas/cortes', array_merge($resultado, compact('flash','pageTitle','activeMenu')));
    }

    public function guardarCorte(?string $p = null): void
    {
        if (!$this->isPost()) $this->redirect('rest-finanzas/cortes');
        $restauranteId = $this->restauranteId();
        $desde  = $this->post('desde', date('Y-m-d'));
        $hasta  = $this->post('hasta', date('Y-m-d'));
        $kpis   = $this->model->kpisDashboard($restauranteId, $desde, $hasta);

        $this->model->insertCorte([
            'restaurante_id' => $restauranteId,
            'turno'          => $this->post('turno', 'General'),
            'usuario_id'     => $this->usuarioId(),
            'ingresos'       => $kpis['ingresos'],
            'gastos'         => $kpis['gastos'],
            'retiros'        => $kpis['retiros'],
            'propinas'       => $kpis['propinas'],
            'utilidad_neta'  => $kpis['utilidad'],
            'notas'          => $this->post('notas'),
        ]);
        $this->flash('success', 'Corte de caja registrado.');
        $this->redirect('rest-finanzas/cortes');
    }
}
