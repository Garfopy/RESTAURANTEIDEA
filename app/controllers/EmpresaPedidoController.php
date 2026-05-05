<?php
require_once ROOT_PATH . '/app/controllers/BaseController.php';

class EmpresaPedidoController extends BaseController
{
    public function __construct()
    {
        parent::__construct();
        $this->requireSupervisor();
    }

    // Lista de pedidos de la empresa con filtros
    public function index(?string $p = null): void
    {
        $empresaId = $this->empresaId();
        $page      = max(1, (int)$this->get('page', 1));
        $filtros   = [
            'empresa_id' => $empresaId,
            'estado'     => $this->get('estado', ''),
            'tipo'       => $this->get('tipo', ''),
            'buscar'     => $this->get('buscar', ''),
            'fecha_desde'=> $this->get('fecha_desde', ''),
            'fecha_hasta'=> $this->get('fecha_hasta', ''),
        ];

        $pedidoModel = new PedidoModel();
        $resultado   = $pedidoModel->listadoEmpresa($empresaId, $filtros, $page);
        $items       = $resultado['data'];
        $paginacion  = $resultado;
        $flash       = $this->getFlash();
        $pageTitle   = 'Pedidos';
        $activeMenu  = 'pedidos';

        ob_start();
        require ROOT_PATH . '/app/views/empresa/pedidos/empresa_index.php';
        $content = ob_get_clean();
        require ROOT_PATH . '/app/views/empresa/layouts/main.php';
    }

    // Cambiar estado de un pedido
    public function cambiarEstado(?string $id = null): void
    {
        if (!$this->isPost()) {
            $this->redirect('empresa-pedido');
        }

        $pedidoId = (int)($id ?? $this->post('pedido_id'));
        $estado   = $this->post('estado');
        $nota     = trim($this->post('nota', ''));
        $estados  = ['pendiente', 'confirmado', 'en_preparacion', 'en_ruta', 'entregado', 'cancelado'];

        if ($pedidoId <= 0 || !in_array($estado, $estados)) {
            $this->flash('error', 'Datos inválidos.');
            $this->redirect('empresa-pedido');
        }

        $pedidoModel = new PedidoModel();
        $pedido      = $pedidoModel->queryOne(
            'SELECT id FROM pedidos WHERE id = ? AND empresa_id = ?',
            [$pedidoId, $this->empresaId()]
        );
        if (!$pedido) {
            $this->redirect('empresa-pedido');
        }

        $pedidoModel->cambiarEstado($pedidoId, $estado);
        $this->log('Cambiar estado pedido', 'pedidos', "Pedido $pedidoId → $estado. $nota");
        $this->flash('success', 'Estado del pedido actualizado.');
        $this->redirect('empresa-pedido');
    }

    // Pedido personalizado: formulario
    public function personalizado(?string $p = null): void
    {
        $this->requireSupervisor();
        $empresaId     = $this->empresaId();
        $productoModel = new ProductoModel();
        $usuarioModel  = new UsuarioModel();

        $compradores = $usuarioModel->query(
            "SELECT u.id, u.nombre, u.apellido_paterno, u.email
               FROM usuarios u
               JOIN roles r ON r.id = u.rol_id
              WHERE u.empresa_id = ? AND r.slug = 'comprador' AND u.activo = 1
           ORDER BY u.nombre",
            [$empresaId]
        );
        $productos = $productoModel->listadoAdmin(['empresa_id' => $empresaId], 1)['data'] ?? [];
        $flash     = $this->getFlash();
        $pageTitle = 'Pedido Personalizado';
        $activeMenu = 'pedidos';

        ob_start();
        require ROOT_PATH . '/app/views/empresa/pedidos/personalizado.php';
        $content = ob_get_clean();
        require ROOT_PATH . '/app/views/empresa/layouts/main.php';
    }

    // Guardar pedido personalizado
    public function guardarPersonalizado(?string $p = null): void
    {
        if (!$this->isPost()) {
            $this->redirect('empresa-pedido/personalizado');
        }

        $empresaId   = $this->empresaId();
        $compradorId = (int)$this->post('comprador_id');
        $nota        = trim($this->post('notas', ''));
        $fechaEntrega = $this->post('fecha_entrega') ?: null;
        $productosIds = (array)$this->post('producto_id', []);
        $cantidades   = (array)$this->post('cantidad', []);
        $precios      = (array)$this->post('precio_unit', []);

        if ($compradorId <= 0 || empty($productosIds)) {
            $this->flash('error', 'Selecciona un comprador y al menos un producto.');
            $this->redirect('empresa-pedido/personalizado');
        }

        $usuarioModel = new UsuarioModel();
        $comprador    = $usuarioModel->queryOne(
            "SELECT u.id FROM usuarios u JOIN roles r ON r.id = u.rol_id
              WHERE u.id = ? AND u.empresa_id = ? AND r.slug = 'comprador'",
            [$compradorId, $empresaId]
        );
        if (!$comprador) {
            $this->flash('error', 'Comprador no válido.');
            $this->redirect('empresa-pedido/personalizado');
        }

        $lineas   = [];
        $subtotal = 0;
        foreach ($productosIds as $i => $pid) {
            $pid      = (int)$pid;
            $cant     = (float)($cantidades[$i] ?? 0);
            $precio   = (float)($precios[$i] ?? 0);
            if ($pid <= 0 || $cant <= 0 || $precio <= 0) continue;
            $sub       = round($cant * $precio, 2);
            $subtotal += $sub;
            $lineas[] = ['producto_id' => $pid, 'cantidad' => $cant, 'precio_unit' => $precio, 'subtotal' => $sub];
        }

        if (empty($lineas)) {
            $this->flash('error', 'Agrega al menos un producto con cantidad y precio válidos.');
            $this->redirect('empresa-pedido/personalizado');
        }

        $folio       = $pedidoModel->generarFolio();

        $pedidoModel->crearPersonalizado(
            $empresaId, $compradorId, $folio, $nota, $fechaEntrega, $lineas, $subtotal, $this->usuarioId()
        );

        $this->log('Pedido personalizado', 'pedidos', "Folio $folio — Comprador $compradorId — Total $$subtotal");
        $this->flash('success', "Pedido personalizado creado con folio <strong>$folio</strong>.");
        $this->redirect('empresa-pedido');
    }
}
