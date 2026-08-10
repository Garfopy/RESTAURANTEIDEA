<?php

class RestCuentaPendienteModel extends BaseModel
{
    protected string $table = 'rest_regularizaciones_adeudo';
    private static array $schemaCache = [];

    private function tableExists(string $table): bool
    {
        $key = 'table:' . $table;
        if (array_key_exists($key, self::$schemaCache)) {
            return self::$schemaCache[$key];
        }
        if (!preg_match('/^[a-zA-Z0-9_]+$/', $table)) {
            return self::$schemaCache[$key] = false;
        }

        try {
            // No usar SHOW TABLES LIKE ? aqui: algunos servidores con prepares
            // nativos no aceptan el placeholder y hacen parecer que la tabla no existe.
            $this->db->query("SELECT 1 FROM `{$table}` LIMIT 0");
            return self::$schemaCache[$key] = true;
        } catch (Throwable $e) {
            return self::$schemaCache[$key] = false;
        }
    }

    private function columnExists(string $table, string $column): bool
    {
        $key = 'column:' . $table . '.' . $column;
        if (array_key_exists($key, self::$schemaCache)) {
            return self::$schemaCache[$key];
        }
        if (!preg_match('/^[a-zA-Z0-9_]+$/', $table) || !preg_match('/^[a-zA-Z0-9_]+$/', $column)) {
            return self::$schemaCache[$key] = false;
        }

        try {
            // Leer el esquema completo evita la misma incompatibilidad de LIKE ?.
            $stmt = $this->db->query("SHOW COLUMNS FROM `{$table}`");
            $found = false;
            foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $definition) {
                $field = (string)($definition['Field'] ?? '');
                self::$schemaCache['column:' . $table . '.' . $field] = true;
                if (strcasecmp($field, $column) === 0) {
                    $found = true;
                }
            }
            return self::$schemaCache[$key] = $found;
        } catch (Throwable $e) {
            return self::$schemaCache[$key] = false;
        }
    }

    private function firstColumn(string $table, array $columns): ?string
    {
        foreach ($columns as $column) {
            if ($this->columnExists($table, $column)) {
                return $column;
            }
        }
        return null;
    }

    private function textExpr(string $alias, string $column): string
    {
        return "CONVERT({$alias}.{$column} USING utf8mb4) COLLATE utf8mb4_unicode_ci";
    }

    private function phoneExpr(string $alias, string $column): string
    {
        $value = $this->textExpr($alias, $column);
        return "NULLIF(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE({$value}, ' ', ''), '-', ''), '(', ''), ')', ''), '+', ''), '')";
    }

    private function mobileColumns(): array
    {
        if (!$this->tableExists('mobile_usuarios')) {
            return ['name' => null, 'email' => null, 'phone' => null];
        }

        return [
            'name' => $this->firstColumn('mobile_usuarios', ['nombre', 'nombre_completo', 'name', 'full_name']),
            'email' => $this->firstColumn('mobile_usuarios', ['email', 'correo']),
            'phone' => $this->firstColumn('mobile_usuarios', ['telefono', 'celular', 'phone', 'mobile', 'whatsapp']),
        ];
    }

    public function listarPendientes(int $restauranteId): array
    {
        $tickets = $this->listarTicketsPendientes($restauranteId);
        $pedidosApp = $this->listarPedidosAppPendientes($restauranteId);
        $monto = 0.0;
        foreach (array_merge($tickets, $pedidosApp) as $cuenta) {
            $monto += (float)($cuenta['monto'] ?? 0);
        }

        return [
            'tickets' => $tickets,
            'pedidos_app' => $pedidosApp,
            'total_cuentas' => count($tickets) + count($pedidosApp),
            'monto_pendiente' => $monto,
        ];
    }

    public function listarSalidasPendientes(int $restauranteId): array
    {
        return $this->query(
            "SELECT v.id AS visita_id,
                    v.qr_code,
                    v.pagada_at,
                    v.total AS visita_total,
                    v.created_at,
                    m.id AS mesa_id,
                    m.nombre AS mesa_nombre,
                    c.nombre AS cliente_nombre,
                    c.email AS cliente_email,
                    c.telefono AS cliente_telefono,
                    tp.folio AS ticket_folio,
                    COALESCE(tp.total, v.total, 0) AS monto,
                    COALESCE(tp.pagado_at, v.pagada_at) AS pago_confirmado_at
               FROM rest_visitas v
          LEFT JOIN rest_mesas m ON m.id = v.mesa_id
          LEFT JOIN rest_comensales c ON c.id = v.comensal_id
          LEFT JOIN (
                    SELECT t1.visita_id, t1.folio, t1.total, t1.pagado_at
                      FROM rest_tickets t1
                      JOIN (
                            SELECT visita_id, MAX(id) AS ticket_id
                              FROM rest_tickets
                             WHERE restaurante_id = ? AND estado = 'pagado'
                          GROUP BY visita_id
                      ) ultimo ON ultimo.ticket_id = t1.id
               ) tp ON tp.visita_id = v.id
              WHERE v.restaurante_id = ?
                AND v.estado = 'pagada'
                AND v.salida_at IS NULL
           ORDER BY COALESCE(v.pagada_at, v.created_at) ASC, v.id ASC",
            [$restauranteId, $restauranteId]
        );
    }

    public function listarPedidosParaLiberar(
        int $restauranteId,
        string $busqueda = '',
        string $estado = 'todos',
        int $limit = 200
    ): array {
        $limit = max(20, min(500, $limit));
        $estados = ['pendiente', 'en_preparacion', 'listo', 'reclamado', 'entregado', 'cancelado'];
        $estado = in_array($estado, $estados, true) ? $estado : 'todos';
        $busqueda = trim($busqueda);

        $pedidoCliente = $this->firstColumn('rest_pedidos', ['cliente_nombre', 'comprador_nombre']);
        $pedidoTelefono = $this->firstColumn('rest_pedidos', ['comprador_telefono', 'cliente_telefono', 'telefono']);
        $pedidoMobile = $this->firstColumn('rest_pedidos', [
            'mobile_usuario_id', 'mobile_user_id', 'app_cliente_id', 'app_usuario_id', 'usuario_mobile_id'
        ]);
        $tipoOrigen = $this->columnExists('rest_pedidos', 'tipo_origen') ? 'p.tipo_origen' : 'NULL';
        $ticketPagadoAt = "(SELECT MAX(t_pago.pagado_at)
                              FROM rest_tickets t_pago
                             WHERE t_pago.visita_id = p.visita_id
                               AND t_pago.estado = 'pagado')";
        $pagadoAt = $this->columnExists('rest_pedidos', 'pagado_at')
            ? "COALESCE(p.pagado_at, {$ticketPagadoAt})"
            : $ticketPagadoAt;
        $pedidoClienteExpr = $pedidoCliente ? "NULLIF({$this->textExpr('p', $pedidoCliente)}, '')" : 'NULL';
        $pedidoTelefonoExpr = $pedidoTelefono ? "NULLIF({$this->textExpr('p', $pedidoTelefono)}, '')" : 'NULL';
        $mobileIdExpr = $pedidoMobile ? "p.{$pedidoMobile}" : 'NULL';

        $mobile = $this->mobileColumns();
        $mobileJoin = $this->tableExists('mobile_usuarios') && $pedidoMobile
            ? "LEFT JOIN mobile_usuarios mu ON mu.id = p.{$pedidoMobile}"
            : '';
        $mobileName = $mobileJoin && $mobile['name'] ? "NULLIF({$this->textExpr('mu', $mobile['name'])}, '')" : 'NULL';
        $mobileEmail = $mobileJoin && $mobile['email'] ? "NULLIF({$this->textExpr('mu', $mobile['email'])}, '')" : 'NULL';
        $mobilePhone = $mobileJoin && $mobile['phone'] ? "NULLIF({$this->textExpr('mu', $mobile['phone'])}, '')" : 'NULL';

        $where = ['p.restaurante_id = ?'];
        $params = [$restauranteId];
        if ($estado !== 'todos') {
            $where[] = 'p.estado = ?';
            $params[] = $estado;
        }
        if ($busqueda !== '') {
            $searchParts = [
                'p.folio LIKE ?',
                'c.nombre LIKE ?',
                'c.email LIKE ?',
                'c.telefono LIKE ?',
            ];
            if ($pedidoCliente) $searchParts[] = "p.{$pedidoCliente} LIKE ?";
            if ($pedidoTelefono) $searchParts[] = "p.{$pedidoTelefono} LIKE ?";
            if ($mobileJoin && $mobile['name']) $searchParts[] = "mu.{$mobile['name']} LIKE ?";
            if ($mobileJoin && $mobile['email']) $searchParts[] = "mu.{$mobile['email']} LIKE ?";
            if ($mobileJoin && $mobile['phone']) $searchParts[] = "mu.{$mobile['phone']} LIKE ?";
            $where[] = '(' . implode(' OR ', $searchParts) . ')';
            foreach ($searchParts as $_) {
                $params[] = '%' . $busqueda . '%';
            }
        }

        return $this->query(
            "SELECT p.id AS pedido_id,
                    p.folio,
                    p.estado,
                    p.total,
                    p.subtotal,
                    p.created_at,
                    p.visita_id,
                    {$tipoOrigen} AS tipo_origen,
                    {$pagadoAt} AS pagado_at,
                    {$mobileIdExpr} AS mobile_usuario_id,
                    m.nombre AS mesa_nombre,
                    COALESCE({$pedidoClienteExpr}, NULLIF({$this->textExpr('c', 'nombre')}, ''), {$mobileName}, CONCAT('Pedido #', p.id)) AS cliente_nombre,
                    COALESCE(NULLIF({$this->textExpr('c', 'email')}, ''), {$mobileEmail}) AS cliente_email,
                    COALESCE({$pedidoTelefonoExpr}, NULLIF({$this->textExpr('c', 'telefono')}, ''), {$mobilePhone}) AS cliente_telefono,
                    (SELECT COUNT(*) FROM rest_pedido_items pi WHERE pi.pedido_id = p.id) AS items_total,
                    (SELECT COUNT(*) FROM rest_pedido_items pi WHERE pi.pedido_id = p.id AND pi.estado = 'entregado') AS items_entregados
               FROM rest_pedidos p
          LEFT JOIN rest_visitas v ON v.id = p.visita_id
          LEFT JOIN rest_comensales c ON c.id = v.comensal_id
          LEFT JOIN rest_mesas m ON m.id = p.mesa_id
               {$mobileJoin}
              WHERE " . implode(' AND ', $where) . "
           ORDER BY p.created_at DESC, p.id DESC
              LIMIT {$limit}",
            $params
        );
    }

    public function getHistorialLiberaciones(int $restauranteId, int $limit = 50): array
    {
        if (!$this->tableExists('rest_liberaciones_pedido_programador')) {
            return [];
        }
        $limit = max(1, min(100, $limit));
        return $this->query(
            "SELECT lp.*, u.nombre AS programador_nombre
               FROM rest_liberaciones_pedido_programador lp
          LEFT JOIN usuarios u ON u.id = lp.usuario_id
              WHERE lp.restaurante_id = ?
           ORDER BY lp.created_at DESC, lp.id DESC
              LIMIT {$limit}",
            [$restauranteId]
        );
    }

    public function liberarPedido(
        int $restauranteId,
        int $pedidoId,
        string $motivo,
        int $usuarioId
    ): array {
        $auditTable = 'rest_liberaciones_pedido_programador';
        if (!$this->tableExists($auditTable)) {
            throw new RuntimeException('Falta ejecutar la migracion 086_liberacion_pedidos_programador.sql.');
        }
        foreach ([
            'restaurante_id', 'pedido_id', 'folio', 'cliente_referencia',
            'estado_anterior', 'estado_nuevo', 'estados_items_anteriores',
            'motivo', 'usuario_id', 'created_at',
        ] as $column) {
            if (!$this->columnExists($auditTable, $column)) {
                throw new DomainException('La migracion 086 esta incompleta. Falta la columna ' . $column . '.');
            }
        }
        $motivo = trim($motivo);
        $length = function_exists('mb_strlen') ? mb_strlen($motivo) : strlen($motivo);
        if ($pedidoId <= 0) {
            throw new InvalidArgumentException('El pedido seleccionado no es valido.');
        }
        if ($length < 5 || $length > 500) {
            throw new InvalidArgumentException('El motivo debe tener entre 5 y 500 caracteres.');
        }

        $this->db->beginTransaction();
        try {
            $clienteColumn = $this->firstColumn('rest_pedidos', ['cliente_nombre', 'comprador_nombre']);
            $clienteSelect = $clienteColumn ? ", p.{$clienteColumn} AS pedido_cliente" : ', NULL AS pedido_cliente';
            $pedido = $this->queryOne(
                "SELECT p.id, p.folio, p.estado, p.restaurante_id, p.visita_id,
                        c.nombre AS comensal_nombre{$clienteSelect}
                   FROM rest_pedidos p
              LEFT JOIN rest_visitas v ON v.id = p.visita_id
              LEFT JOIN rest_comensales c ON c.id = v.comensal_id
                  WHERE p.id = ? AND p.restaurante_id = ?
                  FOR UPDATE",
                [$pedidoId, $restauranteId]
            );
            if (!$pedido) {
                throw new DomainException('El pedido no existe o pertenece a otro restaurante.');
            }

            $items = $this->query(
                "SELECT estado, COUNT(*) AS cantidad
                   FROM rest_pedido_items
                  WHERE pedido_id = ?
               GROUP BY estado",
                [$pedidoId]
            );
            $todosEntregados = true;
            foreach ($items as $item) {
                if (($item['estado'] ?? '') !== 'entregado') {
                    $todosEntregados = false;
                    break;
                }
            }
            if (($pedido['estado'] ?? '') === 'entregado' && $todosEntregados) {
                throw new DomainException('Este pedido ya se encuentra completamente liberado como entregado.');
            }

            // El modelo de pedidos descuenta inventario de forma idempotente al
            // transitar a entregado; nunca modifica el estado financiero.
            (new RestPedidoModel())->cambiarEstadoPedido($pedidoId, 'entregado');
            $this->execute(
                "UPDATE rest_pedido_items SET estado = 'entregado' WHERE pedido_id = ?",
                [$pedidoId]
            );

            $cliente = trim((string)($pedido['pedido_cliente'] ?? ''));
            if ($cliente === '') $cliente = trim((string)($pedido['comensal_nombre'] ?? ''));
            if ($cliente === '') $cliente = 'Pedido #' . $pedidoId;
            $this->execute(
                "INSERT INTO rest_liberaciones_pedido_programador
                    (restaurante_id, pedido_id, folio, cliente_referencia,
                     estado_anterior, estado_nuevo, estados_items_anteriores, motivo, usuario_id)
                 VALUES (?, ?, ?, ?, ?, 'entregado', ?, ?, ?)",
                [
                    $restauranteId,
                    $pedidoId,
                    $pedido['folio'] ?: null,
                    $cliente,
                    (string)$pedido['estado'],
                    json_encode($items, JSON_UNESCAPED_UNICODE),
                    $motivo,
                    $usuarioId,
                ]
            );

            $this->db->commit();
            return ['pedido_id' => $pedidoId, 'folio' => (string)$pedido['folio'], 'cliente' => $cliente];
        } catch (Throwable $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            throw $e;
        }
    }

    public function getHistorialSalidas(int $restauranteId, int $limit = 30): array
    {
        if (!$this->tableExists('rest_validaciones_salida_programador')) {
            return [];
        }
        $limit = max(1, min(100, $limit));
        return $this->query(
            "SELECT vs.*, u.nombre AS programador_nombre
               FROM rest_validaciones_salida_programador vs
          LEFT JOIN usuarios u ON u.id = vs.usuario_id
              WHERE vs.restaurante_id = ?
           ORDER BY vs.created_at DESC, vs.id DESC
              LIMIT {$limit}",
            [$restauranteId]
        );
    }

    public function validarSalidaManual(
        int $restauranteId,
        int $visitaId,
        string $motivo,
        int $usuarioId
    ): array {
        if (!$this->tableExists('rest_validaciones_salida_programador')) {
            throw new RuntimeException('Falta ejecutar la migracion 085_validacion_salida_programador.sql.');
        }
        foreach ([
            'restaurante_id', 'visita_id', 'ticket_folio', 'cliente_referencia',
            'mesa_referencia', 'pagada_at', 'salida_registrada_at', 'motivo',
            'usuario_id', 'created_at',
        ] as $column) {
            if (!$this->columnExists('rest_validaciones_salida_programador', $column)) {
                throw new DomainException('La migracion 085 esta incompleta. Falta la columna ' . $column . '.');
            }
        }
        $motivo = trim($motivo);
        $length = function_exists('mb_strlen') ? mb_strlen($motivo) : strlen($motivo);
        if ($visitaId <= 0) {
            throw new InvalidArgumentException('La visita seleccionada no es valida.');
        }
        if ($length < 5 || $length > 500) {
            throw new InvalidArgumentException('El motivo debe tener entre 5 y 500 caracteres.');
        }

        $this->db->beginTransaction();
        try {
            $visita = $this->queryOne(
                "SELECT v.*, m.nombre AS mesa_nombre, c.nombre AS cliente_nombre,
                        (SELECT t.folio
                           FROM rest_tickets t
                          WHERE t.visita_id = v.id AND t.estado = 'pagado'
                       ORDER BY t.id DESC LIMIT 1) AS ticket_folio
                   FROM rest_visitas v
              LEFT JOIN rest_mesas m ON m.id = v.mesa_id
              LEFT JOIN rest_comensales c ON c.id = v.comensal_id
                  WHERE v.id = ? AND v.restaurante_id = ?
                  FOR UPDATE",
                [$visitaId, $restauranteId]
            );
            if (!$visita) {
                throw new DomainException('La visita no existe o pertenece a otro restaurante.');
            }
            if (($visita['estado'] ?? '') !== 'pagada') {
                throw new DomainException('La visita ya no aparece como pagada. No se puede validar su salida.');
            }
            if (!empty($visita['salida_at'])) {
                throw new DomainException('La salida de esta visita ya fue registrada.');
            }

            $salidaAt = date('Y-m-d H:i:s');
            $this->execute(
                "UPDATE rest_visitas
                    SET salida_at = ?
                  WHERE id = ? AND restaurante_id = ? AND estado = 'pagada' AND salida_at IS NULL",
                [$salidaAt, $visitaId, $restauranteId]
            );

            if (!empty($visita['mesa_id'])) {
                // Solo liberar si ninguna otra visita sin salida ocupa esa mesa.
                $this->execute(
                    "UPDATE rest_mesas m
                        SET estado = 'disponible'
                      WHERE m.id = ? AND m.restaurante_id = ?
                        AND NOT EXISTS (
                            SELECT 1
                              FROM rest_visitas otra
                             WHERE otra.mesa_id = m.id
                               AND otra.restaurante_id = m.restaurante_id
                               AND otra.id <> ?
                               AND otra.salida_at IS NULL
                               AND otra.estado <> 'cancelada'
                        )",
                    [(int)$visita['mesa_id'], $restauranteId, $visitaId]
                );

                if ($this->tableExists('rest_reservaciones')) {
                    $this->execute(
                        "UPDATE rest_reservaciones
                            SET estado = 'completada'
                          WHERE restaurante_id = ? AND mesa_id = ?
                            AND fecha = CURDATE() AND estado IN ('pendiente','confirmada')",
                        [$restauranteId, (int)$visita['mesa_id']]
                    );
                }
            }

            $cliente = trim((string)($visita['cliente_nombre'] ?? ''));
            $cliente = $cliente !== '' ? $cliente : 'Visita #' . $visitaId;
            $this->execute(
                "INSERT INTO rest_validaciones_salida_programador
                    (restaurante_id, visita_id, ticket_folio, cliente_referencia,
                     mesa_referencia, pagada_at, salida_registrada_at, motivo, usuario_id)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)",
                [
                    $restauranteId,
                    $visitaId,
                    $visita['ticket_folio'] ?: null,
                    $cliente,
                    $visita['mesa_nombre'] ?: null,
                    $visita['pagada_at'] ?: null,
                    $salidaAt,
                    $motivo,
                    $usuarioId,
                ]
            );

            $this->db->commit();
            return [
                'visita_id' => $visitaId,
                'ticket_folio' => (string)($visita['ticket_folio'] ?? ''),
                'cliente' => $cliente,
            ];
        } catch (Throwable $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            throw $e;
        }
    }

    private function listarTicketsPendientes(int $restauranteId): array
    {
        $pedidoMobile = $this->firstColumn('rest_pedidos', [
            'mobile_usuario_id', 'mobile_user_id', 'app_cliente_id', 'app_usuario_id', 'usuario_mobile_id'
        ]);
        $pedidoCliente = $this->firstColumn('rest_pedidos', ['cliente_nombre', 'comprador_nombre']);
        $comensalMobile = $this->firstColumn('rest_comensales', [
            'mobile_usuario_id', 'mobile_user_id', 'app_cliente_id', 'app_usuario_id', 'usuario_mobile_id'
        ]);
        $pdSelect = ['visita_id'];
        $pdSelect[] = $pedidoMobile ? "MAX({$pedidoMobile}) AS mobile_usuario_id" : 'NULL AS mobile_usuario_id';
        $pdSelect[] = $pedidoCliente ? "MAX(NULLIF({$pedidoCliente}, '')) AS pedido_cliente" : 'NULL AS pedido_cliente';

        $mobile = $this->mobileColumns();
        $linkedMobileId = $comensalMobile
            ? "COALESCE(c.{$comensalMobile}, pd.mobile_usuario_id)"
            : 'pd.mobile_usuario_id';
        $mobileJoin = $this->tableExists('mobile_usuarios') && ($pedidoMobile || $comensalMobile)
            ? "LEFT JOIN mobile_usuarios mu ON mu.id = {$linkedMobileId}"
            : '';
        $mobileName = $mobileJoin && $mobile['name'] ? "NULLIF({$this->textExpr('mu', $mobile['name'])}, '')" : 'NULL';
        $mobileEmail = $mobileJoin && $mobile['email'] ? $this->textExpr('mu', $mobile['email']) : 'NULL';
        $mobilePhone = $mobileJoin && $mobile['phone'] ? $this->textExpr('mu', $mobile['phone']) : 'NULL';
        $comensalNombre = $this->textExpr('c', 'nombre');
        $comensalEmail = $this->textExpr('c', 'email');
        $comensalTelefono = $this->textExpr('c', 'telefono');
        $pedidoClienteExpr = 'CONVERT(pd.pedido_cliente USING utf8mb4) COLLATE utf8mb4_unicode_ci';
        $emailPorTelefono = 'NULL';
        if ($this->tableExists('mobile_usuarios') && $mobile['email'] && $mobile['phone']) {
            $emailPorTelefono = "(
                SELECT {$this->textExpr('mu_phone', $mobile['email'])}
                  FROM mobile_usuarios mu_phone
                 WHERE {$this->phoneExpr('mu_phone', $mobile['phone'])} = {$this->phoneExpr('c', 'telefono')}
                   AND {$this->phoneExpr('c', 'telefono')} IS NOT NULL
                 ORDER BY mu_phone.id ASC
                 LIMIT 1
            )";
        }

        return $this->query(
            "SELECT 'ticket' AS tipo_registro,
                    t.id AS registro_id,
                    t.folio,
                    t.total AS monto,
                    t.estado,
                    t.metodo_pago,
                    t.created_at,
                    v.id AS visita_id,
                    v.estado AS visita_estado,
                    m.nombre AS mesa_nombre,
                    c.id AS comensal_id,
                    {$linkedMobileId} AS mobile_usuario_id,
                    COALESCE(NULLIF({$comensalNombre}, ''), NULLIF({$pedidoClienteExpr}, ''), {$mobileName}, CONCAT('Visita #', v.id)) AS cliente_nombre,
                    COALESCE(NULLIF({$comensalEmail}, ''), {$mobileEmail}, {$emailPorTelefono}) AS cliente_email,
                    COALESCE(NULLIF({$comensalTelefono}, ''), {$mobilePhone}) AS cliente_telefono
               FROM rest_tickets t
               JOIN rest_visitas v ON v.id = t.visita_id AND v.restaurante_id = t.restaurante_id
          LEFT JOIN rest_mesas m ON m.id = t.mesa_id
          LEFT JOIN rest_comensales c ON c.id = v.comensal_id
          LEFT JOIN (
                    SELECT " . implode(', ', $pdSelect) . "
                      FROM rest_pedidos
                     WHERE restaurante_id = ? AND visita_id IS NOT NULL
                  GROUP BY visita_id
               ) pd ON pd.visita_id = v.id
               {$mobileJoin}
              WHERE t.restaurante_id = ?
                AND t.estado = 'pendiente'
           ORDER BY t.created_at ASC, t.id ASC",
            [$restauranteId, $restauranteId]
        );
    }

    private function listarPedidosAppPendientes(int $restauranteId): array
    {
        if (!$this->columnExists('rest_pedidos', 'pagado_at')) {
            return [];
        }

        $mobileId = $this->firstColumn('rest_pedidos', [
            'mobile_usuario_id', 'mobile_user_id', 'app_cliente_id', 'app_usuario_id', 'usuario_mobile_id'
        ]);
        $cliente = $this->firstColumn('rest_pedidos', ['cliente_nombre', 'comprador_nombre']);
        $tipoOrigen = $this->columnExists('rest_pedidos', 'tipo_origen') ? 'tipo_origen' : null;
        if (!$mobileId && !$cliente) {
            return [];
        }

        $mobile = $this->mobileColumns();
        $mobileJoin = $this->tableExists('mobile_usuarios') && $mobileId
            ? "LEFT JOIN mobile_usuarios mu ON mu.id = p.{$mobileId}"
            : '';
        $mobileName = $mobileJoin && $mobile['name'] ? "NULLIF({$this->textExpr('mu', $mobile['name'])}, '')" : 'NULL';
        $mobileEmail = $mobileJoin && $mobile['email'] ? $this->textExpr('mu', $mobile['email']) : 'NULL';
        $mobilePhone = $mobileJoin && $mobile['phone'] ? $this->textExpr('mu', $mobile['phone']) : 'NULL';
        $clienteExpr = $cliente ? "NULLIF({$this->textExpr('p', $cliente)}, '')" : 'NULL';
        $mobileIdExpr = $mobileId ? "p.{$mobileId}" : 'NULL';
        $metodoExpr = $this->columnExists('rest_pedidos', 'metodo_pago') ? 'p.metodo_pago' : 'NULL';
        $origenWhere = $tipoOrigen
            ? "AND LOWER(COALESCE(p.{$tipoOrigen}, '')) IN ('app','mobile')"
            : ($mobileId ? "AND p.{$mobileId} IS NOT NULL" : 'AND 1 = 0');
        $visitaWhere = $this->columnExists('rest_pedidos', 'visita_id') ? 'AND p.visita_id IS NULL' : '';

        return $this->query(
            "SELECT 'pedido_app' AS tipo_registro,
                    p.id AS registro_id,
                    p.folio,
                    COALESCE(NULLIF(p.total, 0), p.subtotal, 0) AS monto,
                    p.estado,
                    {$metodoExpr} AS metodo_pago,
                    p.created_at,
                    NULL AS visita_id,
                    NULL AS visita_estado,
                    NULL AS mesa_nombre,
                    NULL AS comensal_id,
                    {$mobileIdExpr} AS mobile_usuario_id,
                    COALESCE({$clienteExpr}, {$mobileName}, CONCAT('Usuario app #', COALESCE({$mobileIdExpr}, p.id))) AS cliente_nombre,
                    {$mobileEmail} AS cliente_email,
                    {$mobilePhone} AS cliente_telefono
               FROM rest_pedidos p
               {$mobileJoin}
              WHERE p.restaurante_id = ?
                AND p.pagado_at IS NULL
                AND p.estado <> 'cancelado'
                {$visitaWhere}
                {$origenWhere}
           ORDER BY p.created_at ASC, p.id ASC",
            [$restauranteId]
        );
    }

    public function getHistorial(int $restauranteId, int $limit = 30): array
    {
        if (!$this->tableExists($this->table)) {
            return [];
        }
        $limit = max(1, min(100, $limit));
        return $this->query(
            "SELECT ra.*, u.nombre AS programador_nombre
               FROM rest_regularizaciones_adeudo ra
          LEFT JOIN usuarios u ON u.id = ra.usuario_id
              WHERE ra.restaurante_id = ?
           ORDER BY ra.created_at DESC, ra.id DESC
              LIMIT {$limit}",
            [$restauranteId]
        );
    }

    public function regularizar(
        int $restauranteId,
        string $tipo,
        int $registroId,
        string $metodoPago,
        string $motivo,
        int $usuarioId
    ): array {
        if (!$this->tableExists($this->table)) {
            throw new RuntimeException('Falta ejecutar la migracion 084_regularizacion_adeudos_programador.sql.');
        }
        $this->validarEsquemaAuditoria();
        if (!in_array($tipo, ['ticket', 'pedido_app'], true) || $registroId <= 0) {
            throw new InvalidArgumentException('La cuenta seleccionada no es valida.');
        }
        if (!in_array($metodoPago, ['paypal', 'tarjeta', 'transferencia', 'efectivo'], true)) {
            throw new InvalidArgumentException('Selecciona un metodo de pago valido.');
        }
        $motivo = trim($motivo);
        $motivoLength = function_exists('mb_strlen') ? mb_strlen($motivo) : strlen($motivo);
        if ($motivoLength < 5 || $motivoLength > 500) {
            throw new InvalidArgumentException('El motivo debe tener entre 5 y 500 caracteres.');
        }

        $this->db->beginTransaction();
        try {
            $resultado = $tipo === 'ticket'
                ? $this->regularizarTicket($restauranteId, $registroId, $metodoPago, $motivo, $usuarioId)
                : $this->regularizarPedidoApp($restauranteId, $registroId, $metodoPago, $motivo, $usuarioId);
            $this->db->commit();
            return $resultado;
        } catch (Throwable $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            throw $e;
        }
    }

    private function regularizarTicket(
        int $restauranteId,
        int $ticketId,
        string $metodoPago,
        string $motivo,
        int $usuarioId
    ): array {
        $ticket = $this->queryOne(
            "SELECT t.*, v.estado AS visita_estado, v.comensal_id, c.nombre AS cliente_nombre
               FROM rest_tickets t
               JOIN rest_visitas v ON v.id = t.visita_id AND v.restaurante_id = t.restaurante_id
          LEFT JOIN rest_comensales c ON c.id = v.comensal_id
              WHERE t.id = ? AND t.restaurante_id = ?
              FOR UPDATE",
            [$ticketId, $restauranteId]
        );
        if (!$ticket) {
            throw new DomainException('El ticket no existe o pertenece a otro restaurante.');
        }
        if (($ticket['estado'] ?? '') !== 'pendiente') {
            throw new DomainException('Este ticket ya no tiene un adeudo pendiente.');
        }

        $this->execute(
            "UPDATE rest_tickets
                SET estado = 'pagado', metodo_pago = ?, pagado_at = COALESCE(pagado_at, NOW())
              WHERE id = ? AND restaurante_id = ? AND estado = 'pendiente'",
            [$metodoPago, $ticketId, $restauranteId]
        );

        // Reutiliza el flujo normal de cobro para que pedido, items e inventario
        // queden en el mismo estado que un pago confirmado desde Tickets.
        (new RestPedidoModel())->marcarVisitaEntregada((int)$ticket['visita_id']);
        (new RestVisitaModel())->marcarPagada((int)$ticket['visita_id']);
        // Algunas instalaciones antiguas no tienen la tabla de alertas. Es una
        // limpieza auxiliar y nunca debe impedir que se refleje el pago.
        if ($this->tableExists('rest_alertas')
            && $this->columnExists('rest_alertas', 'atendida')
            && $this->columnExists('rest_alertas', 'visita_id')) {
            $this->execute(
                "UPDATE rest_alertas
                    SET atendida = 1
                  WHERE restaurante_id = ? AND visita_id = ? AND tipo = 'cuenta' AND atendida = 0",
                [$restauranteId, (int)$ticket['visita_id']]
            );
        }

        $cliente = trim((string)($ticket['cliente_nombre'] ?? ''));
        $cliente = $cliente !== '' ? $cliente : 'Visita #' . (int)$ticket['visita_id'];
        $this->registrarAuditoria(
            $restauranteId, 'ticket', $ticketId, (string)$ticket['folio'], $cliente,
            (float)$ticket['total'], 'ticket:' . $ticket['estado'] . '/visita:' . $ticket['visita_estado'],
            $metodoPago, $motivo, $usuarioId
        );

        return ['folio' => (string)$ticket['folio'], 'tipo' => 'ticket'];
    }

    private function regularizarPedidoApp(
        int $restauranteId,
        int $pedidoId,
        string $metodoPago,
        string $motivo,
        int $usuarioId
    ): array {
        if (!$this->columnExists('rest_pedidos', 'pagado_at')) {
            throw new DomainException('La base de datos no soporta el estado de pago para pedidos de app.');
        }
        $clienteColumn = $this->firstColumn('rest_pedidos', ['cliente_nombre', 'comprador_nombre']);
        $clienteSelect = $clienteColumn ? ", {$clienteColumn} AS cliente_nombre" : ', NULL AS cliente_nombre';
        $pedido = $this->queryOne(
            "SELECT id, folio, estado, total, subtotal, pagado_at{$clienteSelect}
               FROM rest_pedidos
              WHERE id = ? AND restaurante_id = ?
              FOR UPDATE",
            [$pedidoId, $restauranteId]
        );
        if (!$pedido) {
            throw new DomainException('El pedido no existe o pertenece a otro restaurante.');
        }
        if (!empty($pedido['pagado_at'])) {
            throw new DomainException('Este pedido ya no tiene un adeudo pendiente.');
        }
        if (($pedido['estado'] ?? '') === 'cancelado') {
            throw new DomainException('No se puede regularizar un pedido cancelado.');
        }

        $setMetodo = $this->columnExists('rest_pedidos', 'metodo_pago') ? ', metodo_pago = ?' : '';
        $params = [$pedidoId, $restauranteId];
        if ($setMetodo !== '') {
            $params = [$metodoPago, $pedidoId, $restauranteId];
        }
        $this->execute(
            "UPDATE rest_pedidos
                SET pagado_at = NOW(){$setMetodo}
              WHERE id = ? AND restaurante_id = ? AND pagado_at IS NULL",
            $params
        );

        $cliente = trim((string)($pedido['cliente_nombre'] ?? ''));
        $cliente = $cliente !== '' ? $cliente : 'Pedido app #' . $pedidoId;
        $monto = (float)($pedido['total'] ?: $pedido['subtotal']);
        $this->registrarAuditoria(
            $restauranteId, 'pedido_app', $pedidoId, (string)$pedido['folio'], $cliente,
            $monto, 'pedido:' . $pedido['estado'] . '/pago:pendiente',
            $metodoPago, $motivo, $usuarioId
        );

        return ['folio' => (string)$pedido['folio'], 'tipo' => 'pedido_app'];
    }

    private function registrarAuditoria(
        int $restauranteId,
        string $tipo,
        int $registroId,
        string $folio,
        string $cliente,
        float $monto,
        string $estadoAnterior,
        string $metodoPago,
        string $motivo,
        int $usuarioId
    ): void {
        $this->execute(
            "INSERT INTO rest_regularizaciones_adeudo
                (restaurante_id, tipo_registro, registro_id, folio, cliente_referencia, monto,
                 estado_anterior, metodo_pago, motivo, usuario_id)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)",
            [$restauranteId, $tipo, $registroId, $folio, $cliente, $monto,
             $estadoAnterior, $metodoPago, $motivo, $usuarioId]
        );
    }

    private function validarEsquemaAuditoria(): void
    {
        $required = [
            'restaurante_id', 'tipo_registro', 'registro_id', 'folio',
            'cliente_referencia', 'monto', 'estado_anterior', 'metodo_pago',
            'motivo', 'usuario_id', 'created_at',
        ];
        $missing = [];
        foreach ($required as $column) {
            if (!$this->columnExists($this->table, $column)) {
                $missing[] = $column;
            }
        }
        if ($missing) {
            throw new DomainException(
                'La migracion 084 esta incompleta. Faltan columnas: ' . implode(', ', $missing) . '.'
            );
        }
    }
}
