<?php
class RestPedidoModel extends BaseModel
{
    protected string $table = 'rest_pedidos';
    private static array $columnCache = [];

    private function hasColumnInTable(string $table, string $column): bool
    {
        $key = $table . '.' . $column;
        if (array_key_exists($key, self::$columnCache)) {
            return self::$columnCache[$key];
        }

        if (!preg_match('/^[a-zA-Z0-9_]+$/', $table) || !preg_match('/^[a-zA-Z0-9_]+$/', $column)) {
            return self::$columnCache[$key] = false;
        }

        try {
            // MySQL 5.7 con PDO::ATTR_EMULATE_PREPARES=false no acepta de
            // forma consistente un placeholder en `SHOW COLUMNS ... LIKE ?`.
            // Cuando fallaba, el catch devolvia false y `crear()` descartaba
            // silenciosamente estado, turno, cajero, pago y UUID del POS.
            // Leer el esquema completo una sola vez evita esa incompatibilidad.
            $stmt = $this->db->query("SHOW COLUMNS FROM `{$table}`");
            $found = false;
            foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $definition) {
                $field = (string)($definition['Field'] ?? '');
                if ($field === '') continue;
                self::$columnCache[$table . '.' . $field] = true;
                if (strcasecmp($field, $column) === 0) {
                    $found = true;
                }
            }
            return self::$columnCache[$key] = $found;
        } catch (\Throwable $e) {
            error_log('[RestPedidoModel] No se pudo leer el esquema de ' . $table . ': ' . $e->getMessage());
            return self::$columnCache[$key] = false;
        }
    }

    private function hasColumn(string $column): bool
    {
        return $this->hasColumnInTable($this->table, $column);
    }

    public function soportaTipoOrigen(): bool
    {
        return $this->hasColumn('tipo_origen');
    }

    private function sqlFechaFinanciera(string $alias = 'p'): string
    {
        $columns = [];
        foreach (['pagado_at', 'cerrado_at', 'actualizado_at', 'updated_at', 'created_at'] as $column) {
            if ($this->hasColumn($column)) {
                $columns[] = "{$alias}.{$column}";
            }
        }
        if (!$columns) return 'NULL';
        return count($columns) === 1 ? $columns[0] : 'COALESCE(' . implode(', ', $columns) . ')';
    }

    private function sqlNoStore(string $alias = 'p'): string
    {
        if (!$this->soportaTipoOrigen()) {
            return '';
        }
        $regalo = $this->sqlRegaloException($alias);
        return " AND (LOWER(COALESCE({$alias}.tipo_origen, '')) <> 'store'{$regalo})";
    }

    private function sqlSoloStore(string $alias = 'p'): string
    {
        if (!$this->soportaTipoOrigen()) {
            return ' AND 1 = 0';
        }
        $regalo = $this->sqlRegaloException($alias);
        return " AND LOWER(COALESCE({$alias}.tipo_origen, '')) = 'store' AND NOT (0{$regalo})";
    }

    private function sqlRegaloException(string $alias = 'p'): string
    {
        $parts = [];
        if ($this->hasColumn('es_regalo')) {
            $parts[] = "COALESCE({$alias}.es_regalo, 0) = 1";
        }
        if ($this->hasColumn('tipo_entrega')) {
            $parts[] = "LOWER(COALESCE({$alias}.tipo_entrega, '')) IN ('gift', 'regalo', 'regalos')";
        }

        return $parts ? ' OR ' . implode(' OR ', $parts) : '';
    }

    private function sqlNoRegalosKds(string $pedidoAlias = 'p', string $itemAlias = 'pi', string $platilloAlias = 'pl'): string
    {
        $parts = [];

        if ($this->hasColumn('es_regalo')) {
            $parts[] = "COALESCE({$pedidoAlias}.es_regalo, 0) = 1";
        }
        if ($this->hasColumn('tipo_entrega')) {
            $parts[] = "LOWER(COALESCE({$pedidoAlias}.tipo_entrega, '')) IN ('gift', 'regalo', 'regalos')";
        }
        if ($this->hasColumn('tipo_origen')) {
            $parts[] = "LOWER(COALESCE({$pedidoAlias}.tipo_origen, '')) IN ('gift', 'regalo', 'regalos')";
        }
        if ($this->hasColumn('tipo_pedido')) {
            $parts[] = "LOWER(COALESCE({$pedidoAlias}.tipo_pedido, '')) IN ('gift', 'regalo', 'regalos')";
        }
        if ($this->hasColumnInTable('rest_pedido_items', 'origen')) {
            $parts[] = "LOWER(COALESCE({$itemAlias}.origen, 'menu')) = 'store'";
        }
        $parts[] = "LOWER(COALESCE({$platilloAlias}.codigo, '')) LIKE 'sg-%'";
        $parts[] = "LOWER(COALESCE({$platilloAlias}.nombre, '')) LIKE 'regalo:%'";
        $parts[] = "LOWER(COALESCE({$itemAlias}.notas, '')) LIKE '%regalo para%'";

        return $parts ? ' AND NOT (' . implode(' OR ', $parts) . ')' : '';
    }

    public function generarFolio(int $restauranteId): string
    {
        $count = $this->queryOne(
            "SELECT COUNT(*) AS c FROM rest_pedidos WHERE restaurante_id = ?",
            [$restauranteId]
        );
        return 'P-' . str_pad((int)($count['c'] ?? 0) + 1, 5, '0', STR_PAD_LEFT);
    }

    /**
     * ¿Este restaurante permite exclusiones ("sin cebolla") y extras?
     *
     * El esquema nuevo movió estas banderas de `rest_restaurantes` a
     * `rest_configuracion`. Se buscan en ese orden y, si no existen en
     * ninguna de las dos, se permiten: bloquear todos los modificadores
     * porque falta una columna deja la venta sin poder capturarse.
     *
     * @return array{sin:bool, extra:bool}
     */
    private function flagsModificadores(int $restauranteId): array
    {
        $rest = (new RestauranteModel())->find($restauranteId) ?: [];
        if (array_key_exists('exclusiones_app_habilitadas', $rest)) {
            return [
                'sin'   => !empty($rest['exclusiones_app_habilitadas']),
                'extra' => !empty($rest['extras_app_habilitados']),
            ];
        }

        $cfg = $this->queryOne(
            "SELECT exclusiones_habilitadas, extras_habilitados FROM rest_configuracion WHERE restaurante_id = ? LIMIT 1",
            [$restauranteId]
        );
        if ($cfg) {
            return [
                'sin'   => !empty($cfg['exclusiones_habilitadas']),
                'extra' => !empty($cfg['extras_habilitados']),
            ];
        }

        return ['sin' => true, 'extra' => true];
    }

    /**
     * Valida los items y les pone precio SIN escribir nada en la base.
     *
     * Existe aparte de `crear()` porque el POS necesita el subtotal antes
     * de poder calcular descuento, propina y total, y esos van en el mismo
     * INSERT del pedido.
     *
     * @return array{items:array, subtotal:float}
     */
    public function prepararItems(int $restauranteId, array $items): array
    {
        $menuModel   = new RestMenuModel();
        $flags       = $this->flagsModificadores($restauranteId);
        $columnaPreparacion = $this->hasColumnInTable('rest_platillos', 'requiere_preparacion');
        $requierePreparacion = false;

        foreach ($items as &$item) {
            $platillo = $menuModel->find((int)$item['platillo_id']);
            if (!$platillo || (int)$platillo['restaurante_id'] !== $restauranteId) {
                throw new \InvalidArgumentException('Platillo no valido para este restaurante.');
            }
            if (!$menuModel->platilloDisponibleParaVenta($restauranteId, (int)$item['platillo_id'])) {
                $faltantes = $menuModel->ingredientesNoDisponiblesParaPlatillo($restauranteId, (int)$item['platillo_id']);
                $nombres = implode(', ', array_column($faltantes, 'nombre'));
                $detalle = $nombres !== '' ? ' Falta: ' . $nombres . '.' : '';
                throw new \InvalidArgumentException('Este platillo no esta disponible en este momento.' . $detalle);
            }
            $item['_requiere_preparacion'] = !$columnaPreparacion || !empty($platillo['requiere_preparacion']);
            $requierePreparacion = $requierePreparacion || $item['_requiere_preparacion'];
            $selecciones = []; $extraUnitario = 0.0; $exclusiones = []; $agrupadas = [];
            foreach ((array)($item['modificadores'] ?? []) as $seleccion) {
                $modId = (int)($seleccion['modificador_id'] ?? 0);
                if ($modId <= 0) continue;
                $agrupadas[$modId] = ($agrupadas[$modId] ?? 0) + max(1, (int)($seleccion['cantidad'] ?? 1));
            }
            foreach ($agrupadas as $modId => $cantidad) {
                $mod = $menuModel->getModificadorValido($restauranteId, (int)$item['platillo_id'], $modId);
                if (!$mod || $cantidad > (int)$mod['max_seleccion']) {
                    throw new \InvalidArgumentException('Modificador invalido o cantidad superior al maximo permitido.');
                }
                if (($mod['tipo'] === 'sin' && !$flags['sin'])
                    || ($mod['tipo'] === 'extra' && !$flags['extra'])) {
                    throw new \InvalidArgumentException('Este tipo de modificador esta deshabilitado para el restaurante.');
                }
                if ($mod['tipo'] === 'sin') { $cantidad = 1; $exclusiones[] = $mod['ingrediente_nombre'] ?: $mod['nombre']; }
                if ($mod['tipo'] === 'extra') $extraUnitario += (float)$mod['precio_extra'] * $cantidad;
                $selecciones[] = ['modificador' => $mod, 'cantidad' => $cantidad];
            }
            $cantidadPlatillos = max(1, (int)($item['cantidad'] ?? 1));
            $item['cantidad'] = $cantidadPlatillos;
            $item['precio_unit'] = round((float)$platillo['precio'] + $extraUnitario, 2);
            $item['subtotal'] = round($item['precio_unit'] * $cantidadPlatillos, 2);
            $item['selecciones_validadas'] = $selecciones;
            $item['exclusiones'] = $exclusiones ? implode(', ', $exclusiones) : null;
            $item['extras'] = json_encode(array_values(array_map(fn($s) => [
                'modificador_id' => (int)$s['modificador']['id'], 'ingrediente_id' => (int)$s['modificador']['ingrediente_id'],
                'nombre' => $s['modificador']['nombre'], 'precio_extra' => (float)$s['modificador']['precio_extra'], 'cantidad' => (int)$s['cantidad'],
            ], array_filter($selecciones, fn($s) => $s['modificador']['tipo'] === 'extra'))), JSON_UNESCAPED_UNICODE);
        }
        unset($item);

        return [
            'items'                  => $items,
            'subtotal'               => round(array_sum(array_column($items, 'subtotal')), 2),
            'requiere_preparacion'   => $requierePreparacion,
        ];
    }

    /**
     * Crea el pedido con sus items y modificadores.
     *
     * Dos detalles importantes para el POS:
     *  - Si ya hay una transacción abierta (el cobro de caja abre la suya
     *    para meter pagos y wallet en el mismo bloque), aquí no se abre otra:
     *    PDO no anida transacciones.
     *  - El folio se arma DESPUÉS del INSERT, a partir del id autoincremental.
     *    El método viejo contaba filas y con dos cajeros vendiendo al mismo
     *    tiempo generaba folios repetidos.
     */
    public function crear(array $data, array $items): int
    {
        $restauranteId = (int)$data['restaurante_id'];

        // El POS ya trae los items con precio (necesita el subtotal antes,
        // para calcular el descuento); el resto del sistema no.
        $primero = $items ? reset($items) : [];
        if (!is_array($primero) || !array_key_exists('selecciones_validadas', $primero)) {
            $preparados = $this->prepararItems($restauranteId, $items);
            $items      = $preparados['items'];
            $subtotal   = $preparados['subtotal'];
            $requierePreparacion = !empty($preparados['requiere_preparacion']);
        } else {
            $subtotal = round(array_sum(array_column($items, 'subtotal')), 2);
            $requierePreparacion = (bool)array_filter(
                $items,
                static fn($item) => !empty($item['_requiere_preparacion'])
            );
        }

        // El pago y la preparación son estados distintos: un pedido pagado
        // inicia pendiente si pasa por Cocina, o listo si todos sus productos
        // son inmediatos. Los estados explícitos de cancelación se respetan.
        if (!array_key_exists('estado', $data)) {
            $data['estado'] = $requierePreparacion ? 'pendiente' : 'listo';
        }

        $transaccionPropia = !$this->db->inTransaction();
        if ($transaccionPropia) {
            $this->db->beginTransaction();
        }

        try {
            $pedidoData = [
                'restaurante_id' => $restauranteId,
                'folio'          => 'TMP',   // se reemplaza abajo con el id real
                'notas'          => $data['notas'] ?? null,
                'subtotal'       => $subtotal,
                'total'          => $data['total'] ?? $subtotal,
            ];

            foreach ([
                'mesa_id',
                'visita_id',
                'mesero_id',
                'estado',
                'tipo_origen',
                'tipo_pedido',
                'tipo_entrega',
                'pedido_origen',
                'direccion_entrega',
                'mobile_usuario_id',
                'cliente_nombre',
                'comprador_nombre',
                'comprador_telefono',
                'comprador_direccion',
                'metodo_pago',
                'pickup_at',
                'app_order_id',
                'pagado_at',
                // Columnas del POS (migración 002_cajero_pos.sql)
                'descuento',
                'promo_code',
                'propina_mxn',
                'iva_mxn',
                'turno_caja_id',
                'cajero_id',
                'pos_client_uuid',
            ] as $column) {
                if (array_key_exists($column, $data) && $this->hasColumn($column)) {
                    $pedidoData[$column] = $data[$column];
                }
            }

            $columns = array_keys($pedidoData);
            $this->execute(
                "INSERT INTO rest_pedidos (`" . implode('`, `', $columns) . "`) VALUES (" . implode(',', array_fill(0, count($columns), '?')) . ")",
                array_values($pedidoData)
            );
            $pedidoId = (int) $this->db->lastInsertId();

            $folio = ($data['folio'] ?? null)
                ?: (($data['folio_prefijo'] ?? 'P') . '-' . str_pad((string)$pedidoId, 5, '0', STR_PAD_LEFT));
            $this->execute("UPDATE rest_pedidos SET folio = ? WHERE id = ?", [$folio, $pedidoId]);

            foreach ($items as $item) {
                $itemValues = [
                    $pedidoId, $item['platillo_id'], $item['cantidad'], $item['precio_unit'],
                    $item['subtotal'], $item['notas'] ?? null, $item['exclusiones'] ?? null,
                    $item['extras'] ?? null,
                ];
                if ($this->hasColumnInTable('rest_pedido_items', 'estado')) {
                    $itemValues[] = !empty($item['_requiere_preparacion']) ? 'pendiente' : 'listo';
                    $this->execute(
                        "INSERT INTO rest_pedido_items (pedido_id, platillo_id, cantidad, precio_unit, subtotal, notas, exclusiones, extras, estado)
                         VALUES (?,?,?,?,?,?,?,?,?)",
                        $itemValues
                    );
                } else {
                    $this->execute(
                        "INSERT INTO rest_pedido_items (pedido_id, platillo_id, cantidad, precio_unit, subtotal, notas, exclusiones, extras)
                         VALUES (?,?,?,?,?,?,?,?)",
                        $itemValues
                    );
                }
                $pedidoItemId = (int)$this->db->lastInsertId();
                foreach ($item['selecciones_validadas'] ?? [] as $seleccion) {
                    $this->execute(
                        "INSERT INTO rest_pedido_item_modificadores (pedido_item_id, modificador_id, cantidad, precio_extra) VALUES (?,?,?,?)",
                        [$pedidoItemId, $seleccion['modificador']['id'], $seleccion['cantidad'], $seleccion['modificador']['precio_extra']]
                    );
                }
            }

            if ($transaccionPropia) {
                $this->db->commit();
            }
            return $pedidoId;
        } catch (\Throwable $e) {
            if ($transaccionPropia && $this->db->inTransaction()) {
                $this->db->rollBack();
            }
            throw $e;
        }
    }

    // ── Caja (POS) ───────────────────────────────────────────────

    /** Un pedido, siempre acotado a su restaurante (aislamiento entre negocios). */
    public function delRestaurante(int $pedidoId, int $restauranteId): ?array
    {
        return $this->queryOne(
            "SELECT * FROM rest_pedidos WHERE id = ? AND restaurante_id = ? LIMIT 1",
            [$pedidoId, $restauranteId]
        );
    }

    /** Idempotencia del cobro: si el mismo uuid ya existe, esto es un reintento. */
    public function porUuidPos(string $uuid, int $restauranteId): ?array
    {
        if (!$this->hasColumn('pos_client_uuid')) {
            return null;
        }
        return $this->queryOne(
            "SELECT * FROM rest_pedidos WHERE pos_client_uuid = ? AND restaurante_id = ? LIMIT 1",
            [$uuid, $restauranteId]
        );
    }

    /** Ata un pedido de la app al turno de caja que lo atendió. */
    public function tomarEnCaja(int $pedidoId, array $campos): bool
    {
        $sets = [];
        $vals = [];
        foreach ($campos as $columna => $valor) {
            if (!$this->hasColumn($columna)) continue;
            $sets[] = "`{$columna}` = ?";
            $vals[] = $valor;
        }
        if (!$sets) return false;

        $vals[] = $pedidoId;
        return $this->execute(
            "UPDATE rest_pedidos SET " . implode(', ', $sets) . " WHERE id = ?",
            $vals
        );
    }

    /** Cancelación desde el POS: motivo obligatorio y rastro de quién la hizo. */
    public function cancelarDesdeCaja(int $pedidoId, string $motivo, int $cajeroId, bool $reembolsoPendiente): void
    {
        $this->tomarEnCaja($pedidoId, [
            'estado'              => 'cancelado',
            'motivo_cancelacion'  => mb_substr($motivo, 0, 255),
            'cancelado_por_id'    => $cajeroId,
            'cancelado_at'        => date('Y-m-d H:i:s'),
            'reembolso_pendiente' => $reembolsoPendiente ? 1 : 0,
        ]);
        $this->cancelarItemsActivos($pedidoId);
    }

    /**
     * Descuenta inventario de una venta ya entregada.
     * Se llama FUERA de la transacción del cobro, igual que en
     * `cambiarEstadoPedido()`: que falte una receta no debe tumbar un cobro.
     */
    public function descontarStockEntrega(int $pedidoId): void
    {
        $row = $this->queryOne("SELECT restaurante_id FROM rest_pedidos WHERE id = ?", [$pedidoId]);
        if (!$row) return;

        try {
            (new RestInventarioModel())->descontarPorOrden($pedidoId, (int)$row['restaurante_id']);
        } catch (\Throwable $e) {
            error_log('[caja] stock pedido=' . $pedidoId . ' ' . $e->getMessage());
        }
    }

    public function getConItems(int $pedidoId, ?int $restauranteId = null, ?string $visibleDesde = null): ?array
    {
        $whereRestaurante = $restauranteId !== null ? ' AND p.restaurante_id = ?' : '';
        $whereVisible = $visibleDesde !== null ? ' AND DATE(' . $this->sqlFechaFinanciera('p') . ') >= ?' : '';
        $params = [$pedidoId];
        if ($restauranteId !== null) {
            $params[] = $restauranteId;
        }
        if ($visibleDesde !== null) {
            $params[] = $visibleDesde;
        }

        $pedido = $this->queryOne(
            "SELECT p.*, m.nombre AS mesa_nombre, u.nombre AS mesero_nombre
             FROM rest_pedidos p
             LEFT JOIN rest_mesas m ON m.id = p.mesa_id
             LEFT JOIN usuarios u ON u.id = p.mesero_id
             WHERE p.id = ?{$whereRestaurante}{$whereVisible}",
            $params
        );
        if (!$pedido) return null;
        $pedido['items'] = $this->query(
            "SELECT pi.*, pl.nombre AS platillo_nombre
             FROM rest_pedido_items pi
             JOIN rest_platillos pl ON pl.id = pi.platillo_id
             WHERE pi.pedido_id = ?",
            [$pedidoId]
        );
        foreach ($pedido['items'] as &$item) {
            $item['modificadores'] = $this->query(
                "SELECT pim.cantidad, pim.precio_extra, m.nombre, m.tipo, m.ingrediente_id, m.cantidad_unidad, m.unidad
                 FROM rest_pedido_item_modificadores pim
                 JOIN rest_modificadores m ON m.id = pim.modificador_id
                 WHERE pim.pedido_item_id = ? ORDER BY m.tipo, m.nombre",
                [(int)$item['id']]
            );
        }
        unset($item);
        return $pedido;
    }

    /**
     * Igual que getConItems() pero sin JOIN a rest_mesas/mesero_id — columnas que
     * no existen en el esquema marketplace (pickup/delivery, idactivo_cafeteq.sql).
     */
    public function getConItemsSinMesas(int $pedidoId, ?int $restauranteId = null, ?string $visibleDesde = null): ?array
    {
        $whereRestaurante = $restauranteId !== null ? ' AND p.restaurante_id = ?' : '';
        $whereVisible = $visibleDesde !== null ? ' AND DATE(' . $this->sqlFechaFinanciera('p') . ') >= ?' : '';
        $params = [$pedidoId];
        if ($restauranteId !== null) {
            $params[] = $restauranteId;
        }
        if ($visibleDesde !== null) {
            $params[] = $visibleDesde;
        }

        $pedido = $this->queryOne(
            "SELECT p.* FROM rest_pedidos p WHERE p.id = ?{$whereRestaurante}{$whereVisible}",
            $params
        );
        if (!$pedido) return null;
        $pedido['items'] = $this->query(
            "SELECT pi.*, pl.nombre AS platillo_nombre
             FROM rest_pedido_items pi
             JOIN rest_platillos pl ON pl.id = pi.platillo_id
             WHERE pi.pedido_id = ?",
            [$pedidoId]
        );
        foreach ($pedido['items'] as &$item) {
            $item['modificadores'] = $this->query(
                "SELECT pim.cantidad, pim.precio_extra, m.nombre, m.tipo, m.ingrediente_id, m.cantidad_unidad, m.unidad
                 FROM rest_pedido_item_modificadores pim
                 JOIN rest_modificadores m ON m.id = pim.modificador_id
                 WHERE pim.pedido_item_id = ? ORDER BY m.tipo, m.nombre",
                [(int)$item['id']]
            );
        }
        unset($item);
        return $pedido;
    }

    /** Igual que listar() pero sin JOIN a rest_mesas/mesero_id. */
    public function listarSinMesas(int $restauranteId, int $page = 1, string $estado = '', ?string $visibleDesde = null): array
    {
        $noStore = $this->sqlNoStore('p');
        $params = [$restauranteId];
        $where = '';
        if ($estado !== '') {
            $where = 'AND p.estado = ?';
            $params[] = $estado;
        }
        if ($visibleDesde !== null) {
            $where .= ' AND DATE(' . $this->sqlFechaFinanciera('p') . ') >= ?';
            $params[] = $visibleDesde;
        }
        $sql = "SELECT p.*
                FROM rest_pedidos p
                WHERE p.restaurante_id = ? $where
                $noStore
                ORDER BY p.created_at DESC";
        return $this->paginate($sql, $params, $page);
    }

    /** Cola de cocina: pedidos activos con sus items, sin JOIN a rest_mesas. */
    public function getColaCocina(int $restauranteId): array
    {
        $filtrarPreparacion = $this->hasColumnInTable('rest_platillos', 'requiere_preparacion');
        $pedidosConPreparacion = $filtrarPreparacion
            ? "AND EXISTS (
                   SELECT 1
                   FROM rest_pedido_items pix
                   JOIN rest_platillos plx ON plx.id = pix.platillo_id
                   WHERE pix.pedido_id = p.id
                     AND pix.estado <> 'cancelado'
                     AND COALESCE(plx.requiere_preparacion, 1) = 1
               )"
            : '';
        $soloItemsDePreparacion = $filtrarPreparacion
            ? 'AND COALESCE(pl.requiere_preparacion, 1) = 1'
            : '';

        $pedidos = $this->query(
            "SELECT p.id, p.folio, p.created_at, p.notas AS pedido_notas,
                    p.tipo_pedido, p.tipo_entrega, p.direccion_entrega,
                    TIMESTAMPDIFF(MINUTE, p.created_at, NOW()) AS minutos_espera
             FROM rest_pedidos p
             WHERE p.restaurante_id = ?
               AND p.estado IN ('pendiente','en_preparacion','listo')
               {$pedidosConPreparacion}
             ORDER BY p.created_at ASC",
            [$restauranteId]
        );
        foreach ($pedidos as &$pedido) {
            $pedido['items'] = $this->query(
                "SELECT pi.id, pi.platillo_id, pi.cantidad, pi.notas, pi.estado,
                        pl.nombre AS platillo_nombre, pl.tiempo_preparacion_min
                 FROM rest_pedido_items pi
                 JOIN rest_platillos pl ON pl.id = pi.platillo_id
                 WHERE pi.pedido_id = ? AND pi.estado <> 'cancelado'
                   {$soloItemsDePreparacion}
                 ORDER BY pi.id ASC",
                [(int)$pedido['id']]
            );
            foreach ($pedido['items'] as &$item) {
                $item['extras'] = $this->query(
                    "SELECT mo.nombre, pim.cantidad
                     FROM rest_pedido_item_modificadores pim
                     JOIN rest_modificadores mo ON mo.id = pim.modificador_id
                     WHERE pim.pedido_item_id = ?",
                    [(int)$item['id']]
                );
            }
            unset($item);
        }
        unset($pedido);
        return $pedidos;
    }

    public function getActivosPorMesa(int $mesaId): array
    {
        return $this->query(
            "SELECT p.*, m.nombre AS mesa_nombre
             FROM rest_pedidos p
             LEFT JOIN rest_mesas m ON m.id = p.mesa_id
             WHERE p.mesa_id = ? AND p.estado NOT IN ('entregado','cancelado')
             ORDER BY p.created_at DESC",
            [$mesaId]
        );
    }

    private function sqlAreaKds(string $area, string $platilloAlias = 'pl', string $categoriaAlias = 'cm'): string
    {
        $esBebida = "(LOWER(COALESCE({$categoriaAlias}.nombre, '')) LIKE '%bebida%'
                     OR COALESCE({$platilloAlias}.codigo, '') REGEXP '^B[0-9]+$')";

        return $area === 'barra' ? " AND {$esBebida}" : " AND NOT {$esBebida}";
    }

    public function getKitchenQueue(int $restauranteId, string $area = 'cocina'): array
    {
        $noStore = $this->sqlNoStore('p');
        $noRegalosKds = $this->sqlNoRegalosKds('p', 'pi', 'pl');
        $areaWhere = $this->sqlAreaKds($area);
        $tipoPedidoSelect = $this->hasColumn('tipo_pedido') ? "p.tipo_pedido" : "NULL AS tipo_pedido";
        $tipoEntregaSelect = $this->hasColumn('tipo_entrega') ? "p.tipo_entrega" : "NULL AS tipo_entrega";
        $direccionEntregaSelect = $this->hasColumn('direccion_entrega') ? "p.direccion_entrega" : "NULL AS direccion_entrega";

        // Formato ingredientes_raw (separador ||, campos |):
        //   codigo | nombre | tipo | cantidad | unidad | notas | es_informativo
        //   - tipo: materia_prima | guarnicion | bebida | otro
        //   - es_informativo=1 → no descuenta stock, sólo muestra al chef
        return $this->query(
            "SELECT p.id, p.folio, p.created_at, p.notas AS pedido_notas,
                    TIMESTAMPDIFF(MINUTE, p.created_at, NOW()) AS minutos_espera,
                    m.nombre AS mesa_nombre,
                    {$tipoPedidoSelect},
                    {$tipoEntregaSelect},
                    {$direccionEntregaSelect},
                    pi.id AS item_id, pi.platillo_id, pi.cantidad, pi.notas AS item_notas, pi.estado AS item_estado,
                    pi.exclusiones,
                    (SELECT GROUP_CONCAT(CONCAT(mo.nombre, ' x', pim.cantidad) SEPARATOR ', ')
                       FROM rest_pedido_item_modificadores pim
                       JOIN rest_modificadores mo ON mo.id = pim.modificador_id
                      WHERE pim.pedido_item_id = pi.id AND mo.tipo = 'extra') AS extras_display,
                    pl.nombre AS platillo_nombre, pl.tiempo_preparacion_min,
                    COALESCE(pl.codigo,'') AS platillo_codigo,
                    (SELECT r.notas
                       FROM rest_recetas r
                      WHERE r.platillo_id = pi.platillo_id LIMIT 1) AS instrucciones_armado,
                    (SELECT GROUP_CONCAT(
                                CONCAT_WS('|',
                                    COALESCE(ing.codigo, CONCAT('#', ing.id)),
                                    ing.nombre,
                                    COALESCE(ing.tipo, 'otro'),
                                    ri.cantidad,
                                    ri.unidad,
                                    COALESCE(ri.notas,''),
                                    COALESCE(ri.es_informativo, 0)
                                )
                                ORDER BY
                                    FIELD(COALESCE(ing.tipo,'otro'),'materia_prima','guarnicion','bebida','otro'),
                                    COALESCE(ing.codigo, ing.nombre)
                                SEPARATOR '||'
                            )
                     FROM rest_recetas r
                     JOIN (
                         SELECT receta_id, ingrediente_id,
                                COALESCE(
                                    MIN(CASE WHEN es_informativo = 0 THEN id END),
                                    MIN(id)
                                ) AS best_id
                         FROM rest_receta_ingredientes
                         GROUP BY receta_id, ingrediente_id
                     ) ri_dedup ON ri_dedup.receta_id = r.id
                     JOIN rest_receta_ingredientes ri ON ri.id = ri_dedup.best_id
                     JOIN rest_ingredientes ing ON ing.id = ri.ingrediente_id
                     WHERE r.platillo_id = pi.platillo_id
                       AND NOT EXISTS (
                           SELECT 1 FROM rest_pedido_item_modificadores pim_ex
                           JOIN rest_modificadores mod_ex ON mod_ex.id=pim_ex.modificador_id
                           WHERE pim_ex.pedido_item_id=pi.id AND mod_ex.tipo='sin'
                             AND mod_ex.ingrediente_id=ri.ingrediente_id
                       )) AS ingredientes_raw
             FROM rest_pedidos p
             JOIN rest_pedido_items pi ON pi.pedido_id = p.id
             JOIN rest_platillos pl ON pl.id = pi.platillo_id
             LEFT JOIN rest_categorias_menu cm ON cm.id = pl.categoria_id
             LEFT JOIN rest_mesas m ON m.id = p.mesa_id
             WHERE p.restaurante_id = ?
               AND p.estado NOT IN ('cancelado', 'entregado')
               AND pi.estado IN ('pendiente','en_preparacion')
               $noStore
               $noRegalosKds
               $areaWhere
             ORDER BY p.created_at ASC, pi.id ASC",
            [$restauranteId]
        );
    }

    public function cancelarItemsActivos(int $pedidoId): void
    {
        $this->execute(
            "UPDATE rest_pedido_items SET estado = 'cancelado' WHERE pedido_id = ? AND estado IN ('pendiente', 'en_preparacion', 'listo')",
            [$pedidoId]
        );
    }

    public function cambiarEstadoPedido(int $pedidoId, string $estado): bool
    {
        // Capturar estado actual + restaurante_id para detectar transición a 'entregado'.
        $row = $this->queryOne(
            "SELECT estado, restaurante_id FROM rest_pedidos WHERE id = ?",
            [$pedidoId]
        );
        $estadoPrevio = $row['estado'] ?? null;

        $ok = $this->execute(
            "UPDATE rest_pedidos SET estado = ? WHERE id = ?",
            [$estado, $pedidoId]
        );

        // Descontar stock SOLO al transitar a 'entregado' (idempotente en el modelo).
        if ($ok && $estado === 'entregado' && $estadoPrevio !== 'entregado' && $row) {
            try {
                (new RestInventarioModel())->descontarPorOrden(
                    $pedidoId,
                    (int)$row['restaurante_id']
                );
            } catch (\Throwable $e) {
                error_log('[RestauranteStock] entrega pedido=' . $pedidoId . ' ' . $e->getMessage());
            }
        }

        return $ok;
    }

    public function cambiarEstadoItem(int $itemId, string $estado): bool
    {
        return $this->execute(
            "UPDATE rest_pedido_items SET estado = ? WHERE id = ?",
            [$estado, $itemId]
        );
    }

    public function marcarItemsEntregados(int $pedidoId): void
    {
        if (!$this->hasColumnInTable('rest_pedido_items', 'estado')) return;
        $this->execute(
            "UPDATE rest_pedido_items
             SET estado = 'entregado'
             WHERE pedido_id = ? AND estado = 'listo'",
            [$pedidoId]
        );
    }

    // Marca como entregados todos los pedidos e items aún no entregados/cancelados
    // de una visita. Se llama al confirmar el pago: si se cobró ya se entregó.
    public function marcarVisitaEntregada(int $visitaId): void
    {
        $pedidos = $this->query(
            "SELECT id FROM rest_pedidos WHERE visita_id = ? AND estado NOT IN ('entregado','cancelado')",
            [$visitaId]
        );
        foreach ($pedidos as $p) {
            $this->cambiarEstadoPedido((int)$p['id'], 'entregado');
        }
        $this->execute(
            "UPDATE rest_pedido_items pi
             JOIN rest_pedidos p ON p.id = pi.pedido_id
             SET pi.estado = 'entregado'
             WHERE p.visita_id = ? AND pi.estado NOT IN ('entregado','cancelado')",
            [$visitaId]
        );
    }

    public function getByVisita(int $visitaId): array
    {
        return $this->query(
            "SELECT p.*, m.nombre AS mesa_nombre
             FROM rest_pedidos p
             LEFT JOIN rest_mesas m ON m.id = p.mesa_id
             WHERE p.visita_id = ? AND p.estado != 'cancelado'
             ORDER BY p.created_at ASC",
            [$visitaId]
        );
    }

    public function listar(int $restauranteId, int $page = 1, string $estado = '', ?string $visibleDesde = null): array
    {
        $noStore = $this->sqlNoStore('p');
        $params = [$restauranteId];
        $where = '';
        if ($estado !== '') {
            $where = 'AND p.estado = ?';
            $params[] = $estado;
        }
        if ($visibleDesde !== null) {
            $where .= ' AND DATE(' . $this->sqlFechaFinanciera('p') . ') >= ?';
            $params[] = $visibleDesde;
        }
        $sql = "SELECT p.*, m.nombre AS mesa_nombre, u.nombre AS mesero_nombre
                FROM rest_pedidos p
                LEFT JOIN rest_mesas m ON m.id = p.mesa_id
                LEFT JOIN usuarios u ON u.id = p.mesero_id
                WHERE p.restaurante_id = ? $where
                $noStore
                ORDER BY p.created_at DESC";
        return $this->paginate($sql, $params, $page);
    }

    public function listarStore(int $restauranteId, int $page = 1, string $estado = '', ?string $visibleDesde = null): array
    {
        $soloStore = $this->sqlSoloStore('p');
        $params = [$restauranteId];
        $where = '';
        if ($estado !== '') {
            $where = 'AND p.estado = ?';
            $params[] = $estado;
        }
        if ($visibleDesde !== null) {
            $where .= ' AND DATE(' . $this->sqlFechaFinanciera('p') . ') >= ?';
            $params[] = $visibleDesde;
        }

        $sql = "SELECT p.*, m.nombre AS mesa_nombre, u.nombre AS mesero_nombre
                FROM rest_pedidos p
                LEFT JOIN rest_mesas m ON m.id = p.mesa_id
                LEFT JOIN usuarios u ON u.id = p.mesero_id
                WHERE p.restaurante_id = ? $where
                $soloStore
                ORDER BY p.created_at DESC";
        return $this->paginate($sql, $params, $page);
    }

    public function cambiarEstadoStore(int $pedidoId, int $restauranteId, string $estado): bool
    {
        if (!$this->soportaTipoOrigen()) {
            return false;
        }

        return $this->execute(
            "UPDATE rest_pedidos
                SET estado = ?
              WHERE id = ?
                AND restaurante_id = ?
                AND LOWER(COALESCE(tipo_origen, '')) = 'store'",
            [$estado, $pedidoId, $restauranteId]
        );
    }

    public function getPedidoIdPorItem(int $itemId): ?int
    {
        $row = $this->queryOne(
            "SELECT pedido_id FROM rest_pedido_items WHERE id = ?",
            [$itemId]
        );
        return $row ? (int)$row['pedido_id'] : null;
    }
}
