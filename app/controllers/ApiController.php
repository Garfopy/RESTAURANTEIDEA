<?php
require_once ROOT_PATH . '/app/controllers/BaseController.php';

/**
 * ApiController — Endpoints AJAX (sin layout HTML)
 * Maneja: precios escalonados, GPS tracking
 */
class ApiController extends BaseController
{
    public function __construct()
    {
        parent::__construct();
    }

    // ── Pedidos confirmados por empresa (para form de rutas) ──────────
    /** GET /api/pedidosConfirmados?empresa_id=X */
    public function pedidosConfirmados(?string $p = null): void
    {
        $this->requireAdmin();
        $empresaId = (int)$this->get('empresa_id', 0);
        if (!$empresaId) {
            $this->json([]);
        }

        $model   = new PedidoModel();
        $pedidos = $model->listadoConfirmadosPorEmpresa($empresaId);
        $this->json($pedidos);
    }

    // ── Precios escalonados ───────────────────────────────────────
    /** GET /api/precios/{producto_id}?cantidad=X */
    public function precios(?string $productoId = null): void
    {
        $this->requireEmpresa();
        $productoId = (int)$productoId;
        $cantidad   = (float)($this->get('cantidad', 0));

        if (!$productoId || $cantidad <= 0) {
            $this->json(['error' => 'Datos inválidos'], 400);
        }

        $model       = new ProductoModel();
        $compradorId = $this->usuarioId();
        $precio      = $model->getPrecioFinal($compradorId, $productoId, $cantidad);
        $escalonados = $model->getEscalonados($productoId);

        // Indicar si el precio aplicado es un precio especial (solo aplica < 10 kg)
        $esPrecioEspecial = false;
        if ($cantidad < 10.0) {
            $especial = $model->getPrecioEspecial($compradorId, $productoId);
            $esPrecioEspecial = ($especial !== null && abs($precio - $especial) < 0.001);
        }

        $this->json([
            'precio'             => $precio,
            'escalonados'        => $escalonados,
            'es_precio_especial' => $esPrecioEspecial,
        ]);
    }

    // ── Planes públicos (sin auth) ────────────────────────────────
    /** GET /api/planes — Devuelve planes activos para polling en landing */
    public function planes(?string $p = null): void
    {
        header('Cache-Control: no-store, no-cache, must-revalidate');

        $model  = new SuscripcionModel();
        $raw    = $model->getPlanesActivos();

        $planes = array_map(function (array $plan): array {
            $features = [];
            if (!empty($plan['features'])) {
                $features = is_array($plan['features'])
                    ? $plan['features']
                    : (json_decode($plan['features'], true) ?? []);
            }
            return [
                'id'             => (int)$plan['id'],
                'nombre'         => $plan['nombre'],
                'slug'           => $plan['slug'],
                'precio_mensual' => (float)$plan['precio_mensual'],
                'precio_anual'   => !empty($plan['precio_anual']) ? (float)$plan['precio_anual'] : null,
                'max_usuarios'   => (int)$plan['max_usuarios'],
                'max_productos'  => (int)$plan['max_productos'],
                'max_sucursales' => (int)$plan['max_sucursales'],
                'features'       => array_slice($features, 0, 6),
            ];
        }, $raw);

        $hash = md5(json_encode($planes));

        $this->json(['planes' => $planes, 'hash' => $hash]);
    }

    // ── GPS Tracking ──────────────────────────────────────────────

    /** GET /api/tracking/{pedido_id} — posición actual del repartidor */
    public function tracking(?string $pedidoId = null): void
    {
        $this->requireAuth();
        $pedidoId = (int)$pedidoId;
        if (!$pedidoId) $this->json(['error' => 'Pedido inválido'], 400);

        $db = Database::getInstance();
        $stmt = $db->prepare(
            'SELECT rd.lat_actual, rd.lng_actual, rd.eta_minutos,
                    rd.estado, rd.tracking_activo,
                    s.lat AS dest_lat, s.lng AS dest_lng, s.nombre AS sucursal,
                    p.estado AS pedido_estado
               FROM ruta_detalle rd
               JOIN sucursales s ON s.id = rd.sucursal_id
               JOIN pedidos p ON p.id = rd.pedido_id
              WHERE rd.pedido_id = ? AND rd.tracking_activo = 1
           ORDER BY rd.orden LIMIT 1'
        );
        $stmt->execute([$pedidoId]);
        $row = $stmt->fetch();

        if (!$row) {
            // Sin tracking activo, devolver estado del pedido
            $stmt2 = $db->prepare('SELECT estado FROM pedidos WHERE id = ?');
            $stmt2->execute([$pedidoId]);
            $ped = $stmt2->fetch();
            $this->json(['tracking_activo' => false, 'estado' => $ped['estado'] ?? 'desconocido']);
        }

        $this->json([
            'tracking_activo' => (bool)$row['tracking_activo'],
            'lat'             => $row['lat_actual'],
            'lng'             => $row['lng_actual'],
            'eta_minutos'     => $row['eta_minutos'],
            'estado'          => $row['estado'],
            'pedido_estado'   => $row['pedido_estado'],
            'destino'         => ['lat' => $row['dest_lat'], 'lng' => $row['dest_lng'], 'nombre' => $row['sucursal']],
        ]);
    }

    /** POST /api/tracking/actualizar — repartidor envía su posición */
    public function actualizarTracking(?string $p = null): void
    {
        $this->requireRepartidor();

        $body = json_decode(file_get_contents('php://input'), true) ?? [];
        $paradaId = (int)($body['ruta_detalle_id'] ?? 0);
        $lat      = (float)($body['lat'] ?? 0);
        $lng      = (float)($body['lng'] ?? 0);

        if (!$paradaId || !$lat || !$lng) {
            $this->json(['ok' => false, 'error' => 'Datos incompletos'], 400);
        }

        $db = Database::getInstance();

        // Calcular ETA aproximado (distancia Haversine a la sucursal)
        $stmt = $db->prepare('SELECT s.lat, s.lng FROM ruta_detalle rd JOIN sucursales s ON s.id = rd.sucursal_id WHERE rd.id = ?');
        $stmt->execute([$paradaId]);
        $dest = $stmt->fetch();

        $etaMinutos = null;
        if ($dest && $dest['lat'] && $dest['lng']) {
            $distKm = $this->haversine($lat, $lng, (float)$dest['lat'], (float)$dest['lng']);
            $etaMinutos = (int)round(($distKm / 30) * 60); // ~30 km/h promedio urbano
        }

        $db->prepare(
            'UPDATE ruta_detalle SET lat_actual = ?, lng_actual = ?, eta_minutos = ?, tracking_activo = 1 WHERE id = ?'
        )->execute([$lat, $lng, $etaMinutos, $paradaId]);

        $this->json(['ok' => true, 'eta_minutos' => $etaMinutos]);
    }

    /** POST /api/tracking/iniciar */
    public function iniciarTracking(?string $paradaId = null): void
    {
        $this->requireRepartidor();
        $paradaId = (int)$paradaId;
        if (!$paradaId) $this->json(['ok' => false], 400);

        Database::getInstance()
            ->prepare('UPDATE ruta_detalle SET tracking_activo = 1 WHERE id = ?')
            ->execute([$paradaId]);

        $this->json(['ok' => true]);
    }

    /** POST /api/tracking/finalizar/{paradaId} */
    public function finalizarTracking(?string $paradaId = null): void
    {
        $this->requireRepartidor();
        $paradaId = (int)$paradaId;
        if (!$paradaId) $this->json(['ok' => false], 400);

        Database::getInstance()
            ->prepare('UPDATE ruta_detalle SET tracking_activo = 0 WHERE id = ?')
            ->execute([$paradaId]);

        $this->json(['ok' => true]);
    }

    // ── Chatbot de datos (sin IA externa) ─────────────────────────
    /** POST /api/chat */
    public function chat(?string $p = null): void
    {
        $this->requireAdminEmpresa();

        try {
            $body    = json_decode(file_get_contents('php://input'), true) ?? [];
            $mensaje = trim($body['mensaje'] ?? '');

            if (!$mensaje) {
                $this->json(['error' => 'Mensaje vacío'], 400);
                return;
            }

            $empresaId = (int)$this->empresaId();
            $respuesta = $this->resolverConsultaChat($empresaId, $mensaje);
            $this->json(['respuesta' => $respuesta]);

        } catch (\Throwable $e) {
            $this->json(['error' => 'Error interno: ' . $e->getMessage()], 500);
        }
    }

    /** Resuelve consultas del chatbot usando datos reales de la BD */
    private function resolverConsultaChat(int $empresaId, string $msg): string
    {
        $db   = Database::getInstance();
        $norm = strtr(mb_strtolower($msg, 'UTF-8'), [
            'á'=>'a','é'=>'e','í'=>'i','ó'=>'o','ú'=>'u','ü'=>'u','ñ'=>'n',
        ]);

        // ── SALUDO / AYUDA ─────────────────────────────────────────
        if (preg_match('/^(hola|buenos|buenas|hey|que tal|que puedes|ayuda|como estas)/u', $norm)) {
            return "¡Hola! Soy tu asistente de datos. Puedo responder preguntas sobre:\n"
                 . "• Pedidos (hoy, esta semana, este mes, pendientes, cancelados)\n"
                 . "• Ventas y gasto acumulado del mes\n"
                 . "• Stock e inventario bajo mínimo\n"
                 . "• Productos más pedidos\n"
                 . "• Compradores más frecuentes\n"
                 . "• Equipo activo\n\n"
                 . "¿Qué quieres consultar?";
        }

        // ── PEDIDOS HOY ────────────────────────────────────────────
        if (preg_match('/pedido.*(hoy|dia de hoy|de hoy)/u', $norm)
            || preg_match('/(hoy|dia de hoy).*(pedido)/u', $norm)) {
            $stmt = $db->prepare(
                "SELECT COUNT(*) AS total, COALESCE(SUM(total),0) AS monto
                 FROM pedidos WHERE empresa_id=? AND DATE(created_at)=CURDATE()"
            );
            $stmt->execute([$empresaId]);
            $r = $stmt->fetch();
            return "Hoy llevas {$r['total']} pedido(s) registrado(s) con un monto total de $"
                 . number_format($r['monto'], 2) . " MXN.";
        }

        // ── PEDIDOS SEMANA ─────────────────────────────────────────
        if (preg_match('/pedido.*(semana|esta semana|7 dias|siete dias)/u', $norm)
            || preg_match('/(semana|esta semana).*(pedido)/u', $norm)) {
            $stmt = $db->prepare(
                "SELECT COUNT(*) AS total, COALESCE(SUM(total),0) AS monto
                 FROM pedidos WHERE empresa_id=? AND created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)"
            );
            $stmt->execute([$empresaId]);
            $r = $stmt->fetch();
            return "En los últimos 7 días tuviste {$r['total']} pedido(s) por un total de $"
                 . number_format($r['monto'], 2) . " MXN.";
        }

        // ── PENDIENTES ─────────────────────────────────────────────
        if (preg_match('/pendiente|por aprobar|aprobacion/u', $norm)) {
            $stmt = $db->prepare("SELECT COUNT(*) FROM pedidos WHERE empresa_id=? AND estado='pendiente'");
            $stmt->execute([$empresaId]);
            $total = (int)$stmt->fetchColumn();
            if ($total === 0) return "No tienes pedidos pendientes de aprobación. ¡Todo al día!";
            return "Tienes $total pedido(s) pendiente(s) de aprobación. Puedes revisarlos en la sección de Pedidos.";
        }

        // ── CANCELADOS ─────────────────────────────────────────────
        if (preg_match('/cancelad/u', $norm)) {
            $stmt = $db->prepare(
                "SELECT COUNT(*) FROM pedidos WHERE empresa_id=? AND estado='cancelado'
                 AND MONTH(created_at)=MONTH(NOW()) AND YEAR(created_at)=YEAR(NOW())"
            );
            $stmt->execute([$empresaId]);
            $total = (int)$stmt->fetchColumn();
            return "Este mes tienes $total pedido(s) cancelado(s).";
        }

        // ── EN RUTA ────────────────────────────────────────────────
        if (preg_match('/en ruta|en camino/u', $norm)) {
            $stmt = $db->prepare("SELECT COUNT(*) FROM pedidos WHERE empresa_id=? AND estado='en_ruta'");
            $stmt->execute([$empresaId]);
            $total = (int)$stmt->fetchColumn();
            return "Ahora mismo hay $total pedido(s) en ruta hacia sus destinos.";
        }

        // ── ENTREGADOS ─────────────────────────────────────────────
        if (preg_match('/entregad/u', $norm)) {
            $stmt = $db->prepare(
                "SELECT COUNT(*) FROM pedidos WHERE empresa_id=? AND estado='entregado'
                 AND MONTH(created_at)=MONTH(NOW()) AND YEAR(created_at)=YEAR(NOW())"
            );
            $stmt->execute([$empresaId]);
            $total = (int)$stmt->fetchColumn();
            return "Este mes tienes $total pedido(s) entregado(s) exitosamente.";
        }

        // ── VENTAS / GASTO ─────────────────────────────────────────
        if (preg_match('/venta|gasto|cuanto vend|cuanto factur|monto|ingreso|facturaci/u', $norm)) {
            $stmt = $db->prepare(
                "SELECT COALESCE(SUM(total),0) AS mes_actual, COUNT(*) AS total_pedidos
                 FROM pedidos WHERE empresa_id=? AND MONTH(created_at)=MONTH(NOW())
                 AND YEAR(created_at)=YEAR(NOW()) AND estado!='cancelado'"
            );
            $stmt->execute([$empresaId]);
            $r = $stmt->fetch();
            $stmt2 = $db->prepare(
                "SELECT COALESCE(SUM(total),0) FROM pedidos WHERE empresa_id=?
                 AND MONTH(created_at)=MONTH(DATE_SUB(NOW(), INTERVAL 1 MONTH))
                 AND YEAR(created_at)=YEAR(DATE_SUB(NOW(), INTERVAL 1 MONTH)) AND estado!='cancelado'"
            );
            $stmt2->execute([$empresaId]);
            $mesAnterior = (float)$stmt2->fetchColumn();
            $mesActual   = (float)$r['mes_actual'];
            $diff        = $mesActual - $mesAnterior;
            $diffStr     = ($diff >= 0 ? '+$' : '-$') . number_format(abs($diff), 2);
            return "Este mes llevas $" . number_format($mesActual, 2) . " MXN en ventas con {$r['total_pedidos']} pedido(s). "
                 . "El mes pasado fue $" . number_format($mesAnterior, 2) . " MXN (diferencia: $diffStr MXN).";
        }

        // ── STOCK / INVENTARIO ─────────────────────────────────────
        if (preg_match('/stock|inventario|sin existencia|producto.*bajo|minimo/u', $norm)) {
            try {
                $stmt = $db->prepare(
                    "SELECT p.nombre, p.presentacion, i.cantidad, i.minimo_stock
                     FROM inventario i JOIN productos p ON p.id=i.producto_id
                     WHERE p.empresa_id=? AND i.cantidad<=i.minimo_stock
                     ORDER BY i.cantidad ASC LIMIT 5"
                );
                $stmt->execute([$empresaId]);
                $rows = $stmt->fetchAll();
                if (empty($rows)) return "No hay productos con stock bajo. ¡Inventario al día!";
                $lista = array_map(
                    fn($row) => "• {$row['nombre']} ({$row['presentacion']}): {$row['cantidad']} / mínimo {$row['minimo_stock']}",
                    $rows
                );
                return "Tienes " . count($rows) . " producto(s) con stock bajo:\n" . implode("\n", $lista);
            } catch (\Throwable $e) {
                return "No pude consultar el inventario en este momento.";
            }
        }

        // ── TOP PRODUCTOS ──────────────────────────────────────────
        if (preg_match('/producto.*(mas|frecuente|popular|pide|vendid|top)|top.*producto|que se pide/u', $norm)) {
            $stmt = $db->prepare(
                "SELECT pr.nombre, pr.presentacion,
                        COUNT(DISTINCT p.id) AS veces,
                        SUM(pd.cantidad) AS cantidad_total
                 FROM pedido_detalle pd
                 JOIN pedidos p ON p.id=pd.pedido_id
                 JOIN productos pr ON pr.id=pd.producto_id
                 WHERE p.empresa_id=? AND p.estado!='cancelado'
                 GROUP BY pr.id, pr.nombre, pr.presentacion
                 ORDER BY veces DESC, cantidad_total DESC LIMIT 5"
            );
            $stmt->execute([$empresaId]);
            $rows = $stmt->fetchAll();
            if (empty($rows)) return "Aún no hay datos suficientes de pedidos para mostrar el ranking de productos.";
            $lista = [];
            foreach ($rows as $i => $r) {
                $lista[] = ($i + 1) . ". {$r['nombre']} ({$r['presentacion']}) — {$r['veces']} pedido(s), "
                         . number_format($r['cantidad_total'], 1) . " uds. totales";
            }
            return "Top 5 productos más pedidos:\n" . implode("\n", $lista);
        }

        // ── COMPRADORES ────────────────────────────────────────────
        if (preg_match('/comprador|cliente|quien compra|quien pide|top.*client|mas frecuente/u', $norm)) {
            $stmt = $db->prepare(
                "SELECT u.nombre, COUNT(DISTINCT p.id) AS total_pedidos, COALESCE(SUM(p.total),0) AS monto
                 FROM pedidos p JOIN usuarios u ON u.id=p.comprador_id
                 WHERE p.empresa_id=? AND p.estado!='cancelado'
                 GROUP BY u.id, u.nombre ORDER BY total_pedidos DESC LIMIT 5"
            );
            $stmt->execute([$empresaId]);
            $rows = $stmt->fetchAll();
            if (empty($rows)) return "Aún no hay datos de compradores con pedidos confirmados.";
            $lista = [];
            foreach ($rows as $i => $r) {
                $lista[] = ($i + 1) . ". {$r['nombre']} — {$r['total_pedidos']} pedido(s), $"
                         . number_format($r['monto'], 2) . " MXN";
            }
            return "Top 5 compradores más frecuentes:\n" . implode("\n", $lista);
        }

        // ── EQUIPO ─────────────────────────────────────────────────
        if (preg_match('/equipo|usuario|empleado|repartidor|supervisor|cuantos trabaj/u', $norm)) {
            $stmt = $db->prepare(
                "SELECT rol, COUNT(*) AS total FROM usuarios
                 WHERE empresa_id=? AND activo=1 GROUP BY rol ORDER BY total DESC"
            );
            $stmt->execute([$empresaId]);
            $rows = $stmt->fetchAll();
            if (empty($rows)) return "No hay usuarios activos registrados en tu empresa.";
            $lista = array_map(fn($r) => "• {$r['rol']}: {$r['total']}", $rows);
            $total = array_sum(array_column($rows, 'total'));
            return "Tu equipo tiene $total usuario(s) activo(s):\n" . implode("\n", $lista);
        }

        // ── PEDIDOS RECIENTES ──────────────────────────────────────
        if (preg_match('/reciente|ultimo.*pedido|pedido.*reciente/u', $norm)) {
            $stmt = $db->prepare(
                "SELECT p.folio, p.estado, p.total, u.nombre AS comprador
                 FROM pedidos p JOIN usuarios u ON u.id=p.comprador_id
                 WHERE p.empresa_id=? ORDER BY p.created_at DESC LIMIT 5"
            );
            $stmt->execute([$empresaId]);
            $rows = $stmt->fetchAll();
            if (empty($rows)) return "No hay pedidos registrados aún.";
            $lista = array_map(
                fn($r) => "• {$r['folio']} — {$r['comprador']}, estado: {$r['estado']}, $" . number_format($r['total'], 2),
                $rows
            );
            return "Últimos 5 pedidos:\n" . implode("\n", $lista);
        }

        // ── RESUMEN GENERAL ────────────────────────────────────────
        if (preg_match('/resumen|como vamos|estado del negocio|informe|panorama|general/u', $norm)) {
            $stmt = $db->prepare(
                "SELECT COUNT(*) AS total_mes,
                        COALESCE(SUM(CASE WHEN estado!='cancelado' THEN total ELSE 0 END),0) AS gasto_mes
                 FROM pedidos WHERE empresa_id=? AND MONTH(created_at)=MONTH(NOW()) AND YEAR(created_at)=YEAR(NOW())"
            );
            $stmt->execute([$empresaId]);
            $resumen = $stmt->fetch();
            $stmt2 = $db->prepare("SELECT COUNT(*) FROM pedidos WHERE empresa_id=? AND estado='pendiente'");
            $stmt2->execute([$empresaId]);
            $pendientes = (int)$stmt2->fetchColumn();
            try {
                $stmt3 = $db->prepare(
                    "SELECT COUNT(*) FROM inventario i JOIN productos p ON p.id=i.producto_id
                     WHERE p.empresa_id=? AND i.cantidad<=i.minimo_stock"
                );
                $stmt3->execute([$empresaId]);
                $stockBajo = (int)$stmt3->fetchColumn();
            } catch (\Throwable $e) {
                $stockBajo = 0;
            }
            $stmt4 = $db->prepare("SELECT COUNT(*) FROM usuarios WHERE empresa_id=? AND activo=1");
            $stmt4->execute([$empresaId]);
            $equipo = (int)$stmt4->fetchColumn();
            return "Resumen del negocio este mes:\n"
                 . "• Pedidos: {$resumen['total_mes']}\n"
                 . "• Ventas acumuladas: $" . number_format($resumen['gasto_mes'], 2) . " MXN\n"
                 . "• Pendientes de aprobación: $pendientes\n"
                 . "• Productos con stock bajo: $stockBajo\n"
                 . "• Usuarios activos en el equipo: $equipo";
        }

        // ── PEDIDOS ESTE MES (fallback "pedidos") ─────────────────
        if (preg_match('/pedido/u', $norm)) {
            $stmt = $db->prepare(
                "SELECT estado, COUNT(*) AS total FROM pedidos
                 WHERE empresa_id=? AND MONTH(created_at)=MONTH(NOW()) AND YEAR(created_at)=YEAR(NOW())
                 GROUP BY estado ORDER BY total DESC"
            );
            $stmt->execute([$empresaId]);
            $rows = $stmt->fetchAll();
            if (empty($rows)) return "No hay pedidos registrados este mes.";
            $lista = array_map(fn($r) => "• {$r['estado']}: {$r['total']}", $rows);
            $total = array_sum(array_column($rows, 'total'));
            return "Este mes tienes $total pedido(s) en total:\n" . implode("\n", $lista);
        }

        // ── FALLBACK ───────────────────────────────────────────────
        return "No entendí tu pregunta. Puedo consultarte sobre pedidos, ventas, inventario, "
             . "productos más pedidos, compradores o tu equipo. ¿Qué quieres saber?";
    }

    /** POST /api/guardarPosicion — guarda posición GPS en historial (cada ~60 s) */
    public function guardarPosicion(?string $p = null): void
    {
        $this->requireRepartidor();

        $body     = json_decode(file_get_contents('php://input'), true) ?? [];
        $pedidoId = (int)($body['pedido_id'] ?? 0);
        $lat      = (float)($body['lat'] ?? 0);
        $lng      = (float)($body['lng'] ?? 0);

        if (!$pedidoId || !$lat || !$lng) {
            $this->json(['ok' => false, 'error' => 'Datos incompletos'], 400);
        }

        try {
            Database::getInstance()
                ->prepare('INSERT INTO tracking_posiciones (pedido_id, lat, lng) VALUES (?, ?, ?)')
                ->execute([$pedidoId, $lat, $lng]);
            $this->json(['ok' => true]);
        } catch (\Throwable $e) {
            $this->json(['ok' => false]);
        }
    }

    /** GET /api/historialTracking/{pedido_id} — devuelve trail para la vista de tracking */
    public function historialTracking(?string $pedidoId = null): void
    {
        $this->requireAuth();
        $pedidoId = (int)$pedidoId;
        if (!$pedidoId) {
            $this->json([]);
        }

        try {
            $db   = Database::getInstance();
            $stmt = $db->prepare(
                'SELECT lat, lng, ts FROM tracking_posiciones
                  WHERE pedido_id = ? ORDER BY ts ASC LIMIT 300'
            );
            $stmt->execute([$pedidoId]);
            $this->json($stmt->fetchAll());
        } catch (\Throwable $e) {
            $this->json([]);
        }
    }

    // ── Fórmula Haversine (distancia entre dos coordenadas en km) ─
    private function haversine(float $lat1, float $lng1, float $lat2, float $lng2): float
    {
        $R   = 6371;
        $dLat = deg2rad($lat2 - $lat1);
        $dLng = deg2rad($lng2 - $lng1);
        $a = sin($dLat/2)**2 + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLng/2)**2;
        return $R * 2 * atan2(sqrt($a), sqrt(1 - $a));
    }

    // =========================================================
    // REST API v1  —  para CapiRest (Bearer token, sin sesión)
    // URL: /api/v1/{resource}/{id?}
    //   ctrlSlug='api'  action='v1'  param='{resource}'
    //   El {id} (4° segmento) se extrae de REQUEST_URI / ?url=
    // =========================================================

    /**
     * Sub-router principal.
     * Rutas soportadas:
     *   POST   /api/v1/pedidos          → v1CrearPedido
     *   GET    /api/v1/pedidos/{id}     → v1ConsultarPedido
     *   GET    /api/v1/productos        → v1BuscarProductos
     *   GET    /api/v1/productos/{id}   → v1DetalleProducto
     *   GET|POST /api/v1/ping          → health-check
     */
    public function v1(?string $resource = null): void
    {
        // CORS preflight
        if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
            header('Access-Control-Allow-Origin: *');
            header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
            header('Access-Control-Allow-Headers: Authorization, Content-Type');
            http_response_code(204);
            exit;
        }

        header('Content-Type: application/json; charset=utf-8');
        header('Access-Control-Allow-Origin: *');

        // Extraer el ID del 4° segmento de la URL
        // Funciona tanto con clean URLs como con ?url=api/v1/pedidos/123
        $urlParam = trim($_GET['url'] ?? '', '/');
        $segs     = array_values(array_filter(explode('/', $urlParam)));
        // fallback: REQUEST_URI
        if (count($segs) < 4) {
            $path = parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH);
            $segs = array_values(array_filter(explode('/', trim($path, '/'))));
        }
        $id     = (isset($segs[3]) && ctype_digit((string)$segs[3])) ? (int)$segs[3] : null;
        $method = $_SERVER['REQUEST_METHOD'];

        // Routing por recurso + método + presencia de id
        $route = strtoupper($method) . ':' . ($resource ?? '') . ($id ? ':id' : '');

        switch ($route) {
            case 'GET:ping':
            case 'POST:ping':
                $token = $this->requireApiToken([]);
                $this->apiOk(['pong' => true, 'empresa_id' => (int)$token['empresa_id']]);
                break;

            case 'POST:pedidos':
                $token = $this->requireApiToken(['pedidos:crear']);
                $this->v1CrearPedido($token);
                break;

            case 'GET:pedidos:id':
                $token = $this->requireApiToken(['pedidos:leer']);
                $this->v1ConsultarPedido($token, $id);
                break;

            case 'GET:productos':
                $token = $this->requireApiToken(['productos:leer']);
                $this->v1BuscarProductos($token);
                break;

            case 'GET:productos:id':
                $token = $this->requireApiToken(['productos:leer']);
                $this->v1DetalleProducto($token, $id);
                break;

            default:
                $this->apiError('Recurso o método no encontrado', 404);
        }
    }

    // ── Helpers de la API v1 ───────────────────────────────────

    /** Respuesta de éxito de la API v1 */
    private function apiOk(array $data): void
    {
        echo json_encode(['ok' => true, 'data' => $data]);
        exit;
    }

    /** Respuesta de error de la API v1 (termina la ejecución) */
    private function apiError(string $message, int $code = 400): void
    {
        http_response_code($code);
        echo json_encode(['ok' => false, 'error' => $message]);
        exit;
    }

    /**
     * Valida el Bearer token contra api_tokens, verifica scopes
     * y registra la llamada en api_access_log.
     * Devuelve la fila del token si es válido; llama apiError() si no.
     */
    private function requireApiToken(array $scopesRequired): array
    {
        $header = $_SERVER['HTTP_AUTHORIZATION']
                  ?? $_SERVER['REDIRECT_HTTP_AUTHORIZATION']
                  ?? '';
        if (!str_starts_with($header, 'Bearer ')) {
            $this->apiError('Authorization: Bearer <token> requerido', 401);
        }

        $rawToken = substr($header, 7);
        if ($rawToken === '') {
            $this->apiError('Token vacío', 401);
        }

        $hash = hash('sha256', $rawToken);
        $db   = Database::getInstance();

        $stmt = $db->prepare(
            "SELECT id, empresa_id, comprador_id, nombre, scopes
               FROM api_tokens
              WHERE token = ? AND activo = 1
              LIMIT 1"
        );
        $stmt->execute([$hash]);
        $token = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$token) {
            $this->apiError('Token inválido o desactivado', 401);
        }

        // Verificar scopes
        $tokenScopes = json_decode($token['scopes'], true) ?? [];
        foreach ($scopesRequired as $scope) {
            if (!in_array($scope, $tokenScopes, true)) {
                $this->apiError("Permiso requerido: {$scope}", 403);
            }
        }

        // Auditoría y actualización de último uso (no crítico)
        try {
            $db->prepare(
                "INSERT INTO api_access_log (token_id, endpoint, metodo, ip, status)
                 VALUES (?, ?, ?, ?, 200)"
            )->execute([
                $token['id'],
                substr($_SERVER['REQUEST_URI'] ?? '', 0, 255),
                $_SERVER['REQUEST_METHOD'] ?? 'GET',
                $_SERVER['REMOTE_ADDR'] ?? '',
            ]);
            $db->prepare("UPDATE api_tokens SET ultimo_uso = NOW() WHERE id = ?")
               ->execute([$token['id']]);
        } catch (\Throwable) {
            // No bloquear la petición por un fallo de auditoría
        }

        return $token;
    }

    // ── Implementaciones de endpoints ──────────────────────────

    /**
     * POST /api/v1/pedidos
     * Body JSON: { "items": [{"producto_id":1,"cantidad":5.0}], "notas":"..." }
     * Crea un pedido B2B a nombre del comprador vinculado al token.
     */
    private function v1CrearPedido(array $token): void
    {
        $body  = json_decode(file_get_contents('php://input'), true) ?? [];
        $items = $body['items'] ?? [];
        $notas = trim($body['notas'] ?? '');

        if (empty($items) || !is_array($items)) {
            $this->apiError('El campo "items" es obligatorio y debe ser un array', 422);
        }

        $empresaId   = (int)$token['empresa_id'];
        $compradorId = (int)$token['comprador_id'];
        $db          = Database::getInstance();

        // Validar y preparar líneas
        $lineas   = [];
        $subtotal = 0.0;

        foreach ($items as $idx => $item) {
            $productoId = (int)($item['producto_id'] ?? 0);
            $cantidad   = (float)($item['cantidad'] ?? 0);

            if (!$productoId || $cantidad <= 0) {
                $this->apiError("Item [{$idx}]: producto_id y cantidad son obligatorios", 422);
            }

            $stmt = $db->prepare(
                "SELECT id, nombre, precio_base, activo
                   FROM productos
                  WHERE id = ? AND empresa_id = ? AND activo = 1
                  LIMIT 1"
            );
            $stmt->execute([$productoId, $empresaId]);
            $prod = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$prod) {
                $this->apiError("Producto {$productoId} no encontrado o inactivo", 404);
            }

            $precioUnit  = (float)$prod['precio_base'];
            $subLinea    = round($precioUnit * $cantidad, 2);
            $subtotal   += $subLinea;
            $lineas[]    = [
                'producto_id' => $productoId,
                'cantidad'    => $cantidad,
                'precio_unit' => $precioUnit,
                'subtotal'    => $subLinea,
            ];
        }

        $total = $subtotal; // sin impuestos adicionales en pedidos API
        $folio = 'API-' . $empresaId . '-' . date('YmdHis') . '-' . rand(100, 999);

        try {
            $db->beginTransaction();

            $db->prepare(
                "INSERT INTO pedidos (folio, empresa_id, comprador_id, estado,
                                      subtotal, total, notas)
                 VALUES (?, ?, ?, 'pendiente', ?, ?, ?)"
            )->execute([$folio, $empresaId, $compradorId, $subtotal, $total, $notas ?: null]);

            $pedidoId = (int)$db->lastInsertId();

            $stmtDet = $db->prepare(
                "INSERT INTO pedido_detalle (pedido_id, producto_id, cantidad, precio_unit, subtotal)
                 VALUES (?, ?, ?, ?, ?)"
            );
            foreach ($lineas as $linea) {
                $stmtDet->execute([
                    $pedidoId,
                    $linea['producto_id'],
                    $linea['cantidad'],
                    $linea['precio_unit'],
                    $linea['subtotal'],
                ]);
            }

            $db->commit();
        } catch (\Throwable $e) {
            $db->rollBack();
            $this->apiError('Error al crear el pedido: ' . $e->getMessage(), 500);
        }

        $this->apiOk([
            'pedido_id' => $pedidoId,
            'folio'     => $folio,
            'estado'    => 'pendiente',
            'subtotal'  => $subtotal,
            'total'     => $total,
            'items'     => count($lineas),
        ]);
    }

    /**
     * GET /api/v1/pedidos/{id}
     * Devuelve estado y detalle del pedido, restringido a la empresa del token.
     */
    private function v1ConsultarPedido(array $token, int $pedidoId): void
    {
        $empresaId = (int)$token['empresa_id'];
        $db        = Database::getInstance();

        $stmt = $db->prepare(
            "SELECT id, folio, estado, subtotal, total, notas, created_at
               FROM pedidos
              WHERE id = ? AND empresa_id = ?
              LIMIT 1"
        );
        $stmt->execute([$pedidoId, $empresaId]);
        $pedido = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$pedido) {
            $this->apiError('Pedido no encontrado', 404);
        }

        $stmtDet = $db->prepare(
            "SELECT pd.producto_id, pr.nombre AS producto_nombre,
                    pd.cantidad, pd.precio_unit, pd.subtotal
               FROM pedido_detalle pd
               JOIN productos pr ON pr.id = pd.producto_id
              WHERE pd.pedido_id = ?"
        );
        $stmtDet->execute([$pedidoId]);
        $detalle = $stmtDet->fetchAll(PDO::FETCH_ASSOC);

        $this->apiOk([
            'pedido_id'  => (int)$pedido['id'],
            'folio'      => $pedido['folio'],
            'estado'     => $pedido['estado'],
            'subtotal'   => (float)$pedido['subtotal'],
            'total'      => (float)$pedido['total'],
            'notas'      => $pedido['notas'],
            'created_at' => $pedido['created_at'],
            'items'      => array_map(static fn($row) => [
                'producto_id'     => (int)$row['producto_id'],
                'producto_nombre' => $row['producto_nombre'],
                'cantidad'        => (float)$row['cantidad'],
                'precio_unit'     => (float)$row['precio_unit'],
                'subtotal'        => (float)$row['subtotal'],
            ], $detalle),
        ]);
    }

    /**
     * GET /api/v1/productos?q=ribeye&categoria_id=2&limit=20
     * Busca en el catálogo de la empresa del token.
     */
    private function v1BuscarProductos(array $token): void
    {
        $empresaId = (int)$token['empresa_id'];
        $q         = trim($_GET['q'] ?? '');
        $catId     = (int)($_GET['categoria_id'] ?? 0);
        $limit     = min(max((int)($_GET['limit'] ?? 20), 1), 100);
        $db        = Database::getInstance();

        $sql    = "SELECT p.id, p.nombre, p.descripcion, p.presentacion,
                          p.precio_base, p.activo, c.nombre AS categoria
                     FROM productos p
                     LEFT JOIN categorias c ON c.id = p.categoria_id
                    WHERE p.empresa_id = ? AND p.activo = 1";
        $params = [$empresaId];

        if ($q !== '') {
            $sql     .= " AND p.nombre LIKE ?";
            $params[] = '%' . $q . '%';
        }
        if ($catId > 0) {
            $sql     .= " AND p.categoria_id = ?";
            $params[] = $catId;
        }

        $sql .= " ORDER BY p.nombre ASC LIMIT {$limit}";

        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $productos = array_map(static fn($r) => [
            'id'          => (int)$r['id'],
            'nombre'      => $r['nombre'],
            'descripcion' => $r['descripcion'],
            'presentacion'=> $r['presentacion'],
            'precio_base' => (float)$r['precio_base'],
            'categoria'   => $r['categoria'],
        ], $rows);

        $this->apiOk(['productos' => $productos, 'total' => count($productos)]);
    }

    /**
     * GET /api/v1/productos/{id}
     * Devuelve el detalle completo de un producto de la empresa del token.
     */
    private function v1DetalleProducto(array $token, int $productoId): void
    {
        $empresaId = (int)$token['empresa_id'];
        $db        = Database::getInstance();

        $stmt = $db->prepare(
            "SELECT p.id, p.nombre, p.descripcion, p.presentacion,
                    p.precio_base, p.imagen, p.activo,
                    c.nombre AS categoria,
                    COALESCE(i.stock, 0) AS stock_actual
               FROM productos p
               LEFT JOIN categorias c ON c.id = p.categoria_id
               LEFT JOIN inventario i ON i.producto_id = p.id
              WHERE p.id = ? AND p.empresa_id = ?
              LIMIT 1"
        );
        $stmt->execute([$productoId, $empresaId]);
        $prod = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$prod) {
            $this->apiError('Producto no encontrado', 404);
        }

        // Precios escalonados
        $stmtEsc = $db->prepare(
            "SELECT cantidad_min, cantidad_max, precio
               FROM precios_escalonados
              WHERE producto_id = ?
              ORDER BY cantidad_min ASC"
        );
        $stmtEsc->execute([$productoId]);
        $escalonados = $stmtEsc->fetchAll(PDO::FETCH_ASSOC);

        $this->apiOk([
            'id'           => (int)$prod['id'],
            'nombre'       => $prod['nombre'],
            'descripcion'  => $prod['descripcion'],
            'presentacion' => $prod['presentacion'],
            'precio_base'  => (float)$prod['precio_base'],
            'imagen'       => $prod['imagen'],
            'categoria'    => $prod['categoria'],
            'stock_actual' => (float)$prod['stock_actual'],
            'precios_escalonados' => array_map(static fn($e) => [
                'cantidad_min' => (float)$e['cantidad_min'],
                'cantidad_max' => $e['cantidad_max'] !== null ? (float)$e['cantidad_max'] : null,
                'precio'       => (float)$e['precio'],
            ], $escalonados),
        ]);
    }

    // ══════════════════════════════════════════════════════════════════
    // API v1 — Integración CapiRest (Bearer token, sin sesión PHP)
    // Rutas públicas: api/pedidos  api/productos
    // Autenticación: Authorization: Bearer {raw_token}
    //   → SHA2(raw_token,256) se compara con api_tokens.token
    // ══════════════════════════════════════════════════════════════════

    /**
     * Valida el Bearer token de la cabecera Authorization.
     * Retorna la fila de api_tokens o termina con HTTP 401.
     */
    private function requireBearer(): array
    {
        $header = $_SERVER['HTTP_AUTHORIZATION']
                  ?? $_SERVER['REDIRECT_HTTP_AUTHORIZATION']
                  ?? '';

        if (!preg_match('/^Bearer\s+(\S+)$/i', trim($header), $m)) {
            http_response_code(401);
            header('Content-Type: application/json');
            echo json_encode(['ok' => false, 'error' => 'Token requerido']);
            exit;
        }

        $rawToken  = $m[1];
        $tokenHash = hash('sha256', $rawToken);

        $db   = Database::getInstance();
        $stmt = $db->prepare(
            'SELECT t.id, t.empresa_id, t.comprador_id, t.scopes, t.webhook_url, t.webhook_secret
               FROM api_tokens t
              WHERE t.token = ? AND t.activo = 1
              LIMIT 1'
        );
        $stmt->execute([$tokenHash]);
        $tokenRow = $stmt->fetch(\PDO::FETCH_ASSOC);

        if (!$tokenRow) {
            http_response_code(401);
            header('Content-Type: application/json');
            echo json_encode(['ok' => false, 'error' => 'Token inválido o inactivo']);
            exit;
        }

        // Actualizar último uso
        $db->prepare('UPDATE api_tokens SET ultimo_uso = NOW() WHERE id = ?')
           ->execute([$tokenRow['id']]);

        // Auditoría
        try {
            $db->prepare(
                'INSERT INTO api_access_log (token_id, endpoint, metodo, ip, status) VALUES (?, ?, ?, ?, ?)'
            )->execute([
                $tokenRow['id'],
                $_SERVER['REQUEST_URI'] ?? '',
                $_SERVER['REQUEST_METHOD'] ?? 'GET',
                $_SERVER['REMOTE_ADDR'] ?? '',
                200,
            ]);
        } catch (\Throwable $_) { /* tabla aún no migrada — no bloquear */ }

        $tokenRow['scopes'] = json_decode($tokenRow['scopes'] ?? '[]', true) ?? [];
        return $tokenRow;
    }

    /** Verifica que el token tenga el scope requerido */
    private function requireScope(array $tokenRow, string $scope): void
    {
        if (!in_array($scope, $tokenRow['scopes'], true)) {
            http_response_code(403);
            header('Content-Type: application/json');
            echo json_encode(['ok' => false, 'error' => "Sin permiso: scope '$scope' requerido"]);
            exit;
        }
    }

    // ── POST /api/pedidos   — crear pedido desde CapiRest ────────────
    // ── GET  /api/pedidos/{id} — consultar estado de un pedido ───────
    public function pedidos(?string $id = null): void
    {
        $tokenRow  = $this->requireBearer();
        $empresaId = (int)$tokenRow['empresa_id'];
        $compradorId = (int)$tokenRow['comprador_id'];
        $method    = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');

        // GET /api/pedidos/{id} — consultar estado
        if ($method === 'GET') {
            $this->requireScope($tokenRow, 'pedidos:leer');
            $pedidoId = (int)$id;
            if (!$pedidoId) {
                $this->json(['ok' => false, 'error' => 'ID de pedido requerido'], 400);
            }

            $db = Database::getInstance();
            // Fallback: si updated_at no existe todavía (migración pendiente), hacer query sin ella
            try {
                $stmt = $db->prepare(
                    'SELECT id, capirest_pedido_id, folio, estado, subtotal, iva, total, created_at, updated_at
                       FROM pedidos
                      WHERE id = ? AND empresa_id = ?
                      LIMIT 1'
                );
                $stmt->execute([$pedidoId, $empresaId]);
            } catch (\PDOException $e) {
                if (str_contains($e->getMessage(), 'updated_at')) {
                    $stmt = $db->prepare(
                        'SELECT id, capirest_pedido_id, folio, estado, subtotal, iva, total, created_at
                           FROM pedidos
                          WHERE id = ? AND empresa_id = ?
                          LIMIT 1'
                    );
                    $stmt->execute([$pedidoId, $empresaId]);
                } else {
                    throw $e;
                }
            }
            $pedido = $stmt->fetch(\PDO::FETCH_ASSOC);

            if (!$pedido) {
                $this->json(['ok' => false, 'error' => 'Pedido no encontrado'], 404);
            }

            $this->json(['ok' => true, 'pedido' => [
                'id'                 => (int)$pedido['id'],
                'capirest_pedido_id' => $pedido['capirest_pedido_id'] ? (int)$pedido['capirest_pedido_id'] : null,
                'folio'              => $pedido['folio'],
                'estado'             => $pedido['estado'],
                'subtotal'           => (float)$pedido['subtotal'],
                'iva'                => (float)$pedido['iva'],
                'total'              => (float)$pedido['total'],
                'created_at'         => $pedido['created_at'],
                'updated_at'         => $pedido['updated_at'] ?? null,
            ]]);
        }

        // POST /api/pedidos — crear pedido
        if ($method === 'POST') {
            $this->requireScope($tokenRow, 'pedidos:crear');

            $body = json_decode(file_get_contents('php://input'), true);
            if (!is_array($body)) {
                $this->json(['ok' => false, 'error' => 'Body JSON inválido'], 400);
            }

            $items              = $body['items'] ?? [];
            $capirestPedidoId   = isset($body['capirest_pedido_id']) ? (int)$body['capirest_pedido_id'] : null;
            $fechaEntrega       = !empty($body['fecha_entrega']) ? $body['fecha_entrega'] : null;
            $notas              = isset($body['notas']) ? substr(trim($body['notas']), 0, 500) : null;
            // Datos de ubicación del comprador (restaurante) enviados por CapiRest
            $compradorNombre    = isset($body['comprador_nombre'])    ? substr(trim((string)$body['comprador_nombre']), 0, 200)    : null;
            $compradorDireccion = isset($body['comprador_direccion']) ? substr(trim((string)$body['comprador_direccion']), 0, 500) : null;
            $compradorTelefono  = isset($body['comprador_telefono'])  ? substr(trim((string)$body['comprador_telefono']), 0, 30)   : null;
            $compradorLat       = isset($body['comprador_lat'])  && is_numeric($body['comprador_lat'])  ? (float)$body['comprador_lat']  : null;
            $compradorLng       = isset($body['comprador_lng'])  && is_numeric($body['comprador_lng'])  ? (float)$body['comprador_lng']  : null;

            if (empty($items) || !is_array($items)) {
                $this->json(['ok' => false, 'error' => 'Se requiere al menos un item'], 422);
            }

            // Validar y preparar líneas del pedido
            $db     = Database::getInstance();
            $lineas = [];
            foreach ($items as $item) {
                $productoId = (int)($item['producto_id'] ?? 0);
                $cantidad   = (float)($item['cantidad'] ?? 0);
                $precioUnit = (float)($item['precio_unit'] ?? 0);

                if ($productoId <= 0 || $cantidad <= 0 || $precioUnit <= 0) {
                    $this->json(['ok' => false, 'error' => "Item inválido: producto_id=$productoId cantidad=$cantidad precio_unit=$precioUnit"], 422);
                }

                // Verificar que el producto pertenece a la empresa
                $stmt = $db->prepare(
                    'SELECT id FROM productos WHERE id = ? AND empresa_id = ? AND activo = 1 LIMIT 1'
                );
                $stmt->execute([$productoId, $empresaId]);
                if (!$stmt->fetch()) {
                    $this->json(['ok' => false, 'error' => "Producto $productoId no encontrado o inactivo"], 422);
                }

                $lineas[] = [
                    'producto_id' => $productoId,
                    'cantidad'    => $cantidad,
                    'precio_unit' => $precioUnit,
                    'subtotal'    => round($cantidad * $precioUnit, 2),
                ];
            }

            // Crear el pedido
            $model = new PedidoModel();
            try {
                $pedidoId = $model->crear(
                    [
                        'empresa_id'          => $empresaId,
                        'comprador_id'        => $compradorId,
                        'capirest_pedido_id'  => $capirestPedidoId,
                        'estado'              => 'pendiente',
                        'requiere_aprobacion' => 1,
                        'fecha_entrega'       => $fechaEntrega,
                        'notas'               => $notas,
                        'tipo'                => 'api',
                        'comprador_nombre'    => $compradorNombre,
                        'comprador_direccion' => $compradorDireccion,
                        'comprador_telefono'  => $compradorTelefono,
                        'comprador_lat'       => $compradorLat,
                        'comprador_lng'       => $compradorLng,
                    ],
                    $lineas
                );
            } catch (\Throwable $e) {
                error_log('[ApiController::pedidos] Error al crear pedido: ' . $e->getMessage());
                $this->json(['ok' => false, 'error' => 'Error interno al crear pedido'], 500);
            }

            $this->json(['ok' => true, 'pedido_id' => $pedidoId], 201);
        }

        $this->json(['ok' => false, 'error' => 'Método no permitido'], 405);
    }

    // ── GET /api/productos — catálogo de la empresa del token ────────
    public function productos(?string $p = null): void
    {
        $tokenRow  = $this->requireBearer();
        $this->requireScope($tokenRow, 'productos:leer');

        $empresaId = (int)$tokenRow['empresa_id'];
        $compradorId = (int)$tokenRow['comprador_id'];

        $buscar = substr(trim($this->get('buscar', '')), 0, 100);
        $page   = max(1, (int)$this->get('page', 1));
        $limit  = min(50, max(1, (int)$this->get('limit', 20)));
        $offset = ($page - 1) * $limit;

        $db = Database::getInstance();

        $where  = ['p.empresa_id = ?', 'p.activo = 1'];
        $params = [$empresaId, $empresaId];

        if ($buscar !== '') {
            $where[]  = '(p.nombre LIKE ? OR p.codigo LIKE ? OR p.descripcion LIKE ?)';
            $t = '%' . $buscar . '%';
            array_push($params, $t, $t, $t);
        }

        $sql = 'SELECT p.id, p.codigo, p.nombre, p.descripcion, p.unidad,
                       p.precio_base,
                       COALESCE(ep.precio_especial, p.precio_base) AS precio_comprador,
                       p.stock_disponible, p.imagen_path
                  FROM productos p
                  LEFT JOIN empresa_precio_especial ep
                    ON ep.producto_id = p.id AND ep.comprador_id = ?
                 WHERE ' . implode(' AND ', $where) . '
              ORDER BY p.nombre ASC
                 LIMIT ' . $limit . ' OFFSET ' . $offset;

        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        $rows = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        $productos = array_map(static fn(array $r): array => [
            'id'               => (int)$r['id'],
            'codigo'           => $r['codigo'],
            'nombre'           => $r['nombre'],
            'descripcion'      => $r['descripcion'],
            'unidad'           => $r['unidad'],
            'precio_base'      => (float)$r['precio_base'],
            'precio_comprador' => (float)$r['precio_comprador'],
            'stock_disponible' => (float)$r['stock_disponible'],
        ], $rows);

        $this->json(['ok' => true, 'page' => $page, 'limit' => $limit, 'productos' => $productos]);
    }
}
