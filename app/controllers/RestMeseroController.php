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
        $pid      = (int)$pedidoId;

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
            $ped['es_mi_zona']    = in_array((int)($ped['zona_id'] ?? 0), $misZonas);
            $ped['es_mi_reclamo'] = $ped['estado'] === 'reclamado' && (int)$ped['reclamado_por'] === $meseroId;
            $ped['reclamado_otro'] = $ped['estado'] === 'reclamado' && (int)$ped['reclamado_por'] !== $meseroId;

            $stmt2 = $db->prepare($this->pedidoItemsSql());
            $stmt2->execute([(int)$ped['id']]);
            $ped['items'] = $stmt2->fetchAll(PDO::FETCH_ASSOC);
        }
        unset($ped);

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
