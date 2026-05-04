<?php
require_once ROOT_PATH . '/app/controllers/BaseController.php';

class EmpresaLogisticaController extends BaseController
{
    public function __construct()
    {
        parent::__construct();
        $this->requireAdminEmpresa();
    }

    public function index(?string $p = null): void
    {
        $empresaId   = $_SESSION['usuario']['empresa_id'] ?? 0;
        $pedidoModel = new PedidoModel();

        $rutasActivas = $pedidoModel->query(
            "SELECT r.id, r.fecha, r.estado,
                    u.nombre AS repartidor_nombre, u.apellido_paterno AS repartidor_apellido,
                    COUNT(rd.id) AS total_paradas,
                    SUM(rd.estado = 'entregado') AS entregadas
               FROM rutas r
               JOIN usuarios u ON u.id = r.repartidor_id
               JOIN ruta_detalle rd ON rd.ruta_id = r.id
              WHERE r.empresa_id = ?
                AND r.estado IN ('pendiente', 'en_ruta')
              GROUP BY r.id
              ORDER BY r.fecha DESC
              LIMIT 50",
            [$empresaId]
        );

        $posiciones = $pedidoModel->query(
            "SELECT DISTINCT rd.lat_actual, rd.lng_actual,
                    u.nombre AS repartidor_nombre,
                    r.id AS ruta_id
               FROM ruta_detalle rd
               JOIN rutas r ON r.id = rd.ruta_id
               JOIN usuarios u ON u.id = r.repartidor_id
              WHERE r.empresa_id = ?
                AND rd.tracking_activo = 1
                AND rd.lat_actual IS NOT NULL",
            [$empresaId]
        );

        $flash      = $this->getFlash();
        $pageTitle  = 'Logística — Mis Rutas';
        $activeMenu = 'logistica';

        ob_start();
        require ROOT_PATH . '/app/views/empresa/logistica/index.php';
        $content = ob_get_clean();
        require ROOT_PATH . '/app/views/empresa/layouts/main.php';
    }

    public function nuevaRuta(?string $p = null): void
    {
        $empresaId    = $_SESSION['usuario']['empresa_id'] ?? 0;
        $usuarioModel = new UsuarioModel();
        $repartidores = $usuarioModel->getByEmpresa($empresaId, 'repartidor');

        $pedidoModel  = new PedidoModel();
        $pedidosDisp  = $pedidoModel->listadoConfirmadosPorEmpresa($empresaId);

        $flash      = $this->getFlash();
        $pageTitle  = 'Nueva Ruta';
        $activeMenu = 'logistica';

        ob_start();
        require ROOT_PATH . '/app/views/empresa/logistica/form_ruta.php';
        $content = ob_get_clean();
        require ROOT_PATH . '/app/views/empresa/layouts/main.php';
    }

    public function guardarRuta(?string $p = null): void
    {
        if (!$this->isPost()) {
            $this->redirect('empresa-logistica/index');
        }

        $empresaId    = $_SESSION['usuario']['empresa_id'] ?? 0;
        $pedidoModel  = new PedidoModel();
        $repartidorId = (int)$this->post('repartidor_id');
        $fecha        = trim($this->post('fecha'));
        $pedidosIds   = array_filter(array_map('intval', $_POST['pedidos_ids'] ?? []));

        if (!$repartidorId || !$fecha || empty($pedidosIds)) {
            $this->flash('error', 'Completa todos los campos obligatorios.');
            $this->redirect('empresa-logistica/nuevaRuta');
        }

        try {
            $rutaId = $pedidoModel->crearRuta($repartidorId, $empresaId, $fecha, $pedidosIds);
        } catch (\Throwable $e) {
            $this->flash('error', 'Error al crear la ruta: ' . $e->getMessage());
            $this->redirect('empresa-logistica/nuevaRuta');
        }

        $this->log('Crear ruta', 'logistica', "Ruta $rutaId — repartidor $repartidorId");
        $this->flash('success', 'Ruta creada y pedidos asignados.');
        $this->redirect('empresa-logistica/index');
    }
}
