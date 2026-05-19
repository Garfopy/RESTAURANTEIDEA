<?php
require_once ROOT_PATH . '/app/controllers/BaseController.php';

class RestPublicoController extends BaseController
{
    private RestauranteModel    $restModel;
    private RestMenuModel        $menuModel;
    private RestPedidoModel      $pedidoModel;
    private RestVisitaModel      $visitaModel;
    private RestTicketModel      $ticketModel;
    private RestMesaModel        $mesaModel;
    private RestInventarioModel  $inventarioModel;

    public function __construct()
    {
        parent::__construct();
        $this->restModel       = new RestauranteModel();
        $this->menuModel       = new RestMenuModel();
        $this->pedidoModel     = new RestPedidoModel();
        $this->visitaModel     = new RestVisitaModel();
        $this->ticketModel     = new RestTicketModel();
        $this->mesaModel       = new RestMesaModel();
        $this->inventarioModel = new RestInventarioModel();
    }

    // /menu/{slug}  o  /menu/{slug}?mesa={qr_codigo}
    public function index(?string $slug = null): void
    {
        $restaurante = $this->restModel->getBySlug($slug ?? '');
        if (!$restaurante) {
            http_response_code(404);
            die('<h1>Restaurante no encontrado</h1>');
        }

        // Si el restaurante exige login del comensal, redirigir al portal staff
        // (la columna puede no existir si migration 026 aún no se aplica → default 0)
        $requiereLogin = (int)($restaurante['requiere_login_comensal'] ?? 0);
        if ($requiereLogin) {
            $this->redirect('acceso/' . $restaurante['slug']);
        }

        $categorias = $this->menuModel->getCategorias((int)$restaurante['id'], true);
        $platillos  = $this->menuModel->getPlatillosDisponibles((int)$restaurante['id']);

        // ── Deduplicar categorías por nombre y normalizar IDs de platillos ──
        // Si hay dos filas con el mismo nombre (ej. dos "Bebidas"), conservar la
        // primera como canónica y reasignar los platillos que apunten a las duplicadas.
        $canonicalByName = [];   // nombre_lower → id canónico
        $idMap           = [];   // id_duplicado  → id canónico
        $categoriasUnicas = [];
        foreach ($categorias as $cat) {
            $key = mb_strtolower(trim($cat['nombre']));
            if (!isset($canonicalByName[$key])) {
                $canonicalByName[$key] = (int)$cat['id'];
                $categoriasUnicas[]    = $cat;
            } else {
                $idMap[(int)$cat['id']] = $canonicalByName[$key];
            }
        }
        $categorias = $categoriasUnicas;
        if (!empty($idMap)) {
            foreach ($platillos as &$p) {
                $cid = (int)($p['categoria_id'] ?? 0);
                if (isset($idMap[$cid])) {
                    $p['categoria_id'] = $idMap[$cid];
                }
            }
            unset($p);
        }
        // ────────────────────────────────────────────────────────────────────
        $recetaIngredientes = [];
        try {
            $recetaIngredientes = $this->menuModel->getIngredientesPorRestaurante((int)$restaurante['id']);
        } catch (\Throwable $e) {}

        $mesa = null;
        $mesaQr = $this->get('mesa');
        $mesaInvalida = false;
        if ($mesaQr) {
            $mesa = (new RestMesaModel())->getByQr($mesaQr);
            $mesaInvalida = !$mesa; // QR escaneado pero la mesa fue eliminada o no existe
        }

        // Recuperar visita previa de cookie (para agregar más pedidos a la misma visita)
        $cookieName = 'visita_' . $restaurante['id'];
        $visitaId   = (int)($_COOKIE[$cookieName] ?? 0);
        if ($visitaId) {
            $visita = $this->visitaModel->find($visitaId);
            // Si la visita ya terminó, ignorar cookie
            if (!$visita || in_array($visita['estado'], ['pagada','cancelada'])) {
                $visitaId = 0;
                setcookie($cookieName, '', time() - 1, '/');
            }
        }

        $pageTitle = $restaurante['nombre'];
        $this->render('publico/menu/index', compact('restaurante','categorias','platillos','recetaIngredientes','mesa','mesaInvalida','visitaId','pageTitle'));
    }

    public function ordenar(?string $slug = null): void
    {
        if (!$this->isPost()) $this->redirect('menu/' . $slug);

        $restaurante = $this->restModel->getBySlug($slug ?? '');
        if (!$restaurante) { http_response_code(404); exit; }

        $restauranteId = (int)$restaurante['id'];
        $mesaQr        = $this->post('mesa_qr');
        $mesa          = $mesaQr ? (new RestMesaModel())->getByQr($mesaQr) : null;

        // Sin mesa válida no se permite ordenar (QR del local es solo lectura)
        if (!$mesa) {
            $this->flash('error', 'Para ordenar escanea el QR de tu mesa dentro del restaurante.');
            $this->redirect('menu/' . $slug);
        }

        $visitaId      = $this->post('visita_id') ?: null;

        // Crear visita si no existe
        if (!$visitaId) {
            $visitaId = $this->visitaModel->crear(
                $restauranteId,
                $mesa ? (int)$mesa['id'] : null
            );
            // Guardar en cookie por 4 horas
            $cookieName = 'visita_' . $restauranteId;
            setcookie($cookieName, (string)$visitaId, time() + 4 * 3600, '/');
        }

        $platillosIds = $this->post('platillo_id', []);
        $cantidades   = $this->post('cantidad', []);
        $exclusiones  = $this->post('exclusiones', []);  // keyed by platillo_id
        $notasItem    = $this->post('notas_item', []);   // keyed by platillo_id
        $extrasPost   = $this->post('extras', []);        // keyed by platillo_id → JSON string

        $items = [];
        foreach ($platillosIds as $k => $platilloId) {
            if (!$platilloId || empty($cantidades[$k])) continue;
            $platillo = $this->menuModel->find((int)$platilloId);
            if (!$platillo || !$platillo['disponible']) continue;
            $cant = max(1, (int)$cantidades[$k]);
            $excl = isset($exclusiones[$platilloId]) && is_array($exclusiones[$platilloId])
                ? implode(', ', array_filter(array_map('trim', $exclusiones[$platilloId])))
                : null;
            $nota = isset($notasItem[$platilloId]) ? trim($notasItem[$platilloId]) : null;

            // Extras: porción adicional de guarniciones (con costo)
            $extrasJson = null;
            $extrasCoste = 0.0;
            if (!empty($extrasPost[$platilloId])) {
                $extrasDecoded = json_decode($extrasPost[$platilloId], true);
                if (is_array($extrasDecoded)) {
                    $extrasValidos = array_filter($extrasDecoded, fn($e) =>
                        isset($e['ingrediente_id'], $e['nombre'], $e['precio_extra'], $e['cantidad'])
                        && (int)$e['cantidad'] > 0
                        && (float)$e['precio_extra'] >= 0
                    );
                    if ($extrasValidos) {
                        $extrasJson  = json_encode(array_values($extrasValidos));
                        $extrasCoste = array_sum(array_map(
                            fn($e) => (float)$e['precio_extra'] * (int)$e['cantidad'],
                            $extrasValidos
                        ));
                    }
                }
            }

            $precioUnit = (float)$platillo['precio'] + $extrasCoste;
            $items[] = [
                'platillo_id' => (int)$platilloId,
                'cantidad'    => $cant,
                'precio_unit' => $precioUnit,
                'subtotal'    => $precioUnit * $cant,
                'notas'       => $nota ?: null,
                'exclusiones' => $excl,
                'extras'      => $extrasJson,
            ];
        }

        if (empty($items)) {
            $this->redirect('menu/' . $slug . ($mesaQr ? '?mesa=' . urlencode($mesaQr) : ''));
        }

        $pedidoId = $this->pedidoModel->crear([
            'restaurante_id' => $restauranteId,
            'mesa_id'        => $mesa ? (int)$mesa['id'] : null,
            'visita_id'      => $visitaId,
            'mesero_id'      => null,
        ], $items);

        // El descuento de stock ya NO ocurre aquí: se aplica automáticamente
        // cuando el pedido pase a estado 'entregado'
        // (ver RestPedidoModel::cambiarEstadoPedido).

        $this->visitaModel->actualizarTotales((int)$visitaId);

        // Marcar mesa como ocupada
        if ($mesa) {
            $this->mesaModel->cambiarEstado((int)$mesa['id'], 'ocupada');
        }

        $this->redirect('menu/' . $slug . '/confirmacion/' . $visitaId);
    }

    public function confirmacion(?string $slug = null): void
    {
        $parts       = explode('/', $slug ?? '');
        $realSlug    = $parts[0] ?? '';
        $visitaId    = (int)($parts[1] ?? 0);
        $restaurante = $this->restModel->getBySlug($realSlug);
        $visita      = $this->visitaModel->find($visitaId);
        $pedidosBase = $this->pedidoModel->getByVisita($visitaId);

        // Enriquecer pedidos con sus ítems para la vista
        $pedidos = [];
        foreach ($pedidosBase as $ped) {
            $detalle = $this->pedidoModel->getConItems((int)$ped['id']);
            $pedidos[] = array_merge($ped, ['items' => $detalle['items'] ?? []]);
        }
        $ticket      = $this->ticketModel->getByVisita($visitaId);
        $pageTitle   = '¡Pedido recibido!';
        $this->render('publico/menu/confirmacion', compact('restaurante','visita','pedidos','ticket','pageTitle'));
    }

    // POST /menu/{slug}/generarTicket/{visitaId} — consolida ticket sin pagar, devuelve JSON
    public function generarTicket(?string $slug = null): void
    {
        $parts       = explode('/', $slug ?? '');
        $realSlug    = $parts[0] ?? '';
        $visitaId    = (int)($parts[1] ?? 0);
        $restaurante = $this->restModel->getBySlug($realSlug);
        $visita      = $this->visitaModel->find($visitaId);

        if (!$restaurante || !$visita
            || (int)$visita['restaurante_id'] !== (int)$restaurante['id']) {
            $this->json(['ok' => false, 'error' => 'Visita no válida']);
            return;
        }

        $ticket = $this->ticketModel->getByVisita($visitaId);
        if (!$ticket) {
            $ticketId = $this->ticketModel->consolidar($visitaId, 0);
            $ticket   = $this->ticketModel->find($ticketId);
        }

        if (!$ticket) {
            $this->json(['ok' => false, 'error' => 'No hay pedidos para generar ticket']);
            return;
        }

        $pedidos = $this->pedidoModel->getByVisita($visitaId);
        $items   = [];
        foreach ($pedidos as $p) {
            $det = $this->pedidoModel->getConItems((int)$p['id']);
            foreach ($det['items'] ?? [] as $it) {
                if ($it['estado'] !== 'cancelado') {
                    $items[] = [
                        'nombre'   => $it['platillo_nombre'] ?? $it['nombre'] ?? '',
                        'cantidad' => (int)$it['cantidad'],
                        'subtotal' => (float)($it['subtotal'] ?? 0),
                    ];
                }
            }
        }

        $this->json([
            'ok'       => true,
            'folio'    => $ticket['folio']    ?? '',
            'subtotal' => (float)($ticket['subtotal'] ?? 0),
            'propina'  => (float)($ticket['propina']  ?? 0),
            'total'    => (float)($ticket['total']    ?? 0),
            'estado'   => $ticket['estado']   ?? '',
            'items'    => $items,
            'qr_code'  => $visita['qr_code']  ?? '',
        ]);
    }

    public function pagar(?string $slug = null): void
    {
        $parts       = explode('/', $slug ?? '');
        $realSlug    = $parts[0] ?? '';
        $visitaId    = (int)($parts[1] ?? 0);
        $restaurante = $this->restModel->getBySlug($realSlug);
        $visita      = $this->visitaModel->find($visitaId);
        $ticket      = $this->ticketModel->getByVisita($visitaId);
        if (!$ticket) {
            $propina  = (float)$this->get('propina', 0);
            $ticketId = $this->ticketModel->consolidar($visitaId, $propina);
            $ticket   = $this->ticketModel->find($ticketId);
        }

        // Cambiar mesa a estado 'pagando'
        if ($visita && !empty($visita['mesa_id']) && ($ticket['estado'] ?? '') !== 'pagado') {
            $this->mesaModel->cambiarEstado((int)$visita['mesa_id'], 'pagando');
        }

        // Cargar ítems de todos los pedidos para la vista dividir-cuenta
        $pedidos   = $this->pedidoModel->getByVisita($visitaId);
        $todoItems = [];
        foreach ($pedidos as $ped) {
            $detalle = $this->pedidoModel->getConItems((int)$ped['id']);
            if ($detalle && !empty($detalle['items'])) {
                foreach ($detalle['items'] as $item) {
                    if ($item['estado'] !== 'cancelado') {
                        $todoItems[] = $item;
                    }
                }
            }
        }

        $pageTitle = 'Pagar cuenta';
        $this->render('publico/menu/pagar', compact('restaurante','ticket','todoItems','pageTitle'));
    }

    // POST /menu/{slug}/confirmarPago/{ticketId} — endpoint PÚBLICO (sin login)
    public function confirmarPago(?string $slug = null): void
    {
        if (!$this->isPost()) $this->redirect('menu/' . $slug);

        $parts    = explode('/', $slug ?? '');
        $realSlug = $parts[0] ?? '';
        $ticketId = (int)($parts[1] ?? 0);

        $restaurante = $this->restModel->getBySlug($realSlug);
        $ticket      = $this->ticketModel->find($ticketId);
        if (!$restaurante || !$ticket || (int)$ticket['restaurante_id'] !== (int)$restaurante['id']) {
            http_response_code(404);
            die('<h1>Ticket no válido</h1>');
        }

        $metodo  = $this->post('metodo_pago', 'efectivo');
        $propina = max(0.0, (float)$this->post('propina', 0));

        // Si propina cambió, recalcular total
        if ($propina !== (float)$ticket['propina']) {
            $this->ticketModel->actualizarPropina($ticketId, $propina);
        }

        if ($metodo === 'paypal') {
            // Redirigir a PayPal Checkout
            $total    = (float)($ticket['subtotal'] ?? 0) + $propina;
            $returnUrl = BASE_URL . 'menu/' . $realSlug . '/paypalRetorno/' . $ticketId . '/' . urlencode($propina);
            $cancelUrl = BASE_URL . 'menu/' . $realSlug . '/pagar/' . $ticket['visita_id'];
            try {
                $paypal = new PayPalOrdenService();
                $orden  = $paypal->crearOrden($total, 'MXN', $returnUrl, $cancelUrl, $ticket['folio']);
                // Guardar ticket en sesión para verificación al retorno
                $_SESSION['paypal_ticket_' . $ticketId] = [
                    'ticket_id' => $ticketId,
                    'propina'   => $propina,
                    'slug'      => $realSlug,
                ];
                header('Location: ' . $orden['approvalUrl']);
                exit;
            } catch (\Throwable $e) {
                // Si falla PayPal, regresar a pagar con error
                $_SESSION['flash_error'] = 'Error al conectar con PayPal. Elige otro método.';
                $this->redirect('menu/' . $realSlug . '/pagar/' . $ticket['visita_id']);
            }
        }

        $this->ticketModel->marcarPagado($ticketId, $metodo, null);
        $this->visitaModel->marcarPagada((int)$ticket['visita_id']);

        // Liberar mesa
        $visita = $this->visitaModel->find((int)$ticket['visita_id']);
        if ($visita && !empty($visita['mesa_id'])) {
            $this->mesaModel->cambiarEstado((int)$visita['mesa_id'], 'disponible');
        }

        // Limpia cookie de visita para que el comensal no quede pegado
        setcookie('visita_' . $restaurante['id'], '', time() - 1, '/');

        $this->redirect('menu/' . $realSlug . '/confirmacion/' . $ticket['visita_id'] . '?pagado=1');
    }

    // GET /menu/{slug}/paypalRetorno/{ticketId}/{propina}
    public function paypalRetorno(?string $slug = null): void
    {
        $parts     = explode('/', $slug ?? '');
        $realSlug  = $parts[0] ?? '';
        $ticketId  = (int)($parts[1] ?? 0);
        $propina   = (float)($parts[2] ?? 0);
        $orderId   = $this->get('token') ?: $this->get('orderId');

        $restaurante = $this->restModel->getBySlug($realSlug);
        $ticket      = $this->ticketModel->find($ticketId);

        if (!$restaurante || !$ticket || !$orderId
            || (int)$ticket['restaurante_id'] !== (int)$restaurante['id']
            || ($ticket['estado'] ?? '') === 'pagado') {
            $this->redirect('menu/' . $realSlug);
        }

        // Verificar token de sesión anti-replay
        $sesKey = 'paypal_ticket_' . $ticketId;
        if (empty($_SESSION[$sesKey]) || $_SESSION[$sesKey]['slug'] !== $realSlug) {
            $_SESSION['flash_error'] = 'Sesión de pago expirada. Intenta de nuevo.';
            $this->redirect('menu/' . $realSlug . '/pagar/' . $ticket['visita_id']);
        }
        unset($_SESSION[$sesKey]);

        try {
            $paypal  = new PayPalOrdenService();
            $capture = $paypal->capturarOrden($orderId);
            $captureStatus = $capture['status'] ?? '';
            if ($captureStatus !== 'COMPLETED') {
                throw new \RuntimeException('PayPal capture status: ' . $captureStatus);
            }

            if ($propina !== (float)$ticket['propina']) {
                $this->ticketModel->actualizarPropina($ticketId, $propina);
            }
            $this->ticketModel->marcarPagado($ticketId, 'paypal', $orderId);
            $this->visitaModel->marcarPagada((int)$ticket['visita_id']);

            $visita = $this->visitaModel->find((int)$ticket['visita_id']);
            if ($visita && !empty($visita['mesa_id'])) {
                $this->mesaModel->cambiarEstado((int)$visita['mesa_id'], 'disponible');
            }

            setcookie('visita_' . $restaurante['id'], '', time() - 1, '/');
        } catch (\Throwable $e) {
            $_SESSION['flash_error'] = 'No se pudo confirmar el pago con PayPal. Contacta al staff.';
        }

        $this->redirect('menu/' . $realSlug . '/confirmacion/' . $ticket['visita_id'] . '?pagado=1');
    }

    // GET /menu/{slug}/paypalCancelar/{ticketId}
    public function paypalCancelar(?string $slug = null): void
    {
        $parts     = explode('/', $slug ?? '');
        $realSlug  = $parts[0] ?? '';
        $ticketId  = (int)($parts[1] ?? 0);
        $ticket    = $this->ticketModel->find($ticketId);

        // Limpiar sesión PayPal huérfana
        unset($_SESSION['paypal_ticket_' . $ticketId]);

        $_SESSION['flash_error'] = 'Pago con PayPal cancelado. Elige otro método.';
        $visitaId = $ticket['visita_id'] ?? 0;
        $this->redirect('menu/' . $realSlug . '/pagar/' . $visitaId);
    }

    // POST /menu/{slug}/llamarMesero
    public function llamarMesero(?string $slug = null): void
    {
        header('Content-Type: application/json');
        $restaurante = $this->restModel->getBySlug($slug ?? '');
        if (!$restaurante) { echo json_encode(['ok'=>false]); exit; }

        $mesaQr   = $this->post('mesa_qr');
        $visitaId = (int)$this->post('visita_id', 0);
        $mesa     = $mesaQr ? $this->mesaModel->getByQr($mesaQr) : null;

        $db = Database::getInstance();
        $db->prepare(
            'INSERT INTO rest_alertas (restaurante_id, tipo, mesa_id, visita_id) VALUES (?,?,?,?)'
        )->execute([
            (int)$restaurante['id'],
            'mesero',
            $mesa ? (int)$mesa['id'] : null,
            $visitaId ?: null,
        ]);

        echo json_encode(['ok' => true]);
        exit;
    }

    // POST /menu/{slug}/cancelarPedido/{pedidoId}
    public function cancelarPedido(?string $slug = null): void
    {
        header('Content-Type: application/json');
        $parts     = explode('/', $slug ?? '');
        $realSlug  = $parts[0] ?? '';
        $pedidoId  = (int)($parts[1] ?? 0);

        $restaurante = $this->restModel->getBySlug($realSlug);
        if (!$restaurante || !$pedidoId) { echo json_encode(['ok'=>false,'msg'=>'Inválido']); exit; }

        $pedido = $this->pedidoModel->find($pedidoId);
        if (!$pedido || (int)$pedido['restaurante_id'] !== (int)$restaurante['id']) {
            echo json_encode(['ok'=>false,'msg'=>'Pedido no encontrado']); exit;
        }
        if ($pedido['estado'] !== 'pendiente') {
            echo json_encode(['ok'=>false,'msg'=>'Solo puedes cancelar pedidos pendientes']); exit;
        }

        $this->pedidoModel->cambiarEstadoPedido($pedidoId, 'cancelado');
        $this->visitaModel->actualizarTotales((int)$pedido['visita_id']);

        echo json_encode(['ok' => true]);
        exit;
    }

    // GET /menu/{slug}/estadoPedido/{visitaId}  — polling JSON
    public function estadoPedido(?string $slug = null): void
    {
        header('Content-Type: application/json');
        $parts     = explode('/', $slug ?? '');
        $realSlug  = $parts[0] ?? '';
        $visitaId  = (int)($parts[1] ?? 0);

        $restaurante = $this->restModel->getBySlug($realSlug);
        if (!$restaurante || !$visitaId) { echo json_encode(['ok'=>false]); exit; }

        $pedidos = $this->pedidoModel->getByVisita($visitaId);
        $result  = [];
        $tiempoMax = 0;

        foreach ($pedidos as $ped) {
            $detalle = $this->pedidoModel->getConItems((int)$ped['id']);
            $items   = [];
            foreach (($detalle['items'] ?? []) as $it) {
                $items[] = [
                    'id'          => $it['id'],
                    'nombre'      => $it['platillo_nombre'] ?? $it['nombre'] ?? '',
                    'cantidad'    => (int)$it['cantidad'],
                    'estado'      => $it['estado'],
                    'tiempo_prep' => (int)($it['tiempo_preparacion_min'] ?? 0),
                ];
                if (in_array($it['estado'], ['pendiente','en_preparacion'])) {
                    $tiempoMax = max($tiempoMax, (int)($it['tiempo_preparacion_min'] ?? 0));
                }
            }
            $result[] = [
                'id'     => $ped['id'],
                'folio'  => $ped['folio'],
                'estado' => $ped['estado'],
                'items'  => $items,
            ];
        }

        $ticketRow     = $this->ticketModel->getByVisita($visitaId);
        $ticketEstado  = $ticketRow['estado'] ?? null;
        $visitaRow     = $this->visitaModel->find($visitaId);

        echo json_encode([
            'ok'            => true,
            'pedidos'       => $result,
            'tiempo_min'    => $tiempoMax,
            'ticket_estado' => $ticketEstado,
            'ticket_total'  => $ticketRow ? (float)$ticketRow['total']   : 0,
            'ticket_propina'=> $ticketRow ? (float)$ticketRow['propina'] : 0,
            'qr_code'       => $visitaRow['qr_code'] ?? '',
        ]);
        exit;
    }

    // POST /menu/{slug}/actualizarPropina/{ticketId}  — AJAX
    public function actualizarPropina(?string $slug = null): void
    {
        header('Content-Type: application/json');
        $parts    = explode('/', $slug ?? '');
        $realSlug = $parts[0] ?? '';
        $ticketId = (int)($parts[1] ?? 0);

        $restaurante = $this->restModel->getBySlug($realSlug);
        $ticket      = $this->ticketModel->find($ticketId);
        if (!$restaurante || !$ticket || (int)$ticket['restaurante_id'] !== (int)$restaurante['id']) {
            echo json_encode(['ok'=>false]); exit;
        }

        $propina   = max(0.0, (float)$this->post('propina', 0));
        $this->ticketModel->actualizarPropina($ticketId, $propina);
        $ticket    = $this->ticketModel->find($ticketId);

        echo json_encode([
            'ok'      => true,
            'propina' => (float)$ticket['propina'],
            'total'   => (float)$ticket['total'],
        ]);
        exit;
    }

    // GET /menu/scanPortero?qr={token}  — página pública de verificación de salida
    public function scanPortero(?string $p = null): void
    {
        $qr     = trim($this->get('qr', ''));
        $visita = $qr ? $this->visitaModel->getByQr($qr) : null;

        if (!$visita) {
            http_response_code(404);
            $this->render('publico/portero/scan_error');
            exit;
        }

        $restaurante = $this->restModel->find((int)$visita['restaurante_id']);
        $pageTitle   = 'Verificar salida';
        $this->render('publico/portero/scan', compact('visita', 'restaurante', 'qr', 'pageTitle'));
    }

    // POST /menu/registrarSalidaPublica  — AJAX; seguro por token de alta entropía
    public function registrarSalidaPublica(?string $p = null): void
    {
        header('Content-Type: application/json');
        $qr     = trim($this->post('qr', ''));
        $visita = $qr ? $this->visitaModel->getByQr($qr) : null;

        if (!$visita) {
            http_response_code(404);
            echo json_encode(['ok' => false, 'mensaje' => 'QR no válido.']);
            exit;
        }

        if ($visita['estado'] !== 'pagada') {
            http_response_code(400);
            echo json_encode(['ok' => false, 'mensaje' => 'La cuenta no está pagada.']);
            exit;
        }

        if (!empty($visita['salida_at'])) {
            echo json_encode(['ok' => false, 'ya_salio' => true, 'mensaje' => 'Salida ya registrada.']);
            exit;
        }

        $this->visitaModel->marcarSalida((int)$visita['id']);

        if (!empty($visita['mesa_id'])) {
            $this->mesaModel->cambiarEstado((int)$visita['mesa_id'], 'disponible');
        }

        echo json_encode(['ok' => true, 'mensaje' => '¡Salida registrada! Mesa liberada.']);
        exit;
    }
}
