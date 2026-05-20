<?php
require_once ROOT_PATH . '/app/controllers/BaseController.php';

class RestMeseroController extends BaseController
{
    private RestPedidoModel $model;

    public function __construct()
    {
        parent::__construct();
        $this->requireMesero();
        $this->model = new RestPedidoModel();
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
        $check = $db->prepare(
            "SELECT estado, reclamado_por FROM rest_pedidos WHERE id = ? AND restaurante_id = ?"
        );
        $check->execute([$pid, $this->restauranteId()]);
        $row = $check->fetch(PDO::FETCH_ASSOC);

        if (!$row || !in_array($row['estado'], ['listo', 'reclamado'], true)) {
            $this->json(['ok' => false, 'msg' => 'Estado inválido']);
            return;
        }

        if ($row['estado'] === 'reclamado' && $row['reclamado_por'] !== null
            && (int)$row['reclamado_por'] !== $meseroId) {
            $this->json(['ok' => false, 'msg' => 'Este pedido fue reclamado por otro mesero']);
            return;
        }

        $db->prepare(
            "UPDATE rest_pedidos SET estado='entregado', mesero_id = ? WHERE id = ? AND restaurante_id = ?"
        )->execute([$meseroId, $pid, $this->restauranteId()]);

        $db->prepare(
            "UPDATE rest_pedido_items SET estado='entregado' WHERE pedido_id = ?"
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
        $db   = Database::getInstance();
        $stmt = $db->prepare(
            "SELECT p.id, p.folio, p.estado, p.created_at
             FROM rest_pedidos p
             WHERE p.mesa_id = ? AND p.restaurante_id = ?
               AND p.estado NOT IN ('entregado','cancelado')
             ORDER BY p.created_at DESC"
        );
        $stmt->execute([(int)$mesaId, $this->restauranteId()]);
        $pedidos = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($pedidos as &$ped) {
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

        $stmt = $db->prepare(
            "SELECT p.id, p.folio, p.estado, p.created_at, p.mesero_id,
                    p.reclamado_por, p.reclamado_at,
                    m.nombre AS mesa_nombre, m.zona_id,
                    u.nombre AS reclamado_por_nombre
             FROM rest_pedidos p
             LEFT JOIN rest_mesas m   ON m.id = p.mesa_id
             LEFT JOIN usuarios u     ON u.id = p.reclamado_por
             WHERE p.restaurante_id = ? AND p.estado IN ('listo','reclamado')
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

            $stmt2 = $db->prepare(
                "SELECT pi.id, pl.nombre AS nombre, pi.cantidad
                 FROM rest_pedido_items pi
                 JOIN rest_platillos pl ON pl.id = pi.platillo_id
                 WHERE pi.pedido_id = ? AND pi.estado != 'cancelado'"
            );
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
        $stmt = $db->prepare(
            "UPDATE rest_pedidos p
             LEFT JOIN rest_mesas m ON m.id = p.mesa_id
             SET p.estado = 'reclamado', p.mesero_id = ?, p.reclamado_por = ?, p.reclamado_at = NOW()
             WHERE p.restaurante_id = ? AND p.estado = 'listo'
               AND m.zona_id IN ($placeholders)"
        );
        $stmt->execute(array_merge([$meseroId, $meseroId, $restauranteId], $misZonas));

        $this->json(['ok' => true, 'count' => $stmt->rowCount()]);
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
