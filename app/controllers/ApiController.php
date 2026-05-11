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

    // ── Chatbot IA ────────────────────────────────────────────────
    /** POST /api/chat */
    public function chat(?string $p = null): void
    {
        $this->requireAdminEmpresa();

        try {
            $body    = json_decode(file_get_contents('php://input'), true) ?? [];
            $mensaje = trim($body['mensaje'] ?? '');
            $hist    = $body['historial'] ?? [];

            if (!$mensaje) {
                $this->json(['error' => 'Mensaje vacío'], 400);
                return;
            }

            $empresaId = (int)$this->empresaId();
            $db        = Database::getInstance();

            $totalMes   = (int)$db->query("SELECT COUNT(*) FROM pedidos WHERE empresa_id=$empresaId AND MONTH(created_at)=MONTH(NOW()) AND YEAR(created_at)=YEAR(NOW())")->fetchColumn();
            $pendientes = (int)$db->query("SELECT COUNT(*) FROM pedidos WHERE empresa_id=$empresaId AND estado='pendiente'")->fetchColumn();
            $gastoMes   = (float)$db->query("SELECT COALESCE(SUM(total),0) FROM pedidos WHERE empresa_id=$empresaId AND MONTH(created_at)=MONTH(NOW()) AND YEAR(created_at)=YEAR(NOW()) AND estado NOT IN ('cancelado')")->fetchColumn();
            $equipo     = (int)$db->query("SELECT COUNT(*) FROM usuarios WHERE empresa_id=$empresaId AND activo=1")->fetchColumn();

            try {
                $stockBajo = (int)$db->query("SELECT COUNT(*) FROM inventario i JOIN productos p ON p.id=i.producto_id WHERE p.empresa_id=$empresaId AND i.cantidad<=i.minimo_stock")->fetchColumn();
            } catch (\Throwable $e) {
                $stockBajo = 0;
            }

            $empresa = htmlspecialchars($_SESSION['empresa']['razon_social'] ?? 'la empresa');

            $system = "Eres el asistente de negocio de \"$empresa\" en CarniHub, plataforma B2B de abasto de carne. "
                    . "Ayudas al administrador a entender y gestionar su negocio. Responde siempre en español, de forma clara y concisa.\n\n"
                    . "Datos actuales del negocio:\n"
                    . "- Pedidos este mes: $totalMes\n"
                    . "- Pedidos pendientes de aprobación: $pendientes\n"
                    . "- Gasto acumulado del mes: $" . number_format($gastoMes, 2) . " MXN\n"
                    . "- Productos con stock bajo: $stockBajo\n"
                    . "- Usuarios activos en el equipo: $equipo\n\n"
                    . "Solo responde preguntas relacionadas con la gestión del negocio. Si te preguntan algo fuera del tema, redirige amablemente.";

            $mensajes = array_merge(
                array_map(fn($m) => ['role' => $m['role'], 'content' => $m['content']], $hist),
                [['role' => 'user', 'content' => $mensaje]]
            );

            $gemini    = new GeminiService();
            $respuesta = $gemini->chat($system, $mensajes);

            $this->json(['respuesta' => $respuesta]);

        } catch (\Throwable $e) {
            $this->json(['error' => 'Error interno: ' . $e->getMessage()], 500);
        }
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
