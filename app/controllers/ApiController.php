<?php
require_once ROOT_PATH . '/app/controllers/BaseController.php';

/**
 * ApiController — Endpoints AJAX (sin layout HTML)
 * Maneja: precios escalonados, GPS tracking, API v1 (CapiRest),
 *         Admin API v1 (JWT Bearer para el sitio web admin)
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

    /** GET /api/promociones?usuario_id=X - promociones activas para la app movil */
    public function promociones(?string $p = null): void
    {
        header('Access-Control-Allow-Origin: *');

        $mobileUsuarioId = (int)(
            $this->get('usuario_id')
            ?: $this->get('mobile_usuario_id')
            ?: $this->get('user_id')
            ?: 0
        );

        if ($mobileUsuarioId <= 0) {
            $this->json(['ok' => false, 'error' => 'usuario_id requerido'], 400);
        }

        $model = new RestClienteModel();
        $promociones = array_map(
            fn(array $promotion): array => $this->mobilePromotionApiResource($promotion),
            $model->getPromocionesMobileActivas($mobileUsuarioId)
        );
        $this->json([
            'ok' => true,
            'data' => [
                'promociones' => $promociones,
            ],
        ]);
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
    // =========================================================

    /**
     * Sub-router principal.
     */
    public function v1(?string $resource = null): void
    {
        if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
            header('Access-Control-Allow-Origin: *');
            header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
            header('Access-Control-Allow-Headers: Authorization, Content-Type');
            http_response_code(204);
            exit;
        }

        header('Content-Type: application/json; charset=utf-8');
        header('Access-Control-Allow-Origin: *');

        $urlParam = trim($_GET['url'] ?? '', '/');
        $segs     = array_values(array_filter(explode('/', $urlParam)));
        if (count($segs) < 4) {
            $path = parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH);
            $segs = array_values(array_filter(explode('/', trim($path, '/'))));
        }
        $resourceParts = array_values(array_filter(explode('/', trim((string)($resource ?? ''), '/'))));
        $resourceName = $resourceParts[0] ?? $resource;
        $resourceId = (isset($resourceParts[1]) && ctype_digit((string)$resourceParts[1])) ? (int)$resourceParts[1] : null;
        $id     = (isset($segs[3]) && ctype_digit((string)$segs[3])) ? (int)$segs[3] : $resourceId;
        $method = $_SERVER['REQUEST_METHOD'];

        $route = strtoupper($method) . ':' . ($resourceName ?? '') . ($id ? ':id' : '');

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

            case 'POST:rest-pedidos':
                $this->v1CrearRestPedidoMobile();
                break;

            case 'POST:push-tokens':
            case 'POST:mobile-push-tokens':
            case 'POST:notification-tokens':
                $this->v1RegistrarPushTokenMobile();
                break;

            case 'POST:reservaciones':
                $this->v1CrearReservacionMobile();
                break;

            case 'GET:pedidos:id':
                $token = $this->requireApiToken(['pedidos:leer']);
                $this->v1ConsultarPedido($token, $id);
                break;

            case 'GET:rest-pedidos:id':
                $this->v1ConsultarRestPedidoMobile($id);
                break;

            case 'GET:productos':
                $token = $this->requireApiToken(['productos:leer']);
                $this->v1BuscarProductos($token);
                break;

            case 'GET:productos:id':
                $token = $this->requireApiToken(['productos:leer']);
                $this->v1DetalleProducto($token, $id);
                break;

            case 'GET:promociones':
                $this->v1PromocionesMobile();
                break;

            case 'POST:promociones':
                if (($segs[3] ?? '') === 'aplicar' || $this->get('action') === 'aplicar') {
                    $this->v1AplicarPromocionMobile();
                }
                $this->apiError('Recurso o metodo no encontrado', 404);
                break;

            default:
                $this->apiError('Recurso o método no encontrado', 404);
        }
    }

    // ── Helpers de la API v1 ───────────────────────────────────

    private function apiOk(array $data): void
    {
        echo json_encode(['ok' => true, 'data' => $data]);
        exit;
    }

    private function apiError(string $message, int $code = 400): void
    {
        http_response_code($code);
        echo json_encode(['ok' => false, 'error' => $message]);
        exit;
    }

    private function v1PromocionesMobile(): void
    {
        $mobileUsuarioId = (int)(
            $this->get('usuario_id')
            ?: $this->get('mobile_usuario_id')
            ?: $this->get('user_id')
            ?: 0
        );

        if ($mobileUsuarioId <= 0) {
            $this->apiError('usuario_id requerido', 400);
        }

        $model = new RestClienteModel();
        $promociones = array_map(
            fn(array $promotion): array => $this->mobilePromotionApiResource($promotion),
            $model->getPromocionesMobileActivas($mobileUsuarioId)
        );
        $this->apiOk([
            'promociones' => $promociones,
        ]);
    }

    private function v1AplicarPromocionMobile(): void
    {
        $body = json_decode(file_get_contents('php://input'), true);
        if (!is_array($body)) {
            $body = $_POST;
        }

        $mobileUsuarioId = (int)($body['usuario_id'] ?? $body['mobile_usuario_id'] ?? $body['user_id'] ?? 0);
        $code = trim((string)($body['code'] ?? $body['codigo'] ?? ''));
        $promotionId = (int)($body['promotion_id'] ?? $body['promocion_id'] ?? 0);
        $items = $body['items'] ?? $body['productos'] ?? [];

        if ($mobileUsuarioId <= 0) {
            $this->apiError('usuario_id requerido', 400);
        }
        if ($code === '' && $promotionId <= 0) {
            $this->apiError('code o promotion_id requerido', 400);
        }
        if (!is_array($items) || empty($items)) {
            $this->apiError('items requerido', 400);
        }

        $promotion = $this->findMobilePromotionForCalculation($mobileUsuarioId, $promotionId, $code);
        if (!$promotion) {
            $this->apiError('Promocion no encontrada o expirada', 404);
        }

        $this->apiOk($this->calculatePromotionDiscount($promotion, $items));
    }

    private function v1RegistrarPushTokenMobile(): void
    {
        $body = json_decode(file_get_contents('php://input'), true);
        if (!is_array($body)) {
            $body = $_POST;
        }

        $mobileUsuarioId = (int)($body['usuario_id'] ?? $body['mobile_usuario_id'] ?? $body['user_id'] ?? 0);
        $fcmToken = trim((string)($body['fcm_token'] ?? $body['token'] ?? $body['device_token'] ?? ''));
        $platform = strtolower(trim((string)($body['platform'] ?? $body['plataforma'] ?? '')));
        $deviceId = trim((string)($body['device_id'] ?? $body['deviceId'] ?? $body['installation_id'] ?? ''));

        if ($mobileUsuarioId <= 0) {
            $this->apiError('usuario_id requerido', 400);
        }
        if ($fcmToken === '') {
            $this->apiError('fcm_token requerido', 400);
        }
        if (strlen($fcmToken) > 500) {
            $this->apiError('fcm_token demasiado largo', 422);
        }
        if ($platform !== '' && !in_array($platform, ['ios', 'android', 'web'], true)) {
            $platform = substr($platform, 0, 30);
        }

        $token = $this->mobileUpsertPushToken($mobileUsuarioId, $fcmToken, $platform, $deviceId);
        $this->apiOk([
            'push_token' => $token,
            'message' => 'Token push registrado correctamente',
        ]);
    }

    private function mobileUpsertPushToken(int $mobileUsuarioId, string $fcmToken, string $platform = '', string $deviceId = ''): array
    {
        if (!$this->adminEnsureMobilePushTokensTable()) {
            $this->apiError('No se pudo preparar la tabla de tokens push', 500);
        }

        $db = Database::getInstance();
        $now = date('Y-m-d H:i:s');
        $platform = $platform !== '' ? substr($platform, 0, 30) : null;
        $deviceId = $deviceId !== '' ? substr($deviceId, 0, 190) : null;

        $stmt = null;
        if ($deviceId !== null) {
            $stmt = $db->prepare(
                "SELECT id
                   FROM mobile_push_tokens
                  WHERE usuario_id = ? AND device_id = ?
                  ORDER BY id DESC
                  LIMIT 1"
            );
            $stmt->execute([$mobileUsuarioId, $deviceId]);
        }
        $existingId = $stmt ? (int)($stmt->fetchColumn() ?: 0) : 0;

        if ($existingId <= 0) {
            $stmt = $db->prepare(
                "SELECT id
                   FROM mobile_push_tokens
                  WHERE usuario_id = ? AND fcm_token = ?
                  ORDER BY id DESC
                  LIMIT 1"
            );
            $stmt->execute([$mobileUsuarioId, $fcmToken]);
            $existingId = (int)($stmt->fetchColumn() ?: 0);
        }

        if ($existingId > 0) {
            $stmt = $db->prepare(
                "UPDATE mobile_push_tokens
                    SET fcm_token = ?,
                        platform = COALESCE(?, platform),
                        device_id = COALESCE(?, device_id),
                        enabled = 1,
                        updated_at = ?,
                        last_seen_at = ?
                  WHERE id = ?"
            );
            $stmt->execute([$fcmToken, $platform, $deviceId, $now, $now, $existingId]);
            $id = $existingId;
        } else {
            $stmt = $db->prepare(
                "INSERT INTO mobile_push_tokens
                    (usuario_id, fcm_token, platform, device_id, enabled, created_at, updated_at, last_seen_at)
                 VALUES (?, ?, ?, ?, 1, ?, ?, ?)"
            );
            $stmt->execute([$mobileUsuarioId, $fcmToken, $platform, $deviceId, $now, $now, $now]);
            $id = (int)$db->lastInsertId();
        }

        return [
            'id' => $id,
            'usuario_id' => $mobileUsuarioId,
            'platform' => $platform,
            'device_id' => $deviceId,
            'enabled' => 1,
            'last_seen_at' => $now,
        ];
    }

    private function adminEnsureMobilePushTokensTable(): bool
    {
        try {
            Database::getInstance()->query("SELECT 1 FROM `mobile_push_tokens` LIMIT 0");
            return true;
        } catch (\Throwable $e) {
            // Continuar e intentar crear la tabla en instalaciones que aun no la tengan.
        }

        try {
            Database::getInstance()->exec(
                "CREATE TABLE IF NOT EXISTS `mobile_push_tokens` (
                    `id` int(11) NOT NULL AUTO_INCREMENT,
                    `usuario_id` int(11) NOT NULL,
                    `fcm_token` varchar(500) COLLATE utf8mb4_unicode_ci NOT NULL,
                    `platform` varchar(30) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
                    `device_id` varchar(190) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
                    `enabled` tinyint(1) NOT NULL DEFAULT '1',
                    `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
                    `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
                    `last_seen_at` timestamp NULL DEFAULT NULL,
                    PRIMARY KEY (`id`),
                    KEY `idx_mobile_push_tokens_usuario` (`usuario_id`,`enabled`),
                    KEY `idx_mobile_push_tokens_device` (`device_id`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
            );
            Database::getInstance()->query("SELECT 1 FROM `mobile_push_tokens` LIMIT 0");
            return true;
        } catch (\Throwable $e) {
            error_log('[adminEnsureMobilePushTokensTable] No se pudo crear mobile_push_tokens: ' . $e->getMessage());
            return false;
        }
    }

    private function v1CrearReservacionMobile(): void
    {
        $body = json_decode(file_get_contents('php://input'), true);
        if (!is_array($body)) {
            $body = $_POST;
        }

        $restauranteId = $this->mobileRestauranteIdFromPayload($body);
        if ($restauranteId <= 0) {
            $this->apiError('restaurante_id o branch_id requerido', 400);
        }

        $restaurante = (new RestauranteModel())->find($restauranteId);
        if (!$restaurante || empty($restaurante['activo'])) {
            $this->apiError('Restaurante no encontrado', 404);
        }
        if (empty($restaurante['reservas_habilitadas'])) {
            $this->apiError('Reservaciones no disponibles para este restaurante', 422);
        }

        $nombre = trim((string)($body['nombre'] ?? $body['cliente_nombre'] ?? $body['customer_name'] ?? ''));
        $telefono = preg_replace('/\D/', '', (string)($body['telefono'] ?? $body['phone'] ?? $body['cliente_telefono'] ?? ''));
        $email = trim((string)($body['email'] ?? $body['correo'] ?? $body['cliente_email'] ?? ''));
        $fecha = trim((string)($body['fecha'] ?? $body['date'] ?? ''));
        $hora = trim((string)($body['hora'] ?? $body['time'] ?? ''));
        $personas = max(1, (int)($body['personas'] ?? $body['people'] ?? $body['party_size'] ?? 2));
        $notas = trim((string)($body['notas'] ?? $body['notes'] ?? ''));
        $mesaId = (int)($body['mesa_id'] ?? $body['table_id'] ?? 0);

        if ($nombre === '' || $telefono === '' || $fecha === '' || $hora === '') {
            $this->apiError('nombre, telefono, fecha y hora son requeridos', 422);
        }
        if (strlen($telefono) !== 10) {
            $this->apiError('telefono debe contener 10 digitos', 422);
        }
        if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->apiError('email invalido', 422);
        }
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $fecha)) {
            $this->apiError('fecha debe tener formato YYYY-MM-DD', 422);
        }
        if (strlen($hora) === 5) {
            $hora .= ':00';
        }
        if (!preg_match('/^\d{2}:\d{2}:\d{2}$/', $hora)) {
            $this->apiError('hora debe tener formato HH:MM o HH:MM:SS', 422);
        }

        $reservaModel = new RestReservaModel();
        $mesa = $this->mobileResolverMesaReserva($restauranteId, $mesaId, $fecha, $hora, $personas, $reservaModel);
        if (!$mesa) {
            $this->apiError('No hay mesa disponible para ese horario', 409);
        }

        $meseroId = $reservaModel->meseroAsignadoPorMesa((int)$mesa['id'], $restauranteId);
        $newId = $reservaModel->insert($reservaModel->aplicarCanalReserva([
            'restaurante_id' => $restauranteId,
            'mesa_id'        => (int)$mesa['id'],
            'mesero_id'      => $meseroId,
            'nombre'         => substr($nombre, 0, 150),
            'telefono'       => $telefono,
            'email'          => $email !== '' ? strtolower($email) : null,
            'fecha'          => $fecha,
            'hora'           => $hora,
            'personas'       => $personas,
            'notas'          => $notas !== '' ? substr($notas, 0, 500) : null,
            'estado'         => 'confirmada',
            'origen'         => 'comensal',
        ], 'movil'));

        $emailResult = $this->mobileEnviarConfirmacionReserva($reservaModel, $restaurante, [
            'id' => $newId,
            'nombre' => $nombre,
            'email' => $email,
            'fecha' => $fecha,
            'hora' => $hora,
            'personas' => $personas,
            'mesa_id' => (int)$mesa['id'],
            'mesa_nombre' => (string)($mesa['nombre'] ?? 'Por asignar'),
            'estado' => 'confirmada',
        ]);

        $this->apiOk([
            'reservacion' => [
                'id' => (int)$newId,
                'restaurante_id' => $restauranteId,
                'mesa_id' => (int)$mesa['id'],
                'mesa_nombre' => (string)($mesa['nombre'] ?? ''),
                'nombre' => $nombre,
                'telefono' => $telefono,
                'email' => $email !== '' ? strtolower($email) : null,
                'fecha' => $fecha,
                'hora' => $hora,
                'personas' => $personas,
                'estado' => 'confirmada',
                'origen' => 'comensal',
                'canal_reserva' => 'movil',
                'confirmacion_enviada' => $emailResult['sent'],
            ],
            'email' => $emailResult,
        ]);
    }

    private function mobileResolverMesaReserva(int $restauranteId, int $mesaId, string $fecha, string $hora, int $personas, RestReservaModel $reservaModel): ?array
    {
        $db = Database::getInstance();
        if ($mesaId > 0) {
            $stmt = $db->prepare(
                "SELECT id, nombre, capacidad
                 FROM rest_mesas
                 WHERE id = ? AND restaurante_id = ? AND activo = 1
                 LIMIT 1"
            );
            $stmt->execute([$mesaId, $restauranteId]);
            $mesa = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$mesa || (int)$mesa['capacidad'] < $personas || $reservaModel->hayConflicto($mesaId, $fecha, $hora)) {
                return null;
            }
            return $mesa;
        }

        $mesas = $reservaModel->mesasDisponiblesParaCapacidad($restauranteId, $fecha, $hora, $personas);
        return $mesas[0] ?? null;
    }

    private function mobileEnviarConfirmacionReserva(RestReservaModel $reservaModel, array $restaurante, array $reserva): array
    {
        $reservaId = (int)($reserva['id'] ?? 0);
        $email = trim((string)($reserva['email'] ?? ''));
        $slug = trim((string)($restaurante['slug'] ?? ''));
        if ($reservaId <= 0 || $email === '' || $slug === '') {
            return ['sent' => false, 'reason' => $email === '' ? 'missing_email' : 'missing_data'];
        }

        try {
            $ok = (new EmailService())->enviarConfirmacionReserva(
                $email,
                $restaurante,
                [
                    'nombre' => $reserva['nombre'] ?? '',
                    'fecha' => $reserva['fecha'] ?? '',
                    'hora' => $reserva['hora'] ?? '',
                    'personas' => (int)($reserva['personas'] ?? 1),
                    'mesa_nombre' => $reserva['mesa_nombre'] ?? 'Por asignar',
                ],
                BASE_URL . 'menu/' . $slug . '/cancelarReserva/' . $reservaId
            );
            if ($ok) {
                $reservaModel->marcarConfirmacionEnviada($reservaId);
                return ['sent' => true, 'reason' => null];
            }
            error_log("[Reserva #$reservaId] FALLO al enviar confirmacion movil a $email");
            return ['sent' => false, 'reason' => 'mailer_failed'];
        } catch (\Throwable $e) {
            error_log("[Reserva #$reservaId] Excepcion enviando confirmacion movil a $email: " . $e->getMessage());
            return ['sent' => false, 'reason' => 'exception'];
        }
    }

    private function v1CrearRestPedidoMobile(): void
    {
        $body = json_decode(file_get_contents('php://input'), true);
        if (!is_array($body)) {
            $body = $_POST;
        }

        $restauranteId = $this->mobileRestauranteIdFromPayload($body);
        if ($restauranteId <= 0) {
            $this->apiError('restaurante_id o branch_id requerido', 400);
        }
        if (!$this->mobilePickupEnabled($restauranteId)) {
            $this->apiError('Pickup no esta habilitado para este restaurante', 422);
        }

        $mobileUsuarioId = (int)($body['usuario_id'] ?? $body['mobile_usuario_id'] ?? $body['user_id'] ?? 0);
        if ($mobileUsuarioId <= 0) {
            $this->apiError('usuario_id requerido', 400);
        }

        $itemsPayload = $body['items'] ?? $body['productos'] ?? [];
        if (!is_array($itemsPayload) || empty($itemsPayload)) {
            $this->apiError('items requerido', 422);
        }

        $appOrderId = trim((string)($body['app_order_id'] ?? $body['mobile_order_id'] ?? $body['order_id'] ?? ''));
        if ($appOrderId !== '' && $this->adminColumnExists('rest_pedidos', 'app_order_id')) {
            $stmt = Database::getInstance()->prepare(
                "SELECT id FROM rest_pedidos WHERE restaurante_id = ? AND app_order_id = ? LIMIT 1"
            );
            $stmt->execute([$restauranteId, $appOrderId]);
            $existingId = (int)($stmt->fetchColumn() ?: 0);
            if ($existingId > 0) {
                $pedido = $this->mobileRestPedidoResource($existingId, $mobileUsuarioId);
                if (!$pedido) {
                    $this->apiError('app_order_id ya existe para otro usuario', 409);
                }
                $this->apiOk(['pedido' => $pedido, 'idempotent' => true]);
            }
        }

        $items = [];
        foreach ($itemsPayload as $idx => $item) {
            if (!is_array($item)) {
                $this->apiError("Item {$idx} invalido", 422);
            }
            $platilloId = (int)($item['platillo_id'] ?? $item['menu_item_id'] ?? $item['producto_id'] ?? $item['id'] ?? 0);
            $cantidad = max(1, (int)($item['cantidad'] ?? $item['qty'] ?? 1));
            if ($platilloId <= 0) {
                $this->apiError("Item {$idx}: platillo_id requerido", 422);
            }

            $modificadores = [];
            foreach ((array)($item['modificadores'] ?? $item['modifiers'] ?? []) as $mod) {
                if (!is_array($mod)) {
                    continue;
                }
                $modId = (int)($mod['modificador_id'] ?? $mod['modifier_id'] ?? $mod['id'] ?? 0);
                if ($modId > 0) {
                    $modificadores[] = [
                        'modificador_id' => $modId,
                        'cantidad' => max(1, (int)($mod['cantidad'] ?? $mod['qty'] ?? 1)),
                    ];
                }
            }

            $items[] = [
                'platillo_id' => $platilloId,
                'cantidad' => $cantidad,
                'notas' => isset($item['notas']) ? substr(trim((string)$item['notas']), 0, 255) : null,
                'modificadores' => $modificadores,
            ];
        }

        $tipoEntrega = strtolower(trim((string)($body['tipo_entrega'] ?? $body['tipo_pedido'] ?? 'pickup')));
        if (!in_array($tipoEntrega, ['pickup', 'pick_up', 'takeaway', 'para_llevar', 'recoger'], true)) {
            $this->apiError('Este endpoint solo crea pedidos pickup', 422);
        }

        $clienteNombre = trim((string)($body['comprador_nombre'] ?? $body['cliente_nombre'] ?? $body['nombre'] ?? $body['customer_name'] ?? ''));
        $compradorTelefono = trim((string)($body['comprador_telefono'] ?? $body['telefono'] ?? $body['phone'] ?? $body['customer_phone'] ?? ''));

        $pedidoData = [
            'restaurante_id' => $restauranteId,
            'mesa_id' => null,
            'visita_id' => null,
            'mesero_id' => null,
            'notas' => isset($body['notas']) ? substr(trim((string)$body['notas']), 0, 500) : null,
            'tipo_origen' => 'app',
            'tipo_pedido' => 'pickup',
            'tipo_entrega' => 'pickup',
            'direccion_entrega' => null,
            'mobile_usuario_id' => $mobileUsuarioId,
            'cliente_nombre' => $clienteNombre !== '' ? substr($clienteNombre, 0, 120) : null,
            'comprador_telefono' => $compradorTelefono !== '' ? substr($compradorTelefono, 0, 30) : null,
            'comprador_direccion' => null,
            'metodo_pago' => isset($body['metodo_pago']) ? substr(trim((string)$body['metodo_pago']), 0, 40) : null,
            'pickup_at' => $this->mobileNormalizeDateTime($body['pickup_at'] ?? $body['hora_recoger'] ?? $body['fecha_recoger'] ?? null),
            'app_order_id' => $appOrderId !== '' ? substr($appOrderId, 0, 80) : null,
            'pagado_at' => !empty($body['pagado']) ? date('Y-m-d H:i:s') : null,
        ];

        try {
            $pedidoId = (new RestPedidoModel())->crear($pedidoData, $items);
        } catch (\InvalidArgumentException $e) {
            $this->apiError($e->getMessage(), 422);
        } catch (\Throwable $e) {
            if ($appOrderId !== '' && $this->adminColumnExists('rest_pedidos', 'app_order_id') && stripos($e->getMessage(), 'Duplicate') !== false) {
                $stmt = Database::getInstance()->prepare(
                    "SELECT id FROM rest_pedidos WHERE restaurante_id = ? AND app_order_id = ? LIMIT 1"
                );
                $stmt->execute([$restauranteId, $appOrderId]);
                $existingId = (int)($stmt->fetchColumn() ?: 0);
                if ($existingId > 0) {
                    $pedido = $this->mobileRestPedidoResource($existingId, $mobileUsuarioId);
                    if ($pedido) {
                        $this->apiOk(['pedido' => $pedido, 'idempotent' => true]);
                    }
                }
            }
            error_log('[v1CrearRestPedidoMobile] Error: ' . $e->getMessage());
            $this->apiError('Error interno al crear pedido pickup', 500);
        }

        $pedido = $this->mobileRestPedidoResource($pedidoId, $mobileUsuarioId);
        $this->apiOk(['pedido' => $pedido, 'idempotent' => false]);
    }

    private function v1ConsultarRestPedidoMobile(?int $pedidoId): void
    {
        $pedidoId = (int)$pedidoId;
        if ($pedidoId <= 0) {
            $this->apiError('pedido_id requerido', 400);
        }

        $mobileUsuarioId = (int)($this->get('usuario_id') ?: $this->get('mobile_usuario_id') ?: 0);
        if ($mobileUsuarioId <= 0) {
            $this->apiError('usuario_id requerido', 400);
        }
        $pedido = $this->mobileRestPedidoResource($pedidoId, $mobileUsuarioId);
        if (!$pedido) {
            $this->apiError('Pedido no encontrado', 404);
        }
        $this->apiOk(['pedido' => $pedido]);
    }

    private function mobileRestauranteIdFromPayload(array $body): int
    {
        $restauranteId = (int)($body['restaurante_id'] ?? $body['restaurant_id'] ?? 0);
        if ($restauranteId > 0) {
            $stmt = Database::getInstance()->prepare("SELECT id FROM rest_restaurantes WHERE id = ? AND activo = 1 LIMIT 1");
            $stmt->execute([$restauranteId]);
            return (int)($stmt->fetchColumn() ?: 0);
        }

        $branchId = (int)($body['branch_id'] ?? $body['sucursal_id'] ?? 0);
        if ($branchId <= 0) {
            return 0;
        }

        return (int)($this->restauranteIdPorSucursal(Database::getInstance(), $branchId) ?? 0);
    }

    private function mobilePickupEnabled(int $restauranteId): bool
    {
        if (!$this->adminTableExists('rest_configuracion')) {
            return true;
        }
        try {
            $stmt = Database::getInstance()->prepare(
                "SELECT tipos_entrega FROM rest_configuracion WHERE restaurante_id = ? LIMIT 1"
            );
            $stmt->execute([$restauranteId]);
            $raw = (string)($stmt->fetchColumn() ?: '');
            if ($raw === '') {
                return true;
            }
            $tipos = json_decode($raw, true);
            if (!is_array($tipos) || empty($tipos)) {
                return true;
            }
            return in_array('pickup', array_map('strtolower', array_map('strval', $tipos)), true);
        } catch (\Throwable $e) {
            return true;
        }
    }

    private function mobileNormalizeDateTime(mixed $value): ?string
    {
        $value = trim((string)($value ?? ''));
        if ($value === '') {
            return null;
        }
        $ts = strtotime(str_replace('T', ' ', $value));
        return $ts ? date('Y-m-d H:i:s', $ts) : null;
    }

    private function mobileRestPedidoResource(int $pedidoId, ?int $mobileUsuarioId = null): ?array
    {
        $where = ['p.id = ?'];
        $params = [$pedidoId];
        if ($mobileUsuarioId && $this->adminColumnExists('rest_pedidos', 'mobile_usuario_id')) {
            $where[] = 'p.mobile_usuario_id = ?';
            $params[] = $mobileUsuarioId;
        }

        $stmt = Database::getInstance()->prepare(
            "SELECT p.*
               FROM rest_pedidos p
              WHERE " . implode(' AND ', $where) . "
              LIMIT 1"
        );
        $stmt->execute($params);
        $pedido = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$pedido) {
            return null;
        }

        $itemsStmt = Database::getInstance()->prepare(
            "SELECT pi.id, pi.platillo_id, pi.cantidad, pi.precio_unit, pi.subtotal, pi.notas, pi.estado,
                    pl.nombre AS platillo_nombre
               FROM rest_pedido_items pi
               LEFT JOIN rest_platillos pl ON pl.id = pi.platillo_id
              WHERE pi.pedido_id = ?
              ORDER BY pi.id ASC"
        );
        $itemsStmt->execute([$pedidoId]);
        $items = array_map(static function (array $item): array {
            return [
                'id' => (int)$item['id'],
                'platillo_id' => (int)$item['platillo_id'],
                'nombre' => (string)($item['platillo_nombre'] ?? ''),
                'cantidad' => (int)$item['cantidad'],
                'precio_unit' => (float)$item['precio_unit'],
                'subtotal' => (float)$item['subtotal'],
                'notas' => $item['notas'],
                'estado' => $item['estado'],
            ];
        }, $itemsStmt->fetchAll(PDO::FETCH_ASSOC) ?: []);

        return [
            'id' => (int)$pedido['id'],
            'folio' => (string)$pedido['folio'],
            'estado' => (string)$pedido['estado'],
            'tipo_origen' => $pedido['tipo_origen'] ?? null,
            'tipo_pedido' => $pedido['tipo_pedido'] ?? null,
            'tipo_entrega' => $pedido['tipo_entrega'] ?? null,
            'pickup_at' => $pedido['pickup_at'] ?? null,
            'cliente_nombre' => $pedido['cliente_nombre'] ?? null,
            'comprador_telefono' => $pedido['comprador_telefono'] ?? null,
            'metodo_pago' => $pedido['metodo_pago'] ?? null,
            'app_order_id' => $pedido['app_order_id'] ?? null,
            'subtotal' => (float)$pedido['subtotal'],
            'total' => (float)$pedido['total'],
            'created_at' => $pedido['created_at'] ?? null,
            'items' => $items,
        ];
    }

    private function findMobilePromotionForCalculation(int $mobileUsuarioId, int $promotionId, string $code): ?array
    {
        $db = Database::getInstance();

        if ($this->adminTableExists('mobile_promociones')) {
            $this->adminEnsureMobilePromocionesTable();

            $where = ["usuario_id = ?", "activo = 1", "(expires_at IS NULL OR expires_at >= NOW())"];
            $params = [$mobileUsuarioId];
            if ($promotionId > 0 && $code === '') {
                $where[] = "id = ?";
                $params[] = $promotionId;
            } else {
                $where[] = "code = ?";
                $params[] = $code;
            }

            $sql = "SELECT id,
                           " . $this->adminColumnExpr('mobile_promociones', 'mp', 'usuario_id', 'usuario_id', 'NULL') . ",
                           " . $this->adminColumnExpr('mobile_promociones', 'mp', 'producto_id', 'producto_id', 'NULL') . ",
                           " . $this->adminColumnExpr('mobile_promociones', 'mp', 'titulo', 'titulo', "''") . ",
                           " . $this->adminColumnExpr('mobile_promociones', 'mp', 'descripcion', 'descripcion', "''") . ",
                           " . $this->adminColumnExpr('mobile_promociones', 'mp', 'code', 'code', "''") . ",
                           " . $this->adminColumnExpr('mobile_promociones', 'mp', 'tipo_descuento', 'tipo_descuento', "'porcentaje'") . ",
                           " . $this->adminColumnExpr('mobile_promociones', 'mp', 'valor_descuento', 'valor_descuento', '0') . ",
                           " . $this->adminColumnExpr('mobile_promociones', 'mp', 'scope_tipo', 'scope_tipo', "'all'") . ",
                           " . $this->adminColumnExpr('mobile_promociones', 'mp', 'scope_ids', 'scope_ids', 'NULL') . ",
                           " . $this->adminColumnExpr('mobile_promociones', 'mp', 'buy_qty', 'buy_qty', 'NULL') . ",
                           " . $this->adminColumnExpr('mobile_promociones', 'mp', 'pay_qty', 'pay_qty', 'NULL') . ",
                           " . $this->adminColumnExpr('mobile_promociones', 'mp', 'min_subtotal', 'min_subtotal', '0') . ",
                           " . $this->adminColumnExpr('mobile_promociones', 'mp', 'max_uses', 'max_uses', 'NULL') . ",
                           " . $this->adminColumnExpr('mobile_promociones', 'mp', 'combinable', 'combinable', '0') . "
                      FROM mobile_promociones mp
                     WHERE " . implode(' AND ', $where) . "
                     LIMIT 1";

            $stmt = $db->prepare($sql);
            $stmt->execute($params);
            $promotion = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($promotion) {
                return $promotion;
            }
        }

        if (!$this->adminTableExists('rest_promociones')) {
            return null;
        }

        $where = ["p.usuario_id = ?", "p.activo = 1"];
        $params = [$mobileUsuarioId];
        if ($this->adminColumnExists('rest_promociones', 'expires_at')) {
            $where[] = "(p.expires_at IS NULL OR p.expires_at >= NOW())";
        } elseif ($this->adminColumnExists('rest_promociones', 'fecha_fin')) {
            $where[] = "p.fecha_fin >= CURDATE()";
        }
        if ($promotionId > 0 && $code === '') {
            $where[] = "p.id = ?";
            $params[] = $promotionId;
        } else {
            $where[] = "p.code = ?";
            $params[] = $code;
        }

        $stmt = $db->prepare(
            "SELECT p.id,
                    p.usuario_id,
                    NULL AS producto_id,
                    p.titulo,
                    p.descripcion,
                    p.code,
                    " . ($this->adminColumnExists('rest_promociones', 'tipo') ? 'p.tipo' : "'porcentaje'") . " AS tipo_descuento,
                    " . ($this->adminColumnExists('rest_promociones', 'valor_descuento') ? 'p.valor_descuento' : '0') . " AS valor_descuento,
                    'all' AS scope_tipo,
                    NULL AS scope_ids,
                    NULL AS buy_qty,
                    NULL AS pay_qty,
                    0 AS min_subtotal,
                    NULL AS max_uses,
                    0 AS combinable
               FROM rest_promociones p
              WHERE " . implode(' AND ', $where) . "
              LIMIT 1"
        );
        $stmt->execute($params);
        $promotion = $stmt->fetch(PDO::FETCH_ASSOC);
        return $promotion ?: null;
    }

    private function calculatePromotionDiscount(array $promotion, array $items): array
    {
        $promotion = $this->normalizePromotionRuleArray($promotion);
        $normalizedItems = $this->normalizePromotionCalculationItems($items);
        $subtotal = 0.0;
        $eligibleSubtotal = 0.0;
        $eligibleUnits = [];
        $scopeTipo = (string)($promotion['scope_tipo'] ?? 'all');
        $scopeIds = $this->parsePromotionScopeIds($promotion['scope_ids'] ?? null);
        $promotionProductId = (int)($promotion['producto_id'] ?? $promotion['platillo_id'] ?? 0);

        if ($promotionProductId > 0 && empty($scopeIds) && ($scopeTipo === '' || $scopeTipo === 'all' || $scopeTipo === 'products')) {
            $scopeTipo = 'products';
            $scopeIds = [$promotionProductId];
        }

        foreach ($normalizedItems as $item) {
            $lineSubtotal = (float)$item['subtotal'];
            $subtotal += $lineSubtotal;
            if (!$this->promotionItemMatchesScope($item, $scopeTipo, $scopeIds)) {
                continue;
            }

            $eligibleSubtotal += $lineSubtotal;
            $qty = max(0, (int)floor((float)$item['cantidad']));
            for ($i = 0; $i < $qty; $i++) {
                $eligibleUnits[] = (float)$item['precio_unit'];
            }
        }

        $minSubtotal = max(0.0, (float)($promotion['min_subtotal'] ?? 0));
        if ($minSubtotal > 0 && $subtotal < $minSubtotal) {
            return [
                'promotion' => $this->promotionCalculationSummary($promotion),
                'items' => $normalizedItems,
                'subtotal' => round($subtotal, 2),
                'eligible_subtotal' => round($eligibleSubtotal, 2),
                'discount' => 0.0,
                'total' => round($subtotal, 2),
                'applied' => false,
                'reason' => 'min_subtotal_not_met',
            ];
        }

        $tipo = (string)($promotion['tipo_descuento'] ?? 'porcentaje');
        $valor = (float)($promotion['valor_descuento'] ?? 0);
        $discount = 0.0;

        if ($eligibleSubtotal > 0) {
            if ($tipo === 'monto_fijo') {
                $discount = min($valor, $eligibleSubtotal);
            } elseif ($tipo === 'bxgy') {
                $buyQty = max(1, (int)($promotion['buy_qty'] ?? 0));
                $payQty = max(1, (int)($promotion['pay_qty'] ?? 0));
                $discount = $this->calculateBuyXPayYDiscount($eligibleUnits, $buyQty, $payQty);
            } else {
                $discount = $eligibleSubtotal * min(100.0, max(0.0, $valor)) / 100;
            }
        }

        $discount = round(min($discount, $subtotal), 2);

        return [
            'promotion' => $this->promotionCalculationSummary($promotion),
            'items' => $normalizedItems,
            'subtotal' => round($subtotal, 2),
            'eligible_subtotal' => round($eligibleSubtotal, 2),
            'discount' => $discount,
            'total' => round(max(0, $subtotal - $discount), 2),
            'applied' => $discount > 0,
            'reason' => $discount > 0 ? null : 'no_eligible_items',
        ];
    }

    private function normalizePromotionCalculationItems(array $items): array
    {
        $ids = [];
        foreach ($items as $item) {
            if (!is_array($item)) {
                continue;
            }
            $id = (int)($item['producto_id'] ?? $item['platillo_id'] ?? $item['id'] ?? 0);
            if ($id > 0) {
                $ids[] = $id;
            }
        }
        $products = $this->adminProductsByIds(array_values(array_unique($ids)));
        $normalized = [];
        foreach ($items as $item) {
            if (!is_array($item)) {
                continue;
            }
            $id = (int)($item['producto_id'] ?? $item['platillo_id'] ?? $item['id'] ?? 0);
            $product = $products[$id] ?? [];
            $qty = max(0.0, (float)($item['cantidad'] ?? $item['qty'] ?? 1));
            $unit = (float)($item['precio_unit'] ?? $item['precio'] ?? $item['price'] ?? ($product['precio'] ?? 0));
            $categoryId = (int)($item['categoria_id'] ?? $item['category_id'] ?? ($product['categoria_id'] ?? 0));
            $lineSubtotal = array_key_exists('subtotal', $item)
                ? (float)$item['subtotal']
                : round($qty * $unit, 2);
            $normalized[] = [
                'producto_id' => $id,
                'platillo_id' => $id,
                'categoria_id' => $categoryId,
                'nombre' => (string)($item['nombre'] ?? $item['name'] ?? ($product['nombre'] ?? 'Producto ' . $id)),
                'cantidad' => $qty,
                'precio_unit' => round($unit, 2),
                'subtotal' => round($lineSubtotal, 2),
            ];
        }
        return $normalized;
    }

    private function adminProductsByIds(array $ids): array
    {
        $ids = array_values(array_unique(array_filter(array_map('intval', $ids))));
        if (!$ids || !$this->adminTableExists('rest_platillos')) {
            return [];
        }

        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $stmt = Database::getInstance()->prepare(
            "SELECT id, nombre, categoria_id, precio
               FROM rest_platillos
              WHERE id IN ({$placeholders})"
        );
        $stmt->execute($ids);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
        $map = [];
        foreach ($rows as $row) {
            $map[(int)$row['id']] = $row;
        }
        return $map;
    }

    private function parsePromotionScopeIds($value): array
    {
        if (is_array($value)) {
            return array_values(array_unique(array_filter(array_map('intval', $value))));
        }
        $raw = trim((string)$value);
        if ($raw === '') {
            return [];
        }
        $decoded = json_decode($raw, true);
        if (is_array($decoded)) {
            return array_values(array_unique(array_filter(array_map('intval', $decoded))));
        }
        return array_values(array_unique(array_filter(array_map('intval', preg_split('/[,\s]+/', $raw)))));
    }

    private function promotionItemMatchesScope(array $item, string $scopeTipo, array $scopeIds): bool
    {
        if ($scopeTipo === 'products') {
            return in_array((int)$item['producto_id'], $scopeIds, true);
        }
        if ($scopeTipo === 'categories') {
            return in_array((int)$item['categoria_id'], $scopeIds, true);
        }
        return true;
    }

    private function calculateBuyXPayYDiscount(array $unitPrices, int $buyQty, int $payQty): float
    {
        if ($buyQty <= 1 || $payQty >= $buyQty || $payQty <= 0 || empty($unitPrices)) {
            return 0.0;
        }
        sort($unitPrices, SORT_NUMERIC);
        $freePerGroup = $buyQty - $payQty;
        $groups = intdiv(count($unitPrices), $buyQty);
        $freeUnits = $groups * $freePerGroup;
        $discount = 0.0;
        for ($i = 0; $i < $freeUnits; $i++) {
            $discount += (float)($unitPrices[$i] ?? 0);
        }
        return $discount;
    }

    private function promotionCalculationSummary(array $promotion): array
    {
        $promotion = $this->normalizePromotionRuleArray($promotion);
        return [
            'id' => (int)($promotion['id'] ?? 0),
            'titulo' => (string)($promotion['titulo'] ?? ''),
            'code' => (string)($promotion['code'] ?? ''),
            'tipo_descuento' => (string)($promotion['tipo_descuento'] ?? 'porcentaje'),
            'valor_descuento' => (float)($promotion['valor_descuento'] ?? 0),
            'scope_tipo' => (string)($promotion['scope_tipo'] ?? 'all'),
            'scope_ids' => $this->parsePromotionScopeIds($promotion['scope_ids'] ?? null),
            'buy_qty' => isset($promotion['buy_qty']) ? (int)$promotion['buy_qty'] : null,
            'pay_qty' => isset($promotion['pay_qty']) ? (int)$promotion['pay_qty'] : null,
            'min_subtotal' => (float)($promotion['min_subtotal'] ?? 0),
            'max_uses' => isset($promotion['max_uses']) ? (int)$promotion['max_uses'] : null,
            'combinable' => (int)($promotion['combinable'] ?? 0),
        ];
    }

    private function normalizePromotionRuleArray(array $promotion): array
    {
        $tipo = $this->normalizePromotionDiscountType((string)($promotion['tipo_descuento'] ?? $promotion['discount_type'] ?? $promotion['tipo'] ?? 'porcentaje'));
        $valor = $promotion['valor_descuento']
            ?? $promotion['discount_value']
            ?? $promotion['descuento_valor']
            ?? $promotion['descuento']
            ?? $promotion['value']
            ?? $promotion['discount_percent']
            ?? $promotion['porcentaje_descuento']
            ?? 0;
        $valor = (float)$valor;
        if ($tipo === 'porcentaje') {
            $valor = min(100.0, max(0.0, $valor));
        } elseif ($tipo === 'monto_fijo') {
            $valor = max(0.0, $valor);
        }

        $scopeTipo = $this->normalizePromotionScopeType((string)($promotion['scope_tipo'] ?? $promotion['scope_type'] ?? $promotion['scope'] ?? 'all'));
        $scopeIds = $this->parsePromotionScopeIds($promotion['scope_ids_array'] ?? $promotion['scope_ids'] ?? $promotion['scopeIds'] ?? []);
        if (!$scopeIds && $scopeTipo === 'products') {
            $scopeIds = $this->parsePromotionScopeIds($promotion['producto_ids'] ?? $promotion['product_ids'] ?? []);
        }
        if (!$scopeIds && $scopeTipo === 'categories') {
            $scopeIds = $this->parsePromotionScopeIds($promotion['categoria_ids'] ?? $promotion['category_ids'] ?? []);
        }

        $promotion['tipo_descuento'] = $tipo;
        $promotion['tipo'] = $tipo;
        $promotion['discount_type'] = $tipo;
        $promotion['valor_descuento'] = $valor;
        $promotion['discount_value'] = $valor;
        $promotion['discount'] = $valor;
        $promotion['descuento'] = $valor;
        $promotion['valor'] = $valor;
        $promotion['discount_percent'] = $tipo === 'porcentaje' ? $valor : 0;
        $promotion['porcentaje_descuento'] = $tipo === 'porcentaje' ? $valor : 0;
        $promotion['scope_tipo'] = $scopeTipo;
        $promotion['scope_type'] = $scopeTipo;
        $promotion['scope_ids_array'] = $scopeIds;
        $promotion['scope_ids'] = $scopeIds ? json_encode($scopeIds) : null;
        $promotion['min_subtotal'] = (float)($promotion['min_subtotal'] ?? $promotion['minimum_subtotal'] ?? 0);
        $promotion['minimum_subtotal'] = $promotion['min_subtotal'];
        $promotion['buy_qty'] = isset($promotion['buy_qty']) ? (int)$promotion['buy_qty'] : null;
        $promotion['pay_qty'] = isset($promotion['pay_qty']) ? (int)$promotion['pay_qty'] : null;
        $promotion['max_uses'] = isset($promotion['max_uses']) ? (int)$promotion['max_uses'] : null;
        $promotion['combinable'] = (int)($promotion['combinable'] ?? 0);

        return $promotion;
    }

    private function mobilePromotionApiResource(array $promotion): array
    {
        $promotion = $this->normalizePromotionRuleArray($promotion);
        $scopeIds = $promotion['scope_ids_array'] ?? [];
        $promotion['scope_ids'] = $scopeIds;
        $promotion['producto_ids'] = $promotion['scope_tipo'] === 'products' ? $scopeIds : [];
        $promotion['categoria_ids'] = $promotion['scope_tipo'] === 'categories' ? $scopeIds : [];
        $promotion['requires_server_calculation'] = true;
        $promotion['calculation_endpoint'] = '/api/v1/promociones/aplicar';
        $promotion['applies_to_all_menu'] = $promotion['scope_tipo'] === 'all';
        return $promotion;
    }

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

        $tokenScopes = json_decode($token['scopes'], true) ?? [];
        foreach ($scopesRequired as $scope) {
            if (!in_array($scope, $tokenScopes, true)) {
                $this->apiError("Permiso requerido: {$scope}", 403);
            }
        }

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
        } catch (\Throwable) {}

        return $token;
    }

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

        $total = $subtotal;
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
    // API — Integración CapiRest (Bearer token, sin sesión PHP)
    // ══════════════════════════════════════════════════════════════════

    /**
     * Valida el Bearer token de la cabecera Authorization.
     */
    private function requireBearer(): array
    {
        $header = $_SERVER['HTTP_AUTHORIZATION']
                  ?? $_SERVER['REDIRECT_HTTP_AUTHORIZATION']
                  ?? $_SERVER['REDIRECT_REDIRECT_HTTP_AUTHORIZATION']
                  ?? '';

        error_log('[CarniHub API] requireBearer'
            . ' | URI='    . ($_SERVER['REQUEST_URI']    ?? '')
            . ' | METHOD=' . ($_SERVER['REQUEST_METHOD'] ?? '')
            . ' | HTTP_AUTHORIZATION='          . (isset($_SERVER['HTTP_AUTHORIZATION'])                        ? substr($_SERVER['HTTP_AUTHORIZATION'], 0, 40)                        : 'NO\_SET')
            . ' | REDIRECT_HTTP_AUTHORIZATION=' . (isset($_SERVER['REDIRECT_HTTP_AUTHORIZATION'])               ? substr($_SERVER['REDIRECT_HTTP_AUTHORIZATION'], 0, 40)               : 'NO\_SET')
            . ' | header_usado='                . (empty($header) ? 'VACIO' : substr($header, 0, 40))
        );

        if (!preg_match('/^Bearer\s+(\S+)$/i', trim($header), $m)) {
            http_response_code(401);
            header('Content-Type: application/json');
            error_log('[CarniHub API] requireBearer FALLO — header vacío o malformado'
                . ' | URI=' . ($_SERVER['REQUEST_URI'] ?? ''));
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
            error_log('[CarniHub API] requireBearer FALLO — token no encontrado en BD'
                . ' | hash=' . $tokenHash
                . ' | raw_prefix=' . substr($rawToken, 0, 12));
            echo json_encode(['ok' => false, 'error' => 'Token inválido o inactivo']);
            exit;
        }

        $db->prepare('UPDATE api_tokens SET ultimo_uso = NOW() WHERE id = ?')
           ->execute([$tokenRow['id']]);

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
        } catch (\Throwable $_) {}

        $tokenRow['scopes'] = json_decode($tokenRow['scopes'] ?? '[]', true) ?? [];
        return $tokenRow;
    }

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

        if ($method === 'GET') {
            $this->requireScope($tokenRow, 'pedidos:leer');
            $pedidoId = (int)$id;
            if (!$pedidoId) {
                $this->json(['ok' => false, 'error' => 'ID de pedido requerido'], 400);
            }

            $db = Database::getInstance();
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
            $compradorNombre    = isset($body['comprador_nombre'])    ? substr(trim((string)$body['comprador_nombre']), 0, 200)    : null;
            $compradorDireccion = isset($body['comprador_direccion']) ? substr(trim((string)$body['comprador_direccion']), 0, 500) : null;
            $compradorTelefono  = isset($body['comprador_telefono'])  ? substr(trim((string)$body['comprador_telefono']), 0, 30)   : null;
            $compradorLat       = isset($body['comprador_lat'])  && is_numeric($body['comprador_lat'])  ? (float)$body['comprador_lat']  : null;
            $compradorLng       = isset($body['comprador_lng'])  && is_numeric($body['comprador_lng'])  ? (float)$body['comprador_lng']  : null;

            if (empty($items) || !is_array($items)) {
                $this->json(['ok' => false, 'error' => 'Se requiere al menos un item'], 422);
            }

            $db     = Database::getInstance();
            $lineas = [];
            foreach ($items as $item) {
                $productoId = (int)($item['producto_id'] ?? 0);
                $cantidad   = (float)($item['cantidad'] ?? 0);
                $precioUnit = (float)($item['precio_unit'] ?? 0);

                if ($productoId <= 0 || $cantidad <= 0 || $precioUnit <= 0) {
                    $this->json(['ok' => false, 'error' => "Item inválido: producto_id=$productoId cantidad=$cantidad precio_unit=$precioUnit"], 422);
                }

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
                        'tipo'                => 'normal',
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

        $buscar = substr(trim($this->get('buscar', $this->get('q', ''))), 0, 100);
        $page   = max(1, (int)$this->get('page', 1));
        $limit  = min(100, max(1, (int)$this->get('limit', 20)));
        $offset = ($page - 1) * $limit;

        $db = Database::getInstance();

        $where  = ['p.empresa_id = ?', 'p.activo = 1'];
        $params = [$empresaId];

        if ($buscar !== '') {
            $where[]  = '(p.nombre LIKE ? OR p.descripcion LIKE ?)';
            $t = '%' . $buscar . '%';
            array_push($params, $t, $t);
        }

        $sql = 'SELECT p.id, p.nombre, p.descripcion, p.presentacion, p.precio_base, p.imagen,
                       c.id AS categoria_id, c.nombre AS categoria
                  FROM productos p
                  LEFT JOIN categorias c ON c.id = p.categoria_id
                 WHERE ' . implode(' AND ', $where) . '
              ORDER BY p.nombre ASC
                 LIMIT ' . $limit . ' OFFSET ' . $offset;

        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        $rows = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        $productos = array_map(static fn(array $r): array => [
            'id'           => (int)$r['id'],
            'nombre'       => $r['nombre'],
            'descripcion'  => $r['descripcion'],
            'presentacion' => $r['presentacion'],
            'precio_base'  => (float)$r['precio_base'],
            'imagen'       => $r['imagen'],
            'categoria_id' => $r['categoria_id'] !== null ? (int)$r['categoria_id'] : null,
            'categoria'    => $r['categoria'],
        ], $rows);

        $this->json(['ok' => true, 'page' => $page, 'limit' => $limit, 'productos' => $productos]);
    }

    /**
     * GET /api/legal/terminos?slug={restaurante}
     * Terminos publicos para app movil o clientes web.
     */
    public function legal(?string $resource = null): void
    {
        header('Content-Type: application/json; charset=utf-8');
        header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');

        $origin = $_SERVER['HTTP_ORIGIN'] ?? '';
        header('Access-Control-Allow-Origin: ' . ($origin ?: '*'));
        header('Access-Control-Allow-Methods: GET, OPTIONS');
        header('Access-Control-Allow-Headers: Authorization, Content-Type');

        if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
            http_response_code(204);
            exit;
        }

        if ($_SERVER['REQUEST_METHOD'] !== 'GET' || !in_array($resource, ['terminos', 'terms', null, ''], true)) {
            $this->json(['ok' => false, 'message' => 'Ruta legal no encontrada'], 404);
        }

        $restaurante = null;
        $slug = trim((string)$this->get('slug', ''));
        if ($slug !== '') {
            try {
                $restaurante = (new RestauranteModel())->getBySlug($slug) ?: null;
            } catch (\Throwable $e) {
                $restaurante = null;
            }
        }

        $terms = (new LegalContentService())->getTerms($restaurante);
        $this->json([
            'ok' => true,
            'data' => [
                'title'      => $terms['title'],
                'version'    => $terms['version'],
                'updated_at' => $terms['updated_at'],
                'brand'      => $terms['brand'],
                'html'       => $terms['html'],
                'plain_text' => $terms['plain_text'],
                'web_url'    => BASE_URL . 'legal/terminos' . ($slug !== '' ? '?slug=' . rawurlencode($slug) : ''),
            ],
        ]);
    }

    // ══════════════════════════════════════════════════════════════════
    // Admin API v1 — Endpoints para el sitio web admin (JWT Bearer)
    // Autenticación: POST /api/auth/login devuelve JWT
    // Todas las demás requieren Authorization: Bearer <jwt>
    // Respuesta estándar: { success: true|false, message: "...", data: {...} }
    // ══════════════════════════════════════════════════════════════════

    private string $jwtSecret = 'amare_api_secret_key_2024_change_this_in_production_use_a_longer_random_string';

    /** POST /api/auth/login | GET /api/auth/token */
    public function auth(?string $subAction = null): void
    {
        header('Content-Type: application/json; charset=utf-8');
        
        // CORS: Permitir credenciales con origen específico
        $origin = $_SERVER['HTTP_ORIGIN'] ?? '';
        if ($origin) {
            header('Access-Control-Allow-Origin: ' . $origin);
            header('Access-Control-Allow-Credentials: true');
        }
        
        if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
            header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
            header('Access-Control-Allow-Headers: Authorization, Content-Type');
            http_response_code(204); exit;
        }

        // GET /api/auth/token — Generar JWT si tienes sesión PHP activa
        if ($subAction === 'token' && $_SERVER['REQUEST_METHOD'] === 'GET') {
            // Verificar que hay sesión PHP activa con usuario logueado
            if (empty($_SESSION['usuario'])) {
                $this->adminApiError('No hay sesión activa. Por favor inicia sesión primero.', 401);
            }

            $usuario = $_SESSION['usuario'];
            
            // Usuarios del portal restaurante que pueden consumir la Admin API.
            $rolActual = $usuario['rol_slug'] ?? $usuario['rol'] ?? '';
            $rolValido = in_array($rolActual, ['admin', 'admin_restaurante', 'comprador', 'admin_local', 'superadmin'], true);
            if (!$rolValido) {
                $this->adminApiError('Acceso denegado. Se requiere rol de administrador.', 403);
            }

            // Generar JWT y devolverlo
            $token = $this->generateJWT($usuario);
            $this->adminApiOk('Token generado', [
                'user'  => [
                    'id' => (int)$usuario['id'],
                    'nombre' => $usuario['nombre'],
                    'email' => $usuario['email'],
                    'rol' => $rolActual,
                    'rol_slug' => $usuario['rol_slug'] ?? $rolActual,
                ],
                'token' => $token,
            ]);
            return;
        }

        // POST /api/auth/login — Login con credenciales
        if ($subAction !== 'login' || $_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->adminApiError('Ruta no encontrada', 404);
        }
        $body     = json_decode(file_get_contents('php://input'), true) ?? [];
        $email    = trim($body['email'] ?? '');
        $password = $body['password'] ?? '';
        if (!$email || !$password) {
            $this->adminApiError('Email y contraseña son requeridos', 422);
        }
        $db   = Database::getInstance();
        $stmt = $db->prepare("SELECT * FROM usuarios WHERE email = ? AND activo = 1 LIMIT 1");
        $stmt->execute([$email]);
        $usuario = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$usuario || !password_verify($password, $usuario['password'] ?? '')) {
            $this->adminApiError('Credenciales incorrectas', 401);
        }
        $rolValido = ($usuario['rol'] === 'admin' || ($usuario['rol_slug'] ?? '') === 'admin_restaurante');
        if (!$rolValido) {
            $this->adminApiError('Acceso denegado. Se requiere rol de administrador.', 403);
        }
        $token = $this->generateJWT($usuario);
        $this->adminApiOk('Login exitoso', [
            'user'  => ['id' => (int)$usuario['id'], 'nombre' => $usuario['nombre'], 'email' => $usuario['email'], 'rol' => $usuario['rol']],
            'token' => $token,
        ]);
    }

    /** Sub-router /api/admin/{resource} */
    public function admin(?string $resource = null): void
    {
        header('Content-Type: application/json; charset=utf-8');
        
        // CORS: Permitir credenciales desde el origen actual
        $origin = $_SERVER['HTTP_ORIGIN'] ?? '';
        if ($origin) {
            header('Access-Control-Allow-Origin: ' . $origin);
            header('Access-Control-Allow-Credentials: true');
        }
        
        if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
            header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
            header('Access-Control-Allow-Headers: Authorization, Content-Type');
            http_response_code(204); exit;
        }
        
        // DEBUG: Log incoming request
        error_log('[admin] ' . $_SERVER['REQUEST_METHOD'] . ' ' . ($resource ?? 'null') . ' | Session: ' . (isset($_SESSION['usuario']) ? 'YES' : 'NO') . ' | Auth header: ' . (isset($_SERVER['HTTP_AUTHORIZATION']) ? 'YES' : 'NO'));
        
        $jwtUser = $this->requireAdminJWT();
        $method  = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');
        if ($method === 'POST' && isset($_POST['_method'])) {
            $override = strtoupper((string)$_POST['_method']);
            if (in_array($override, ['PUT', 'DELETE'], true)) {
                $method = $override;
            }
        }

        // Parsear resource compuesto: ej. "promotions/123/deactivate"
        $parts    = $resource ? array_values(array_filter(explode('/', $resource))) : [];
        $resType  = $parts[0] ?? null;
        $id       = (isset($parts[1]) && ctype_digit((string)$parts[1])) ? (int)$parts[1] : null;
        $subAct   = $parts[2] ?? null;

        switch ($resType) {
            case 'users':
                if ($method === 'GET') $this->adminListUsers($jwtUser);
                else $this->adminApiError('Método no permitido', 405);
                break;
            case 'promo-catalog':
                if ($method === 'GET') $this->adminPromotionCatalog($jwtUser);
                else $this->adminApiError('Metodo no permitido', 405);
                break;
            case 'promotions':
                $this->adminPromotionsRouter($method, $id, $subAct, $jwtUser);
                break;
            case 'social':
                $this->adminSocialRouter($method, $parts, $jwtUser);
                break;
            case 'invoice-requests':
                $this->adminInvoiceRequestsRouter($method, $id, $subAct, $jwtUser);
                break;
            default:
                $this->adminApiError('Recurso no encontrado: ' . ($resType ?? 'null'), 404);
        }
    }

    /** Alias /admin/social/{resource} */
    public function social(?string $resource = null): void
    {
        $resource = trim((string)$resource, '/');
        $this->admin('social' . ($resource !== '' ? '/' . $resource : ''));
    }

    /** GET|PUT /api/branches/{id}/config y /menu-items/{id}/modifiers */
    public function branches(?string $branchId = null): void
    {
        header('Content-Type: application/json; charset=utf-8');
        header('Access-Control-Allow-Origin: *');
        header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
        header('Pragma: no-cache');
        if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
            header('Access-Control-Allow-Methods: GET, PUT, OPTIONS');
            header('Access-Control-Allow-Headers: Authorization, Content-Type');
            http_response_code(204); exit;
        }
        $urlParam = trim($_GET['url'] ?? '', '/');
        $segs     = array_values(array_filter(explode('/', $urlParam)));
        $branchId = (isset($segs[2]) && ctype_digit((string)$segs[2])) ? (int)$segs[2] : null;
        $subAct   = $segs[3] ?? null;
        $menuItemId = ($subAct === 'menu-items' && isset($segs[4]) && ctype_digit((string)$segs[4])) ? (int)$segs[4] : null;
        $esModificadores = $menuItemId && (($segs[5] ?? null) === 'modifiers');
        $method = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');
        if (!$branchId || ($subAct !== 'config' && !$esModificadores) || !in_array($method, ['GET','PUT'], true)) {
            $this->adminApiError('Ruta no encontrada', 404);
        }
        $jwtUser = $this->requireAdminJWT(false);
        $body    = $method === 'PUT' ? (json_decode(file_get_contents('php://input'), true) ?? []) : [];
        $db      = Database::getInstance();
        $stmt = $db->prepare("SELECT id, empresa_id FROM sucursales WHERE id = ? LIMIT 1");
        $stmt->execute([$branchId]);
        $branch = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$branch) { $this->adminApiError('Sucursal no encontrada', 404); }
        if ((int)$branch['empresa_id'] !== (int)$jwtUser['empresa_id']) {
            $this->adminApiError('No tienes permiso para modificar esta sucursal', 403);
        }
        try {
            $restauranteId = $this->restauranteIdPorSucursal($db, $branchId);
            if ($esModificadores && !$restauranteId) {
                $this->adminApiError('La sucursal no tiene un restaurante vinculado.', 404);
            }
            if ($esModificadores) {
                $platilloStmt = $db->prepare(
                    "SELECT id FROM rest_platillos WHERE id=? AND restaurante_id=? LIMIT 1"
                );
                $platilloStmt->execute([$menuItemId, $restauranteId]);
                if (!$platilloStmt->fetchColumn()) {
                    $this->adminApiError('El platillo no existe para esta sucursal.', 404);
                }
            }
            if ($method === 'GET' && $esModificadores) {
                $payload = (new AmareModifierSyncService())->buildPayload($restauranteId, $menuItemId);
                $this->adminApiOk('Modificadores obtenidos correctamente', $payload);
            }
            if ($method === 'GET' && $subAct === 'config') {
                $config = [];
                $restauranteId = $restauranteId ?: $this->restauranteIdPorSucursal($db, $branchId);
                if ($restauranteId) {
                    $configStmt = $db->prepare(
                        "SELECT metodos_pago, tipos_entrega, costo_envio, pedido_minimo
                           FROM rest_configuracion
                          WHERE restaurante_id = ?
                          LIMIT 1"
                    );
                    $configStmt->execute([$restauranteId]);
                    $config = $configStmt->fetch(\PDO::FETCH_ASSOC) ?: [];
                }
                $modsStmt = $db->prepare("SELECT modificadores_config FROM sucursales WHERE id=? LIMIT 1");
                $modsStmt->execute([$branchId]);
                $modsConfig = $modsStmt->fetch(\PDO::FETCH_ASSOC) ?: [];
                $platillos = [];
                if ($restauranteId) {
                    $menuStmt = $db->prepare(
                        "SELECT id FROM rest_platillos WHERE restaurante_id=? AND activo=1 ORDER BY id"
                    );
                    $menuStmt->execute([$restauranteId]);
                    $modifierService = new AmareModifierSyncService();
                    foreach ($menuStmt->fetchAll(\PDO::FETCH_COLUMN) as $platilloId) {
                        $platillos[(int)$platilloId] = $modifierService->buildPayload($restauranteId, (int)$platilloId);
                    }
                }
                $this->adminApiOk('Configuracion de sucursal obtenida correctamente', [
                    'metodos_pago' => json_decode($config['metodos_pago'] ?? '[]', true) ?: [],
                    'tipos_entrega' => json_decode($config['tipos_entrega'] ?? '[]', true) ?: [],
                    'costo_envio' => (float)($config['costo_envio'] ?? 0),
                    'pedido_minimo' => (float)($config['pedido_minimo'] ?? 0),
                    'modificadores' => json_decode($modsConfig['modificadores_config'] ?? '{}', true) ?: [],
                    'facturacion' => $this->branchFacturacionConfig($db, $restauranteId),
                    'platillos_modificadores' => $platillos,
                ]);
            }
            if ($esModificadores) {
                if (!is_array($body)) {
                    $this->adminApiError('El cuerpo JSON debe ser un arreglo u objeto.', 422);
                }
                $mods = array_is_list($body)
                    ? $body
                    : ($body['modifiers'] ?? $body['modificadores'] ?? null);
                if (!is_array($mods)) {
                    $this->adminApiError('modifiers debe ser un arreglo.', 422);
                }
                $count = $this->sincronizarModificadoresOficiales(
                    $db, $restauranteId, $menuItemId, $mods
                );
                $this->adminApiOk('Modificadores sincronizados correctamente.', [
                    'count' => $count,
                    'platillo_id' => $menuItemId,
                ]);
            }
            $sets = []; $params = [];
            if (isset($body['metodos_pago']))  { $sets[] = 'metodos_pago = ?';  $params[] = json_encode($body['metodos_pago']); }
            if (isset($body['tipos_entrega'])) { $sets[] = 'tipos_entrega = ?'; $params[] = json_encode($body['tipos_entrega']); }
            if (isset($body['costo_envio']))   { $sets[] = 'costo_envio = ?';   $params[] = (float)$body['costo_envio']; }
            if (isset($body['pedido_minimo'])) { $sets[] = 'pedido_minimo = ?'; $params[] = (float)$body['pedido_minimo']; }
            if (isset($body['activo']))        { $sets[] = 'activo = ?';        $params[] = $body['activo'] ? 1 : 0; }
            if (isset($body['modificadores']) && is_array($body['modificadores'])) {
                $modifierConfig = [
                    'exclusiones_habilitadas' => !empty($body['modificadores']['exclusiones_habilitadas']),
                    'extras_habilitados' => !empty($body['modificadores']['extras_habilitados']),
                ];
                $modStmt = $db->prepare("UPDATE sucursales SET modificadores_config = ? WHERE id = ?");
                $modStmt->execute([json_encode($modifierConfig), $branchId]);
            }
            if (!empty($sets)) {
                if (!$restauranteId) {
                    $this->adminApiError('La sucursal no tiene un restaurante vinculado.', 404);
                }
                $row = $db->prepare("SELECT id FROM rest_configuracion WHERE restaurante_id = ? LIMIT 1");
                $row->execute([$restauranteId]);
                if ((int)$row->fetchColumn() > 0) {
                    $params[] = $restauranteId;
                    $db->prepare(
                        "UPDATE rest_configuracion
                         SET " . implode(', ', $sets) . ",
                             config_version = config_version + 1
                         WHERE restaurante_id = ?"
                    )->execute($params);
                } else {
                    $payload = [
                        'metodos_pago' => json_encode($body['metodos_pago'] ?? ['card', 'cash']),
                        'tipos_entrega' => json_encode($body['tipos_entrega'] ?? ['delivery', 'pickup']),
                        'costo_envio' => (float)($body['costo_envio'] ?? 0),
                        'pedido_minimo' => (float)($body['pedido_minimo'] ?? 0),
                        'activo' => isset($body['activo']) ? ($body['activo'] ? 1 : 0) : 1,
                    ];
                    $stmt = $db->prepare(
                        "INSERT INTO rest_configuracion
                            (restaurante_id, metodos_pago, tipos_entrega, costo_envio, pedido_minimo,
                             exclusiones_habilitadas, extras_habilitados, config_version, activo)
                         VALUES (?, ?, ?, ?, ?, 1, 1, 1, ?)"
                    );
                    $stmt->execute([
                        $restauranteId,
                        $payload['metodos_pago'],
                        $payload['tipos_entrega'],
                        $payload['costo_envio'],
                        $payload['pedido_minimo'],
                        $payload['activo'],
                    ]);
                }
            }
            if (isset($body['facturacion']) && is_array($body['facturacion']) && $restauranteId) {
                $this->actualizarBranchFacturacion($db, $restauranteId, $body['facturacion']);
            }
            $this->adminApiOk('Configuración de sucursal actualizada correctamente');
        } catch (\InvalidArgumentException $e) {
            if ($db->inTransaction()) $db->rollBack();
            $this->adminApiError($e->getMessage(), 422);
        } catch (\Throwable $e) {
            if ($db->inTransaction()) $db->rollBack();
            error_log('[ApiController::syncModifiers] ' . $e->getMessage());
            error_log('[ApiController::syncModifiers TRACE] ' . $e->getTraceAsString());
            $message = 'No se pudieron sincronizar los modificadores.';
            if (defined('APP_ENV') && APP_ENV !== 'production') $message .= ' ' . $e->getMessage();
            $this->adminApiError($message, 500);
        }
    }

    private function restauranteIdPorSucursal(\PDO $db, int $branchId): ?int
    {
        $columns = [];
        try {
            foreach (['sucursal_id', 'sucursal_carnihub_id'] as $candidate) {
                $stmt = $db->prepare("SHOW COLUMNS FROM `rest_restaurantes` LIKE ?");
                $stmt->execute([$candidate]);
                if ($stmt->fetch()) {
                    $columns[] = $candidate;
                }
            }
        } catch (\Throwable $e) {
            $columns = [];
        }
        foreach (['sucursal_id', 'sucursal_carnihub_id'] as $column) {
            if (!in_array($column, $columns, true)) continue;
            $stmt = $db->prepare("SELECT id FROM rest_restaurantes WHERE `{$column}`=? AND activo=1 LIMIT 1");
            $stmt->execute([$branchId]);
            $id = (int)$stmt->fetchColumn();
            if ($id > 0) return $id;
        }
        if (!$columns) {
            $stmt = $db->prepare("SELECT id FROM rest_restaurantes WHERE id=? AND activo=1 LIMIT 1");
            $stmt->execute([$branchId]);
            $id = (int)$stmt->fetchColumn();
            return $id > 0 ? $id : null;
        }
        return null;
    }

    private function branchFacturacionConfig(\PDO $db, ?int $restauranteId): array
    {
        if (!$restauranteId) {
            return [
                'habilitada' => false,
                'modo' => 'solicitud',
                'emisor_configurado' => false,
                'emisor' => null,
                'email_notificacion' => null,
            ];
        }

        try {
            $stmt = $db->prepare(
                "SELECT facturacion_habilitada, facturacion_emisor_json, facturacion_email_notificacion
                   FROM rest_configuracion
                  WHERE restaurante_id = ?
                  LIMIT 1"
            );
            $stmt->execute([$restauranteId]);
            $row = $stmt->fetch(\PDO::FETCH_ASSOC) ?: [];
        } catch (\Throwable $e) {
            $row = [];
        }

        $emisorJson = json_decode((string)($row['facturacion_emisor_json'] ?? ''), true);
        if (!is_array($emisorJson)) {
            $emisorJson = [];
        }
        $emisor = [
            'rfc' => $emisorJson['rfc'] ?? null,
            'nombre_fiscal' => $emisorJson['nombre_fiscal'] ?? null,
            'regimen_fiscal' => $emisorJson['regimen_fiscal'] ?? null,
            'codigo_postal' => $emisorJson['codigo_postal'] ?? null,
        ];
        $emisorConfigurado = trim((string)$emisor['rfc']) !== ''
            && trim((string)$emisor['nombre_fiscal']) !== ''
            && trim((string)$emisor['regimen_fiscal']) !== ''
            && trim((string)$emisor['codigo_postal']) !== '';

        return [
            'habilitada' => !empty($row['facturacion_habilitada']),
            'modo' => 'solicitud',
            'emisor_configurado' => $emisorConfigurado,
            'emisor' => $emisor,
            'email_notificacion' => $row['facturacion_email_notificacion'] ?? null,
        ];
    }

    private function actualizarBranchFacturacion(\PDO $db, int $restauranteId, array $facturacion): void
    {
        $emisor = is_array($facturacion['emisor'] ?? null) ? $facturacion['emisor'] : [];
        try {
            $emisorJson = json_encode([
                'rfc' => strtoupper(trim((string)($emisor['rfc'] ?? ''))) ?: null,
                'nombre_fiscal' => trim((string)($emisor['nombre_fiscal'] ?? '')) ?: null,
                'regimen_fiscal' => trim((string)($emisor['regimen_fiscal'] ?? '')) ?: null,
                'codigo_postal' => trim((string)($emisor['codigo_postal'] ?? '')) ?: null,
            ]);
            $row = $db->prepare("SELECT id FROM rest_configuracion WHERE restaurante_id = ? LIMIT 1");
            $row->execute([$restauranteId]);
            if ((int)$row->fetchColumn() > 0) {
                $stmt = $db->prepare(
                    "UPDATE rest_configuracion
                     SET facturacion_habilitada = ?,
                         facturacion_emisor_json = ?,
                         facturacion_email_notificacion = ?,
                         config_version = config_version + 1
                     WHERE restaurante_id = ?"
                );
                $stmt->execute([
                    !empty($facturacion['habilitada']) ? 1 : 0,
                    $emisorJson,
                    trim((string)($facturacion['email_notificacion'] ?? '')) ?: null,
                    $restauranteId,
                ]);
                return;
            }

            $stmt = $db->prepare(
                "INSERT INTO rest_configuracion
                    (restaurante_id, metodos_pago, tipos_entrega, costo_envio, pedido_minimo,
                     exclusiones_habilitadas, extras_habilitados, config_version, activo,
                     facturacion_habilitada, facturacion_emisor_json, facturacion_email_notificacion)
                 VALUES (?, ?, ?, 0.00, 0.00, 1, 1, 1, 1, ?, ?, ?)"
            );
            $stmt->execute([
                $restauranteId,
                json_encode(['card', 'cash']),
                json_encode(['delivery', 'pickup']),
                !empty($facturacion['habilitada']) ? 1 : 0,
                $emisorJson,
                trim((string)($facturacion['email_notificacion'] ?? '')) ?: null,
            ]);
        } catch (\Throwable $e) {
            error_log('[ApiController facturacion config] ' . $e->getMessage());
        }
    }

    private function sincronizarModificadoresOficiales(
        \PDO $db,
        int $restauranteId,
        int $platilloId,
        array $mods
    ): int {
        if (!$mods) return 0;
        $db->beginTransaction();
        $count = 0;
        foreach ($mods as $mod) {
            if (!is_array($mod)) throw new \InvalidArgumentException('Modificador invalido.');
            $tipoEntrada = (string)($mod['tipo'] ?? '');
            $tipo = $tipoEntrada === 'exclusion' ? 'sin' : $tipoEntrada;
            if (!in_array($tipo, ['sin', 'extra', 'opcion'], true)) {
                throw new \InvalidArgumentException('Tipo de modificador invalido.');
            }
            $ingredienteId = (int)($mod['ingrediente_id'] ?? 0);
            $ingrediente = $db->prepare(
                "SELECT id, nombre, unidad_principal FROM rest_ingredientes
                 WHERE id=? AND restaurante_id=? AND activo=1 LIMIT 1"
            );
            $ingrediente->execute([$ingredienteId, $restauranteId]);
            $ingredienteRow = $ingrediente->fetch(\PDO::FETCH_ASSOC);
            if (!$ingredienteRow) {
                throw new \InvalidArgumentException('El ingrediente del modificador no pertenece al restaurante.');
            }
            $id = (int)($mod['id'] ?? 0);
            $alcanceRecibido = in_array(($mod['alcance'] ?? ''), ['platillo', 'restaurante'], true)
                ? $mod['alcance'] : null;
            $alcance = $alcanceRecibido ?: 'platillo';
            $nombre = trim((string)($mod['nombre'] ?? '')) ?: ($tipo === 'sin' ? 'Sin ' : 'Extra ') . $ingredienteRow['nombre'];
            $precio = max(0, (float)($mod['precio_extra'] ?? $mod['precio_unitario'] ?? 0));
            $cantidad = max(0.001, (float)($mod['cantidad_unidad'] ?? 1));
            $unidad = trim((string)($mod['unidad'] ?? $ingredienteRow['unidad_principal'] ?? 'pza')) ?: 'pza';
            $max = max(1, (int)($mod['max_seleccion'] ?? $mod['max_cantidad'] ?? 1));

            if ($id > 0) {
                $owner = $db->prepare("SELECT id, alcance FROM rest_modificadores WHERE id=? AND restaurante_id=? LIMIT 1");
                $owner->execute([$id, $restauranteId]);
                $ownerRow = $owner->fetch(\PDO::FETCH_ASSOC);
                if (!$ownerRow) {
                    throw new \InvalidArgumentException('El modificador no pertenece al restaurante.');
                }
                $alcance = $alcanceRecibido ?: $ownerRow['alcance'];
                $db->prepare(
                    "UPDATE rest_modificadores SET ingrediente_id=?, nombre=?, tipo=?, alcance=?,
                     precio_extra=?, cantidad_unidad=?, unidad=?, max_seleccion_global=?, activo=1 WHERE id=?"
                )->execute([$ingredienteId, $nombre, $tipo, $alcance, $precio, $cantidad, $unidad, $max, $id]);
            } else {
                $existing = $db->prepare(
                    "SELECT id FROM rest_modificadores WHERE restaurante_id=? AND ingrediente_id=?
                     AND tipo=? AND alcance=? AND nombre=? LIMIT 1"
                );
                $existing->execute([$restauranteId, $ingredienteId, $tipo, $alcance, $nombre]);
                $id = (int)$existing->fetchColumn();
                if (!$id) {
                    $db->prepare(
                        "INSERT INTO rest_modificadores
                         (restaurante_id, ingrediente_id, nombre, tipo, alcance, precio_extra,
                          cantidad_unidad, unidad, max_seleccion_global, activo)
                         VALUES (?,?,?,?,?,?,?,?,?,1)"
                    )->execute([$restauranteId, $ingredienteId, $nombre, $tipo, $alcance, $precio, $cantidad, $unidad, $max]);
                    $id = (int)$db->lastInsertId();
                } else {
                    $db->prepare(
                        "UPDATE rest_modificadores SET precio_extra=?, cantidad_unidad=?, unidad=?,
                         max_seleccion_global=?, activo=1 WHERE id=?"
                    )->execute([$precio, $cantidad, $unidad, $max, $id]);
                }
            }
            if ($alcance === 'restaurante') {
                $db->prepare(
                    "INSERT INTO rest_platillo_modificador
                     (platillo_id, modificador_id, obligatorio, max_seleccion)
                     SELECT DISTINCT p.id, ?, 0, ? FROM rest_platillos p
                     JOIN rest_recetas r ON r.platillo_id=p.id
                     JOIN rest_receta_ingredientes ri ON ri.receta_id=r.id
                     JOIN rest_ingredientes i ON i.id=ri.ingrediente_id
                     WHERE p.restaurante_id=? AND p.activo=1
                       AND ri.ingrediente_id=? AND COALESCE(ri.precio_extra, 0)=0
                       AND (ri.tipo_componente='guarnicion'
                            OR (i.tipo='guarnicion' AND COALESCE(ri.es_informativo, 0)=0))
                     ON DUPLICATE KEY UPDATE max_seleccion=VALUES(max_seleccion)"
                )->execute([$id, $max, $restauranteId, $ingredienteId]);
            } else {
                $db->prepare(
                    "INSERT INTO rest_platillo_modificador
                     (platillo_id, modificador_id, obligatorio, max_seleccion)
                     VALUES (?,?,0,?) ON DUPLICATE KEY UPDATE max_seleccion=VALUES(max_seleccion)"
                )->execute([$platilloId, $id, $max]);
            }
            $count++;
        }
        $db->commit();
        return $count;
    }

    // ── Admin API Helpers ────────────────────────────────────────

    private function adminApiOk(string $message, mixed $data = null): void
    {
        $resp = ['success' => true, 'message' => $message];
        if ($data !== null) $resp['data'] = $data;
        echo json_encode($resp); exit;
    }

    private function adminApiError(string $message, int $code = 400, ?array $errors = null): void
    {
        http_response_code($code);
        $resp = ['success' => false, 'message' => $message];
        if ($errors !== null) $resp['errors'] = $errors;
        echo json_encode($resp); exit;
    }

    private function requireAdminJWT(bool $requireAdmin = true): array
    {
        // 1) Si hay sesión PHP activa, usarla directamente (el usuario ya se autenticó via web)
        if (!empty($_SESSION['usuario'])) {
            $user = $_SESSION['usuario'];
            $rol = $user['rol'] ?? $user['rol_slug'] ?? '';
            if ($requireAdmin && !in_array($rol, ['admin', 'admin_restaurante', 'comprador', 'admin_local', 'superadmin'], true)) {
                $this->adminApiError('Acceso denegado. Se requiere rol de administrador.', 403);
            }
            return [
                'sub'        => (int)($user['id'] ?? 0),
                'nombre'     => $user['nombre'] ?? '',
                'email'      => $user['email'] ?? '',
                'rol'        => $rol,
                'empresa_id' => (int)($user['empresa_id'] ?? 0),
            ];
        }

        // 2) Fallback: validar JWT Bearer token
        $header = $_SERVER['HTTP_AUTHORIZATION'] ?? $_SERVER['REDIRECT_HTTP_AUTHORIZATION'] ?? '';
        if (!preg_match('/^Bearer\s+(\S+)$/i', trim($header), $m)) {
            $this->adminApiError('Token de autenticación requerido', 401);
        }
        $payload = $this->validateJWT($m[1]);
        if (!$payload) { $this->adminApiError('Token inválido o expirado', 401); }
        if ($requireAdmin && !in_array($payload['rol'] ?? '', ['admin', 'admin_restaurante', 'comprador', 'admin_local', 'superadmin'], true)) {
            $this->adminApiError('Acceso denegado. Se requiere rol de administrador.', 403);
        }
        return $payload;
    }

    public function generateJWT(array $user): string
    {
        error_log('WEB JWT SECRET=' . $this->jwtSecret);
        $header  = self::b64e(json_encode(['alg' => 'HS256', 'typ' => 'JWT']));
        $now     = time();
        $payload = self::b64e(json_encode([
            'sub' => (int)$user['id'], 'nombre' => $user['nombre'], 'email' => $user['email'],
            'rol' => $user['rol_slug'] ?? $user['rol'] ?? 'unknown', 'empresa_id' => (int)($user['empresa_id'] ?? 0),
            'iat' => $now, 'exp' => $now + 86400,
        ]));
        $sig = self::b64e(hash_hmac('sha256', "$header.$payload", $this->jwtSecret, true));
        return "$header.$payload.$sig";
    }

    private function validateJWT(string $token): ?array
    {
        $parts = explode('.', $token);
        if (count($parts) !== 3) return null;
        [$header, $payload, $signature] = $parts;
        $expected = self::b64e(hash_hmac('sha256', "$header.$payload", $this->jwtSecret, true));
        if (!hash_equals($expected, $signature)) return null;
        $data = json_decode(self::b64d($payload), true);
        if (!$data || ($data['exp'] ?? 0) < time()) return null;
        return $data;
    }

    private static function b64e(string $d): string { return rtrim(strtr(base64_encode($d), '+/', '-_'), '='); }
    private static function b64d(string $d): string { return base64_decode(strtr($d, '-_', '+/')); }

    // ── Admin: Users ─────────────────────────────────────────────

    private function adminListUsers(array $jwtUser): void
    {
        $empresaId = (int)$jwtUser['empresa_id'];
        $branchId  = $this->getAmareBranchId($empresaId);
        $search      = trim($this->get('search', ''));
        $page        = max(1, (int)$this->get('page', 1));
        $perPageRaw  = trim((string)$this->get('per_page', 50));
        $fetchAll    = in_array(strtolower($perPageRaw), ['all', 'todos', '0', '-1', '*'], true);
        $perPage     = $fetchAll ? 500 : min(100, max(1, (int)$perPageRaw));

        if (!$branchId) {
            $users = $this->adminLocalListUsers($empresaId, $search, $fetchAll ? null : $perPage);
            $this->adminApiOk('Usuarios locales obtenidos correctamente', [
                'users' => $users,
                'pagination' => [
                    'page' => 1,
                    'per_page' => count($users),
                    'total' => count($users),
                    'all' => $fetchAll,
                ],
                'source' => 'local',
            ]);
        }

        if (!$branchId) {
            $this->adminApiError('No se encontró la sucursal vinculada para tu empresa en la API Amare.', 404);
        }

        $search      = trim($this->get('search', ''));
        $page        = max(1, (int)$this->get('page', 1));
        $perPageRaw  = trim((string)$this->get('per_page', 50));
        $fetchAll    = in_array(strtolower($perPageRaw), ['all', 'todos', '0', '-1', '*'], true);
        $perPage     = $fetchAll ? 500 : min(100, max(1, (int)$perPageRaw));

        // Proxy: llamar a la API Amare para obtener usuarios móviles de esa sucursal
        $endpoint = "branches/{$branchId}/users?" . http_build_query([
            'search'   => $search,
            'page'     => $page,
            'per_page' => $perPage,
        ]);

        $result = $fetchAll
            ? $this->adminFetchAllRemoteUsers("branches/{$branchId}/users", $search, $perPage)
            : $this->callAmareApi('GET', $endpoint);
        if (!$result['success']) {
            error_log('[adminListUsers] Fallo API Amare, usando usuarios locales: ' . ($result['error'] ?? 'Desconocido'));
            $this->adminApiOk('Usuarios locales obtenidos correctamente', [
                'users' => $fetchAll
                    ? $this->adminRemoteGlobalUsersOrLocal($empresaId, $search, 1, $perPage, true)
                    : $this->adminRemoteGlobalUsersOrLocal($empresaId, $search, $page, $perPage),
                'pagination' => [],
                'source' => 'local',
            ]);
        }
        if (!$result['success']) {
            error_log('[adminListUsers] Falló API Amare: ' . ($result['error'] ?? 'Desconocido'));
            $this->adminApiError('No se pudieron obtener los usuarios de la app móvil: ' . ($result['error'] ?? 'Error de conexión'), 502);
        }

        $data = $result['data'];
        // La API Amare responde { ok: true, data: { users: [...], pagination: {...} } }
        // o puede responder { ok: true, users: [...], pagination: {...} }
        $users      = $data['data']['users'] ?? $data['users'] ?? [];
        $pagination = $data['data']['pagination'] ?? $data['pagination'] ?? [];
        if (empty($users)) {
            $globalQuery = http_build_query([
                'search' => $search,
                'page' => $page,
                'per_page' => $perPage,
            ]);
            foreach (["users", "usuarios"] as $globalEndpointBase) {
                $globalEndpoint = "{$globalEndpointBase}?{$globalQuery}";
                $globalResult = $fetchAll
                    ? $this->adminFetchAllRemoteUsers($globalEndpointBase, $search, $perPage)
                    : $this->callAmareApi('GET', $globalEndpoint);
                if (!$globalResult['success']) {
                    continue;
                }
                $globalData = $globalResult['data'];
                $users = $globalData['data']['users'] ?? $globalData['users'] ?? $globalData['data']['usuarios'] ?? $globalData['usuarios'] ?? [];
                $pagination = $globalData['data']['pagination'] ?? $globalData['pagination'] ?? [];
                if (!empty($users)) {
                    break;
                }
            }
        }
        if (empty($users)) {
            $users = $this->adminLocalListUsers($empresaId, $search, $fetchAll ? null : $perPage);
            $pagination = [
                'page' => 1,
                'per_page' => count($users),
                'total' => count($users),
                'all' => $fetchAll,
            ];
        }

        $this->adminApiOk('Usuarios obtenidos correctamente', [
            'users'      => $users,
            'pagination' => $pagination,
        ]);
    }

    // ── Admin: Promotions CRUD ───────────────────────────────────

    private function adminSocialRouter(string $method, array $parts, array $jwtUser): void
    {
        $resource = $parts[1] ?? null;
        if ($resource !== 'photos') {
            $this->adminApiError('Ruta social no encontrada', 404);
        }

        $photoId = (isset($parts[2]) && ctype_digit((string)$parts[2])) ? (int)$parts[2] : null;
        $subAction = $parts[3] ?? null;
        $rol = (string)($jwtUser['rol'] ?? '');
        $central = in_array($rol, ['admin', 'superadmin'], true);
        $restauranteId = $central ? 0 : ((int)($_SESSION['restaurante_activo_id'] ?? 0) ?: (int)$this->adminRestauranteIdByEmpresa((int)($jwtUser['empresa_id'] ?? 0)));
        if (!$central && $restauranteId <= 0) {
            $this->adminApiError('No hay sucursal vinculada para este administrador.', 403);
        }

        $model = new RestSocialModeracionModel();
        if ($method === 'GET' && $photoId === null) {
            $status = (string)$this->get('status', 'pending');
            $page = max(1, (int)$this->get('page', 1));
            $perPage = min(100, max(1, (int)$this->get('per_page', 25)));
            $search = trim((string)$this->get('search', ''));
            $result = $model->gestionFotos($restauranteId, $status, $page, $perPage, $search, $central);
            if (empty($result['available'])) {
                $this->adminApiError('La cola de fotografias no esta disponible.', 404);
            }
            $this->adminApiOk('Fotografias obtenidas correctamente', [
                'photos' => $result['photos'],
                'pagination' => $result['pagination'],
            ]);
        }

        if ($method === 'POST' && $photoId !== null && $subAction === 'decision') {
            $body = json_decode(file_get_contents('php://input'), true) ?? [];
            $decision = (string)($body['decision'] ?? '');
            if ($decision === 'approved') {
                $result = $model->aprobarFoto($photoId, $restauranteId, (int)($jwtUser['sub'] ?? 0), $central);
            } elseif ($decision === 'rejected') {
                $result = $model->rechazarFoto($photoId, $restauranteId, (string)($body['notes'] ?? ''), (int)($jwtUser['sub'] ?? 0), $central);
            } else {
                $this->adminApiError('Decision no valida.', 422);
            }

            if (($result['status'] ?? '') === 'validation') {
                $this->adminApiError($result['message'] ?? 'Datos invalidos.', 422);
            }
            if (($result['status'] ?? '') === 'conflict') {
                $this->adminApiError('Otro moderador ya decidio esta fotografia. Refresca la cola.', 409);
            }
            if (($result['status'] ?? '') === 'not_found') {
                $this->adminApiError('Fotografia no encontrada o fuera de tu sucursal.', 404);
            }
            if (empty($result['ok'])) {
                $this->adminApiError('No se pudo registrar la decision.', 500);
            }

            $this->adminApiOk('Decision registrada correctamente', ['status' => $result['status'] ?? $decision]);
        }

        $this->adminApiError('Metodo no permitido', 405);
    }

    private function adminInvoiceRequestsRouter(string $method, ?int $id, ?string $subAction, array $jwtUser): void
    {
        if ($id === null) {
            if ($method !== 'GET') $this->adminApiError('Metodo no permitido', 405);
            $this->adminListInvoiceRequests($jwtUser);
            return;
        }

        if ($method === 'POST' && $subAction === 'facturapi-stamp') {
            $this->adminStampInvoiceRequest($id, $jwtUser);
            return;
        }

        match ($method) {
            'GET' => $this->adminGetInvoiceRequest($id, $jwtUser),
            'PUT' => $this->adminUpdateInvoiceRequest($id, $jwtUser),
            default => $this->adminApiError('Metodo no permitido', 405),
        };
    }

    private function adminListInvoiceRequests(array $jwtUser): void
    {
        $empresaId = (int)$jwtUser['empresa_id'];
        $restauranteId = (int)$this->get('restaurant_id', 0);
        $db = Database::getInstance();
        $where = ['r.empresa_id = ?'];
        $params = [$empresaId];

        if ($restauranteId > 0) {
            $where[] = 'fs.restaurante_id = ?';
            $params[] = $restauranteId;
        }
        $estado = (string)$this->get('estado', '');
        if ($estado !== '' && in_array($estado, ['pendiente','en_proceso','facturada','cancelada'], true)) {
            $where[] = 'fs.estado = ?';
            $params[] = $estado;
        }
        foreach (['from' => '>=', 'to' => '<='] as $key => $op) {
            $value = trim((string)$this->get($key, ''));
            if ($value !== '' && preg_match('/^\d{4}-\d{2}-\d{2}$/', $value)) {
                $where[] = "DATE(fs.created_at) {$op} ?";
                $params[] = $value;
            }
        }

        $page = max(1, (int)$this->get('page', 1));
        $perPage = min(100, max(1, (int)$this->get('per_page', 20)));
        $offset = ($page - 1) * $perPage;
        $whereSql = implode(' AND ', $where);

        $count = $db->prepare("SELECT COUNT(*) FROM facturacion_solicitudes fs JOIN rest_restaurantes r ON r.id = fs.restaurante_id WHERE {$whereSql}");
        $count->execute($params);
        $total = (int)$count->fetchColumn();

        $stmt = $db->prepare(
            "SELECT fs.*,
                    fs.receptor_nombre AS receptor_nombre_fiscal,
                    fs.uso_cfdi AS receptor_uso_cfdi,
                    NULL AS ticket_id,
                    r.nombre AS restaurante_nombre
               FROM facturacion_solicitudes fs
               JOIN rest_restaurantes r ON r.id = fs.restaurante_id
              WHERE {$whereSql}
              ORDER BY fs.created_at DESC, fs.id DESC
              LIMIT {$perPage} OFFSET {$offset}"
        );
        $stmt->execute($params);
        $model = new RestFacturaSolicitudModel();
        $items = array_map([$model, 'normalizar'], $stmt->fetchAll(\PDO::FETCH_ASSOC));

        $this->adminApiOk('Solicitudes de factura obtenidas correctamente', [
            'items' => $items,
            'pagination' => [
                'page' => $page,
                'per_page' => $perPage,
                'total' => $total,
                'last_page' => (int)ceil($total / $perPage),
            ],
        ]);
    }

    private function adminGetInvoiceRequest(int $id, array $jwtUser): void
    {
        $row = $this->adminInvoiceRequestRow($id, (int)$jwtUser['empresa_id']);
        if (!$row) $this->adminApiError('Solicitud de factura no encontrada', 404);
        $this->adminApiOk('Solicitud de factura obtenida correctamente', (new RestFacturaSolicitudModel())->normalizar($row));
    }

    private function adminUpdateInvoiceRequest(int $id, array $jwtUser): void
    {
        $row = $this->adminInvoiceRequestRow($id, (int)$jwtUser['empresa_id']);
        if (!$row) $this->adminApiError('Solicitud de factura no encontrada', 404);
        $body = json_decode(file_get_contents('php://input'), true) ?? [];

        try {
            (new RestFacturaSolicitudModel())->actualizarEstado($id, (int)$row['restaurante_id'], [
                'estado' => $body['estado'] ?? $row['estado'],
                'cfdi_uuid' => $body['cfdi_uuid'] ?? $row['cfdi_uuid'],
                'pdf_url' => $body['pdf_url'] ?? $row['pdf_url'],
                'xml_url' => $body['xml_url'] ?? $row['xml_url'],
                'notas' => $body['notas'] ?? $row['notas'],
            ]);
        } catch (\InvalidArgumentException $e) {
            $this->adminApiError($e->getMessage(), 422);
        }

        $updated = $this->adminInvoiceRequestRow($id, (int)$jwtUser['empresa_id']);
        $this->adminApiOk('Solicitud de factura actualizada correctamente', (new RestFacturaSolicitudModel())->normalizar($updated));
    }

    private function adminStampInvoiceRequest(int $id, array $jwtUser): void
    {
        $body = json_decode(file_get_contents('php://input'), true) ?? [];

        try {
            (new FacturApiService())->stampInvoiceRequest($id, (int)$jwtUser['empresa_id'], is_array($body) ? $body : []);
        } catch (\Throwable $e) {
            error_log('[FacturAPI admin stamp] ' . $e->getMessage());
            $row = $this->adminInvoiceRequestRow($id, (int)$jwtUser['empresa_id']);
            if ($row) {
                (new RestFacturaSolicitudModel())->actualizarEstado($id, (int)$row['restaurante_id'], [
                    'estado' => 'en_proceso',
                    'cfdi_uuid' => $row['cfdi_uuid'] ?? '',
                    'pdf_url' => $row['pdf_url'] ?? '',
                    'xml_url' => $row['xml_url'] ?? '',
                    'notas' => 'Error FacturAPI: ' . mb_substr($e->getMessage(), 0, 500),
                ]);
            }
            $this->adminApiError($e->getMessage(), 422);
        }

        $updated = $this->adminInvoiceRequestRow($id, (int)$jwtUser['empresa_id']);
        $this->adminApiOk('Solicitud timbrada correctamente con FacturAPI', (new RestFacturaSolicitudModel())->normalizar($updated));
    }

    private function adminInvoiceRequestRow(int $id, int $empresaId): ?array
    {
        $stmt = Database::getInstance()->prepare(
            "SELECT fs.*,
                    fs.receptor_nombre AS receptor_nombre_fiscal,
                    fs.uso_cfdi AS receptor_uso_cfdi,
                    NULL AS ticket_id,
                    r.nombre AS restaurante_nombre
               FROM facturacion_solicitudes fs
               JOIN rest_restaurantes r ON r.id = fs.restaurante_id
              WHERE fs.id = ? AND r.empresa_id = ?
              LIMIT 1"
        );
        $stmt->execute([$id, $empresaId]);
        $row = $stmt->fetch(\PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    private function adminPromotionsRouter(string $method, ?int $id, ?string $subAction, array $jwtUser): void
    {
        if ($id === null) {
            match ($method) {
                'GET'  => $this->adminListPromotions($jwtUser),
                'POST' => $this->adminCreatePromotion($jwtUser),
                default => $this->adminApiError('Método no permitido', 405),
            };
            return;
        }
        match (true) {
            $method === 'GET'                               => $this->adminGetPromotion($id, $jwtUser),
            $method === 'POST' && $subAction === 'notify'   => $this->adminNotifyPromotion($id, $jwtUser),
            $method === 'PUT' && $subAction === 'deactivate' => $this->adminDeactivatePromotion($id, $jwtUser),
            $method === 'PUT'                               => $this->adminUpdatePromotion($id, $jwtUser),
            $method === 'DELETE'                            => $this->adminDeletePromotion($id, $jwtUser),
            default => $this->adminApiError('Método no permitido', 405),
        };
    }

    private function adminListPromotions(array $jwtUser): void
    {
        $empresaId = (int)$jwtUser['empresa_id'];
        $branchId  = $this->getAmareBranchId($empresaId);

        if (!$branchId) {
            $this->adminApiError('No se encontró la sucursal vinculada para tu empresa en la API Amare.', 404);
        }

        $page      = max(1, (int)$this->get('page', 1));
        $perPage   = min(100, max(1, (int)$this->get('per_page', 20)));
        $usuarioId = $this->get('usuario_id') ? (int)$this->get('usuario_id') : null;

        // Proxy: llamar a la API Amare para obtener promociones de esa sucursal
        $queryParams = [
            'page'     => $page,
            'per_page' => $perPage,
        ];
        if ($usuarioId) {
            $queryParams['usuario_id'] = $usuarioId;
        }

        $endpoint = "branches/{$branchId}/promotions?" . http_build_query($queryParams);

        $result = $this->callAmareApi('GET', $endpoint);
        if (!$result['success'] && $this->esEndpointNoEncontrado($result)) {
            $endpoint = "branches/{$branchId}/promociones?" . http_build_query($queryParams);
            $result = $this->callAmareApi('GET', $endpoint);
        }
        if (!$result['success'] && $this->esEndpointNoEncontrado($result)) {
            $this->adminApiOk('Promociones locales obtenidas correctamente', [
                'promotions' => $this->adminLocalListPromotions($empresaId),
                'pagination' => [],
                'source' => 'local',
            ]);
        }

        if (!$result['success']) {
            error_log('[adminListPromotions] Falló API Amare: ' . ($result['error'] ?? 'Desconocido'));
            $this->adminApiError('No se pudieron obtener las promociones de la app móvil: ' . ($result['error'] ?? 'Error de conexión'), 502);
        }

        $data = $result['data'];
        // La API Amare responde { ok: true, data: { promotions: [...], pagination: {...} } }
        $promotions = $data['data']['promotions'] ?? $data['promotions'] ?? $data['data']['promociones'] ?? $data['promociones'] ?? [];
        $promotions = $this->adminNormalizePromotionList($empresaId, is_array($promotions) ? $promotions : []);
        $pagination = $data['data']['pagination'] ?? $data['pagination'] ?? [];

        $this->adminApiOk('Promociones obtenidas correctamente', [
            'promotions' => $promotions,
            'pagination' => $pagination,
        ]);
    }

    private function adminGetPromotion(int $id, array $jwtUser): void
    {
        $empresaId = (int)$jwtUser['empresa_id'];
        $branchId  = $this->getAmareBranchId($empresaId);

        if (!$branchId) {
            $this->adminApiError('No se encontró la sucursal vinculada para tu empresa en la API Amare.', 404);
        }

        $endpoint = "branches/{$branchId}/promotions/{$id}";
        $result = $this->callAmareApi('GET', $endpoint);
        if (!$result['success'] && $this->esEndpointNoEncontrado($result)) {
            $result = $this->callAmareApi('GET', "branches/{$branchId}/promociones/{$id}");
        }
        if (!$result['success'] && $this->esEndpointNoEncontrado($result)) {
            $promotion = $this->adminLocalGetPromotion($id, $empresaId);
            if (!$promotion) {
                $this->adminApiError('PromociÃ³n no encontrada', 404);
            }
            $this->adminApiOk('PromociÃ³n local obtenida correctamente', $promotion);
        }

        if (!$result['success']) {
            if ($result['httpCode'] === 404) {
                $this->adminApiError('Promoción no encontrada', 404);
            }
            $this->adminApiError('No se pudo obtener la promoción de la app móvil: ' . ($result['error'] ?? 'Error de conexión'), 502);
        }

        $data = $result['data'];
        $promotion = $data['data']['promotion'] ?? $data['promotion'] ?? $data;
        if (is_array($promotion)) {
            $promotion = $this->adminNormalizePromotionList($empresaId, [$promotion])[0] ?? $promotion;
            error_log(
                '[adminGetPromotion] id=' . $id
                . ' usuario_id=' . (int)($promotion['usuario_id'] ?? 0)
                . ' email=' . (string)($promotion['usuario_email'] ?? '')
                . ' has_push_token=' . (int)($promotion['has_push_token'] ?? 0)
                . ' push_token_count=' . (int)($promotion['push_token_count'] ?? 0)
                . ' notification_status=' . (string)($promotion['notification_status'] ?? '')
                . ' notification_error=' . (string)($promotion['notification_error'] ?? '')
            );
        }

        $this->adminApiOk('Promoción obtenida correctamente', $promotion);
    }

    private function adminCreatePromotion(array $jwtUser): void
    {
        $empresaId = (int)$jwtUser['empresa_id'];
        $branchId  = $this->getAmareBranchId($empresaId);

        if (!$branchId) {
            $this->adminApiError('No se encontró la sucursal vinculada para tu empresa en la API Amare.', 404);
        }

        $body   = $this->readAdminPromotionPayload();
        $usuarioIds = $this->adminPromotionUserIds($body);
        
        // Auto-llenar usuario_id desde JWT si no viene en el request
        if (empty($body['usuario_id'])) {
            $body['usuario_id'] = $usuarioIds[0] ?? ($jwtUser['sub'] ?? null);
        }
        $usuarioIds = $this->adminPromotionUserIds($body);
        
        $errors = $this->validatePromotionData($body, null);
        if (!empty($errors)) { $this->adminApiError('Error de validación', 422, $errors); }

        if (count($usuarioIds) > 1) {
            if (trim((string)($body['code'] ?? '')) !== '') {
                $this->adminApiError('Para crear promociones a varios usuarios deja el codigo vacio; se generara uno unico para cada usuario.', 422, [
                    'code' => ['El codigo manual solo puede usarse con un usuario.'],
                ]);
            }
            $created = [];
            foreach ($usuarioIds as $usuarioId) {
                $userBody = $body;
                $userBody['usuario_id'] = $usuarioId;
                unset($userBody['usuario_ids']);
                $created[] = $this->adminLocalCreatePromotion($empresaId, $userBody);
            }

            $this->adminApiOk(count($created) . ' promociones guardadas localmente para la app movil', [
                'promotions' => $created,
                'count' => count($created),
                'source' => 'local',
            ]);
        }

        $remoteBody = $this->promotionPayloadForRemote($body);
        unset($remoteBody['usuario_ids']);

        $endpoint = "branches/{$branchId}/promotions";
        $result = $this->callAmareApi('POST', $endpoint, $remoteBody);
        if (!$result['success'] && $this->esEndpointNoEncontrado($result)) {
            $result = $this->callAmareApi('POST', "branches/{$branchId}/promociones", $remoteBody);
        }
        if (!$result['success'] && $this->esEndpointNoEncontrado($result)) {
            $promotion = $this->adminLocalCreatePromotion($empresaId, $body);
            $this->adminApiOk('PromociÃ³n guardada localmente para la app mÃ³vil', $promotion);
        }

        if (!$result['success']) {
            $data = $result['data'];
            if ($result['httpCode'] === 422 && !empty($data['errors'])) {
                $this->adminApiError($data['message'] ?? 'Error de validación', 422, $data['errors']);
            }
            if ($result['httpCode'] === 409 && str_contains($data['error'] ?? '', 'code')) {
                $this->adminApiError('Error de validación', 422, ['code' => ['El código ya está en uso por otra promoción.']]);
            }
            $this->adminApiError('No se pudo crear la promoción en la app móvil: ' . ($result['error'] ?? 'Error de conexión'), 502);
        }

        $data = $result['data'];
        $promotion = $data['data']['promotion'] ?? $data['promotion'] ?? $data;
        $localPromotion = $this->adminLocalSyncPromotion($empresaId, $body, is_array($promotion) ? $promotion : []);
        if ($localPromotion && is_array($promotion)) {
            $promotion['local_promotion'] = $localPromotion;
        }

        $this->adminApiOk('Promoción creada correctamente', $promotion);
    }

    private function adminUpdatePromotion(int $id, array $jwtUser): void
    {
        $empresaId = (int)$jwtUser['empresa_id'];
        $branchId  = $this->getAmareBranchId($empresaId);

        if (!$branchId) {
            $this->adminApiError('No se encontró la sucursal vinculada para tu empresa en la API Amare.', 404);
        }

        $body   = $this->readAdminPromotionPayload();
        $errors = $this->validatePromotionData($body, $id);
        if (!empty($errors)) { $this->adminApiError('Error de validación', 422, $errors); }

        $remoteBody = $this->promotionPayloadForRemote($body);
        $endpoint = "branches/{$branchId}/promotions/{$id}";
        $result = $this->callAmareApi('PUT', $endpoint, $remoteBody);
        if (!$result['success'] && $this->esEndpointNoEncontrado($result)) {
            $result = $this->callAmareApi('PUT', "branches/{$branchId}/promociones/{$id}", $remoteBody);
        }
        if (!$result['success'] && $this->esEndpointNoEncontrado($result)) {
            $promotion = $this->adminLocalUpdatePromotion($id, $empresaId, $body);
            if (!$promotion) {
                $this->adminApiError('PromociÃ³n no encontrada', 404);
            }
            $this->adminApiOk('PromociÃ³n local actualizada correctamente', $promotion);
        }

        if (!$result['success']) {
            if ($result['httpCode'] === 404) {
                $this->adminApiError('Promoción no encontrada', 404);
            }
            $data = $result['data'];
            if ($result['httpCode'] === 422 && !empty($data['errors'])) {
                $this->adminApiError($data['message'] ?? 'Error de validación', 422, $data['errors']);
            }
            if ($result['httpCode'] === 409 && str_contains($data['error'] ?? '', 'code')) {
                $this->adminApiError('Error de validación', 422, ['code' => ['El código ya está en uso por otra promoción.']]);
            }
            $this->adminApiError('No se pudo actualizar la promoción en la app móvil: ' . ($result['error'] ?? 'Error de conexión'), 502);
        }

        $data = $result['data'];
        $promotion = $data['data']['promotion'] ?? $data['promotion'] ?? $data;
        $localPromotion = $this->adminLocalSyncPromotion($empresaId, ['id' => $id] + $body, is_array($promotion) ? $promotion : []);
        if ($localPromotion && is_array($promotion)) {
            $promotion['local_promotion'] = $localPromotion;
        }

        $this->adminApiOk('Promoción actualizada correctamente', $promotion);
    }

    private function adminDeletePromotion(int $id, array $jwtUser): void
    {
        $empresaId = (int)$jwtUser['empresa_id'];
        $branchId  = $this->getAmareBranchId($empresaId);

        if (!$branchId) {
            $this->adminApiError('No se encontró la sucursal vinculada para tu empresa en la API Amare.', 404);
        }

        $endpoint = "branches/{$branchId}/promotions/{$id}";
        $result = $this->callAmareApi('DELETE', $endpoint);
        if (!$result['success'] && $this->esEndpointNoEncontrado($result)) {
            $result = $this->callAmareApi('DELETE', "branches/{$branchId}/promociones/{$id}");
        }
        if (!$result['success'] && $this->esEndpointNoEncontrado($result)) {
            if (!$this->adminLocalDeletePromotion($id, $empresaId)) {
                $this->adminApiError('PromociÃ³n no encontrada', 404);
            }
            $this->adminApiOk('PromociÃ³n local eliminada correctamente');
        }

        if (!$result['success']) {
            if ($result['httpCode'] === 404) {
                $this->adminApiError('Promoción no encontrada', 404);
            }
            $this->adminApiError('No se pudo eliminar la promoción en la app móvil: ' . ($result['error'] ?? 'Error de conexión'), 502);
        }

        $this->adminApiOk('Promoción eliminada correctamente');
    }

    private function adminNotifyPromotion(int $id, array $jwtUser): void
    {
        $empresaId = (int)$jwtUser['empresa_id'];
        $promotion = $this->adminLocalGetPromotion($id, $empresaId);
        if (!$promotion) {
            $this->adminApiError('Promoción no encontrada', 404);
        }

        $usuarioId = (int)($promotion['usuario_id'] ?? 0);
        if ($usuarioId <= 0) {
            $this->adminApiError('La promoción no tiene usuario asignado.', 422);
        }

        if (empty($this->adminPromotionPushTokens($usuarioId))) {
            $this->adminLogPromotionNotification(
                $id,
                $usuarioId,
                null,
                null,
                'skipped',
                (string)($promotion['titulo'] ?? 'Nueva promocion'),
                (string)($promotion['descripcion'] ?? 'Tienes una nueva promocion en Amare.'),
                null,
                'no_push_token'
            );
            $this->adminApiOk('El usuario no tiene token push activo.', [
                'promotion' => $promotion,
                'notification' => $this->adminLatestPromotionNotificationLog($id),
                'notification_summary' => $this->adminPromotionNotificationSummary($id, 'skipped', 'no_push_token'),
            ]);
        }

        $notification = $this->adminSendPromotionNotification($promotion, $promotion);
        $this->adminApiOk('Notificación enviada o registrada para revisión.', [
            'promotion' => $this->adminLocalGetPromotion($id, $empresaId),
            'notification' => $notification['log'] ?? $this->adminLatestPromotionNotificationLog($id),
            'notification_summary' => $notification,
        ]);
    }

    private function adminDeactivatePromotion(int $id, array $jwtUser): void
    {
        $empresaId = (int)$jwtUser['empresa_id'];
        $branchId  = $this->getAmareBranchId($empresaId);

        if (!$branchId) {
            $this->adminApiError('No se encontró la sucursal vinculada para tu empresa en la API Amare.', 404);
        }

        $endpoint = "branches/{$branchId}/promotions/{$id}/deactivate";
        $result = $this->callAmareApi('PUT', $endpoint);
        if (!$result['success'] && $this->esEndpointNoEncontrado($result)) {
            $result = $this->callAmareApi('PUT', "branches/{$branchId}/promociones/{$id}/deactivate");
        }
        if (!$result['success'] && $this->esEndpointNoEncontrado($result)) {
            if (!$this->adminLocalDeactivatePromotion($id, $empresaId)) {
                $this->adminApiError('PromociÃ³n no encontrada', 404);
            }
            $this->adminApiOk('PromociÃ³n local desactivada correctamente');
        }

        if (!$result['success']) {
            if ($result['httpCode'] === 404) {
                $this->adminApiError('Promoción no encontrada', 404);
            }
            $this->adminApiError('No se pudo desactivar la promoción en la app móvil: ' . ($result['error'] ?? 'Error de conexión'), 502);
        }

        $this->adminApiOk('Promoción desactivada correctamente');
    }

    private function readAdminPromotionPayload(): array
    {
        if (!empty($_POST) || !empty($_FILES)) {
            $body = $_POST;
            unset($body['_method']);

            $imageValue = $this->handlePromotionImagePayload(
                $_FILES['imagen'] ?? null,
                !empty($body['remove_image'])
            );
            unset($body['remove_image']);

            if ($imageValue !== false) {
                $body['imagen'] = $imageValue;
            }

            return $this->normalizePromotionPayload($body);
        }

        $raw = file_get_contents('php://input');
        $body = json_decode($raw, true);
        return is_array($body) ? $this->normalizePromotionPayload($body) : [];
    }

    private function normalizePromotionPayload(array $body): array
    {
        foreach (['code', 'expires_at'] as $field) {
            if (array_key_exists($field, $body) && trim((string)$body[$field]) === '') {
                $body[$field] = null;
            }
        }

        if (isset($body['usuario_id'])) {
            $body['usuario_id'] = (int)$body['usuario_id'];
        }
        if (isset($body['usuario_ids']) && is_array($body['usuario_ids'])) {
            $body['usuario_ids'] = array_values(array_unique(array_filter(array_map('intval', $body['usuario_ids']))));
            if (empty($body['usuario_id']) && !empty($body['usuario_ids'])) {
                $body['usuario_id'] = $body['usuario_ids'][0];
            }
        }
        if (isset($body['activo'])) {
            $body['activo'] = (int)$body['activo'] ? 1 : 0;
        }

        $body['tipo_descuento'] = $this->normalizePromotionDiscountType($body['tipo_descuento'] ?? 'porcentaje');
        $body['scope_tipo'] = $this->normalizePromotionScopeType($body['scope_tipo'] ?? 'all');
        $scopeIds = [];
        if ($body['scope_tipo'] === 'products') {
            $scopeIds = $this->parsePromotionScopeIds($body['producto_ids'] ?? $body['scope_ids'] ?? []);
            if (empty($body['producto_id']) && $scopeIds) {
                $body['producto_id'] = $scopeIds[0];
            }
        } elseif ($body['scope_tipo'] === 'categories') {
            $scopeIds = $this->parsePromotionScopeIds($body['categoria_ids'] ?? $body['scope_ids'] ?? []);
        }
        $body['scope_ids'] = $scopeIds ? json_encode($scopeIds) : null;

        foreach (['valor_descuento', 'min_subtotal'] as $field) {
            if (array_key_exists($field, $body)) {
                $body[$field] = (float)$body[$field];
            }
        }
        foreach (['buy_qty', 'pay_qty', 'max_uses'] as $field) {
            if (array_key_exists($field, $body) && $body[$field] !== '' && $body[$field] !== null) {
                $body[$field] = (int)$body[$field];
            }
        }
        if (isset($body['combinable'])) {
            $body['combinable'] = (int)$body['combinable'] ? 1 : 0;
        }

        return $body;
    }

    private function promotionPayloadForRemote(array $body): array
    {
        $normalized = $this->normalizePromotionRuleArray($body);
        $scopeIds = $normalized['scope_ids_array'] ?? [];
        $payload = $body;

        $payload['tipo_descuento'] = $normalized['tipo_descuento'];
        $payload['tipo'] = $normalized['tipo_descuento'];
        $payload['discount_type'] = $normalized['tipo_descuento'];
        $payload['valor_descuento'] = $normalized['valor_descuento'];
        $payload['discount_value'] = $normalized['valor_descuento'];
        $payload['discount'] = $normalized['valor_descuento'];
        $payload['descuento'] = $normalized['valor_descuento'];
        $payload['valor'] = $normalized['valor_descuento'];
        $payload['discount_percent'] = $normalized['tipo_descuento'] === 'porcentaje' ? $normalized['valor_descuento'] : 0;
        $payload['porcentaje_descuento'] = $normalized['tipo_descuento'] === 'porcentaje' ? $normalized['valor_descuento'] : 0;
        $payload['scope_tipo'] = $normalized['scope_tipo'];
        $payload['scope_type'] = $normalized['scope_tipo'];
        $payload['scope_ids'] = $scopeIds;
        $payload['producto_ids'] = $normalized['scope_tipo'] === 'products' ? $scopeIds : [];
        $payload['categoria_ids'] = $normalized['scope_tipo'] === 'categories' ? $scopeIds : [];
        $payload['min_subtotal'] = $normalized['min_subtotal'];
        $payload['minimum_subtotal'] = $normalized['min_subtotal'];
        $payload['buy_qty'] = $normalized['buy_qty'];
        $payload['pay_qty'] = $normalized['pay_qty'];
        $payload['max_uses'] = $normalized['max_uses'];
        $payload['combinable'] = $normalized['combinable'];

        return $payload;
    }

    private function normalizePromotionDiscountType(string $type): string
    {
        $type = strtolower(trim($type));
        $aliases = [
            'percentage' => 'porcentaje',
            'percent' => 'porcentaje',
            'fixed' => 'monto_fijo',
            'amount' => 'monto_fijo',
            'fixed_amount' => 'monto_fijo',
            'monto' => 'monto_fijo',
            'paquete' => 'bxgy',
            '2x1' => 'bxgy',
            '3x2' => 'bxgy',
            'buy_x_pay_y' => 'bxgy',
        ];
        $type = $aliases[$type] ?? $type;
        return in_array($type, ['porcentaje', 'monto_fijo', 'bxgy'], true) ? $type : 'porcentaje';
    }

    private function normalizePromotionScopeType(string $type): string
    {
        $type = strtolower(trim($type));
        $aliases = [
            'all_menu' => 'all',
            'todo' => 'all',
            'productos' => 'products',
            'producto' => 'products',
            'categorias' => 'categories',
            'categoria' => 'categories',
        ];
        $type = $aliases[$type] ?? $type;
        return in_array($type, ['all', 'products', 'categories'], true) ? $type : 'all';
    }

    private function adminPromotionUserIds(array $body): array
    {
        $ids = [];
        if (isset($body['usuario_ids']) && is_array($body['usuario_ids'])) {
            $ids = array_merge($ids, $body['usuario_ids']);
        }
        if (isset($body['usuario_id'])) {
            $ids[] = $body['usuario_id'];
        }

        return array_values(array_unique(array_filter(array_map('intval', $ids))));
    }

    private function handlePromotionImagePayload(?array $file, bool $removeImage): string|null|false
    {
        if ($removeImage) {
            return null;
        }

        if (!$file || ($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
            return false;
        }

        if (($file['error'] ?? UPLOAD_ERR_OK) !== UPLOAD_ERR_OK) {
            $this->adminApiError('No se pudo subir la imagen de la promoción.', 422, [
                'imagen' => ['La carga del archivo falló. Intenta nuevamente.'],
            ]);
        }

        if (($file['size'] ?? 0) > 5 * 1024 * 1024) {
            $this->adminApiError('Error de validación', 422, [
                'imagen' => ['La imagen no debe exceder 5MB.'],
            ]);
        }

        $allowed = [
            'jpg'  => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'png'  => 'image/png',
            'webp' => 'image/webp',
            'gif'  => 'image/gif',
        ];

        $ext = strtolower(pathinfo((string)($file['name'] ?? ''), PATHINFO_EXTENSION));
        $tmp = (string)($file['tmp_name'] ?? '');
        $mime = '';
        if (function_exists('finfo_open')) {
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            if ($finfo) {
                $mime = (string)finfo_file($finfo, $tmp);
                finfo_close($finfo);
            }
        }

        if (!isset($allowed[$ext]) || ($mime !== '' && $mime !== $allowed[$ext])) {
            $this->adminApiError('Error de validación', 422, [
                'imagen' => ['La imagen debe ser JPG, PNG, WEBP o GIF.'],
            ]);
        }

        $uploadDir = ROOT_PATH . '/public/uploads/promociones';
        if (!is_dir($uploadDir) && !mkdir($uploadDir, 0775, true) && !is_dir($uploadDir)) {
            $this->adminApiError('No se pudo preparar el directorio de imágenes.', 500);
        }

        $filename = 'promo_' . date('Ymd_His') . '_' . bin2hex(random_bytes(6)) . '.' . $ext;
        $dest = $uploadDir . '/' . $filename;
        if (!move_uploaded_file($tmp, $dest)) {
            $this->adminApiError('No se pudo guardar la imagen de la promoción.', 500);
        }

        return rtrim(BASE_URL, '/') . '/public/uploads/promociones/' . $filename;
    }

    private function validatePromotionData(array $data, ?int $excludeId): array
    {
        $errors = [];
        
        // usuario_id es obligatorio en creación y edición (auto-llenado desde JWT en creación)
        if (empty($data['usuario_id'])) { 
            $errors['usuario_id'] = ['El usuario es obligatorio.']; 
        }
        
        // titulo es obligatorio en creación; en edición, es opcional (PUT permite actualización parcial)
        if ($excludeId === null && empty(trim($data['titulo'] ?? ''))) { 
            $errors['titulo'] = ['El título es obligatorio.']; 
        }
        elseif (isset($data['titulo']) && empty(trim($data['titulo']))) { 
            $errors['titulo'] = ['El título no puede estar vacío.']; 
        }
        elseif (isset($data['titulo']) && strlen(trim($data['titulo'])) > 255) { 
            $errors['titulo'] = ['El título no puede exceder los 255 caracteres.']; 
        }
        
        // code: validación de unicidad delegada a la API Amare (BD remota, validación 409)
        // Aquí solo validamos formato básico si es necesario. Amare retorna 409 si duplicado.
        if (!empty($data['code']) && !is_string($data['code'])) {
            $errors['code'] = ['El código debe ser una cadena de texto.'];
        }
        
        // expires_at: validar formato si viene (YYYY-MM-DD o YYYY-MM-DD HH:MM:SS)
        if (!empty($data['expires_at']) && !preg_match('/^\d{4}-\d{2}-\d{2}( \d{2}:\d{2}:\d{2})?$/', $data['expires_at'])) {
            $errors['expires_at'] = ['Formato inválido. Use YYYY-MM-DD o YYYY-MM-DD HH:MM:SS.'];
        }

        $tipoDescuento = $this->normalizePromotionDiscountType((string)($data['tipo_descuento'] ?? 'porcentaje'));
        $valorDescuento = (float)($data['valor_descuento'] ?? 0);
        if ($tipoDescuento === 'porcentaje' && ($valorDescuento <= 0 || $valorDescuento > 100)) {
            $errors['valor_descuento'] = ['El porcentaje debe ser mayor a 0 y menor o igual a 100.'];
        }
        if ($tipoDescuento === 'monto_fijo' && $valorDescuento <= 0) {
            $errors['valor_descuento'] = ['El monto de descuento debe ser mayor a 0.'];
        }
        if ($tipoDescuento === 'bxgy') {
            $buyQty = (int)($data['buy_qty'] ?? 0);
            $payQty = (int)($data['pay_qty'] ?? 0);
            if ($buyQty < 2 || $payQty < 1 || $payQty >= $buyQty) {
                $errors['buy_qty'] = ['Configura una promocion valida, por ejemplo 2x1 o 3x2.'];
            }
        }

        $scopeTipo = $this->normalizePromotionScopeType((string)($data['scope_tipo'] ?? 'all'));
        $scopeIds = $this->parsePromotionScopeIds($data['scope_ids'] ?? null);
        if ($scopeTipo !== 'all' && !$scopeIds) {
            $errors['scope_ids'] = ['Selecciona al menos un producto o categoria para esta promocion.'];
        }

        return $errors;
    }

    private function esEndpointNoEncontrado(array $result): bool
    {
        $message = mb_strtolower((string)($result['error'] ?? ''));
        return (int)($result['httpCode'] ?? 0) === 404
            || str_contains($message, 'endpoint no encontrado')
            || str_contains($message, 'ruta no encontrada')
            || str_contains($message, 'not found');
    }

    private function adminLocalListUsers(int $empresaId, string $search = '', ?int $limit = 50): array
    {
        $db = Database::getInstance();
        $limit = $limit === null ? null : min(5000, max(1, $limit));

        foreach (['mobile_usuarios', 'app_clientes', 'app_usuarios'] as $table) {
            $users = $this->adminLocalListUsersFromTable($table, $search, $limit);
            if (!empty($users)) {
                return $users;
            }
        }

        if ($this->adminTableExists('mobile_usuarios')) {
            $nameCol = $this->adminFirstExistingColumn('mobile_usuarios', ['nombre', 'nombre_completo', 'name', 'full_name']);
            $emailCol = $this->adminFirstExistingColumn('mobile_usuarios', ['email', 'correo']);
            $phoneCol = $this->adminFirstExistingColumn('mobile_usuarios', ['telefono', 'celular', 'phone', 'mobile', 'whatsapp']);
            $activeCol = $this->adminFirstExistingColumn('mobile_usuarios', ['activo', 'active', 'is_active']);

            $where = [];
            $params = [];
            if ($activeCol) {
                $where[] = "COALESCE(mu.`{$activeCol}`, 1) = 1";
            }
            if ($search !== '') {
                $parts = [];
                foreach ([$nameCol, $emailCol, $phoneCol] as $column) {
                    if ($column) {
                        $parts[] = "mu.`{$column}` LIKE ?";
                        $params[] = '%' . $search . '%';
                    }
                }
                if ($parts) {
                    $where[] = '(' . implode(' OR ', $parts) . ')';
                }
            }

            $whereSql = $where ? 'WHERE ' . implode(' AND ', $where) : '';
            $nombreExpr = $nameCol ? "mu.`{$nameCol}`" : "CONCAT('Usuario ', mu.id)";
            $emailExpr = $emailCol ? "mu.`{$emailCol}`" : "''";
            $phoneExpr = $phoneCol ? "mu.`{$phoneCol}`" : "''";

            $limitSql = $limit === null ? '' : " LIMIT {$limit}";
            $stmt = $db->prepare(
                "SELECT mu.id,
                        {$nombreExpr} AS nombre,
                        {$nombreExpr} AS name,
                        {$emailExpr} AS email,
                        {$phoneExpr} AS telefono,
                        {$phoneExpr} AS phone
                   FROM mobile_usuarios mu
                   {$whereSql}
                  ORDER BY mu.id DESC
                  {$limitSql}"
            );
            $stmt->execute($params);
            $users = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
            if (!empty($users)) {
                return $users;
            }
        }

        return $this->adminLocalUsersFromPushTokens($search, $limit);
    }

    private function adminLocalListUsersFromTable(string $table, string $search = '', ?int $limit = 50): array
    {
        if (!preg_match('/^[a-zA-Z0-9_]+$/', $table)) {
            return [];
        }

        try {
            $db = Database::getInstance();
            $db->query("SELECT 1 FROM `{$table}` LIMIT 0");

            $knownColumns = [
                'mobile_usuarios' => [
                    'name' => 'nombre',
                    'email' => 'email',
                    'phone' => 'telefono',
                    'active' => 'activo',
                ],
            ];
            $known = $knownColumns[$table] ?? null;

            $nameCol = $known['name'] ?? $this->adminFirstExistingColumn($table, ['nombre', 'nombre_completo', 'name', 'full_name']);
            $emailCol = $known['email'] ?? $this->adminFirstExistingColumn($table, ['email', 'correo']);
            $phoneCol = $known['phone'] ?? $this->adminFirstExistingColumn($table, ['telefono', 'celular', 'phone', 'mobile', 'whatsapp']);
            $activeCol = $known['active'] ?? null;

            if (!$nameCol && !$emailCol && !$phoneCol) {
                error_log("[adminLocalListUsersFromTable] Tabla {$table} existe, pero no tiene columnas reconocidas de usuario.");
                return [];
            }

            $where = [];
            $params = [];
            if ($activeCol) {
                $where[] = "COALESCE(u.`{$activeCol}`, 1) = 1";
            }
            if ($search !== '') {
                $parts = [];
                foreach ([$nameCol, $emailCol, $phoneCol] as $column) {
                    if ($column) {
                        $parts[] = "u.`{$column}` LIKE ?";
                        $params[] = '%' . $search . '%';
                    }
                }
                if ($parts) {
                    $where[] = '(' . implode(' OR ', $parts) . ')';
                }
            }

            $whereSql = $where ? 'WHERE ' . implode(' AND ', $where) : '';
            $nombreExpr = $nameCol ? "u.`{$nameCol}`" : "CONCAT('Usuario ', u.id)";
            $emailExpr = $emailCol ? "u.`{$emailCol}`" : "''";
            $phoneExpr = $phoneCol ? "u.`{$phoneCol}`" : "''";
            $limitSql = $limit === null ? '' : " LIMIT " . min(5000, max(1, $limit));

            $stmt = $db->prepare(
                "SELECT u.id,
                        {$nombreExpr} AS nombre,
                        {$nombreExpr} AS name,
                        {$emailExpr} AS email,
                        {$phoneExpr} AS telefono,
                        {$phoneExpr} AS phone
                   FROM `{$table}` u
                   {$whereSql}
                  ORDER BY u.id DESC
                  {$limitSql}"
            );
            $stmt->execute($params);
            $users = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
            error_log("[adminLocalListUsersFromTable] Tabla {$table} devolvio " . count($users) . " usuarios.");

            return array_map(static function (array $user): array {
                $id = (int)($user['id'] ?? 0);
                $nombre = trim((string)($user['nombre'] ?? $user['name'] ?? ''));
                return [
                    'id' => $id,
                    'nombre' => $nombre !== '' ? $nombre : "Usuario {$id}",
                    'name' => $nombre !== '' ? $nombre : "Usuario {$id}",
                    'email' => trim((string)($user['email'] ?? '')),
                    'telefono' => trim((string)($user['telefono'] ?? $user['phone'] ?? '')),
                    'phone' => trim((string)($user['phone'] ?? $user['telefono'] ?? '')),
                ];
            }, $users);
        } catch (\Throwable $e) {
            error_log("[adminLocalListUsersFromTable] No se pudo leer {$table}: " . $e->getMessage());
            return [];
        }
    }

    private function adminLocalUsersFromPushTokens(string $search = '', ?int $limit = 50): array
    {
        $table = $this->adminFindTableByColumns(
            ['usuario_id', 'fcm_token'],
            ['mobile_push_tokens', 'push_tokens', 'mobile_tokens', 'mobile_fcm_tokens']
        );

        if (!$table) {
            error_log('[adminLocalUsersFromPushTokens] No se encontro tabla local de push tokens.');
            return [];
        }

        $db = Database::getInstance();
        $enabledCol = $this->adminFirstExistingColumn($table, ['enabled', 'activo', 'active', 'is_active']);
        $lastSeenCol = $this->adminFirstExistingColumn($table, ['last_seen_at', 'updated_at', 'created_at']);

        $where = [];
        if ($enabledCol) {
            $where[] = "COALESCE(pt.`{$enabledCol}`, 1) = 1";
        }

        $whereSql = $where ? 'WHERE ' . implode(' AND ', $where) : '';
        $orderSql = $lastSeenCol ? "MAX(pt.`{$lastSeenCol}`) DESC, " : '';

        $limitSql = $limit === null ? '' : " LIMIT " . min(5000, max(1, $limit));
        $stmt = $db->prepare(
            "SELECT pt.usuario_id AS id
               FROM `{$table}` pt
               {$whereSql}
              GROUP BY pt.usuario_id
              ORDER BY {$orderSql} pt.usuario_id DESC
              {$limitSql}"
        );
        $stmt->execute();
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
        if (empty($rows)) {
            error_log('[adminLocalUsersFromPushTokens] Tabla ' . $table . ' sin usuarios con token activo.');
            return [];
        }

        $users = [];
        foreach ($rows as $row) {
            $id = (int)($row['id'] ?? 0);
            if ($id <= 0) {
                continue;
            }
            $label = 'Usuario app #' . $id;
            $users[$id] = [
                'id' => $id,
                'nombre' => $label,
                'name' => $label,
                'email' => '',
                'telefono' => '',
                'phone' => '',
            ];
        }

        if ($users && $this->adminTableExists('usuarios')) {
            $ids = array_keys($users);
            $placeholders = implode(', ', array_fill(0, count($ids), '?'));

            $nameCol = $this->adminFirstExistingColumn('usuarios', ['nombre', 'name']);
            $phoneCol = $this->adminFirstExistingColumn('usuarios', ['telefono', 'celular', 'phone', 'mobile', 'whatsapp']);
            $emailCol = $this->adminFirstExistingColumn('usuarios', ['email', 'correo']);
            $activeCol = $this->adminFirstExistingColumn('usuarios', ['activo', 'active', 'is_active']);

            $selectParts = ['u.id'];
            $selectParts[] = $nameCol ? "u.`{$nameCol}` AS nombre" : "'' AS nombre";
            $selectParts[] = $phoneCol ? "u.`{$phoneCol}` AS telefono" : "'' AS telefono";
            $selectParts[] = $emailCol ? "u.`{$emailCol}` AS email" : "'' AS email";

            $where = ["u.id IN ({$placeholders})"];
            if ($activeCol) {
                $where[] = "COALESCE(u.`{$activeCol}`, 1) = 1";
            }

            $stmt = $db->prepare(
                "SELECT " . implode(', ', $selectParts) . "
                   FROM usuarios u
                  WHERE " . implode(' AND ', $where)
            );
            $stmt->execute($ids);

            foreach (($stmt->fetchAll(PDO::FETCH_ASSOC) ?: []) as $row) {
                $id = (int)($row['id'] ?? 0);
                if ($id <= 0 || !isset($users[$id])) {
                    continue;
                }

                $nombre = trim((string)($row['nombre'] ?? ''));
                $telefono = trim((string)($row['telefono'] ?? ''));
                $email = trim((string)($row['email'] ?? ''));

                if ($nombre !== '') {
                    $users[$id]['nombre'] = $nombre;
                    $users[$id]['name'] = $nombre;
                }
                if ($telefono !== '') {
                    $users[$id]['telefono'] = $telefono;
                    $users[$id]['phone'] = $telefono;
                }
                if ($email !== '') {
                    $users[$id]['email'] = $email;
                }
            }
        }

        if ($users && $this->adminTableExists('rest_comensales') && $this->adminColumnExists('rest_comensales', 'mobile_usuario_id')) {
            $ids = array_keys($users);
            $placeholders = implode(', ', array_fill(0, count($ids), '?'));

            $nameParts = [];
            if ($this->adminColumnExists('rest_comensales', 'mobile_nombre')) {
                $nameParts[] = "NULLIF(TRIM(rc.`mobile_nombre`), '')";
            }
            if ($this->adminColumnExists('rest_comensales', 'nombre')) {
                $nameParts[] = "NULLIF(TRIM(rc.`nombre`), '')";
            }
            $nameExpr = $nameParts
                ? 'COALESCE(' . implode(', ', $nameParts) . ", CONCAT('Usuario app #', rc.mobile_usuario_id))"
                : "CONCAT('Usuario app #', rc.mobile_usuario_id)";

            $emailParts = [];
            if ($this->adminColumnExists('rest_comensales', 'mobile_email')) {
                $emailParts[] = "NULLIF(TRIM(rc.`mobile_email`), '')";
            }
            if ($this->adminColumnExists('rest_comensales', 'email')) {
                $emailParts[] = "NULLIF(TRIM(rc.`email`), '')";
            }
            $emailExpr = $emailParts ? 'COALESCE(' . implode(', ', $emailParts) . ", '')" : "''";

            $phoneParts = [];
            if ($this->adminColumnExists('rest_comensales', 'mobile_telefono')) {
                $phoneParts[] = "NULLIF(TRIM(rc.`mobile_telefono`), '')";
            }
            if ($this->adminColumnExists('rest_comensales', 'telefono')) {
                $phoneParts[] = "NULLIF(TRIM(rc.`telefono`), '')";
            }
            $phoneExpr = $phoneParts ? 'COALESCE(' . implode(', ', $phoneParts) . ", '')" : "''";

            $stmt = $db->prepare(
                "SELECT rc.mobile_usuario_id AS id,
                        MAX({$nameExpr}) AS nombre,
                        MAX({$emailExpr}) AS email,
                        MAX({$phoneExpr}) AS telefono
                   FROM rest_comensales rc
                  WHERE rc.mobile_usuario_id IN ({$placeholders})
                  GROUP BY rc.mobile_usuario_id"
            );
            $stmt->execute($ids);

            foreach (($stmt->fetchAll(PDO::FETCH_ASSOC) ?: []) as $row) {
                $id = (int)($row['id'] ?? 0);
                if ($id <= 0 || !isset($users[$id])) {
                    continue;
                }

                $nombre = trim((string)($row['nombre'] ?? ''));
                $email = trim((string)($row['email'] ?? ''));
                $telefono = trim((string)($row['telefono'] ?? ''));

                if ($nombre !== '') {
                    $users[$id]['nombre'] = $nombre;
                    $users[$id]['name'] = $nombre;
                }
                if ($email !== '') {
                    $users[$id]['email'] = $email;
                }
                if ($telefono !== '') {
                    $users[$id]['telefono'] = $telefono;
                    $users[$id]['phone'] = $telefono;
                }
            }
        }

        $users = array_values($users);
        if ($search !== '') {
            $searchNeedle = mb_strtolower($search);
            $users = array_values(array_filter($users, static function (array $user) use ($searchNeedle): bool {
                $haystack = mb_strtolower(trim(implode(' ', [
                    (string)($user['nombre'] ?? ''),
                    (string)($user['name'] ?? ''),
                    (string)($user['email'] ?? ''),
                    (string)($user['telefono'] ?? ''),
                    (string)($user['phone'] ?? ''),
                ])));
                return $haystack !== '' && str_contains($haystack, $searchNeedle);
            }));
        }

        error_log('[adminLocalUsersFromPushTokens] Tabla ' . $table . ' devolvio ' . count($users) . ' usuarios.');
        return $limit === null ? $users : array_slice($users, 0, $limit);
    }

    private function adminFindTableByColumns(array $requiredColumns, array $preferredTables = []): ?string
    {
        $requiredColumns = array_values(array_unique(array_filter($requiredColumns, static function ($column): bool {
            return is_string($column) && preg_match('/^[a-zA-Z0-9_]+$/', $column);
        })));

        if (!$requiredColumns) {
            return null;
        }

        foreach ($preferredTables as $table) {
            if (!$this->adminTableExists($table)) {
                continue;
            }

            $allPresent = true;
            foreach ($requiredColumns as $column) {
                if (!$this->adminColumnExists($table, $column)) {
                    $allPresent = false;
                    break;
                }
            }

            if ($allPresent) {
                return $table;
            }
        }

        try {
            $db = Database::getInstance();
            $tables = $db->query('SHOW TABLES')->fetchAll(PDO::FETCH_COLUMN) ?: [];
            foreach ($tables as $table) {
                $table = (string)$table;
                if (!preg_match('/^[a-zA-Z0-9_]+$/', $table)) {
                    continue;
                }
                $allPresent = true;
                foreach ($requiredColumns as $column) {
                    if (!$this->adminColumnExists($table, $column)) {
                        $allPresent = false;
                        break;
                    }
                }
                if ($allPresent) {
                    return $table;
                }
            }
            return null;
        } catch (\Throwable $e) {
            return null;
        }
    }

    private function adminFetchAllRemoteUsers(string $endpointBase, string $search, int $perPage = 500): array
    {
        $perPage = min(5000, max(1, $perPage));
        $usersByKey = [];
        $pagination = [];
        $maxPages = 200;

        for ($page = 1; $page <= $maxPages; $page++) {
            $query = http_build_query([
                'search' => $search,
                'page' => $page,
                'per_page' => $perPage,
            ]);
            $result = $this->callAmareApi('GET', "{$endpointBase}?{$query}");
            if (!$result['success']) {
                return $page === 1
                    ? $result
                    : ['success' => true, 'data' => ['users' => array_values($usersByKey), 'pagination' => $pagination]];
            }

            $data = $result['data'];
            $users = $data['data']['users'] ?? $data['users'] ?? $data['data']['usuarios'] ?? $data['usuarios'] ?? [];
            $pagination = $data['data']['pagination'] ?? $data['pagination'] ?? $pagination;
            if (empty($users)) {
                break;
            }

            $before = count($usersByKey);
            foreach ($users as $user) {
                if (!is_array($user)) {
                    continue;
                }
                $key = (string)($user['id'] ?? $user['usuario_id'] ?? $user['email'] ?? $user['correo'] ?? $user['telefono'] ?? $user['phone'] ?? '');
                if ($key === '') {
                    $key = 'row_' . $page . '_' . count($usersByKey);
                }
                $usersByKey[$key] = $user;
            }

            $totalPages = (int)($pagination['total_pages'] ?? $pagination['last_page'] ?? $pagination['pages'] ?? 0);
            if ($totalPages <= 0) {
                $total = (int)($pagination['total'] ?? 0);
                $pageSize = (int)($pagination['per_page'] ?? $pagination['limit'] ?? count($users));
                $totalPages = $total > 0 && $pageSize > 0 ? (int)ceil($total / $pageSize) : 0;
            }
            if (count($usersByKey) === $before || ($totalPages > 0 ? $page >= $totalPages : count($users) < $perPage)) {
                break;
            }
        }

        return [
            'success' => true,
            'data' => [
                'users' => array_values($usersByKey),
                'pagination' => array_merge($pagination, [
                    'page' => 1,
                    'per_page' => count($usersByKey),
                    'total' => count($usersByKey),
                    'all' => true,
                ]),
            ],
        ];
    }

    private function adminRemoteGlobalUsersOrLocal(int $empresaId, string $search, int $page, int $perPage, bool $fetchAll = false): array
    {
        $query = http_build_query([
            'search' => $search,
            'page' => $page,
            'per_page' => $perPage,
        ]);

        foreach (["users", "usuarios"] as $endpointBase) {
            $endpoint = "{$endpointBase}?{$query}";
            $result = $fetchAll
                ? $this->adminFetchAllRemoteUsers($endpointBase, $search, $perPage)
                : $this->callAmareApi('GET', $endpoint);
            if (!$result['success']) {
                continue;
            }
            $data = $result['data'];
            $users = $data['data']['users'] ?? $data['users'] ?? $data['data']['usuarios'] ?? $data['usuarios'] ?? [];
            if (!empty($users)) {
                return $users;
            }
        }

        return $this->adminLocalListUsers($empresaId, $search, $fetchAll ? null : $perPage);
    }

    private function adminPromotionCatalog(array $jwtUser): void
    {
        $empresaId = (int)$jwtUser['empresa_id'];
        $restauranteId = $this->adminRestauranteIdByEmpresa($empresaId);
        $candidateIds = [];
        $sessionRestauranteId = (int)($_SESSION['restaurante_activo_id'] ?? 0);
        foreach ([$sessionRestauranteId, $restauranteId] as $candidateId) {
            $candidateId = (int)$candidateId;
            if ($candidateId > 0 && !in_array($candidateId, $candidateIds, true)) {
                $candidateIds[] = $candidateId;
            }
        }

        $db = Database::getInstance();
        if ($this->adminTableExists('rest_restaurantes')) {
            try {
                $restaurantOrder = $this->adminColumnExists('rest_restaurantes', 'menu_principal')
                    ? 'menu_principal DESC, id ASC'
                    : 'id ASC';
                $restaurantActiveWhere = $this->adminColumnExists('rest_restaurantes', 'activo')
                    ? 'AND COALESCE(activo, 1) = 1'
                    : '';
                $stmt = $db->prepare(
                    "SELECT id
                       FROM rest_restaurantes
                      WHERE empresa_id = ?
                        {$restaurantActiveWhere}
                      ORDER BY {$restaurantOrder}"
                );
                $stmt->execute([$empresaId]);
                foreach ($stmt->fetchAll(PDO::FETCH_COLUMN) ?: [] as $id) {
                    $id = (int)$id;
                    if ($id > 0 && !in_array($id, $candidateIds, true)) {
                        $candidateIds[] = $id;
                    }
                }

                if (!$candidateIds) {
                    $singleActive = $db->query(
                        "SELECT id
                           FROM rest_restaurantes
                          " . ($restaurantActiveWhere !== '' ? "WHERE COALESCE(activo, 1) = 1" : "") . "
                          ORDER BY id ASC
                          LIMIT 2"
                    )->fetchAll(PDO::FETCH_COLUMN) ?: [];
                    if (count($singleActive) === 1) {
                        $candidateIds[] = (int)$singleActive[0];
                    }
                }
            } catch (\Throwable $e) {
                error_log('[adminPromotionCatalog] No se pudieron resolver restaurantes candidatos: ' . $e->getMessage());
            }
        }

        if ($this->adminTableExists('rest_platillos')) {
            try {
                $productActiveWhere = $this->adminColumnExists('rest_platillos', 'activo')
                    ? ' AND COALESCE(p.activo, 1) = 1'
                    : '';
                $productAvailableWhere = $this->adminColumnExists('rest_platillos', 'disponible')
                    ? ' AND COALESCE(p.disponible, 1) = 1'
                    : '';
                $restaurantJoin = '';
                $restaurantWhere = '';
                $params = [];
                if ($this->adminTableExists('rest_restaurantes') && $this->adminColumnExists('rest_restaurantes', 'empresa_id')) {
                    $restaurantJoin = ' JOIN rest_restaurantes r ON r.id = p.restaurante_id';
                    $restaurantWhere = ' AND r.empresa_id = ?';
                    $params[] = $empresaId;
                    if ($this->adminColumnExists('rest_restaurantes', 'activo')) {
                        $restaurantWhere .= ' AND COALESCE(r.activo, 1) = 1';
                    }
                }
                $stmt = $db->prepare(
                    "SELECT p.restaurante_id
                       FROM rest_platillos p
                       {$restaurantJoin}
                      WHERE p.restaurante_id IS NOT NULL
                            {$productActiveWhere}
                            {$productAvailableWhere}
                            {$restaurantWhere}
                      GROUP BY p.restaurante_id
                      ORDER BY COUNT(*) DESC, p.restaurante_id ASC"
                );
                $stmt->execute($params);
                foreach ($stmt->fetchAll(PDO::FETCH_COLUMN) ?: [] as $id) {
                    $id = (int)$id;
                    if ($id > 0 && !in_array($id, $candidateIds, true)) {
                        $candidateIds[] = $id;
                    }
                }

                $stmt = $db->query(
                    "SELECT p.restaurante_id
                       FROM rest_platillos p
                      WHERE p.restaurante_id IS NOT NULL
                      GROUP BY p.restaurante_id
                      ORDER BY COUNT(*) DESC, p.restaurante_id ASC"
                );
                foreach ($stmt->fetchAll(PDO::FETCH_COLUMN) ?: [] as $id) {
                    $id = (int)$id;
                    if ($id > 0 && !in_array($id, $candidateIds, true)) {
                        $candidateIds[] = $id;
                    }
                }

                $stmt = $db->query(
                    "SELECT p.restaurante_id
                       FROM rest_platillos p
                      WHERE p.restaurante_id IS NOT NULL
                            {$productActiveWhere}
                            {$productAvailableWhere}
                      GROUP BY p.restaurante_id
                      ORDER BY COUNT(*) DESC, p.restaurante_id ASC"
                );
                foreach ($stmt->fetchAll(PDO::FETCH_COLUMN) ?: [] as $id) {
                    $id = (int)$id;
                    if ($id > 0 && !in_array($id, $candidateIds, true)) {
                        $candidateIds[] = $id;
                    }
                }
            } catch (\Throwable $e) {
                error_log('[adminPromotionCatalog] No se pudieron agregar restaurantes con platillos: ' . $e->getMessage());
            }
        }

        if (!$candidateIds) {
            $this->adminApiError('No se encontro restaurante vinculado para tu empresa.', 404);
        }

        $categories = [];
        $products = [];
        $usedRestauranteId = (int)$candidateIds[0];

        foreach ($candidateIds as $candidateId) {
            $candidateId = (int)$candidateId;
            $candidateCategories = [];
            $candidateProducts = [];

            if ($this->adminTableExists('rest_categorias_menu')) {
                $categoryActiveWhere = $this->adminColumnExists('rest_categorias_menu', 'activo')
                    ? ' AND COALESCE(activo, 1) = 1'
                    : '';
                $categoryOrder = $this->adminColumnExists('rest_categorias_menu', 'orden')
                    ? 'orden ASC, nombre ASC'
                    : 'nombre ASC';
                $stmt = $db->prepare(
                    "SELECT id,
                            nombre,
                            " . ($this->adminColumnExists('rest_categorias_menu', 'descripcion') ? 'descripcion' : "'' AS descripcion") . ",
                            " . ($this->adminColumnExists('rest_categorias_menu', 'activo') ? 'activo' : '1 AS activo') . "
                       FROM rest_categorias_menu
                      WHERE restaurante_id = ?
                            {$categoryActiveWhere}
                      ORDER BY {$categoryOrder}"
                );
                $stmt->execute([$candidateId]);
                $candidateCategories = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
                if (!$candidateCategories && $categoryActiveWhere !== '') {
                    $stmt = $db->prepare(
                        "SELECT id,
                                nombre,
                                " . ($this->adminColumnExists('rest_categorias_menu', 'descripcion') ? 'descripcion' : "'' AS descripcion") . ",
                                " . ($this->adminColumnExists('rest_categorias_menu', 'activo') ? 'activo' : '1 AS activo') . "
                           FROM rest_categorias_menu
                          WHERE restaurante_id = ?
                          ORDER BY {$categoryOrder}"
                    );
                    $stmt->execute([$candidateId]);
                    $candidateCategories = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
                }
            }

            if (!$candidateCategories) {
                try {
                    $stmt = $db->prepare(
                        "SELECT id,
                                nombre,
                                descripcion,
                                1 AS activo
                           FROM rest_categorias_menu
                          WHERE restaurante_id = ?
                          ORDER BY nombre ASC"
                    );
                    $stmt->execute([$candidateId]);
                    $candidateCategories = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
                } catch (\Throwable $e) {
                    error_log('[adminPromotionCatalog] Lectura directa rest_categorias_menu fallo: ' . $e->getMessage());
                }
            }

            if ($this->adminTableExists('rest_platillos')) {
                $hasCategories = $this->adminTableExists('rest_categorias_menu');
                $categoryJoin = $hasCategories
                    ? " LEFT JOIN rest_categorias_menu c ON c.id = p.categoria_id"
                        . ($this->adminColumnExists('rest_categorias_menu', 'restaurante_id') ? " AND c.restaurante_id = p.restaurante_id" : "")
                    : "";
                $categoryName = $hasCategories ? "COALESCE(c.nombre, '')" : "''";
                $productActiveWhere = $this->adminColumnExists('rest_platillos', 'activo')
                    ? ' AND COALESCE(p.activo, 1) = 1'
                    : '';
                $productAvailableWhere = $this->adminColumnExists('rest_platillos', 'disponible')
                    ? ' AND COALESCE(p.disponible, 1) = 1'
                    : '';
                $priceExpr = $this->adminColumnExists('rest_platillos', 'precio')
                    ? 'p.precio'
                    : ($this->adminColumnExists('rest_platillos', 'precio_base') ? 'p.precio_base' : '0');
                $descriptionExpr = $this->adminColumnExists('rest_platillos', 'descripcion')
                    ? 'p.descripcion'
                    : "''";
                $stmt = $db->prepare(
                    "SELECT p.id,
                            p.nombre,
                            {$descriptionExpr} AS descripcion,
                            {$priceExpr} AS precio,
                            " . ($this->adminColumnExists('rest_platillos', 'categoria_id') ? 'p.categoria_id' : 'NULL AS categoria_id') . ",
                            {$categoryName} AS categoria_nombre
                       FROM rest_platillos p
                       {$categoryJoin}
                      WHERE p.restaurante_id = ?
                            {$productActiveWhere}
                            {$productAvailableWhere}
                      ORDER BY categoria_nombre ASC, p.nombre ASC"
                );
                $stmt->execute([$candidateId]);
                $candidateProducts = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
                if (!$candidateProducts && ($productActiveWhere !== '' || $productAvailableWhere !== '')) {
                    $stmt = $db->prepare(
                        "SELECT p.id,
                                p.nombre,
                                {$descriptionExpr} AS descripcion,
                                {$priceExpr} AS precio,
                                " . ($this->adminColumnExists('rest_platillos', 'categoria_id') ? 'p.categoria_id' : 'NULL AS categoria_id') . ",
                                {$categoryName} AS categoria_nombre
                           FROM rest_platillos p
                           {$categoryJoin}
                          WHERE p.restaurante_id = ?
                          ORDER BY categoria_nombre ASC, p.nombre ASC"
                    );
                    $stmt->execute([$candidateId]);
                    $candidateProducts = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
                    if ($candidateProducts) {
                        error_log('[adminPromotionCatalog] Catalogo usando platillos sin filtro activo/disponible para restaurante=' . $candidateId . ' count=' . count($candidateProducts));
                    }
                }
            }

            if (!$candidateProducts) {
                try {
                    $stmt = $db->prepare(
                        "SELECT p.id,
                                p.nombre,
                                p.descripcion AS descripcion,
                                p.precio AS precio,
                                p.categoria_id,
                                COALESCE(c.nombre, '') AS categoria_nombre
                           FROM rest_platillos p
                           LEFT JOIN rest_categorias_menu c ON c.id = p.categoria_id
                          WHERE p.restaurante_id = ?
                          ORDER BY categoria_nombre ASC, p.nombre ASC"
                    );
                    $stmt->execute([$candidateId]);
                    $candidateProducts = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
                    if ($candidateProducts) {
                        error_log('[adminPromotionCatalog] Catalogo usando lectura directa de rest_platillos restaurante=' . $candidateId . ' count=' . count($candidateProducts));
                    }
                } catch (\Throwable $e) {
                    error_log('[adminPromotionCatalog] Lectura directa rest_platillos fallo: ' . $e->getMessage());
                }
            }

            if ($candidateProducts || $candidateCategories || (!$categories && !$products)) {
                $usedRestauranteId = $candidateId;
                $categories = $candidateCategories;
                $products = $candidateProducts;
            }
            if ($candidateProducts) {
                break;
            }
        }

        $catalogDiagnostics = [];
        try {
            $catalogDiagnostics[] = 'db=' . (defined('DB_NAME') ? (string)DB_NAME : 'unknown');
            try {
                $catalogDiagnostics[] = 'pdo_database=' . (string)$db->query('SELECT DATABASE()')->fetchColumn();
            } catch (\Throwable $e) {
                $catalogDiagnostics[] = 'pdo_database_error=' . $e->getMessage();
            }
            try {
                $catalogDiagnostics[] = 'pdo_user=' . (string)$db->query('SELECT USER()')->fetchColumn();
            } catch (\Throwable $e) {
                $catalogDiagnostics[] = 'pdo_user_error=' . $e->getMessage();
            }
            $catalogDiagnostics[] = 'rest_platillos_exists=' . ($this->adminTableExists('rest_platillos') ? 'yes' : 'no');
            try {
                $catalogDiagnostics[] = 'direct_platillos_total=' . (int)$db->query('SELECT COUNT(*) FROM rest_platillos')->fetchColumn();
            } catch (\Throwable $e) {
                $catalogDiagnostics[] = 'direct_platillos_error=' . $e->getMessage();
            }
            if ($this->adminTableExists('rest_platillos')) {
                $catalogDiagnostics[] = 'platillos_total=' . (int)$db->query('SELECT COUNT(*) FROM rest_platillos')->fetchColumn();
                $stmt = $db->prepare('SELECT COUNT(*) FROM rest_platillos WHERE restaurante_id = ?');
                $stmt->execute([$usedRestauranteId]);
                $catalogDiagnostics[] = 'platillos_used_restaurante=' . (int)$stmt->fetchColumn();
                $stmt = $db->query('SELECT restaurante_id, COUNT(*) AS total FROM rest_platillos GROUP BY restaurante_id ORDER BY total DESC, restaurante_id ASC LIMIT 5');
                $byRestaurant = [];
                foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) ?: [] as $row) {
                    $byRestaurant[] = (int)($row['restaurante_id'] ?? 0) . ':' . (int)($row['total'] ?? 0);
                }
                $catalogDiagnostics[] = 'platillos_por_restaurante=' . ($byRestaurant ? implode('|', $byRestaurant) : 'none');
            }
            $catalogDiagnostics[] = 'categorias_exists=' . ($this->adminTableExists('rest_categorias_menu') ? 'yes' : 'no');
            try {
                $catalogDiagnostics[] = 'direct_categorias_total=' . (int)$db->query('SELECT COUNT(*) FROM rest_categorias_menu')->fetchColumn();
            } catch (\Throwable $e) {
                $catalogDiagnostics[] = 'direct_categorias_error=' . $e->getMessage();
            }
            if ($this->adminTableExists('rest_categorias_menu')) {
                $catalogDiagnostics[] = 'categorias_total=' . (int)$db->query('SELECT COUNT(*) FROM rest_categorias_menu')->fetchColumn();
                $stmt = $db->prepare('SELECT COUNT(*) FROM rest_categorias_menu WHERE restaurante_id = ?');
                $stmt->execute([$usedRestauranteId]);
                $catalogDiagnostics[] = 'categorias_used_restaurante=' . (int)$stmt->fetchColumn();
            }
            $likeTables = [];
            $allTables = $db->query('SHOW TABLES')->fetchAll(PDO::FETCH_COLUMN) ?: [];
            foreach ($allTables as $table) {
                $table = (string)$table;
                if ($table === '') {
                    continue;
                }
                if (!preg_match('/platillo|categoria|menu|producto/i', $table)) {
                    continue;
                }
                if (!in_array($table, $likeTables, true)) {
                    $likeTables[] = $table;
                }
            }
            $catalogDiagnostics[] = 'tablas_similares=' . ($likeTables ? implode(',', $likeTables) : 'none');
        } catch (\Throwable $e) {
            $catalogDiagnostics[] = 'diagnostics_error=' . $e->getMessage();
        }

        error_log(
            '[adminPromotionCatalog] empresa=' . $empresaId
            . ' session_restaurante=' . $sessionRestauranteId
            . ' used_restaurante=' . $usedRestauranteId
            . ' candidates=' . implode(',', $candidateIds)
            . ' products=' . count($products)
            . ' categories=' . count($categories)
            . ' | ' . implode(' ', $catalogDiagnostics)
        );

        $this->adminApiOk('Catalogo de promociones obtenido correctamente', [
            'categories' => $categories,
            'products' => $products,
            'restaurante_id' => $usedRestauranteId,
            'candidate_restaurante_ids' => $candidateIds,
        ]);
    }

    private function adminNormalizePromotionList(int $empresaId, array $promotions): array
    {
        $localById = [];
        $localByCode = [];
        if ($this->adminTableExists('mobile_promociones')) {
            foreach ($this->adminLocalListPromotions($empresaId) as $local) {
                $localById[(int)($local['id'] ?? 0)] = $local;
                $code = trim((string)($local['code'] ?? ''));
                if ($code !== '') {
                    $localByCode[strtoupper($code)] = $local;
                }
            }
        }

        $normalized = [];
        foreach ($promotions as $promotion) {
            if (!is_array($promotion)) {
                continue;
            }
            $id = (int)($promotion['id'] ?? 0);
            $code = strtoupper(trim((string)($promotion['code'] ?? '')));
            $local = $localById[$id] ?? ($code !== '' ? ($localByCode[$code] ?? []) : []);
            $row = $this->normalizePromotionRuleArray(array_merge($local, $promotion));
            if (!empty($row['usuario_id']) && !empty($row['code'])) {
                $synced = $this->adminLocalSyncPromotion($empresaId, $row, $promotion);
                if ($synced) {
                    $row = $this->normalizePromotionRuleArray(array_merge($row, $synced));
                }
            }
            $row['scope_ids'] = $row['scope_ids_array'] ?? [];
            $row['producto_ids'] = $row['scope_tipo'] === 'products' ? $row['scope_ids'] : [];
            $row['categoria_ids'] = $row['scope_tipo'] === 'categories' ? $row['scope_ids'] : [];
            $normalized[] = $row;
        }

        $this->adminAttachPromotionPushState($normalized);

        return $normalized;
    }

    private function adminLocalListPromotions(int $empresaId): array
    {
        $db = Database::getInstance();
        $restauranteId = $this->adminRestauranteIdByEmpresa($empresaId);

        if ($this->adminTableExists('mobile_promociones')) {
            $this->adminEnsureMobilePromocionesRuleColumns();
            $join = '';
            $notificationJoin = '';
            $usuarioNombre = "'' AS usuario_nombre";
            $usuarioEmail = "'' AS usuario_email";
            $notificationStatus = "NULL AS notification_status";
            $notificationError = "NULL AS notification_error";
            $notificationSentAt = "NULL AS notification_sent_at";
            if ($this->adminTableExists('mobile_usuarios')) {
                $nameCol = $this->adminFirstExistingColumn('mobile_usuarios', ['nombre', 'nombre_completo', 'name', 'full_name']);
                $emailCol = $this->adminFirstExistingColumn('mobile_usuarios', ['email', 'correo']);
                $usuarioNombre = $nameCol ? "mu.`{$nameCol}` AS usuario_nombre" : $usuarioNombre;
                $usuarioEmail = $emailCol ? "mu.`{$emailCol}` AS usuario_email" : $usuarioEmail;
                $join = ' LEFT JOIN mobile_usuarios mu ON mu.id = mp.usuario_id';
            }
            if ($this->adminEnsureMobileNotificationLogsTable()) {
                $notificationJoin = " LEFT JOIN (
                        SELECT n1.*
                          FROM mobile_notification_logs n1
                          JOIN (
                                SELECT promotion_id, MAX(id) AS id
                                  FROM mobile_notification_logs
                                 WHERE promotion_id IS NOT NULL
                                 GROUP BY promotion_id
                               ) nx ON nx.id = n1.id
                    ) nl ON nl.promotion_id = mp.id";
                $notificationStatus = "nl.status AS notification_status";
                $notificationError = "nl.error AS notification_error";
                $notificationSentAt = "nl.sent_at AS notification_sent_at";
            }

            $sql = "SELECT mp.id,
                           " . $this->adminColumnExpr('mobile_promociones', 'mp', 'usuario_id', 'usuario_id', 'NULL') . ",
                           {$usuarioNombre},
                           {$usuarioEmail},
                           " . $this->adminColumnExpr('mobile_promociones', 'mp', 'titulo', 'titulo', "''") . ",
                           " . $this->adminColumnExpr('mobile_promociones', 'mp', 'descripcion', 'descripcion', "''") . ",
                           " . $this->adminColumnExpr('mobile_promociones', 'mp', 'imagen', 'imagen', "''") . ",
                           " . $this->adminColumnExpr('mobile_promociones', 'mp', 'deep_link', 'deep_link', "''") . ",
                           " . $this->adminColumnExpr('mobile_promociones', 'mp', 'code', 'code', "''") . ",
                           " . $this->adminColumnExpr('mobile_promociones', 'mp', 'tipo_descuento', 'tipo_descuento', "'porcentaje'") . ",
                           " . $this->adminColumnExpr('mobile_promociones', 'mp', 'valor_descuento', 'valor_descuento', '0') . ",
                           " . $this->adminColumnExpr('mobile_promociones', 'mp', 'scope_tipo', 'scope_tipo', "'all'") . ",
                           " . $this->adminColumnExpr('mobile_promociones', 'mp', 'scope_ids', 'scope_ids', 'NULL') . ",
                           " . $this->adminColumnExpr('mobile_promociones', 'mp', 'buy_qty', 'buy_qty', 'NULL') . ",
                           " . $this->adminColumnExpr('mobile_promociones', 'mp', 'pay_qty', 'pay_qty', 'NULL') . ",
                           " . $this->adminColumnExpr('mobile_promociones', 'mp', 'min_subtotal', 'min_subtotal', '0') . ",
                           " . $this->adminColumnExpr('mobile_promociones', 'mp', 'max_uses', 'max_uses', 'NULL') . ",
                           " . $this->adminColumnExpr('mobile_promociones', 'mp', 'combinable', 'combinable', '0') . ",
                           " . $this->adminColumnExpr('mobile_promociones', 'mp', 'activo', 'activo', '1') . ",
                           " . $this->adminColumnExpr('mobile_promociones', 'mp', 'expires_at', 'expires_at', 'NULL') . ",
                           " . $this->adminColumnExpr('mobile_promociones', 'mp', 'created_at', 'created_at', 'NULL') . ",
                           {$notificationStatus},
                           {$notificationError},
                           {$notificationSentAt}
                      FROM mobile_promociones mp{$join}{$notificationJoin}
                     ORDER BY mp.id DESC
                     LIMIT 100";
            $rows = $db->query($sql)->fetchAll(PDO::FETCH_ASSOC) ?: [];
            $this->adminAttachPromotionPushState($rows);
            return $rows;
        }

        if (!$restauranteId || !$this->adminRestPromocionesDisponible()) {
            return [];
        }

        $notificationJoin = '';
        $notificationStatus = "NULL AS notification_status";
        $notificationError = "NULL AS notification_error";
        $notificationSentAt = "NULL AS notification_sent_at";
        if ($this->adminEnsureMobileNotificationLogsTable()) {
            $notificationJoin = " LEFT JOIN (
                    SELECT n1.*
                      FROM mobile_notification_logs n1
                      JOIN (
                            SELECT promotion_id, MAX(id) AS id
                              FROM mobile_notification_logs
                             WHERE promotion_id IS NOT NULL
                             GROUP BY promotion_id
                           ) nx ON nx.id = n1.id
                ) nl ON nl.promotion_id = p.id";
            $notificationStatus = "nl.status AS notification_status";
            $notificationError = "nl.error AS notification_error";
            $notificationSentAt = "nl.sent_at AS notification_sent_at";
        }

        $stmt = $db->prepare(
            "SELECT p.id,
                    p.usuario_id,
                    COALESCE(mu.nombre, '') AS usuario_nombre,
                    COALESCE(mu.email, '') AS usuario_email,
                    p.titulo,
                    p.descripcion,
                    p.imagen,
                    p.deep_link,
                    p.code,
                    p.tipo AS tipo_descuento,
                    p.valor_descuento,
                    'all' AS scope_tipo,
                    NULL AS scope_ids,
                    NULL AS buy_qty,
                    NULL AS pay_qty,
                    0 AS min_subtotal,
                    NULL AS max_uses,
                    0 AS combinable,
                    p.activo,
                    COALESCE(p.expires_at, CONCAT(p.fecha_fin, ' 23:59:59')) AS expires_at,
                    p.created_at,
                    {$notificationStatus},
                    {$notificationError},
                    {$notificationSentAt}
               FROM rest_promociones p
               LEFT JOIN mobile_usuarios mu ON mu.id = p.usuario_id
               {$notificationJoin}
              WHERE p.restaurante_id = ?
              ORDER BY p.id DESC
              LIMIT 100"
        );
        $stmt->execute([$restauranteId]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
        $this->adminAttachPromotionPushState($rows);

        return $rows;
    }

    private function adminLocalGetPromotion(int $id, int $empresaId): ?array
    {
        foreach ($this->adminLocalListPromotions($empresaId) as $promotion) {
            if ((int)($promotion['id'] ?? 0) === $id) {
                return $promotion;
            }
        }
        return null;
    }

    private function adminLocalPromotionCodeExists(string $code, ?int $excludeId = null, array $tables = ['rest_promociones', 'mobile_promociones']): bool
    {
        $code = trim($code);
        if ($code === '') {
            return false;
        }

        $db = Database::getInstance();
        foreach ($tables as $table) {
            if (!$this->adminTableExists($table) || !$this->adminColumnExists($table, 'code')) {
                continue;
            }
            $where = 'TRIM(code) = ?';
            $params = [$code];
            if ($excludeId !== null && $this->adminColumnExists($table, 'id')) {
                $where .= ' AND id <> ?';
                $params[] = $excludeId;
            }
            try {
                $stmt = $db->prepare("SELECT id FROM `{$table}` WHERE {$where} LIMIT 1");
                $stmt->execute($params);
                if ($stmt->fetchColumn()) {
                    return true;
                }
            } catch (\Throwable $e) {
                error_log('[adminLocalPromotionCodeExists] No se pudo validar codigo en ' . $table . ': ' . $e->getMessage());
            }
        }

        return false;
    }

    private function adminIsDuplicateKeyError(\PDOException $e): bool
    {
        $info = $e->errorInfo ?? [];
        return (string)($info[0] ?? '') === '23000'
            || (int)($info[1] ?? 0) === 1062
            || str_contains($e->getMessage(), 'Duplicate entry');
    }

    private function adminLocalCreatePromotion(int $empresaId, array $body): array
    {
        $db = Database::getInstance();
        $restauranteId = $this->adminRestauranteIdByEmpresa($empresaId);
        $code = trim((string)($body['code'] ?? ''));
        if ($code === '') {
            do {
                $code = 'PROMO' . strtoupper(bin2hex(random_bytes(3)));
            } while ($this->adminLocalPromotionCodeExists($code, null, ['mobile_promociones']));
        } elseif ($this->adminLocalPromotionCodeExists($code, null, ['mobile_promociones'])) {
            $this->adminApiError('Ese codigo de promocion ya existe. Usa otro codigo.', 409, [
                'code' => ['El codigo "' . $code . '" ya esta registrado.'],
            ]);
        }
        $expiresAt = $body['expires_at'] ?? null;
        if (!$expiresAt) {
            $expiresAt = date('Y-m-d H:i:s', strtotime('+30 days'));
        }

        $this->adminEnsureMobilePromocionesTable();
        if ($this->adminTableExists('mobile_promociones')) {
            $values = [
                'usuario_id' => (int)($body['usuario_id'] ?? 0),
                'producto_id' => $body['producto_id'] ?? $body['platillo_id'] ?? null,
                'platillo_id' => $body['producto_id'] ?? $body['platillo_id'] ?? null,
                'titulo' => (string)($body['titulo'] ?? ''),
                'descripcion' => (string)($body['descripcion'] ?? ''),
                'imagen' => (string)($body['imagen'] ?? ''),
                'deep_link' => $this->adminPromotionDeepLink($code),
                'code' => $code,
                'tipo_descuento' => $body['tipo_descuento'] ?? 'porcentaje',
                'valor_descuento' => (float)($body['valor_descuento'] ?? 0),
                'scope_tipo' => $body['scope_tipo'] ?? 'all',
                'scope_ids' => $body['scope_ids'] ?? null,
                'buy_qty' => $body['buy_qty'] ?? null,
                'pay_qty' => $body['pay_qty'] ?? null,
                'min_subtotal' => (float)($body['min_subtotal'] ?? 0),
                'max_uses' => $body['max_uses'] ?? null,
                'combinable' => (int)($body['combinable'] ?? 0),
                'activo' => isset($body['activo']) ? (int)$body['activo'] : 1,
                'expires_at' => $expiresAt,
                'created_at' => date('Y-m-d H:i:s'),
            ];
            try {
                $this->adminInsertExistingColumns('mobile_promociones', $values);
            } catch (\PDOException $e) {
                if ($this->adminIsDuplicateKeyError($e)) {
                    $this->adminApiError('Ese codigo de promocion ya existe. Usa otro codigo.', 409, [
                        'code' => ['El codigo "' . $code . '" ya esta registrado.'],
                    ]);
                }
                throw $e;
            }
            $promotionId = (int)$db->lastInsertId();
            $promotion = $this->adminLocalGetPromotion($promotionId, $empresaId) ?? ['id' => $promotionId] + $values;
            $notification = $this->adminSendPromotionNotification($promotion, $body);
            $promotion = $this->adminLocalGetPromotion($promotionId, $empresaId) ?? $promotion;
            $promotion['notification_summary'] = $notification;
            $promotion['notification'] = $notification['log'] ?? null;
            return $promotion;
        }

        if ($restauranteId && $this->adminRestPromocionesDisponible()) {
            $tipo = $this->normalizePromotionDiscountType((string)($body['tipo_descuento'] ?? $body['tipo'] ?? 'porcentaje'));
            if (!in_array($tipo, ['porcentaje', 'monto_fijo'], true)) {
                $tipo = 'porcentaje';
            }
            $stmt = $db->prepare(
                "INSERT INTO rest_promociones
                    (restaurante_id, usuario_id, titulo, descripcion, code, tipo, valor_descuento, fecha_inicio, fecha_fin, activo, expires_at, imagen, deep_link)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"
            );
            try {
                $stmt->execute([
                    $restauranteId,
                    (int)($body['usuario_id'] ?? 0) ?: null,
                    (string)($body['titulo'] ?? ''),
                    (string)($body['descripcion'] ?? ''),
                    $code,
                    $tipo,
                    (float)($body['valor_descuento'] ?? 0),
                    date('Y-m-d'),
                    substr((string)$expiresAt, 0, 10),
                    isset($body['activo']) ? (int)$body['activo'] : 1,
                    $expiresAt,
                    (string)($body['imagen'] ?? ''),
                    $this->adminPromotionDeepLink($code),
                ]);
            } catch (\PDOException $e) {
                if ($this->adminIsDuplicateKeyError($e)) {
                    $this->adminApiError('Ese codigo de promocion ya existe. Usa otro codigo.', 409, [
                        'code' => ['El codigo "' . $code . '" ya esta registrado.'],
                    ]);
                }
                throw $e;
            }
            $promotionId = (int)$db->lastInsertId();
            $promotion = $this->adminLocalGetPromotion($promotionId, $empresaId) ?? ['id' => $promotionId];
            $notification = $this->adminSendPromotionNotification($promotion, $body);
            $promotion = $this->adminLocalGetPromotion($promotionId, $empresaId) ?? $promotion;
            $promotion['notification_summary'] = $notification;
            $promotion['notification'] = $notification['log'] ?? null;
            return $promotion;
        }

        $this->adminEnsureMobilePromocionesTable();
        if ($this->adminTableExists('mobile_promociones')) {
            $values = [
                'usuario_id' => (int)($body['usuario_id'] ?? 0),
                'producto_id' => $body['producto_id'] ?? $body['platillo_id'] ?? null,
                'titulo' => (string)($body['titulo'] ?? ''),
                'descripcion' => (string)($body['descripcion'] ?? ''),
                'imagen' => (string)($body['imagen'] ?? ''),
                'deep_link' => $this->adminPromotionDeepLink($code),
                'code' => $code,
                'tipo_descuento' => $body['tipo_descuento'] ?? 'porcentaje',
                'valor_descuento' => (float)($body['valor_descuento'] ?? 0),
                'scope_tipo' => $body['scope_tipo'] ?? 'all',
                'scope_ids' => $body['scope_ids'] ?? null,
                'buy_qty' => $body['buy_qty'] ?? null,
                'pay_qty' => $body['pay_qty'] ?? null,
                'min_subtotal' => (float)($body['min_subtotal'] ?? 0),
                'max_uses' => $body['max_uses'] ?? null,
                'combinable' => (int)($body['combinable'] ?? 0),
                'activo' => isset($body['activo']) ? (int)$body['activo'] : 1,
                'expires_at' => $expiresAt,
                'created_at' => date('Y-m-d H:i:s'),
            ];
            $this->adminInsertExistingColumns('mobile_promociones', $values);
            $promotionId = (int)$db->lastInsertId();
            $promotion = $this->adminLocalGetPromotion($promotionId, $empresaId) ?? ['id' => $promotionId] + $values;
            $notification = $this->adminSendPromotionNotification($promotion, $body);
            $promotion = $this->adminLocalGetPromotion($promotionId, $empresaId) ?? $promotion;
            $promotion['notification_summary'] = $notification;
            $promotion['notification'] = $notification['log'] ?? null;
            return $promotion;
        }

        if (!$restauranteId || !$this->adminRestPromocionesDisponible()) {
            $this->adminApiError('No existe tabla local para guardar promociones.', 500);
        }

        $stmt = $db->prepare(
            "INSERT INTO rest_promociones (restaurante_id, titulo, descripcion, tipo, valor_descuento, fecha_inicio, fecha_fin, activo)
             VALUES (?, ?, ?, 'porcentaje', 0, ?, ?, ?)"
        );
        $stmt->execute([
            $restauranteId,
            (string)($body['titulo'] ?? ''),
            (string)($body['descripcion'] ?? ''),
            date('Y-m-d'),
            substr((string)$expiresAt, 0, 10),
            isset($body['activo']) ? (int)$body['activo'] : 1,
        ]);
        $promotionId = (int)$db->lastInsertId();
        $promotion = $this->adminLocalGetPromotion($promotionId, $empresaId) ?? ['id' => $promotionId];
        $notification = $this->adminSendPromotionNotification($promotion, $body);
        $promotion['notification_summary'] = $notification;
        $promotion['notification'] = $notification['log'] ?? null;
        return $promotion;
    }

    private function adminLocalSyncPromotion(int $empresaId, array $body, array $remotePromotion = []): ?array
    {
        if (!$this->adminEnsureMobilePromocionesTable() || !$this->adminTableExists('mobile_promociones')) {
            return null;
        }

        $db = Database::getInstance();
        $merged = array_merge($remotePromotion, $body);
        $merged = $this->normalizePromotionRuleArray($merged);
        $scopeIds = $merged['scope_ids_array'] ?? [];
        $usuarioId = (int)($merged['usuario_id'] ?? $merged['mobile_usuario_id'] ?? $body['usuario_id'] ?? 0);
        if ($usuarioId <= 0) {
            return null;
        }

        $code = trim((string)($merged['code'] ?? $body['code'] ?? ''));
        if ($code === '') {
            return null;
        }

        $productoId = (int)($merged['producto_id'] ?? $merged['platillo_id'] ?? 0);
        if ($productoId <= 0 && $merged['scope_tipo'] === 'products' && $scopeIds) {
            $productoId = (int)$scopeIds[0];
        }

        $preferredId = (int)($body['id'] ?? $remotePromotion['id'] ?? $merged['id'] ?? 0);
        $existingId = 0;
        if ($preferredId > 0) {
            $stmt = $db->prepare("SELECT id FROM mobile_promociones WHERE id = ? LIMIT 1");
            $stmt->execute([$preferredId]);
            $existingId = (int)($stmt->fetchColumn() ?: 0);
        }
        if ($existingId <= 0) {
            $stmt = $db->prepare("SELECT id FROM mobile_promociones WHERE usuario_id = ? AND code = ? ORDER BY id DESC LIMIT 1");
            $stmt->execute([$usuarioId, $code]);
            $existingId = (int)($stmt->fetchColumn() ?: 0);
        }

        $values = [
            'usuario_id' => $usuarioId,
            'producto_id' => $productoId > 0 ? $productoId : null,
            'platillo_id' => $productoId > 0 ? $productoId : null,
            'titulo' => (string)($merged['titulo'] ?? $merged['title'] ?? ''),
            'descripcion' => (string)($merged['descripcion'] ?? $merged['description'] ?? ''),
            'imagen' => (string)($merged['imagen'] ?? $merged['image'] ?? ''),
            'deep_link' => $this->adminPromotionDeepLink($code),
            'code' => $code,
            'tipo_descuento' => $merged['tipo_descuento'],
            'valor_descuento' => (float)$merged['valor_descuento'],
            'scope_tipo' => $merged['scope_tipo'],
            'scope_ids' => $scopeIds ? json_encode($scopeIds) : null,
            'buy_qty' => $merged['buy_qty'],
            'pay_qty' => $merged['pay_qty'],
            'min_subtotal' => (float)$merged['min_subtotal'],
            'max_uses' => $merged['max_uses'],
            'combinable' => (int)$merged['combinable'],
            'activo' => isset($merged['activo']) ? (int)$merged['activo'] : 1,
            'expires_at' => $merged['expires_at'] ?? null,
            'updated_at' => date('Y-m-d H:i:s'),
        ];

        if ($existingId > 0) {
            $this->adminUpdateExistingColumns('mobile_promociones', $existingId, $values);
            return $this->adminLocalGetPromotion($existingId, $empresaId);
        }

        if ($preferredId > 0) {
            $values = ['id' => $preferredId] + $values;
        }
        $values['created_at'] = date('Y-m-d H:i:s');
        $this->adminInsertExistingColumns('mobile_promociones', $values);
        $newId = $preferredId > 0 ? $preferredId : (int)$db->lastInsertId();
        return $this->adminLocalGetPromotion($newId, $empresaId);
    }

    private function adminPromotionDeepLink(string $code): string
    {
        $code = trim($code);
        return $code !== ''
            ? 'amare://promociones?code=' . rawurlencode($code)
            : 'amare://promociones';
    }

    private function adminEnsureMobilePromocionesTable(): bool
    {
        if ($this->adminTableExists('mobile_promociones')) {
            $this->adminEnsureMobilePromocionesRuleColumns();
            return true;
        }

        try {
            $db = Database::getInstance();
            $db->exec(
                "CREATE TABLE IF NOT EXISTS `mobile_promociones` (
                    `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
                    `usuario_id` int(10) UNSIGNED NOT NULL,
                    `producto_id` int(10) UNSIGNED DEFAULT NULL,
                    `platillo_id` int(11) DEFAULT NULL,
                    `titulo` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
                    `descripcion` text COLLATE utf8mb4_unicode_ci,
                    `imagen` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
                    `deep_link` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
                    `code` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
                    `tipo_descuento` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'porcentaje',
                    `valor_descuento` decimal(10,2) NOT NULL DEFAULT 0.00,
                    `scope_tipo` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'all',
                    `scope_ids` text COLLATE utf8mb4_unicode_ci DEFAULT NULL,
                    `buy_qty` int(10) UNSIGNED DEFAULT NULL,
                    `pay_qty` int(10) UNSIGNED DEFAULT NULL,
                    `min_subtotal` decimal(10,2) NOT NULL DEFAULT 0.00,
                    `max_uses` int(10) UNSIGNED DEFAULT NULL,
                    `combinable` tinyint(1) NOT NULL DEFAULT 0,
                    `activo` tinyint(1) NOT NULL DEFAULT 1,
                    `expires_at` datetime DEFAULT NULL,
                    `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
                    `created_by` int(11) DEFAULT NULL,
                    `updated_by` int(11) DEFAULT NULL,
                    `updated_at` datetime DEFAULT NULL,
                    PRIMARY KEY (`id`),
                    KEY `idx_mobile_promociones_usuario` (`usuario_id`, `activo`, `expires_at`),
                    KEY `idx_mobile_promociones_code` (`code`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
            );

            $created = $this->adminTableExists('mobile_promociones');
            if ($created) {
                $this->adminEnsureMobilePromocionesRuleColumns();
            }
            return $created;
        } catch (\Throwable $e) {
            error_log('[adminEnsureMobilePromocionesTable] No se pudo crear mobile_promociones: ' . $e->getMessage());
            return false;
        }
    }

    private function adminEnsureMobilePromocionesRuleColumns(): void
    {
        if (!$this->adminTableExists('mobile_promociones')) {
            return;
        }

        $columns = [
            'tipo_descuento' => "ALTER TABLE mobile_promociones ADD COLUMN tipo_descuento varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'porcentaje'",
            'valor_descuento' => "ALTER TABLE mobile_promociones ADD COLUMN valor_descuento decimal(10,2) NOT NULL DEFAULT 0.00",
            'scope_tipo' => "ALTER TABLE mobile_promociones ADD COLUMN scope_tipo varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'all'",
            'scope_ids' => "ALTER TABLE mobile_promociones ADD COLUMN scope_ids text COLLATE utf8mb4_unicode_ci DEFAULT NULL",
            'buy_qty' => "ALTER TABLE mobile_promociones ADD COLUMN buy_qty int(10) UNSIGNED DEFAULT NULL",
            'pay_qty' => "ALTER TABLE mobile_promociones ADD COLUMN pay_qty int(10) UNSIGNED DEFAULT NULL",
            'min_subtotal' => "ALTER TABLE mobile_promociones ADD COLUMN min_subtotal decimal(10,2) NOT NULL DEFAULT 0.00",
            'max_uses' => "ALTER TABLE mobile_promociones ADD COLUMN max_uses int(10) UNSIGNED DEFAULT NULL",
            'combinable' => "ALTER TABLE mobile_promociones ADD COLUMN combinable tinyint(1) NOT NULL DEFAULT 0",
        ];

        $db = Database::getInstance();
        foreach ($columns as $column => $sql) {
            if ($this->adminColumnExists('mobile_promociones', $column)) {
                continue;
            }
            try {
                $db->exec($sql);
            } catch (\Throwable $e) {
                error_log("[adminEnsureMobilePromocionesRuleColumns] No se pudo agregar {$column}: " . $e->getMessage());
            }
        }
    }

    private function adminEnsureMobileNotificationLogsTable(): bool
    {
        try {
            Database::getInstance()->query("SELECT 1 FROM `mobile_notification_logs` LIMIT 0");
            return true;
        } catch (\Throwable $e) {
            // Si no se puede leer, intentamos crearla por compatibilidad con instalaciones viejas.
        }

        if ($this->adminTableExists('mobile_notification_logs')) {
            return true;
        }

        try {
            $db = Database::getInstance();
            $db->exec(
                "CREATE TABLE IF NOT EXISTS `mobile_notification_logs` (
                    `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
                    `promotion_id` int(10) UNSIGNED DEFAULT NULL,
                    `usuario_id` int(10) UNSIGNED NOT NULL,
                    `fcm_token_id` int(10) UNSIGNED DEFAULT NULL,
                    `fcm_token` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
                    `provider` varchar(40) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'fcm',
                    `status` enum('pending','sent','failed','skipped') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending',
                    `title` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
                    `body` text COLLATE utf8mb4_unicode_ci,
                    `response` text COLLATE utf8mb4_unicode_ci,
                    `error` text COLLATE utf8mb4_unicode_ci,
                    `sent_at` datetime DEFAULT NULL,
                    `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
                    PRIMARY KEY (`id`),
                    KEY `idx_mobile_notification_promotion` (`promotion_id`),
                    KEY `idx_mobile_notification_usuario` (`usuario_id`),
                    KEY `idx_mobile_notification_status` (`status`, `created_at`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
            );

            return $this->adminTableExists('mobile_notification_logs');
        } catch (\Throwable $e) {
            error_log('[adminEnsureMobileNotificationLogsTable] No se pudo crear mobile_notification_logs: ' . $e->getMessage());
            return false;
        }
    }

    private function adminSendPromotionNotification(array $promotion, array $body): array
    {
        $promotionId = (int)($promotion['id'] ?? 0);
        $usuarioId = (int)($promotion['usuario_id'] ?? $body['usuario_id'] ?? 0);
        $activo = array_key_exists('activo', $promotion) ? (int)$promotion['activo'] : (int)($body['activo'] ?? 1);
        $title = trim((string)($promotion['titulo'] ?? $body['titulo'] ?? 'Nueva promocion'));
        $message = trim((string)($promotion['descripcion'] ?? $body['descripcion'] ?? 'Tienes una nueva promocion en Amare.'));
        $code = trim((string)($promotion['code'] ?? $body['code'] ?? ''));
        $deepLink = $this->adminPromotionDeepLink($code);

        if ($title === '') {
            $title = 'Nueva promocion';
        }
        if ($message === '') {
            $message = 'Tienes una nueva promocion en Amare.';
        }

        if ($usuarioId <= 0) {
            $this->adminLogPromotionNotification($promotionId, 0, null, null, 'skipped', $title, $message, null, 'missing_usuario_id');
            return $this->adminPromotionNotificationSummary($promotionId, 'skipped', 'missing_usuario_id');
        }
        if ($activo !== 1) {
            $this->adminLogPromotionNotification($promotionId, $usuarioId, null, null, 'skipped', $title, $message, null, 'inactive_promotion');
            return $this->adminPromotionNotificationSummary($promotionId, 'skipped', 'inactive_promotion');
        }

        $tokens = $this->adminPromotionPushTokens($usuarioId);
        if (empty($tokens)) {
            $this->adminLogPromotionNotification($promotionId, $usuarioId, null, null, 'skipped', $title, $message, null, 'no_push_token');
            return $this->adminPromotionNotificationSummary($promotionId, 'skipped', 'no_push_token');
        }

        $sent = 0;
        $failed = 0;
        $skipped = 0;
        $invalidated = 0;
        $lastError = null;

        if (!$this->adminCanSendFcm()) {
            foreach ($tokens as $tokenRow) {
                $this->adminLogPromotionNotification(
                    $promotionId,
                    $usuarioId,
                    (int)($tokenRow['id'] ?? 0) ?: null,
                    (string)($tokenRow['token'] ?? ''),
                    'skipped',
                    $title,
                    $message,
                    null,
                    'missing_fcm_config'
                );
                $skipped++;
            }
            error_log('[adminSendPromotionNotification] Falta configurar Firebase HTTP v1 o FCM legacy.');
            return $this->adminPromotionNotificationSummary($promotionId, 'skipped', 'missing_fcm_config', $sent, $failed, $skipped, $invalidated);
        }

        foreach ($tokens as $tokenRow) {
            $token = trim((string)($tokenRow['token'] ?? ''));
            if ($token === '') {
                $skipped++;
                continue;
            }

            $platform = strtolower(trim((string)($tokenRow['platform'] ?? '')));
            $payload = [
                'to' => $token,
                'priority' => 'high',
                'notification' => [
                    'title' => $title,
                    'body' => $message,
                ],
                'data' => [
                    'type' => 'promotion',
                    'promotion_id' => (string)$promotionId,
                    'usuario_id' => (string)$usuarioId,
                    'code' => $code,
                    'promo_code' => $code,
                    'route' => '/promociones',
                    'screen' => 'promociones',
                    'deep_link' => $deepLink,
                ],
            ];
            if ($platform === 'ios') {
                $payload['apns'] = [
                    'headers' => [
                        'apns-priority' => '10',
                    ],
                    'payload' => [
                        'aps' => [
                            'sound' => 'default',
                        ],
                    ],
                ];
            }

            try {
                $result = $this->adminPostFcm($payload);
                $decoded = json_decode($result['body'] ?? '', true);
                $legacySuccess = is_array($decoded) && array_key_exists('success', $decoded) ? (int)($decoded['success'] ?? 0) : null;
                $v1Success = is_array($decoded) && !empty($decoded['name']);
                $status = ($result['http_code'] >= 200 && $result['http_code'] < 300 && ($v1Success || $legacySuccess === null || $legacySuccess > 0)) ? 'sent' : 'failed';
                $error = $status === 'sent' ? null : ('fcm_http_' . $result['http_code']);
                if ($status === 'failed' && is_array($decoded) && !empty($decoded['results'][0]['error'])) {
                    $error = (string)$decoded['results'][0]['error'];
                } elseif ($status === 'failed' && is_array($decoded) && !empty($decoded['error'])) {
                    $error = $this->adminFcmErrorMessage($decoded['error']);
                }
                if ($status === 'failed') {
                    error_log('[adminSendPromotionNotification] FCM failed usuario=' . $usuarioId
                        . ' promotion=' . $promotionId
                        . ' token_id=' . ((int)($tokenRow['id'] ?? 0) ?: 'null')
                        . ' platform=' . ($platform !== '' ? $platform : 'unknown')
                        . ' error=' . ($error ?? 'unknown')
                        . ' response=' . substr((string)($result['body'] ?? ''), 0, 900));
                }

                $logStatus = $status;
                if ($status === 'failed' && $this->adminFcmTokenIsInvalid($error)) {
                    $logStatus = 'skipped';
                    $error = 'invalid_push_token';
                    $invalidated++;
                    $this->adminDisablePromotionPushToken(
                        (string)($tokenRow['_table'] ?? ''),
                        (string)($tokenRow['_token_column'] ?? 'fcm_token'),
                        (int)($tokenRow['id'] ?? 0),
                        $usuarioId,
                        $token
                    );
                }

                $this->adminLogPromotionNotification(
                    $promotionId,
                    $usuarioId,
                    (int)($tokenRow['id'] ?? 0) ?: null,
                    $token,
                    $logStatus,
                    $title,
                    $message,
                    $result['body'] ?? '',
                    $error
                );
                if ($logStatus === 'sent') {
                    $sent++;
                } elseif ($logStatus === 'skipped') {
                    $skipped++;
                } else {
                    $failed++;
                }
                $lastError = $error;
            } catch (\Throwable $e) {
                $this->adminLogPromotionNotification(
                    $promotionId,
                    $usuarioId,
                    (int)($tokenRow['id'] ?? 0) ?: null,
                    $token,
                    'failed',
                    $title,
                    $message,
                    null,
                    $e->getMessage()
                );
                $failed++;
                $lastError = $e->getMessage();
                error_log('[adminSendPromotionNotification] Error FCM: ' . $e->getMessage());
            }
        }

        if ($sent > 0) {
            return $this->adminPromotionNotificationSummary($promotionId, 'sent', null, $sent, $failed, $skipped, $invalidated);
        }
        if ($invalidated > 0 && $failed === 0) {
            return $this->adminPromotionNotificationSummary($promotionId, 'skipped', 'invalid_push_token', $sent, $failed, $skipped, $invalidated);
        }
        if ($failed > 0) {
            return $this->adminPromotionNotificationSummary($promotionId, 'failed', $lastError, $sent, $failed, $skipped, $invalidated);
        }
        return $this->adminPromotionNotificationSummary($promotionId, 'skipped', 'no_deliverable_push_token', $sent, $failed, $skipped, $invalidated);
    }

    private function adminPromotionNotificationSummary(
        int $promotionId,
        string $status,
        ?string $error = null,
        int $sent = 0,
        int $failed = 0,
        int $skipped = 0,
        int $invalidated = 0
    ): array {
        return [
            'status' => $status,
            'error' => $error,
            'sent' => $sent,
            'failed' => $failed,
            'skipped' => $skipped,
            'invalidated_tokens' => $invalidated,
            'log' => $this->adminLatestPromotionNotificationLog($promotionId),
        ];
    }

    private function adminAttachPromotionPushState(array &$rows): void
    {
        $cache = [];
        foreach ($rows as &$row) {
            $tokenRows = [];
            $userIds = $this->adminPromotionUserTokenIds($row);
            $cacheKey = implode(',', $userIds);
            if ($cacheKey === '') {
                $cacheKey = 'none';
            }
            if (!array_key_exists($cacheKey, $cache)) {
                foreach ($userIds as $usuarioId) {
                    $tokenRows = $this->adminPromotionPushTokens($usuarioId);
                    if ($tokenRows) {
                        $row['push_usuario_id'] = $usuarioId;
                        break;
                    }
                }
                $cache[$cacheKey] = $tokenRows;
            }
            $row['push_token_count'] = count($cache[$cacheKey]);
            $row['has_push_token'] = !empty($cache[$cacheKey]) ? 1 : 0;
        }
        unset($row);
    }

    private function adminPromotionUserTokenIds(array $row): array
    {
        $ids = [];
        foreach (['usuario_id', 'mobile_usuario_id', 'mobile_user_id', 'user_id', 'cliente_id', 'comensal_id'] as $key) {
            $id = (int)($row[$key] ?? 0);
            if ($id > 0 && !in_array($id, $ids, true)) {
                $ids[] = $id;
            }
        }

        if (!$this->adminTableExists('mobile_usuarios')) {
            return $ids;
        }

        $email = strtolower(trim((string)($row['usuario_email'] ?? $row['email'] ?? $row['correo'] ?? '')));
        $name = trim((string)($row['usuario_nombre'] ?? $row['nombre'] ?? $row['name'] ?? ''));
        $db = Database::getInstance();

        try {
            $emailCol = $this->adminFirstExistingColumn('mobile_usuarios', ['email', 'correo']);
            if ($email !== '' && $emailCol) {
                $stmt = $db->prepare("SELECT id FROM mobile_usuarios WHERE LOWER(TRIM(`{$emailCol}`)) = ? LIMIT 5");
                $stmt->execute([$email]);
                foreach ($stmt->fetchAll(PDO::FETCH_COLUMN) ?: [] as $id) {
                    $id = (int)$id;
                    if ($id > 0 && !in_array($id, $ids, true)) {
                        $ids[] = $id;
                    }
                }
            }

            $nameCol = $this->adminFirstExistingColumn('mobile_usuarios', ['nombre', 'nombre_completo', 'name', 'full_name']);
            if ($name !== '' && $nameCol) {
                $stmt = $db->prepare("SELECT id FROM mobile_usuarios WHERE LOWER(TRIM(`{$nameCol}`)) = LOWER(TRIM(?)) LIMIT 5");
                $stmt->execute([$name]);
                foreach ($stmt->fetchAll(PDO::FETCH_COLUMN) ?: [] as $id) {
                    $id = (int)$id;
                    if ($id > 0 && !in_array($id, $ids, true)) {
                        $ids[] = $id;
                    }
                }
            }
        } catch (\Throwable $e) {
            error_log('[adminPromotionUserTokenIds] No se pudieron resolver usuarios alternos para push: ' . $e->getMessage());
        }

        return $ids;
    }

    private function adminPromotionPushTokenSource(): ?array
    {
        static $resolved = false;
        static $cached = null;
        if ($resolved) {
            return $cached;
        }

        $preferredTables = ['mobile_push_tokens', 'push_tokens', 'mobile_tokens', 'mobile_fcm_tokens', 'notification_tokens'];
        $userColumns = ['usuario_id', 'mobile_usuario_id', 'mobile_user_id', 'user_id', 'cliente_id', 'comensal_id'];
        $tokenColumns = ['fcm_token', 'token', 'device_token', 'push_token', 'notification_token', 'expo_push_token'];

        $checkTable = function (string $table) use ($userColumns, $tokenColumns): ?array {
            if (!preg_match('/^[a-zA-Z0-9_]+$/', $table) || !$this->adminTableExists($table)) {
                return null;
            }
            $userCol = $this->adminFirstExistingColumn($table, $userColumns);
            $tokenCol = $this->adminFirstExistingColumn($table, $tokenColumns);
            if (!$userCol || !$tokenCol) {
                return null;
            }
            return [
                'table' => $table,
                'user_column' => $userCol,
                'token_column' => $tokenCol,
            ];
        };

        foreach ($preferredTables as $table) {
            $source = $checkTable($table);
            if ($source) {
                $resolved = true;
                $cached = $source;
                return $cached;
            }
        }

        try {
            $db = Database::getInstance();
            $tables = $db->query('SHOW TABLES')->fetchAll(PDO::FETCH_COLUMN) ?: [];
            foreach ($tables as $table) {
                $table = (string)$table;
                if (!preg_match('/(?:push|fcm|notification).*token|token.*(?:push|fcm|notification)/i', $table)) {
                    continue;
                }
                $source = $checkTable($table);
                if ($source) {
                    $resolved = true;
                    $cached = $source;
                    return $cached;
                }
            }
        } catch (\Throwable $e) {
            error_log('[adminPromotionPushTokenSource] No se pudo buscar tabla de tokens: ' . $e->getMessage());
        }

        $resolved = true;
        $cached = null;
        return $cached;
    }

    private function adminPromotionPushTokens(int $usuarioId): array
    {
        if ($usuarioId <= 0) {
            return [];
        }

        $source = $this->adminPromotionPushTokenSource();
        if (!$source) {
            return [];
        }

        $table = (string)$source['table'];
        $userCol = (string)$source['user_column'];
        $tokenCol = (string)$source['token_column'];
        $enabledCol = $this->adminFirstExistingColumn($table, ['enabled', 'activo', 'active', 'is_active']);
        $idCol = $this->adminColumnExists($table, 'id') ? 'id' : null;
        $platformCol = $this->adminFirstExistingColumn($table, ['platform', 'plataforma', 'os']);
        $where = ["`{$userCol}` = ?"];
        if ($enabledCol) {
            $where[] = "COALESCE(`{$enabledCol}`, 1) = 1";
        }

        $db = Database::getInstance();
        $stmt = $db->prepare(
            "SELECT " . ($idCol ? "`{$idCol}` AS id," : "NULL AS id,")
                . ($platformCol ? " `{$platformCol}` AS platform," : " NULL AS platform,")
                . " `{$tokenCol}` AS token
               FROM `{$table}`
              WHERE " . implode(' AND ', $where) . "
                AND TRIM(COALESCE(`{$tokenCol}`, '')) <> ''
              ORDER BY " . ($idCol ? "`{$idCol}`" : "`{$userCol}`") . " DESC"
        );
        $stmt->execute([$usuarioId]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
        if (empty($rows)) {
            try {
                $totalStmt = $db->prepare(
                    "SELECT COUNT(*)
                       FROM `{$table}`
                      WHERE `{$userCol}` = ?
                        AND TRIM(COALESCE(`{$tokenCol}`, '')) <> ''"
                );
                $totalStmt->execute([$usuarioId]);
                $totalTokens = (int)$totalStmt->fetchColumn();
                if ($totalTokens > 0 && $enabledCol) {
                    error_log('[adminPromotionPushTokens] Usuario ' . $usuarioId . ' tiene tokens push, pero ninguno activo en ' . $table . '.');
                } else {
                    error_log('[adminPromotionPushTokens] Usuario ' . $usuarioId . ' no tiene tokens push registrados en ' . $table . '.');
                }
            } catch (\Throwable $e) {
                error_log('[adminPromotionPushTokens] No se pudo diagnosticar tokens usuario=' . $usuarioId . ': ' . $e->getMessage());
            }
        }
        foreach ($rows as &$row) {
            $row['_table'] = $table;
            $row['_token_column'] = $tokenCol;
        }
        unset($row);
        return $rows;
    }

    private function adminFcmTokenIsInvalid(?string $error): bool
    {
        $error = strtoupper((string)$error);
        return str_contains($error, 'UNREGISTERED')
            || str_contains($error, 'REGISTRATION TOKEN')
            || str_contains($error, 'REQUESTED ENTITY WAS NOT FOUND');
    }

    private function adminDisablePromotionPushToken(string $table, string $tokenColumn, int $tokenId, int $usuarioId, string $token): void
    {
        if ($table === '' || !preg_match('/^[a-zA-Z0-9_]+$/', $table) || !$this->adminTableExists($table)) {
            return;
        }
        if (!preg_match('/^[a-zA-Z0-9_]+$/', $tokenColumn) || !$this->adminColumnExists($table, $tokenColumn)) {
            return;
        }

        $enabledCol = $this->adminFirstExistingColumn($table, ['enabled', 'activo', 'active', 'is_active']);
        if (!$enabledCol) {
            return;
        }

        try {
            $db = Database::getInstance();
            if ($tokenId > 0 && $this->adminColumnExists($table, 'id')) {
                $stmt = $db->prepare("UPDATE `{$table}` SET `{$enabledCol}` = 0 WHERE id = ?");
                $stmt->execute([$tokenId]);
            } else {
                $userCol = $this->adminFirstExistingColumn($table, ['usuario_id', 'mobile_usuario_id', 'mobile_user_id', 'user_id', 'cliente_id', 'comensal_id']);
                if (!$userCol) {
                    return;
                }
                $stmt = $db->prepare("UPDATE `{$table}` SET `{$enabledCol}` = 0 WHERE `{$userCol}` = ? AND `{$tokenColumn}` = ?");
                $stmt->execute([$usuarioId, $token]);
            }
            error_log('[adminDisablePromotionPushToken] Token FCM invalido desactivado usuario=' . $usuarioId . ' token_id=' . ($tokenId ?: 'null'));
        } catch (\Throwable $e) {
            error_log('[adminDisablePromotionPushToken] No se pudo desactivar token invalido: ' . $e->getMessage());
        }
    }

    private function adminFcmErrorMessage(array $error): string
    {
        $message = trim((string)($error['message'] ?? ''));
        foreach (($error['details'] ?? []) as $detail) {
            if (is_array($detail) && !empty($detail['errorCode'])) {
                $code = trim((string)$detail['errorCode']);
                return $code . ($message !== '' ? ': ' . $message : '');
            }
        }
        if (!empty($error['status'])) {
            return (string)$error['status'] . ($message !== '' ? ': ' . $message : '');
        }
        return $message !== '' ? $message : 'fcm_error';
    }

    private function adminCanSendFcm(): bool
    {
        return $this->adminFcmV1Config() !== null || $this->adminFcmServerKey() !== '';
    }

    private function adminFcmV1Config(): ?array
    {
        $serviceAccount = $this->adminFirebaseServiceAccount();
        $projectId = $this->adminFirebaseProjectId($serviceAccount);
        if (!$serviceAccount || $projectId === '') {
            error_log('[adminFcmV1Config] Service account no disponible o project_id vacio.');
            return null;
        }
        if (empty($serviceAccount['client_email']) || empty($serviceAccount['private_key'])) {
            error_log('[adminFcmV1Config] Service account sin client_email o private_key.');
            return null;
        }

        return [
            'project_id' => $projectId,
            'client_email' => (string)$serviceAccount['client_email'],
            'private_key' => (string)$serviceAccount['private_key'],
        ];
    }

    private function adminFirebaseServiceAccount(): ?array
    {
        static $cached = false;
        static $cachedValue = null;
        if ($cached) {
            return $cachedValue;
        }

        $json = trim((string)(getenv('FIREBASE_SERVICE_ACCOUNT_JSON') ?: ($_ENV['FIREBASE_SERVICE_ACCOUNT_JSON'] ?? $_SERVER['FIREBASE_SERVICE_ACCOUNT_JSON'] ?? '')));
        if ($json === '') {
            $path = trim((string)(getenv('GOOGLE_APPLICATION_CREDENTIALS') ?: ($_ENV['GOOGLE_APPLICATION_CREDENTIALS'] ?? $_SERVER['GOOGLE_APPLICATION_CREDENTIALS'] ?? '')));
            if ($path !== '' && is_readable($path)) {
                $json = (string)file_get_contents($path);
                error_log('[adminFirebaseServiceAccount] Service account leido desde GOOGLE_APPLICATION_CREDENTIALS.');
            }
        }
        if ($json === '') {
            $paths = [];
            if (defined('ROOT_PATH')) {
                $paths[] = ROOT_PATH . '/amare-service-account.json';
                $paths[] = ROOT_PATH . '/firebase/amare-service-account.json';
                $paths[] = dirname(ROOT_PATH) . '/amare-service-account.json';
                $paths[] = dirname(ROOT_PATH) . '/firebase/amare-service-account.json';
            }
            $documentRoot = (string)($_SERVER['DOCUMENT_ROOT'] ?? '');
            if ($documentRoot !== '') {
                $documentRoot = rtrim($documentRoot, '/\\');
                $paths[] = $documentRoot . '/amare-service-account.json';
                $paths[] = $documentRoot . '/firebase/amare-service-account.json';
            }
            $scriptDir = !empty($_SERVER['SCRIPT_FILENAME']) ? dirname((string)$_SERVER['SCRIPT_FILENAME']) : '';
            if ($scriptDir !== '') {
                $paths[] = $scriptDir . '/amare-service-account.json';
                $paths[] = $scriptDir . '/firebase/amare-service-account.json';
            }
            $paths = array_values(array_unique($paths));

            foreach ($paths as $path) {
                if (is_readable($path)) {
                    $json = (string)file_get_contents($path);
                    error_log('[adminFirebaseServiceAccount] Service account leido desde archivo: ' . $path);
                    break;
                }
                error_log('[adminFirebaseServiceAccount] Archivo no legible/no existe: ' . $path);
            }
        }
        if ($json === '' && $this->adminTableExists('global_settings')) {
            try {
                $db = Database::getInstance();
                $stmt = $db->prepare(
                    "SELECT valor
                       FROM global_settings
                      WHERE clave IN ('firebase_service_account_json', 'FIREBASE_SERVICE_ACCOUNT_JSON')
                        AND COALESCE(valor, '') <> ''
                      LIMIT 1"
                );
                $stmt->execute();
                $json = trim((string)($stmt->fetchColumn() ?: ''));
                if ($json !== '') {
                    error_log('[adminFirebaseServiceAccount] Service account leido desde global_settings.');
                }
            } catch (\Throwable $e) {
                error_log('[adminFirebaseServiceAccount] No se pudo leer service account: ' . $e->getMessage());
            }
        }

        if ($json === '') {
            error_log('[adminFirebaseServiceAccount] No se encontro JSON de Firebase.');
            $cached = true;
            $cachedValue = null;
            return null;
        }

        $json = preg_replace('/^\xEF\xBB\xBF/', '', $json) ?? $json;
        $data = json_decode($json, true);
        if (!is_array($data)) {
            error_log('[adminFirebaseServiceAccount] JSON invalido: ' . json_last_error_msg());
            $cached = true;
            $cachedValue = null;
            return null;
        }

        if (($data['type'] ?? '') !== 'service_account') {
            error_log('[adminFirebaseServiceAccount] JSON no parece ser service_account.');
            $cached = true;
            $cachedValue = null;
            return null;
        }

        $cached = true;
        $cachedValue = is_array($data) ? $data : null;
        return $cachedValue;
    }

    private function adminFirebaseProjectId(?array $serviceAccount = null): string
    {
        foreach (['FIREBASE_PROJECT_ID', 'GOOGLE_CLOUD_PROJECT', 'GCLOUD_PROJECT'] as $key) {
            $value = getenv($key) ?: ($_ENV[$key] ?? $_SERVER[$key] ?? '');
            if (trim((string)$value) !== '') {
                return trim((string)$value);
            }
        }

        if ($this->adminTableExists('global_settings')) {
            try {
                $db = Database::getInstance();
                $stmt = $db->prepare(
                    "SELECT valor
                       FROM global_settings
                      WHERE clave IN ('firebase_project_id', 'FIREBASE_PROJECT_ID')
                        AND COALESCE(valor, '') <> ''
                      LIMIT 1"
                );
                $stmt->execute();
                $value = trim((string)($stmt->fetchColumn() ?: ''));
                if ($value !== '') {
                    return $value;
                }
            } catch (\Throwable $e) {
                error_log('[adminFirebaseProjectId] No se pudo leer project_id: ' . $e->getMessage());
            }
        }

        return trim((string)($serviceAccount['project_id'] ?? ''));
    }

    private function adminFcmServerKey(): string
    {
        foreach (['FCM_SERVER_KEY', 'FIREBASE_SERVER_KEY'] as $key) {
            $value = getenv($key) ?: ($_ENV[$key] ?? $_SERVER[$key] ?? '');
            if (trim((string)$value) !== '') {
                return trim((string)$value);
            }
        }

        if (!$this->adminTableExists('global_settings')) {
            return '';
        }

        try {
            $db = Database::getInstance();
            $stmt = $db->prepare(
                "SELECT valor
                   FROM global_settings
                  WHERE clave IN ('fcm_server_key', 'firebase_server_key', 'FCM_SERVER_KEY', 'FIREBASE_SERVER_KEY')
                    AND COALESCE(valor, '') <> ''
                  LIMIT 1"
            );
            $stmt->execute();
            return trim((string)($stmt->fetchColumn() ?: ''));
        } catch (\Throwable $e) {
            error_log('[adminFcmServerKey] No se pudo leer configuracion FCM: ' . $e->getMessage());
            return '';
        }
    }

    private function adminPostFcm(array $payload): array
    {
        $v1Config = $this->adminFcmV1Config();
        if ($v1Config !== null) {
            return $this->adminPostFcmV1($v1Config, $payload);
        }

        $serverKey = $this->adminFcmServerKey();
        if ($serverKey !== '') {
            return $this->adminPostFcmLegacy($serverKey, $payload);
        }

        throw new RuntimeException('missing_fcm_config');
    }

    private function adminPostFcmV1(array $config, array $payload): array
    {
        $accessToken = trim($this->adminFcmAccessToken($config));
        if ($accessToken === '') {
            throw new RuntimeException('Firebase HTTP v1 no devolvio access token.');
        }
        $token = (string)($payload['to'] ?? '');
        $message = [
            'message' => [
                'token' => $token,
                'notification' => [
                    'title' => (string)($payload['notification']['title'] ?? ''),
                    'body' => (string)($payload['notification']['body'] ?? ''),
                ],
                'data' => array_map('strval', $payload['data'] ?? []),
                'android' => [
                    'priority' => 'HIGH',
                ],
            ],
        ];
        if (!empty($payload['apns']) && is_array($payload['apns'])) {
            $message['message']['apns'] = $payload['apns'];
        }

        $json = json_encode($message, JSON_UNESCAPED_UNICODE);
        if ($json === false) {
            throw new RuntimeException('No se pudo preparar el payload de FCM v1.');
        }

        return $this->adminHttpPostJson(
            'https://fcm.googleapis.com/v1/projects/' . rawurlencode((string)$config['project_id']) . '/messages:send',
            [
                'Authorization: Bearer ' . $accessToken,
                'Accept: application/json',
                'Content-Type: application/json; charset=UTF-8',
            ],
            $json
        );
    }

    private function adminFcmAccessToken(array $config): string
    {
        if (!function_exists('openssl_sign')) {
            throw new RuntimeException('La extension openssl de PHP es requerida para Firebase HTTP v1.');
        }

        $now = time();
        $header = ['alg' => 'RS256', 'typ' => 'JWT'];
        $claims = [
            'iss' => (string)$config['client_email'],
            'scope' => 'https://www.googleapis.com/auth/firebase.messaging',
            'aud' => 'https://oauth2.googleapis.com/token',
            'iat' => $now,
            'exp' => $now + 3600,
        ];
        $unsigned = $this->adminBase64Url(json_encode($header)) . '.' . $this->adminBase64Url(json_encode($claims));
        $signature = '';
        if (!openssl_sign($unsigned, $signature, (string)$config['private_key'], OPENSSL_ALGO_SHA256)) {
            throw new RuntimeException('No se pudo firmar el JWT de Firebase.');
        }

        $assertion = $unsigned . '.' . $this->adminBase64Url($signature);
        $body = http_build_query([
            'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
            'assertion' => $assertion,
        ]);

        $result = $this->adminHttpPostRaw(
            'https://oauth2.googleapis.com/token',
            ['Content-Type: application/x-www-form-urlencoded'],
            $body
        );
        $decoded = json_decode($result['body'] ?? '', true);
        if ($result['http_code'] < 200 || $result['http_code'] >= 300 || empty($decoded['access_token'])) {
            throw new RuntimeException('No se pudo obtener access token de Firebase: ' . ($result['body'] ?? ''));
        }

        return trim((string)$decoded['access_token']);
    }

    private function adminBase64Url($value): string
    {
        return rtrim(strtr(base64_encode((string)$value), '+/', '-_'), '=');
    }

    private function adminPostFcmLegacy(string $serverKey, array $payload): array
    {
        $json = json_encode($payload, JSON_UNESCAPED_UNICODE);
        if ($json === false) {
            throw new RuntimeException('No se pudo preparar el payload de FCM.');
        }

        $headers = [
            'Authorization: key=' . $serverKey,
            'Content-Type: application/json',
        ];

        return $this->adminHttpPostJson('https://fcm.googleapis.com/fcm/send', $headers, $json);
    }

    private function adminHttpPostJson(string $url, array $headers, string $json): array
    {
        return $this->adminHttpPostRaw($url, $headers, $json);
    }

    private function adminHttpPostRaw(string $url, array $headers, string $bodyContent): array
    {
        if (function_exists('curl_init')) {
            $ch = curl_init($url);
            $opts = [
                CURLOPT_POST => true,
                CURLOPT_HTTPHEADER => $headers,
                CURLOPT_POSTFIELDS => $bodyContent,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_CONNECTTIMEOUT => 8,
                CURLOPT_TIMEOUT => 12,
            ];
            if (defined('CURL_HTTP_VERSION_1_1')) {
                $opts[CURLOPT_HTTP_VERSION] = CURL_HTTP_VERSION_1_1;
            }
            curl_setopt_array($ch, $opts);
            $body = curl_exec($ch);
            $error = curl_error($ch);
            $httpCode = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            if ($body === false) {
                throw new RuntimeException($error ?: 'No se pudo conectar con FCM.');
            }

            return ['http_code' => $httpCode, 'body' => (string)$body];
        }

        $context = stream_context_create([
            'http' => [
                'method' => 'POST',
                'header' => implode("\r\n", $headers),
                'content' => $bodyContent,
                'timeout' => 12,
                'ignore_errors' => true,
            ],
        ]);
        $body = @file_get_contents($url, false, $context);
        if ($body === false) {
            throw new RuntimeException('No se pudo conectar con FCM.');
        }

        $httpCode = 0;
        if (!empty($http_response_header[0]) && preg_match('/\s(\d{3})\s/', $http_response_header[0], $m)) {
            $httpCode = (int)$m[1];
        }

        return ['http_code' => $httpCode, 'body' => (string)$body];
    }

    private function adminLogPromotionNotification(
        int $promotionId,
        int $usuarioId,
        ?int $tokenId,
        ?string $token,
        string $status,
        string $title,
        string $body,
        ?string $response,
        ?string $error
    ): void {
        if (!$this->adminEnsureMobileNotificationLogsTable()) {
            try {
                $this->adminInsertPromotionNotificationLog($promotionId, $usuarioId, $tokenId, $token, $status, $title, $body, $response, $error);
                return;
            } catch (\Throwable $e) {
                error_log('[adminLogPromotionNotification] No se pudo registrar notificacion: ' . ($error ?? $status) . ' detalle=' . $e->getMessage());
                return;
            }
        }

        try {
            $this->adminInsertPromotionNotificationLog($promotionId, $usuarioId, $tokenId, $token, $status, $title, $body, $response, $error);
        } catch (\Throwable $e) {
            error_log('[adminLogPromotionNotification] No se pudo guardar log de notificacion: ' . $e->getMessage());
        }
    }

    private function adminInsertPromotionNotificationLog(
        int $promotionId,
        int $usuarioId,
        ?int $tokenId,
        ?string $token,
        string $status,
        string $title,
        string $body,
        ?string $response,
        ?string $error
    ): void {
        $sentAt = $status === 'sent' ? date('Y-m-d H:i:s') : null;
        $createdAt = date('Y-m-d H:i:s');
        $values = [
            'promotion_id' => $promotionId > 0 ? $promotionId : null,
            'usuario_id' => $usuarioId,
            'fcm_token_id' => $tokenId,
            'fcm_token' => $token,
            'provider' => 'fcm',
            'status' => $status,
            'title' => $title,
            'body' => $body,
            'response' => $response,
            'error' => $error,
            'sent_at' => $sentAt,
            'created_at' => $createdAt,
        ];

        try {
            $this->adminInsertExistingColumns('mobile_notification_logs', $values);
            return;
        } catch (\Throwable $flexibleError) {
            $stmt = Database::getInstance()->prepare(
                "INSERT INTO mobile_notification_logs
                    (promotion_id, usuario_id, fcm_token_id, fcm_token, provider, status, title, body, response, error, sent_at, created_at)
                 VALUES (?, ?, ?, ?, 'fcm', ?, ?, ?, ?, ?, ?, ?)"
            );
            $stmt->execute([
                $values['promotion_id'],
                $usuarioId,
                $tokenId,
                $token,
                $status,
                $title,
                $body,
                $response,
                $error,
                $sentAt,
                $createdAt,
            ]);
        }
    }

    private function adminLatestPromotionNotificationLog(int $promotionId): ?array
    {
        if ($promotionId <= 0 || !$this->adminEnsureMobileNotificationLogsTable()) {
            return null;
        }

        try {
            $stmt = Database::getInstance()->prepare(
                "SELECT id, promotion_id, usuario_id, fcm_token_id, provider, status, error, sent_at, created_at
                   FROM mobile_notification_logs
                  WHERE promotion_id = ?
                  ORDER BY id DESC
                  LIMIT 1"
            );
            $stmt->execute([$promotionId]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            return $row ?: null;
        } catch (\Throwable $e) {
            error_log('[adminLatestPromotionNotificationLog] No se pudo leer ultimo log: ' . $e->getMessage());
            return null;
        }
    }

    private function adminLocalUpdatePromotion(int $id, int $empresaId, array $body): ?array
    {
        $db = Database::getInstance();
        if (array_key_exists('code', $body)) {
            $newCode = trim((string)$body['code']);
            if ($newCode !== '' && $this->adminLocalPromotionCodeExists($newCode, $id)) {
                $this->adminApiError('Ese codigo de promocion ya existe. Usa otro codigo.', 409, [
                    'code' => ['El codigo "' . $newCode . '" ya esta registrado.'],
                ]);
            }
        }
        if ($this->adminTableExists('mobile_promociones') && $this->adminLocalGetPromotion($id, $empresaId)) {
            $values = [];
            $this->adminEnsureMobilePromocionesRuleColumns();
            foreach ([
                'usuario_id', 'titulo', 'descripcion', 'imagen', 'code', 'tipo_descuento',
                'valor_descuento', 'scope_tipo', 'scope_ids', 'buy_qty', 'pay_qty',
                'min_subtotal', 'max_uses', 'combinable', 'activo', 'expires_at'
            ] as $field) {
                if (array_key_exists($field, $body)) {
                    $values[$field] = $body[$field];
                }
            }
            if (isset($values['code']) && $this->adminColumnExists('mobile_promociones', 'deep_link')) {
                $values['deep_link'] = $this->adminPromotionDeepLink((string)$values['code']);
            }
            try {
                $this->adminUpdateExistingColumns('mobile_promociones', $id, $values);
            } catch (\PDOException $e) {
                if ($this->adminIsDuplicateKeyError($e)) {
                    $this->adminApiError('Ese codigo de promocion ya existe. Usa otro codigo.', 409, [
                        'code' => ['El codigo "' . trim((string)($values['code'] ?? '')) . '" ya esta registrado.'],
                    ]);
                }
                throw $e;
            }
            return $this->adminLocalGetPromotion($id, $empresaId);
        }

        $restauranteId = $this->adminRestauranteIdByEmpresa($empresaId);
        if (!$restauranteId || !$this->adminRestPromocionesDisponible()) {
            return null;
        }
        $existing = $this->adminLocalGetPromotion($id, $empresaId);
        if (!$existing) {
            return null;
        }
        $tipo = null;
        if (array_key_exists('tipo_descuento', $body) || array_key_exists('tipo', $body)) {
            $tipo = $this->normalizePromotionDiscountType((string)($body['tipo_descuento'] ?? $body['tipo'] ?? 'porcentaje'));
            if (!in_array($tipo, ['porcentaje', 'monto_fijo'], true)) {
                $tipo = 'porcentaje';
            }
        }
        $stmt = $db->prepare(
            "UPDATE rest_promociones
                SET titulo = COALESCE(?, titulo),
                    descripcion = COALESCE(?, descripcion),
                    code = COALESCE(?, code),
                    tipo = COALESCE(?, tipo),
                    valor_descuento = COALESCE(?, valor_descuento),
                    expires_at = COALESCE(?, expires_at),
                    fecha_fin = COALESCE(?, fecha_fin),
                    imagen = COALESCE(?, imagen),
                    deep_link = COALESCE(?, deep_link),
                    activo = COALESCE(?, activo)
              WHERE id = ? AND restaurante_id = ?"
        );
        $code = array_key_exists('code', $body) ? (string)$body['code'] : null;
        try {
            $stmt->execute([
                $body['titulo'] ?? null,
                $body['descripcion'] ?? null,
                $code,
                $tipo,
                array_key_exists('valor_descuento', $body) ? (float)$body['valor_descuento'] : null,
                $body['expires_at'] ?? null,
                !empty($body['expires_at']) ? substr((string)$body['expires_at'], 0, 10) : null,
                array_key_exists('imagen', $body) ? (string)$body['imagen'] : null,
                $code !== null ? $this->adminPromotionDeepLink($code) : null,
                array_key_exists('activo', $body) ? (int)$body['activo'] : null,
                $id,
                $restauranteId,
            ]);
        } catch (\PDOException $e) {
            if ($this->adminIsDuplicateKeyError($e)) {
                $this->adminApiError('Ese codigo de promocion ya existe. Usa otro codigo.', 409, [
                    'code' => ['El codigo "' . trim((string)$code) . '" ya esta registrado.'],
                ]);
            }
            throw $e;
        }
        return $this->adminLocalGetPromotion($id, $empresaId);
    }

    private function adminLocalDeletePromotion(int $id, int $empresaId): bool
    {
        $db = Database::getInstance();
        if ($this->adminTableExists('mobile_promociones') && $this->adminLocalGetPromotion($id, $empresaId)) {
            $stmt = $db->prepare("DELETE FROM mobile_promociones WHERE id = ?");
            $stmt->execute([$id]);
            return $stmt->rowCount() > 0;
        }
        $restauranteId = $this->adminRestauranteIdByEmpresa($empresaId);
        if (!$restauranteId || !$this->adminRestPromocionesDisponible()) {
            return false;
        }
        $stmt = $db->prepare("DELETE FROM rest_promociones WHERE id = ? AND restaurante_id = ?");
        $stmt->execute([$id, $restauranteId]);
        return $stmt->rowCount() > 0;
    }

    private function adminLocalDeactivatePromotion(int $id, int $empresaId): bool
    {
        $db = Database::getInstance();
        if ($this->adminTableExists('mobile_promociones') && $this->adminLocalGetPromotion($id, $empresaId)) {
            $stmt = $db->prepare("UPDATE mobile_promociones SET activo = 0 WHERE id = ?");
            $stmt->execute([$id]);
            return $stmt->rowCount() > 0;
        }
        $restauranteId = $this->adminRestauranteIdByEmpresa($empresaId);
        if (!$restauranteId || !$this->adminRestPromocionesDisponible()) {
            return false;
        }
        $stmt = $db->prepare("UPDATE rest_promociones SET activo = 0 WHERE id = ? AND restaurante_id = ?");
        $stmt->execute([$id, $restauranteId]);
        return $stmt->rowCount() > 0;
    }

    private function adminRestauranteIdByEmpresa(int $empresaId): ?int
    {
        $db = Database::getInstance();
        $stmt = $db->prepare("SELECT id FROM rest_restaurantes WHERE empresa_id = ? AND activo = 1 ORDER BY id ASC LIMIT 1");
        $stmt->execute([$empresaId]);
        $id = $stmt->fetchColumn();
        if ($id) {
            return (int)$id;
        }

        $sessionRestauranteId = (int)($_SESSION['restaurante_activo_id'] ?? 0);
        return $sessionRestauranteId > 0 ? $sessionRestauranteId : null;
    }

    private function adminRestPromocionesDisponible(): bool
    {
        try {
            $db = Database::getInstance();
            $db->query("SELECT 1 FROM rest_promociones LIMIT 0");
            return true;
        } catch (\Throwable $e) {
            error_log('[adminRestPromocionesDisponible] No se pudo leer rest_promociones: ' . $e->getMessage());
            return false;
        }
    }

    private function adminTableExists(string $table): bool
    {
        if (!preg_match('/^[a-zA-Z0-9_]+$/', $table)) {
            return false;
        }
        $quoted = "`{$table}`";
        try {
            $db = Database::getInstance();
            $db->query("SELECT 1 FROM {$quoted} LIMIT 0");
            return true;
        } catch (\Throwable $e) {
            try {
                $stmt = Database::getInstance()->prepare(
                    "SELECT COUNT(*)
                       FROM information_schema.tables
                      WHERE table_schema = DATABASE()
                        AND table_name = ?"
                );
                $stmt->execute([$table]);
                return (int)$stmt->fetchColumn() > 0;
            } catch (\Throwable $inner) {
                return false;
            }
        }
    }

    private function adminColumnExists(string $table, string $column): bool
    {
        if (!preg_match('/^[a-zA-Z0-9_]+$/', $table) || !preg_match('/^[a-zA-Z0-9_]+$/', $column)) {
            return false;
        }
        try {
            $db = Database::getInstance();
            $stmt = $db->prepare(
                "SELECT COUNT(*)
                   FROM information_schema.columns
                  WHERE table_schema = DATABASE()
                    AND table_name = ?
                    AND column_name = ?"
            );
            $stmt->execute([$table, $column]);
            if ((int)$stmt->fetchColumn() > 0) {
                return true;
            }

            $db->query("SELECT `{$column}` FROM `{$table}` LIMIT 0");
            return true;
        } catch (\Throwable $e) {
            return false;
        }
    }

    private function adminFirstExistingColumn(string $table, array $columns): ?string
    {
        foreach ($columns as $column) {
            if ($this->adminColumnExists($table, $column)) {
                return $column;
            }
        }
        return null;
    }

    private function adminColumnExpr(string $table, string $alias, string $column, string $as, string $fallback): string
    {
        return $this->adminColumnExists($table, $column)
            ? "{$alias}.`{$column}` AS `{$as}`"
            : "{$fallback} AS `{$as}`";
    }

    private function adminInsertExistingColumns(string $table, array $values): void
    {
        $db = Database::getInstance();
        $columns = [];
        $params = [];
        foreach ($values as $column => $value) {
            if ($this->adminColumnExists($table, $column)) {
                $columns[] = $column;
                $params[] = $value;
            }
        }
        if (!$columns) {
            throw new RuntimeException('No hay columnas compatibles para guardar la promocion.');
        }
        $sqlColumns = implode(', ', array_map(fn($column) => "`{$column}`", $columns));
        $placeholders = implode(', ', array_fill(0, count($columns), '?'));
        $stmt = $db->prepare("INSERT INTO `{$table}` ({$sqlColumns}) VALUES ({$placeholders})");
        $stmt->execute($params);
    }

    private function adminUpdateExistingColumns(string $table, int $id, array $values): void
    {
        $db = Database::getInstance();
        $sets = [];
        $params = [];
        foreach ($values as $column => $value) {
            if ($column !== 'id' && $this->adminColumnExists($table, $column)) {
                $sets[] = "`{$column}` = ?";
                $params[] = $value;
            }
        }
        if (!$sets) {
            return;
        }
        $params[] = $id;
        $stmt = $db->prepare("UPDATE `{$table}` SET " . implode(', ', $sets) . " WHERE id = ?");
        $stmt->execute($params);
    }

    // ══════════════════════════════════════════════════════════════════
    // Helpers para API Amare (App Móvil) — Proxy HTTP
    // ══════════════════════════════════════════════════════════════════

    /**
     * Obtiene la configuración de conexión con la API Amare.
     * @return array{url: string, token: string}|null null si no está configurada
     */
    private function getAmareConfig(): ?array
    {
        $db = Database::getInstance();
        $stmt = $db->prepare(
            "SELECT clave, valor FROM global_settings WHERE clave IN ('amare_api_url','amare_api_token') AND grupo = 'pagos'"
        );
        $stmt->execute();
        $settings = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $settings[$row['clave']] = $row['valor'] ?? '';
        }

        $url   = rtrim($settings['amare_api_url'] ?? '', '/');
        $token = $settings['amare_api_token'] ?? '';

        if (empty($url) || empty($token)) {
            return null;
        }

        return ['url' => $url, 'token' => $token];
    }

    /**
     * Obtiene el branch_id (sucursal) de Amare correspondiente al restaurante.
     */
    private function getAmareBranchId(int $empresaId): ?int
    {
        $db = Database::getInstance();

        $column = null;
        try {
            foreach (['sucursal_id', 'sucursal_carnihub_id'] as $candidate) {
                $stmt = $db->prepare("SHOW COLUMNS FROM `rest_restaurantes` LIKE ?");
                $stmt->execute([$candidate]);
                if ($stmt->fetch()) {
                    $column = $candidate;
                    break;
                }
            }
        } catch (\Throwable $e) {
            $column = null;
        }

        $branchSelect = $column ? "{$column} AS branch_ref" : "NULL AS branch_ref";
        $stmt = $db->prepare(
            "SELECT id, {$branchSelect}
             FROM rest_restaurantes
             WHERE empresa_id = ? AND activo = 1
             ORDER BY id ASC
             LIMIT 1"
        );
        $stmt->execute([$empresaId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$row) {
            return null;
        }

        $branchId = (int)($row['branch_ref'] ?? 0);
        if ($branchId > 0) {
            return $branchId;
        }

        try {
            $stmt = $db->prepare("SHOW TABLES LIKE 'sucursales'");
            $stmt->execute();
            if ($stmt->fetch()) {
                $stmt = $db->prepare("SELECT id FROM sucursales WHERE empresa_id = ? AND activo = 1 ORDER BY id ASC LIMIT 1");
                $stmt->execute([$empresaId]);
                $fallback = (int)($stmt->fetchColumn() ?: 0);
                if ($fallback > 0) {
                    return $fallback;
                }
            }
        } catch (\Throwable $e) {
            // Fallback below keeps the admin API aligned with config sync.
        }

        return (int)$row['id'] ?: null;
    }

    /**
     * Realiza una llamada HTTP a la API Amare.
     * @return array{success: bool, httpCode: int, data: array|null, error: string|null}
     */
    private function callAmareApi(string $method, string $endpoint, ?array $body = null): array
    {
        $config = $this->getAmareConfig();
        if (!$config) {
            return ['success' => false, 'httpCode' => 0, 'data' => null, 'error' => 'API Amare no configurada. Configura amare_api_url y amare_api_token en Configuración.'];
        }

        $url = $config['url'] . '/' . ltrim($endpoint, '/');

        $ch = curl_init($url);
        $headers = [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $config['token'],
            'Accept: application/json',
        ];

        $opts = [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 15,
            CURLOPT_CONNECTTIMEOUT => 5,
            CURLOPT_HTTPHEADER     => $headers,
        ];

        if ($method === 'POST' || $method === 'PUT') {
            $opts[CURLOPT_CUSTOMREQUEST] = $method;
            if ($body !== null) {
                $opts[CURLOPT_POSTFIELDS] = json_encode($body);
            }
        } elseif ($method === 'DELETE') {
            $opts[CURLOPT_CUSTOMREQUEST] = 'DELETE';
        }

        curl_setopt_array($ch, $opts);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error    = curl_error($ch);
        curl_close($ch);

        if ($error) {
            error_log('[callAmareApi] cURL error: ' . $error . ' | URL: ' . $url);
            return ['success' => false, 'httpCode' => 0, 'data' => null, 'error' => 'Error de conexión con la API Amare: ' . $error];
        }

        $decoded = json_decode($response, true);
        if (!is_array($decoded)) {
            return ['success' => false, 'httpCode' => $httpCode, 'data' => null, 'error' => 'Respuesta inválida de la API Amare (HTTP ' . $httpCode . ')'];
        }

        if ($httpCode >= 200 && $httpCode < 300) {
            return ['success' => true, 'httpCode' => $httpCode, 'data' => $decoded, 'error' => null];
        }

        return ['success' => false, 'httpCode' => $httpCode, 'data' => $decoded, 'error' => $decoded['error'] ?? $decoded['message'] ?? 'Error HTTP ' . $httpCode];
    }
}
