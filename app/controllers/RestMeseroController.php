<?php
require_once ROOT_PATH . '/app/controllers/BaseController.php';

class RestMeseroController extends BaseController
{
    private RestPedidoModel $model;
    private static array $columnCache = [];

    public function __construct()
    {
        parent::__construct();
        $this->requireMesero();
        $this->model = new RestPedidoModel();
    }

    private function hasColumn(string $table, string $column): bool
    {
        $key = $table . '.' . $column;
        if (array_key_exists($key, self::$columnCache)) {
            return self::$columnCache[$key];
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
            self::$columnCache[$key] = (int)$stmt->fetchColumn() > 0;
        } catch (\Throwable $e) {
            self::$columnCache[$key] = false;
        }

        return self::$columnCache[$key];
    }

    private function hasTable(string $table): bool
    {
        $key = 'table.' . $table;
        if (array_key_exists($key, self::$columnCache)) {
            return self::$columnCache[$key];
        }

        try {
            $db = Database::getInstance();
            $stmt = $db->prepare(
                "SELECT COUNT(*)
                   FROM information_schema.tables
                  WHERE table_schema = DATABASE()
                    AND table_name = ?"
            );
            $stmt->execute([$table]);
            self::$columnCache[$key] = (int)$stmt->fetchColumn() > 0;
        } catch (\Throwable $e) {
            self::$columnCache[$key] = false;
        }

        return self::$columnCache[$key];
    }

    private function optionalPedidoSelect(string $alias, array $columns): string
    {
        $parts = [];
        foreach ($columns as $column) {
            if ($this->hasColumn('rest_pedidos', $column)) {
                $parts[] = "{$alias}.`{$column}` AS `{$column}`";
            } else {
                $parts[] = "NULL AS `{$column}`";
            }
        }
        return implode(",\n                    ", $parts);
    }

    private function giftExistsSql(string $pedidoAlias = 'p'): string
    {
        if (!$this->hasColumn('rest_pedido_items', 'origen') || !$this->hasTable('social_gift_products')) {
            return '0';
        }

        return "EXISTS (
            SELECT 1
              FROM rest_pedido_items gi
              JOIN social_gift_products sgp ON sgp.id = gi.platillo_id
             WHERE gi.pedido_id = {$pedidoAlias}.id
               AND LOWER(COALESCE(gi.origen, '')) = 'store'
               AND COALESCE(sgp.es_regalo, 0) = 1
        )";
    }

    private function productoDirectoMeseroSql(string $pedidoAlias = 'p'): string
    {
        $parts = [];

        if ($this->hasColumn('rest_pedidos', 'tipo_origen')) {
            $parts[] = "LOWER(COALESCE({$pedidoAlias}.tipo_origen, '')) = 'store'";
        }

        if ($this->hasColumn('rest_pedidos', 'es_regalo')) {
            $parts[] = "COALESCE({$pedidoAlias}.es_regalo, 0) = 1";
        }

        if ($this->hasColumn('rest_pedidos', 'tipo_entrega')) {
            $parts[] = "LOWER(COALESCE({$pedidoAlias}.tipo_entrega, '')) IN ('gift','regalo','regalos')";
        }

        if ($this->hasColumn('rest_pedido_items', 'origen')) {
            $parts[] = "EXISTS (
                SELECT 1
                  FROM rest_pedido_items dpi
                 WHERE dpi.pedido_id = {$pedidoAlias}.id
                   AND LOWER(COALESCE(dpi.origen, '')) = 'store'
                   AND dpi.estado != 'cancelado'
            )";
        }

        return $parts ? '(' . implode(' OR ', $parts) . ')' : '0';
    }

    private function pedidoItemsSql(): string
    {
        $hasOrigen = $this->hasColumn('rest_pedido_items', 'origen');
        $hasGiftProducts = $this->hasTable('social_gift_products');

        $joinPlatillos = $hasOrigen
            ? "LEFT JOIN rest_platillos pl ON pl.id = pi.platillo_id AND LOWER(COALESCE(pi.origen, 'menu')) = 'menu'"
            : "LEFT JOIN rest_platillos pl ON pl.id = pi.platillo_id";

        $joinGifts = $hasGiftProducts
            ? "LEFT JOIN social_gift_products sgp ON sgp.id = pi.platillo_id" . ($hasOrigen ? " AND LOWER(COALESCE(pi.origen, '')) = 'store'" : "")
            : "";

        $nameExpr = $hasGiftProducts
            ? "COALESCE(pl.nombre, sgp.nombre, CONCAT('Producto #', pi.platillo_id))"
            : "COALESCE(pl.nombre, CONCAT('Producto #', pi.platillo_id))";

        return "SELECT pi.id, {$nameExpr} AS nombre, pi.cantidad, pi.estado
                  FROM rest_pedido_items pi
                  {$joinPlatillos}
                  {$joinGifts}
                 WHERE pi.pedido_id = ? AND pi.estado != 'cancelado'";
    }

    private function socialGiftSelect(string $alias, string $column, string $fallback): string
    {
        if ($this->hasColumn('social_gift_orders', $column)) {
            return "{$alias}.`{$column}`";
        }

        return $fallback;
    }

    private function socialGiftOrdersListos(int $restauranteId, array $misZonas): array
    {
        if (!$this->hasTable('social_gift_orders')) {
            return [];
        }

        $folioExpr = $this->socialGiftSelect('go', 'folio', "CONCAT('SG-', LPAD(go.id, 6, '0'))");
        $statusExpr = $this->socialGiftSelect('go', 'status', "'listo'");
        $createdAtExpr = $this->socialGiftSelect('go', 'created_at', 'NOW()');
        $mesaIdExpr = $this->socialGiftSelect('go', 'mesa_id', 'NULL');
        $senderNombreExpr = $this->socialGiftSelect('go', 'sender_nombre', 'NULL');
        $recipientNombreExpr = $this->socialGiftSelect('go', 'recipient_nombre', 'NULL');
        $senderMesaExpr = $this->socialGiftSelect('go', 'sender_mesa', 'NULL');
        $recipientMesaExpr = $this->socialGiftSelect('go', 'recipient_mesa', 'NULL');
        $giftNombreExpr = $this->socialGiftSelect('go', 'gift_nombre', "'Regalo'");

        $db = Database::getInstance();
        $stmt = $db->prepare(
            "SELECT CONCAT('gift-', go.id) AS id,
                    go.id AS gift_order_id,
                    'social_gift_orders' AS origen_fuente,
                    {$folioExpr} AS folio,
                    COALESCE({$statusExpr}, 'listo') AS estado,
                    {$createdAtExpr} AS created_at,
                    NULL AS mesero_id,
                    NULL AS reclamado_por,
                    NULL AS reclamado_at,
                    {$mesaIdExpr} AS mesa_id,
                    'store' AS tipo_origen,
                    'gift' AS tipo_entrega,
                    'gift' AS tipo_pedido,
                    NULL AS direccion_entrega,
                    {$senderNombreExpr} AS comprador_nombre,
                    {$senderMesaExpr} AS comprador_direccion,
                    NULL AS comprador_telefono,
                    {$recipientNombreExpr} AS destinatario_nombre,
                    {$recipientMesaExpr} AS destinatario_direccion,
                    NULL AS destinatario_telefono,
                    1 AS es_regalo,
                    1 AS es_producto_directo,
                    m.nombre AS mesa_nombre,
                    m.zona_id,
                    NULL AS reclamado_por_nombre,
                    {$giftNombreExpr} AS gift_nombre
             FROM social_gift_orders go
             LEFT JOIN rest_mesas m ON m.id = {$mesaIdExpr}
             WHERE go.restaurante_id = ?
               AND LOWER(COALESCE({$statusExpr}, 'listo')) NOT IN ('entregado','cancelado','cancelada')
             ORDER BY {$createdAtExpr} ASC, go.id ASC
             LIMIT 50"
        );
        $stmt->execute([$restauranteId]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($rows as &$row) {
            $row['es_mi_zona'] = in_array((int)($row['zona_id'] ?? 0), $misZonas);
            $row['es_mi_reclamo'] = false;
            $row['reclamado_otro'] = false;
            $row['items'] = [[
                'id' => $row['gift_order_id'],
                'nombre' => $row['gift_nombre'] ?: 'Regalo',
                'cantidad' => 1,
                'estado' => $row['estado'] ?: 'listo',
            ]];
        }
        unset($row);

        return $rows;
    }

    public function dashboard(?string $p = null): void
    {
        $restauranteId = $this->restauranteId();
        if (!$restauranteId) {
            $this->redirect('acceso/login');
            return;
        }

        $restaurante = (new RestauranteModel())->find($restauranteId);
        $meseroId    = $this->usuarioId();
        $db          = Database::getInstance();

        // Zonas asignadas al mesero en el turno de hoy
        $stmtZ = $db->prepare(
            "SELECT zona_id FROM rest_mesero_turno
             WHERE restaurante_id = ? AND usuario_id = ? AND turno_fecha = CURDATE() AND activo = 1"
        );
        $stmtZ->execute([$restauranteId, $meseroId]);
        $misZonas = array_column($stmtZ->fetchAll(PDO::FETCH_ASSOC), 'zona_id');

        // Mesas con indicador de si pertenece a mi zona
        $stmt = $db->prepare(
            "SELECT m.id, m.nombre, m.capacidad, m.estado, m.zona_id
             FROM rest_mesas m
             WHERE m.restaurante_id = ? AND m.activo = 1
             ORDER BY m.nombre ASC"
        );
        $stmt->execute([$restauranteId]);
        $mesas = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($mesas as &$m) {
            $m['es_mi_zona'] = in_array((int)($m['zona_id'] ?? 0), $misZonas);
        }
        unset($m);

        $flash     = $this->getFlash();
        $pageTitle = 'Mesero';
        $this->render('mesero/dashboard', compact(
            'restaurante', 'mesas', 'misZonas', 'flash', 'pageTitle'
        ));
    }

    // POST /rest-mesero/reclamar/{pedidoId}
    // Toma ownership del pedido: estado listo → reclamado, registra quién lo reclamó
    public function reclamar(?string $pedidoId = null): void
    {
        $db       = Database::getInstance();
        $meseroId = $this->usuarioId();
        $pid      = (int)$pedidoId;

        // Solo se puede reclamar si está en 'listo' (no reclamado por otro)
        $stmt = $db->prepare(
            "UPDATE rest_pedidos
             SET estado = 'reclamado', mesero_id = ?, reclamado_por = ?, reclamado_at = NOW()
             WHERE id = ? AND restaurante_id = ? AND estado = 'listo'"
        );
        $stmt->execute([$meseroId, $meseroId, $pid, $this->restauranteId()]);

        if ($stmt->rowCount() === 0) {
            // Verificar si ya lo reclamó este mismo mesero
            $check = $db->prepare(
                "SELECT estado, reclamado_por FROM rest_pedidos WHERE id = ? AND restaurante_id = ?"
            );
            $check->execute([$pid, $this->restauranteId()]);
            $row = $check->fetch(PDO::FETCH_ASSOC);

            if ($row && $row['estado'] === 'reclamado' && (int)$row['reclamado_por'] === $meseroId) {
                $this->json(['ok' => true, 'ya_reclamado' => true]);
            } else {
                $this->json(['ok' => false, 'msg' => 'Pedido no disponible para reclamar']);
            }
            return;
        }

        $this->json(['ok' => true]);
    }

    public function marcarEntregado(?string $pedidoId = null): void
    {
        $db       = Database::getInstance();
        $meseroId = $this->usuarioId();
        if (is_string($pedidoId) && substr($pedidoId, 0, 5) === 'gift-') {
            $giftOrderId = (int)substr($pedidoId, 5);
            if (!$this->hasTable('social_gift_orders') || !$this->hasColumn('social_gift_orders', 'status')) {
                $this->json(['ok' => false, 'msg' => 'No se pudo actualizar el regalo']);
                return;
            }

            $stmt = $db->prepare(
                "UPDATE social_gift_orders
                 SET status = 'entregado'
                 WHERE id = ? AND restaurante_id = ?
                   AND LOWER(COALESCE(status, 'listo')) NOT IN ('entregado','cancelado','cancelada')"
            );
            $stmt->execute([$giftOrderId, $this->restauranteId()]);

            $this->json(['ok' => $stmt->rowCount() > 0]);
            return;
        }

        $pid = (int)$pedidoId;

        // Solo puede entregar el mesero que reclamó, o cualquiera si no fue reclamado
        $giftExistsSql = $this->giftExistsSql('p');
        $productoDirectoSql = $this->productoDirectoMeseroSql('p');
        $check = $db->prepare(
            "SELECT p.estado, p.reclamado_por,
                    ({$giftExistsSql}) AS es_regalo,
                    ({$productoDirectoSql}) AS es_producto_directo
             FROM rest_pedidos p
             WHERE p.id = ? AND p.restaurante_id = ?"
        );
        $check->execute([$pid, $this->restauranteId()]);
        $row = $check->fetch(PDO::FETCH_ASSOC);
        $esProductoDirecto = $row && (int)($row['es_producto_directo'] ?? 0) === 1;
        $sinCocina = $esProductoDirecto;
        $estadosValidos = $sinCocina
            ? ['pendiente', 'en_preparacion', 'listo', 'en_camino', 'reclamado']
            : ['listo', 'reclamado'];

        if (!$row || !in_array($row['estado'], $estadosValidos, true)) {
            $this->json(['ok' => false, 'msg' => 'Estado inválido']);
            return;
        }

        if ($row['estado'] === 'reclamado' && $row['reclamado_por'] !== null
            && (int)$row['reclamado_por'] !== $meseroId) {
            $this->json(['ok' => false, 'msg' => 'Este pedido fue reclamado por otro mesero']);
            return;
        }

        // Verificar que no haya ítems aún por preparar/pendientes (chef todavía trabajando)
        if (!$sinCocina) {
            $pend = $db->prepare(
                "SELECT COUNT(*) FROM rest_pedido_items
                 WHERE pedido_id = ? AND estado IN ('pendiente','en_preparacion')"
            );
            $pend->execute([$pid]);
            if ((int)$pend->fetchColumn() > 0) {
                $this->json(['ok' => false, 'msg' => 'Aún hay platillos sin marcar listos por el chef']);
                return;
            }
        }

        $db->prepare(
            "UPDATE rest_pedidos SET estado='entregado', mesero_id = ? WHERE id = ? AND restaurante_id = ?"
        )->execute([$meseroId, $pid, $this->restauranteId()]);

        $itemEstados = $sinCocina
            ? "estado NOT IN ('entregado','cancelado')"
            : "estado IN ('listo','reclamado')";

        $db->prepare(
            "UPDATE rest_pedido_items SET estado='entregado'
             WHERE pedido_id = ? AND {$itemEstados}"
        )->execute([$pid]);

        // Propagar mesero_id al ticket si aún no tiene
        $db->prepare(
            "UPDATE rest_tickets t
             JOIN rest_visitas v ON v.id = t.visita_id
             JOIN rest_pedidos p ON p.visita_id = v.id AND p.id = ?
             SET t.mesero_id = ?
             WHERE t.mesero_id IS NULL"
        )->execute([$pid, $meseroId]);

        $this->json(['ok' => true]);
    }

    // POST /rest-mesero/atenderAlerta/{alertaId}
    public function atenderAlerta(?string $alertaId = null): void
    {
        $db   = Database::getInstance();
        $stmt = $db->prepare("UPDATE rest_alertas SET atendida=1 WHERE id=? AND restaurante_id=?");
        $stmt->execute([(int)$alertaId, $this->restauranteId()]);
        $this->json(['ok' => true]);
    }

    // GET /rest-mesero/alertas  — polling JSON para el dashboard
    public function alertas(?string $p = null): void
    {
        $db   = Database::getInstance();
        $stmt = $db->prepare(
            "SELECT a.id, a.tipo, a.created_at,
                    a.mesa_id,
                    m.nombre AS mesa_nombre
             FROM rest_alertas a
             LEFT JOIN rest_mesas m ON m.id = a.mesa_id
             WHERE a.restaurante_id = ? AND a.atendida = 0
             ORDER BY a.created_at DESC
             LIMIT 20"
        );
        $stmt->execute([$this->restauranteId()]);
        $this->json(['ok' => true, 'alertas' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
    }

    // GET /rest-mesero/pedidosMesa/{mesaId}  — pedidos activos de una mesa (para modal)
    public function pedidosMesa(?string $mesaId = null): void
    {
        $db       = Database::getInstance();
        $meseroId = $this->usuarioId();
        $stmt = $db->prepare(
            "SELECT p.id, p.folio, p.estado, p.created_at, p.reclamado_por,
                    u.nombre AS reclamado_por_nombre
             FROM rest_pedidos p
             LEFT JOIN usuarios u ON u.id = p.reclamado_por
             WHERE p.mesa_id = ? AND p.restaurante_id = ?
               AND p.estado NOT IN ('entregado','cancelado')
             ORDER BY p.created_at DESC"
        );
        $stmt->execute([(int)$mesaId, $this->restauranteId()]);
        $pedidos = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($pedidos as &$ped) {
            $ped['es_mi_reclamo'] = $ped['estado'] === 'reclamado'
                && (int)$ped['reclamado_por'] === $meseroId;

            $stmt2 = $db->prepare(
                "SELECT pi.id, pl.nombre AS nombre, pi.cantidad, pi.estado
                 FROM rest_pedido_items pi
                 JOIN rest_platillos pl ON pl.id = pi.platillo_id
                 WHERE pi.pedido_id = ? AND pi.estado != 'cancelado'"
            );
            $stmt2->execute([(int)$ped['id']]);
            $ped['items'] = $stmt2->fetchAll(PDO::FETCH_ASSOC);
        }
        unset($ped);

        $this->json(['ok' => true, 'pedidos' => $pedidos]);
    }

    // GET /rest-mesero/listos  — pedidos en estado 'listo' o 'reclamado' para entregar
    public function listos(?string $p = null): void
    {
        $db          = Database::getInstance();
        $meseroId    = $this->usuarioId();
        $restauranteId = $this->restauranteId();

        // Zonas del mesero hoy
        $stmtZ = $db->prepare(
            "SELECT zona_id FROM rest_mesero_turno
             WHERE restaurante_id = ? AND usuario_id = ? AND turno_fecha = CURDATE() AND activo = 1"
        );
        $stmtZ->execute([$restauranteId, $meseroId]);
        $misZonas = array_column($stmtZ->fetchAll(PDO::FETCH_ASSOC), 'zona_id');
        $giftExistsSql = $this->giftExistsSql('p');
        $productoDirectoSql = $this->productoDirectoMeseroSql('p');
        $pedidoMetaSelect = $this->optionalPedidoSelect('p', [
            'tipo_origen',
            'tipo_entrega',
            'tipo_pedido',
            'direccion_entrega',
            'comprador_nombre',
            'comprador_direccion',
            'comprador_telefono',
            'destinatario_nombre',
            'destinatario_direccion',
            'destinatario_telefono',
        ]);

        $stmt = $db->prepare(
            "SELECT p.id, p.folio, p.estado, p.created_at, p.mesero_id,
                    p.reclamado_por, p.reclamado_at, p.mesa_id,
                    {$pedidoMetaSelect},
                    ({$giftExistsSql}) AS es_regalo,
                    ({$productoDirectoSql}) AS es_producto_directo,
                    m.nombre AS mesa_nombre, m.zona_id,
                    u.nombre AS reclamado_por_nombre
             FROM rest_pedidos p
             LEFT JOIN rest_mesas m   ON m.id = p.mesa_id
             LEFT JOIN usuarios u     ON u.id = p.reclamado_por
             WHERE p.restaurante_id = ?
               AND (
                    p.estado IN ('listo','reclamado')
                    OR (({$productoDirectoSql}) AND p.estado IN ('pendiente','en_preparacion','en_camino'))
               )
             ORDER BY
               CASE WHEN m.zona_id IN (" . (count($misZonas) ? implode(',', array_fill(0, count($misZonas), '?')) : '0') . ") THEN 0 ELSE 1 END ASC,
               p.created_at ASC
             LIMIT 50"
        );
        $params = array_merge([$restauranteId], $misZonas);
        $stmt->execute($params);
        $pedidos = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($pedidos as &$ped) {
            $ped['origen_fuente'] = 'rest_pedidos';
            $ped['es_mi_zona']    = in_array((int)($ped['zona_id'] ?? 0), $misZonas);
            $ped['es_mi_reclamo'] = $ped['estado'] === 'reclamado' && (int)$ped['reclamado_por'] === $meseroId;
            $ped['reclamado_otro'] = $ped['estado'] === 'reclamado' && (int)$ped['reclamado_por'] !== $meseroId;

            $stmt2 = $db->prepare($this->pedidoItemsSql());
            $stmt2->execute([(int)$ped['id']]);
            $ped['items'] = $stmt2->fetchAll(PDO::FETCH_ASSOC);
        }
        unset($ped);

        $pedidos = array_merge($pedidos, $this->socialGiftOrdersListos($restauranteId, $misZonas));
        usort($pedidos, static function (array $a, array $b): int {
            return strcmp((string)($a['created_at'] ?? ''), (string)($b['created_at'] ?? ''));
        });
        $pedidos = array_slice($pedidos, 0, 50);

        $this->json(['ok' => true, 'listos' => $pedidos, 'mis_zonas' => $misZonas]);
    }

    // POST /rest-mesero/tomarZona  — reclama todos los pedidos 'listo' en las zonas del mesero
    public function tomarZona(?string $p = null): void
    {
        $db            = Database::getInstance();
        $meseroId      = $this->usuarioId();
        $restauranteId = $this->restauranteId();

        $stmtZ = $db->prepare(
            "SELECT zona_id FROM rest_mesero_turno
             WHERE restaurante_id = ? AND usuario_id = ? AND turno_fecha = CURDATE() AND activo = 1"
        );
        $stmtZ->execute([$restauranteId, $meseroId]);
        $misZonas = array_column($stmtZ->fetchAll(PDO::FETCH_ASSOC), 'zona_id');

        if (empty($misZonas)) {
            $this->json(['ok' => false, 'msg' => 'Sin zonas asignadas hoy', 'count' => 0]);
            return;
        }

        $placeholders = implode(',', array_fill(0, count($misZonas), '?'));

        // Recopilar IDs de los pedidos a entregar
        $stmtIds = $db->prepare(
            "SELECT p.id FROM rest_pedidos p
             LEFT JOIN rest_mesas m ON m.id = p.mesa_id
             WHERE p.restaurante_id = ? AND p.estado = 'listo'
               AND m.zona_id IN ($placeholders)"
        );
        $stmtIds->execute(array_merge([$restauranteId], $misZonas));
        $pedidoIds = array_column($stmtIds->fetchAll(PDO::FETCH_ASSOC), 'id');

        if (empty($pedidoIds)) {
            $this->json(['ok' => true, 'count' => 0]);
            return;
        }

        $idPlaceholders = implode(',', array_fill(0, count($pedidoIds), '?'));

        // Marcar pedidos como entregados directamente (sin pasar por 'reclamado')
        $db->prepare(
            "UPDATE rest_pedidos
             SET estado = 'entregado', mesero_id = ?, reclamado_por = ?, reclamado_at = NOW()
             WHERE id IN ($idPlaceholders) AND restaurante_id = ?"
        )->execute(array_merge([$meseroId, $meseroId], $pedidoIds, [$restauranteId]));

        // Marcar items como entregados
        $db->prepare(
            "UPDATE rest_pedido_items
             SET estado = 'entregado'
             WHERE pedido_id IN ($idPlaceholders) AND estado IN ('listo','reclamado')"
        )->execute($pedidoIds);

        // Propagar mesero_id al ticket si aún no tiene
        $db->prepare(
            "UPDATE rest_tickets t
             JOIN rest_visitas v ON v.id = t.visita_id
             JOIN rest_pedidos p ON p.visita_id = v.id
             SET t.mesero_id = ?
             WHERE p.id IN ($idPlaceholders) AND t.mesero_id IS NULL"
        )->execute(array_merge([$meseroId], $pedidoIds));

        $this->json(['ok' => true, 'count' => count($pedidoIds)]);
    }

    // GET /rest-mesero/reservasHoy  — reservaciones de hoy en las zonas del mesero
    public function reservasHoy(?string $p = null): void
    {
        $restauranteId = $this->restauranteId();
        $meseroId      = $this->usuarioId();
        $db            = Database::getInstance();

        // Zonas del mesero hoy
        $stmtZ = $db->prepare(
            "SELECT zona_id FROM rest_mesero_turno
             WHERE restaurante_id = ? AND usuario_id = ? AND turno_fecha = CURDATE() AND activo = 1"
        );
        $stmtZ->execute([$restauranteId, $meseroId]);
        $misZonas = array_column($stmtZ->fetchAll(PDO::FETCH_ASSOC), 'zona_id');

        $reservas = (new RestReservaModel())->getHoyPorZonas($restauranteId, $misZonas);
        $this->json(['ok' => true, 'reservas' => $reservas]);
    }
}
