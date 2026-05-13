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

        $model  = new ProductoModel();
        $precio = $model->getPrecioParaCantidad($productoId, $cantidad);
        $escalonados = $model->getEscalonados($productoId);

        $this->json(['precio' => $precio, 'escalonados' => $escalonados]);
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
}
