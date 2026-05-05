<?php
require_once ROOT_PATH . '/app/controllers/BaseController.php';

/**
 * CarritoController — Flujo de compra en 3 pasos.
 *
 * Paso 1 /carrito/index      — Selección de productos y cantidades
 * Paso 2 /carrito/resumen    — Revisión + fecha/notas
 * Paso 3 /carrito/confirmado — Pedido creado con folio
 *
 * Cada comprador representa un punto de entrega (sucursal).
 * No hay distribución multi-sucursal; el pedido va directo al comprador.
 */
class CarritoController extends BaseController
{
    private ProductoModel $productoModel;
    private PedidoModel   $pedidoModel;

    public function __construct()
    {
        parent::__construct();
        $this->requireComprador();
        $this->productoModel = new ProductoModel();
        $this->pedidoModel   = new PedidoModel();
    }

    // ── Paso 1: Selección de productos ────────────────────────────
    public function index(?string $p = null): void
    {
        $filtros = [
            'buscar'       => $this->get('buscar', ''),
            'categoria_id' => (int)$this->get('categoria_id', 0) ?: null,
        ];

        $resultado  = $this->productoModel->listadoConPrecio($filtros);
        $productos  = $resultado['data'];

        $db = Database::getInstance();
        $categorias = $db->query('SELECT * FROM categorias WHERE activo = 1 ORDER BY nombre')->fetchAll();

        $carrito    = $_SESSION['carrito']['items'] ?? [];
        $flash      = $this->getFlash();
        $pageTitle  = 'Hacer pedido — Paso 1: Productos';
        $activeMenu = 'carrito';

        ob_start();
        require ROOT_PATH . '/app/views/empresa/carrito/paso1.php';
        $content = ob_get_clean();
        require ROOT_PATH . '/app/views/empresa/layouts/main.php';
    }

    // Guarda los ítems del carrito (POST desde paso 1)
    public function actualizar(?string $p = null): void
    {
        if (!$this->isPost()) {
            $this->redirect('carrito/index');
        }

        $compradorId = $this->usuarioId();
        $cantidades  = $_POST['cantidad'] ?? [];
        $items = [];

        foreach ($cantidades as $productoId => $cantidad) {
            $productoId = (int)$productoId;
            $cantidad   = (float)str_replace(',', '.', $cantidad);
            if ($cantidad <= 0) continue;

            $producto = $this->productoModel->find($productoId);
            if (!$producto || !$producto['activo']) continue;

            $precio   = $this->productoModel->getPrecioFinal($compradorId, $productoId, $cantidad);
            $items[$productoId] = [
                'producto_id'  => $productoId,
                'nombre'       => $producto['nombre'],
                'presentacion' => $producto['presentacion'],
                'cantidad'     => $cantidad,
                'precio'       => $precio,
                'subtotal'     => round($precio * $cantidad, 2),
            ];
        }

        if (empty($items)) {
            $this->flash('error', 'Agrega al menos un producto con cantidad mayor a 0.');
            $this->redirect('carrito/index');
        }

        $_SESSION['carrito']['items'] = $items;
        unset($_SESSION['carrito']['meta']);

        $this->redirect('carrito/resumen');
    }

    // Paso 2 ya no existe (redirect directo) — se mantiene por compatibilidad de URLs
    public function sucursales(?string $p = null): void
    {
        $this->redirect('carrito/resumen');
    }

    public function guardarSucursales(?string $p = null): void
    {
        $this->redirect('carrito/resumen');
    }

    // ── Paso 2: Resumen y confirmación ────────────────────────────
    public function resumen(?string $p = null): void
    {
        $items = $_SESSION['carrito']['items'] ?? [];

        if (empty($items)) {
            $this->redirect('carrito/index');
        }

        $total = array_sum(array_column($items, 'subtotal'));

        $flash      = $this->getFlash();
        $meta       = $_SESSION['carrito']['meta'] ?? [];
        $pageTitle  = 'Hacer pedido — Paso 2: Resumen';
        $activeMenu = 'carrito';

        ob_start();
        require ROOT_PATH . '/app/views/empresa/carrito/paso3.php';
        $content = ob_get_clean();
        require ROOT_PATH . '/app/views/empresa/layouts/main.php';
    }

    // Crea el pedido en BD (POST desde resumen)
    public function confirmar(?string $p = null): void
    {
        if (!$this->isPost()) {
            $this->redirect('carrito/resumen');
        }

        $items = $_SESSION['carrito']['items'] ?? [];

        if (empty($items)) {
            $this->redirect('carrito/index');
        }

        $fechaEntrega = trim($this->post('fecha_entrega', ''));
        $notas        = trim($this->post('notas', ''));

        if (empty($fechaEntrega)) {
            $this->flash('error', 'Selecciona la fecha de entrega.');
            $this->redirect('carrito/resumen');
        }

        $compradorId = $this->usuarioId();

        // Revalidar precios con precios especiales aplicados
        $itemsDB = [];
        foreach ($items as $prodId => $item) {
            $precio = $this->productoModel->getPrecioFinal($compradorId, (int)$prodId, $item['cantidad']);
            $itemsDB[] = [
                'producto_id' => $prodId,
                'cantidad'    => $item['cantidad'],
                'precio_unit' => $precio,
                'subtotal'    => round($precio * $item['cantidad'], 2),
            ];
        }

        $pedidoData = [
            'empresa_id'          => $this->empresaId(),
            'comprador_id'        => $compradorId,
            'estado'              => 'pendiente',
            'requiere_aprobacion' => 0,
            'fecha_entrega'       => $fechaEntrega,
            'notas'               => $notas ?: null,
        ];

        try {
            $pedidoId = $this->pedidoModel->crear($pedidoData, $itemsDB);
            $pedido   = $this->pedidoModel->find($pedidoId);

            $this->log('crear_pedido', 'carrito', "Pedido {$pedido['folio']} creado");

            $_SESSION['carrito'] = [];
            $_SESSION['ultimo_folio'] = $pedido['folio'];
            $_SESSION['ultimo_pedido_id'] = $pedidoId;

            $this->redirect('carrito/confirmado');
        } catch (\Throwable $e) {
            $this->flash('error', 'Error al crear el pedido. Intenta de nuevo.');
            $this->redirect('carrito/resumen');
        }
    }

    // ── Paso 3: Confirmado ────────────────────────────────────────
    public function confirmado(?string $p = null): void
    {
        $folio    = $_SESSION['ultimo_folio'] ?? null;
        $pedidoId = $_SESSION['ultimo_pedido_id'] ?? null;

        if (!$folio) {
            $this->redirect('pedido/index');
        }

        unset($_SESSION['ultimo_folio'], $_SESSION['ultimo_pedido_id']);

        $flash      = $this->getFlash();
        $pageTitle  = 'Pedido confirmado';
        $activeMenu = 'carrito';

        ob_start();
        require ROOT_PATH . '/app/views/empresa/carrito/paso4.php';
        $content = ob_get_clean();
        require ROOT_PATH . '/app/views/empresa/layouts/main.php';
    }

    public function vaciar(?string $p = null): void
    {
        $_SESSION['carrito'] = [];
        $this->redirect('carrito/index');
    }
}

    private ProductoModel $productoModel;
    private PedidoModel   $pedidoModel;

    public function __construct()
    {
        parent::__construct();
        $this->requireComprador();
        $this->productoModel = new ProductoModel();
        $this->pedidoModel   = new PedidoModel();
    }

    // ── Paso 1: Selección de productos ────────────────────────────
    public function index(?string $p = null): void
    {
        $filtros = [
            'buscar'       => $this->get('buscar', ''),
            'categoria_id' => (int)$this->get('categoria_id', 0) ?: null,
        ];

        $resultado  = $this->productoModel->listadoConPrecio($filtros);
        $productos  = $resultado['data'];

        $db = Database::getInstance();
        $categorias = $db->query('SELECT * FROM categorias WHERE activo = 1 ORDER BY nombre')->fetchAll();

        $carrito    = $_SESSION['carrito']['items'] ?? [];
        $flash      = $this->getFlash();
        $pageTitle  = 'Hacer pedido — Paso 1: Productos';
        $activeMenu = 'carrito';

        ob_start();
        require ROOT_PATH . '/app/views/empresa/carrito/paso1.php';
        $content = ob_get_clean();
        require ROOT_PATH . '/app/views/empresa/layouts/main.php';
    }

    // Guarda los ítems del carrito (POST desde paso 1)
    public function actualizar(?string $p = null): void
    {
        if (!$this->isPost()) {
            $this->redirect('carrito/index');
        }

        $cantidades = $_POST['cantidad'] ?? [];
        $items = [];

        foreach ($cantidades as $productoId => $cantidad) {
            $productoId = (int)$productoId;
            $cantidad   = (float)str_replace(',', '.', $cantidad);
            if ($cantidad <= 0) continue;

            $producto = $this->productoModel->find($productoId);
            if (!$producto || !$producto['activo']) continue;

            $precio   = $this->productoModel->getPrecioParaCantidad($productoId, $cantidad);
            $items[$productoId] = [
                'producto_id'  => $productoId,
                'nombre'       => $producto['nombre'],
                'presentacion' => $producto['presentacion'],
                'cantidad'     => $cantidad,
                'precio'       => $precio,
                'subtotal'     => round($precio * $cantidad, 2),
            ];
        }

        if (empty($items)) {
            $this->flash('error', 'Agrega al menos un producto con cantidad mayor a 0.');
            $this->redirect('carrito/index');
        }

        $_SESSION['carrito']['items'] = $items;
        // Reset downstream steps when items change
        unset($_SESSION['carrito']['distribucion'], $_SESSION['carrito']['meta']);

        $this->redirect('carrito/sucursales');
    }

    // ── Paso 2: Distribución por sucursal ─────────────────────────
    public function sucursales(?string $p = null): void
    {
        $items = $_SESSION['carrito']['items'] ?? [];
        if (empty($items)) {
            $this->redirect('carrito/index');
        }

        $db = Database::getInstance();
        $stmt = $db->prepare(
            'SELECT id, nombre, direccion FROM sucursales WHERE empresa_id = ? AND activo = 1 ORDER BY nombre'
        );
        $stmt->execute([$this->empresaId()]);
        $sucursales = $stmt->fetchAll();

        if (empty($sucursales)) {
            $this->flash('error', 'Tu empresa no tiene sucursales activas. Contacta al administrador.');
            $this->redirect('carrito/index');
        }

        // Si hay solo una sucursal, auto-distribución y saltar al paso 3
        if (count($sucursales) === 1) {
            $sucursalId = $sucursales[0]['id'];
            $distribucion = [];
            foreach ($items as $prodId => $item) {
                $distribucion[$prodId] = [$sucursalId => $item['cantidad']];
            }
            $_SESSION['carrito']['distribucion'] = $distribucion;
            $this->redirect('carrito/resumen');
        }

        $distribucion = $_SESSION['carrito']['distribucion'] ?? [];
        $flash        = $this->getFlash();
        $pageTitle    = 'Hacer pedido — Paso 2: Sucursales';
        $activeMenu   = 'carrito';

        ob_start();
        require ROOT_PATH . '/app/views/empresa/carrito/paso2.php';
        $content = ob_get_clean();
        require ROOT_PATH . '/app/views/empresa/layouts/main.php';
    }

    // Guarda distribución por sucursal (POST desde paso 2)
    public function guardarSucursales(?string $p = null): void
    {
        if (!$this->isPost()) {
            $this->redirect('carrito/sucursales');
        }

        $items = $_SESSION['carrito']['items'] ?? [];
        if (empty($items)) {
            $this->redirect('carrito/index');
        }

        $distribucionPost = $_POST['dist'] ?? [];
        $distribucion     = [];
        $errores          = [];

        foreach ($items as $prodId => $item) {
            $totalAsignado = 0;
            $distProd      = [];

            foreach ($distribucionPost[$prodId] ?? [] as $sucursalId => $qty) {
                $qty = (float)str_replace(',', '.', $qty);
                if ($qty > 0) {
                    $distProd[(int)$sucursalId] = $qty;
                    $totalAsignado += $qty;
                }
            }

            // Tolerancia de redondeo de 0.01
            if (abs($totalAsignado - $item['cantidad']) > 0.01) {
                $errores[] = "'{$item['nombre']}': debes distribuir exactamente {$item['cantidad']} {$item['presentacion']} (distribuiste {$totalAsignado}).";
            }
            $distribucion[$prodId] = $distProd;
        }

        if (!empty($errores)) {
            $this->flash('error', implode(' | ', $errores));
            $this->redirect('carrito/sucursales');
        }

        $_SESSION['carrito']['distribucion'] = $distribucion;
        $this->redirect('carrito/resumen');
    }

    // ── Paso 3: Resumen y confirmación ────────────────────────────
    public function resumen(?string $p = null): void
    {
        $items        = $_SESSION['carrito']['items'] ?? [];
        $distribucion = $_SESSION['carrito']['distribucion'] ?? [];

        if (empty($items) || empty($distribucion)) {
            $this->redirect('carrito/index');
        }

        $total = array_sum(array_column($items, 'subtotal'));

        $db = Database::getInstance();
        $stmt = $db->prepare(
            'SELECT id, nombre FROM sucursales WHERE empresa_id = ? AND activo = 1'
        );
        $stmt->execute([$this->empresaId()]);
        $sucursalesArr = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);

        $flash      = $this->getFlash();
        $meta       = $_SESSION['carrito']['meta'] ?? [];
        $pageTitle  = 'Hacer pedido — Paso 3: Resumen';
        $activeMenu = 'carrito';

        ob_start();
        require ROOT_PATH . '/app/views/empresa/carrito/paso3.php';
        $content = ob_get_clean();
        require ROOT_PATH . '/app/views/empresa/layouts/main.php';
    }

    // Crea el pedido en BD (POST desde paso 3)
    public function confirmar(?string $p = null): void
    {
        if (!$this->isPost()) {
            $this->redirect('carrito/resumen');
        }

        $items        = $_SESSION['carrito']['items'] ?? [];
        $distribucion = $_SESSION['carrito']['distribucion'] ?? [];

        if (empty($items) || empty($distribucion)) {
            $this->redirect('carrito/index');
        }

        $fechaEntrega = trim($this->post('fecha_entrega', ''));
        $metodoPago   = $this->post('metodo_pago', 'transferencia');
        $notas        = trim($this->post('notas', ''));

        if (empty($fechaEntrega)) {
            $this->flash('error', 'Selecciona la fecha de entrega.');
            $this->redirect('carrito/resumen');
        }

        // Revalidar precios actuales
        $itemsDB = [];
        foreach ($items as $prodId => $item) {
            $precio = $this->productoModel->getPrecioParaCantidad((int)$prodId, $item['cantidad']);
            $itemsDB[] = [
                'producto_id' => $prodId,
                'cantidad'    => $item['cantidad'],
                'precio_unit' => $precio,
                'subtotal'    => round($precio * $item['cantidad'], 2),
            ];
        }

        // Sucursales únicas involucradas
        $sucursalesIds = [];
        foreach ($distribucion as $distProd) {
            foreach (array_keys($distProd) as $sucursalId) {
                $sucursalesIds[] = (int)$sucursalId;
            }
        }

        $pedidoData = [
            'empresa_id'          => $this->empresaId(),
            'comprador_id'        => $this->usuarioId(),
            'estado'              => 'pendiente',
            'requiere_aprobacion' => 0,
            'fecha_entrega'       => $fechaEntrega,
            'metodo_pago'         => $metodoPago,
            'notas'               => $notas ?: null,
        ];

        try {
            $pedidoId = $this->pedidoModel->crear($pedidoData, $itemsDB, $sucursalesIds);
            $pedido   = $this->pedidoModel->find($pedidoId);

            $this->log('crear_pedido', 'carrito', "Pedido {$pedido['folio']} creado");

            $_SESSION['carrito'] = [];
            $_SESSION['ultimo_folio'] = $pedido['folio'];
            $_SESSION['ultimo_pedido_id'] = $pedidoId;

            $this->redirect('carrito/confirmado');
        } catch (\Throwable $e) {
            $this->flash('error', 'Error al crear el pedido. Intenta de nuevo.');
            $this->redirect('carrito/resumen');
        }
    }

    // ── Paso 4: Confirmado ────────────────────────────────────────
    public function confirmado(?string $p = null): void
    {
        $folio    = $_SESSION['ultimo_folio'] ?? null;
        $pedidoId = $_SESSION['ultimo_pedido_id'] ?? null;

        if (!$folio) {
            $this->redirect('pedido/index');
        }

        unset($_SESSION['ultimo_folio'], $_SESSION['ultimo_pedido_id']);

        $flash      = $this->getFlash();
        $pageTitle  = 'Pedido confirmado';
        $activeMenu = 'carrito';

        ob_start();
        require ROOT_PATH . '/app/views/empresa/carrito/paso4.php';
        $content = ob_get_clean();
        require ROOT_PATH . '/app/views/empresa/layouts/main.php';
    }

    public function vaciar(?string $p = null): void
    {
        $_SESSION['carrito'] = [];
        $this->redirect('carrito/index');
    }
}
