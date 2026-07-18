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
            $stmt = $this->db->prepare("SHOW COLUMNS FROM `{$table}` LIKE ?");
            $stmt->execute([$column]);
            return self::$columnCache[$key] = (bool)$stmt->fetch(PDO::FETCH_ASSOC);
        } catch (\Throwable $e) {
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

    public function crear(array $data, array $items): int
    {
        $menuModel = new RestMenuModel();
        $restauranteId = (int)$data['restaurante_id'];
        $restaurante = (new RestauranteModel())->find($restauranteId) ?: [];
        foreach ($items as &$item) {
            $platillo = $menuModel->find((int)$item['platillo_id']);
            if (!$platillo || (int)$platillo['restaurante_id'] !== $restauranteId) {
                throw new \InvalidArgumentException('Platillo no valido para este restaurante.');
            }
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
                if (($mod['tipo'] === 'sin' && empty($restaurante['exclusiones_app_habilitadas']))
                    || ($mod['tipo'] === 'extra' && empty($restaurante['extras_app_habilitados']))) {
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
        $this->db->beginTransaction();
        try {
            $folio = $this->generarFolio($data['restaurante_id']);
            $subtotal = array_sum(array_column($items, 'subtotal'));

            $pedidoData = [
                'restaurante_id' => $data['restaurante_id'],
                'mesa_id' => $data['mesa_id'] ?? null,
                'visita_id' => $data['visita_id'] ?? null,
                'mesero_id' => $data['mesero_id'] ?? null,
                'folio' => $folio,
                'notas' => $data['notas'] ?? null,
                'subtotal' => $subtotal,
                'total' => $data['total'] ?? $subtotal,
            ];

            foreach ([
                'tipo_origen',
                'tipo_pedido',
                'tipo_entrega',
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

            foreach ($items as $item) {
                $this->execute(
                    "INSERT INTO rest_pedido_items (pedido_id, platillo_id, cantidad, precio_unit, subtotal, notas, exclusiones, extras)
                     VALUES (?,?,?,?,?,?,?,?)",
                    [$pedidoId, $item['platillo_id'], $item['cantidad'], $item['precio_unit'], $item['subtotal'], $item['notas'] ?? null, $item['exclusiones'] ?? null, $item['extras'] ?? null]
                );
                $pedidoItemId = (int)$this->db->lastInsertId();
                foreach ($item['selecciones_validadas'] ?? [] as $seleccion) {
                    $this->execute(
                        "INSERT INTO rest_pedido_item_modificadores (pedido_item_id, modificador_id, cantidad, precio_extra) VALUES (?,?,?,?)",
                        [$pedidoItemId, $seleccion['modificador']['id'], $seleccion['cantidad'], $seleccion['modificador']['precio_extra']]
                    );
                }
            }

            $this->db->commit();
            return $pedidoId;
        } catch (\Throwable $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    public function getConItems(int $pedidoId, ?int $restauranteId = null): ?array
    {
        $whereRestaurante = $restauranteId !== null ? ' AND p.restaurante_id = ?' : '';
        $params = [$pedidoId];
        if ($restauranteId !== null) {
            $params[] = $restauranteId;
        }

        $pedido = $this->queryOne(
            "SELECT p.*, m.nombre AS mesa_nombre, u.nombre AS mesero_nombre
             FROM rest_pedidos p
             LEFT JOIN rest_mesas m ON m.id = p.mesa_id
             LEFT JOIN usuarios u ON u.id = p.mesero_id
             WHERE p.id = ?{$whereRestaurante}",
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

    public function listar(int $restauranteId, int $page = 1, string $estado = ''): array
    {
        $noStore = $this->sqlNoStore('p');
        $params = [$restauranteId];
        $where = '';
        if ($estado !== '') {
            $where = 'AND p.estado = ?';
            $params[] = $estado;
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

    public function listarStore(int $restauranteId, int $page = 1, string $estado = ''): array
    {
        $soloStore = $this->sqlSoloStore('p');
        $params = [$restauranteId];
        $where = '';
        if ($estado !== '') {
            $where = 'AND p.estado = ?';
            $params[] = $estado;
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
}
