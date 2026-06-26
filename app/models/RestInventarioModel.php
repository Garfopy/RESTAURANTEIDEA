<?php
class RestInventarioModel extends BaseModel
{
    protected string $table = 'rest_ingredientes';
    private static ?array $columnCache = null;

    private function getColumns(): array
    {
        if (self::$columnCache !== null) {
            return self::$columnCache;
        }

        $stmt = $this->db->prepare(
            "SELECT COLUMN_NAME
               FROM information_schema.COLUMNS
              WHERE TABLE_SCHEMA = DATABASE()
                AND TABLE_NAME = ?"
        );
        $stmt->execute([$this->table]);
        self::$columnCache = array_fill_keys($stmt->fetchAll(PDO::FETCH_COLUMN), true);

        return self::$columnCache;
    }

    private function filterExistingColumns(array $data): array
    {
        $columns = $this->getColumns();
        return array_filter(
            $data,
            static fn($column) => isset($columns[$column]),
            ARRAY_FILTER_USE_KEY
        );
    }

    public function insert(array $data): int
    {
        return parent::insert($this->filterExistingColumns($data));
    }

    public function update(int $id, array $data): bool
    {
        return parent::update($id, $this->filterExistingColumns($data));
    }

    public function getByRestaurante(int $restauranteId, bool $soloActivos = false): array
    {
        $where = $soloActivos ? 'AND activo = 1' : '';
        return $this->query(
            "SELECT * FROM rest_ingredientes WHERE restaurante_id = ? $where ORDER BY nombre",
            [$restauranteId]
        );
    }

    public function alertasStockBajo(int $restauranteId): array
    {
        return $this->query(
            "SELECT * FROM rest_ingredientes
             WHERE restaurante_id = ? AND activo = 1 AND stock <= stock_minimo
             ORDER BY (stock_minimo - stock) DESC",
            [$restauranteId]
        );
    }

    public function registrarMovimiento(array $data): void
    {
        $this->execute(
            "INSERT INTO rest_movimientos_inventario
             (restaurante_id, ingrediente_id, tipo, cantidad, stock_antes, stock_despues, motivo, referencia, usuario_id)
             VALUES (?,?,?,?,?,?,?,?,?)",
            [
                $data['restaurante_id'],
                $data['ingrediente_id'],
                $data['tipo'],
                $data['cantidad'],
                $data['stock_antes'],
                $data['stock_despues'],
                $data['motivo'] ?? null,
                $data['referencia'] ?? null,
                $data['usuario_id'] ?? null,
            ]
        );
    }

    public function ajustarStock(int $ingredienteId, float $delta, string $tipo, string $motivo, ?string $ref, int $restauranteId, ?int $usuarioId): void
    {
        $ing = $this->find($ingredienteId);
        if (!$ing) return;

        $stockAntes = (float) $ing['stock'];
        $stockDespues = max(0, $stockAntes + $delta);

        $this->execute(
            "UPDATE rest_ingredientes SET stock = ? WHERE id = ?",
            [$stockDespues, $ingredienteId]
        );

        $this->registrarMovimiento([
            'restaurante_id' => $restauranteId,
            'ingrediente_id' => $ingredienteId,
            'tipo'           => $tipo,
            'cantidad'       => abs($delta),
            'stock_antes'    => $stockAntes,
            'stock_despues'  => $stockDespues,
            'motivo'         => $motivo,
            'referencia'     => $ref,
            'usuario_id'     => $usuarioId,
        ]);
    }

    private function referenciaTieneMovimientos(string $ref): bool
    {
        $row = $this->queryOne(
            "SELECT COUNT(*) AS c FROM rest_movimientos_inventario WHERE referencia = ?",
            [$ref]
        );
        return $row && (int)$row['c'] > 0;
    }

    public static function convertirUnidad(float $cantidad, string $desde, string $hasta): float
    {
        $d = strtolower(trim($desde));
        $h = strtolower(trim($hasta));
        if ($d === $h) return $cantidad;
        if ($d === 'g'  && $h === 'kg') return $cantidad / 1000;
        if ($d === 'kg' && $h === 'g')  return $cantidad * 1000;
        if ($d === 'mg' && $h === 'kg') return $cantidad / 1_000_000;
        if ($d === 'mg' && $h === 'g')  return $cantidad / 1000;
        if ($d === 'ml' && $h === 'l')  return $cantidad / 1000;
        if ($d === 'l'  && $h === 'ml') return $cantidad * 1000;
        // Same family but unknown direction → 1:1 (chef's responsibility)
        return $cantidad;
    }

    public function descontarPorOrden(int $pedidoId, int $restauranteId, ?int $usuarioId = null): void
    {
        // Idempotencia: si ya se descontó stock para este pedido, no repetir.
        $ref = 'rest_pedido:' . $pedidoId;
        if ($this->referenciaTieneMovimientos($ref)) {
            return;
        }

        $items = $this->query(
            "SELECT pi.id AS pedido_item_id, pi.cantidad AS cantidad_pedida, pi.exclusiones,
                    ri.ingrediente_id, ri.cantidad AS cant_receta, ri.unidad,
                    rec.porciones_base, i.unidad_principal, i.nombre AS ingrediente_nombre
             FROM rest_pedido_items pi
             JOIN rest_platillos pl ON pl.id = pi.platillo_id
             JOIN rest_recetas rec ON rec.platillo_id = pl.id
             JOIN rest_receta_ingredientes ri ON ri.receta_id = rec.id
             JOIN rest_ingredientes i ON i.id = ri.ingrediente_id
             WHERE pi.pedido_id = ?
               AND COALESCE(ri.es_informativo, 0) = 0
               AND NOT EXISTS (
                   SELECT 1 FROM rest_pedido_item_modificadores pim
                   JOIN rest_modificadores m ON m.id=pim.modificador_id
                   WHERE pim.pedido_item_id=pi.id AND m.tipo='sin' AND m.ingrediente_id=ri.ingrediente_id
               )",
            [$pedidoId]
        );

        foreach ($items as $item) {
            if ($this->referenciaTieneMovimientos('rest_item:' . (int)$item['pedido_item_id'])) {
                continue;
            }

            // Si el comensal excluyó este ingrediente, no se abrió el paquete → no descontar
            if (!empty($item['exclusiones'])) {
                $excluidos = array_map('trim', explode(',', $item['exclusiones']));
                if (in_array($item['ingrediente_nombre'], $excluidos, true)) {
                    continue;
                }
            }

            $cantidadEnUnidadReceta = ($item['cant_receta'] / max(1, $item['porciones_base'])) * $item['cantidad_pedida'];
            $descuento = self::convertirUnidad($cantidadEnUnidadReceta, $item['unidad'], $item['unidad_principal']);
            $this->ajustarStock(
                (int) $item['ingrediente_id'],
                -$descuento,
                'salida',
                'Consumo pedido restaurante',
                'rest_pedido:' . $pedidoId,
                $restauranteId,
                $usuarioId
            );
        }

        // ── Platillos sin ingredientes de receta (bebidas, dulces, postres) ────
        // Migration 036 crea una receta vacía para TODOS los platillos, pero
        // bebidas/postres no tienen filas en rest_receta_ingredientes.
        // Detectamos platillos cuya receta tiene 0 ingredientes no-informativos
        // y buscamos el ingrediente correspondiente por su codigo (B*, DP*).
        $extras = $this->query(
            "SELECT pi.id AS pedido_item_id, pi.cantidad AS cantidad_pedida, pim.cantidad AS cantidad_extra,
                    m.ingrediente_id, m.cantidad_unidad, m.unidad, i.unidad_principal
             FROM rest_pedido_items pi
             JOIN rest_pedido_item_modificadores pim ON pim.pedido_item_id = pi.id
             JOIN rest_modificadores m ON m.id = pim.modificador_id AND m.tipo = 'extra'
             JOIN rest_ingredientes i ON i.id = m.ingrediente_id
             WHERE pi.pedido_id = ?",
            [$pedidoId]
        );
        foreach ($extras as $extra) {
            if ($this->referenciaTieneMovimientos('rest_item:' . (int)$extra['pedido_item_id'])) {
                continue;
            }

            $cantidad = (float)$extra['cantidad_unidad'] * (int)$extra['cantidad_extra'] * (int)$extra['cantidad_pedida'];
            $descuento = self::convertirUnidad($cantidad, $extra['unidad'], $extra['unidad_principal']);
            $this->ajustarStock((int)$extra['ingrediente_id'], -$descuento, 'salida', 'Extra de pedido restaurante', $ref, $restauranteId, $usuarioId);
        }

        $sinReceta = $this->query(
            "SELECT pi.id AS pedido_item_id,
                    pi.cantidad AS cantidad_pedida,
                    i.id        AS ingrediente_id,
                    i.nombre    AS ingrediente_nombre,
                    i.unidad_principal
             FROM rest_pedido_items pi
             JOIN rest_platillos pl ON pl.id = pi.platillo_id
             JOIN rest_ingredientes i
                  ON i.restaurante_id = ?
                 AND TRIM(i.codigo) != ''
                 AND TRIM(i.codigo) = TRIM(COALESCE(pl.codigo, ''))
                 AND i.activo = 1
             LEFT JOIN rest_recetas rec
                  ON rec.platillo_id = pl.id
             LEFT JOIN rest_receta_ingredientes ri_check
                  ON ri_check.receta_id = rec.id
                 AND COALESCE(ri_check.es_informativo, 0) = 0
             WHERE pi.pedido_id = ?
               AND ri_check.id IS NULL
               AND COALESCE(TRIM(pl.codigo), '') != ''",
            [$restauranteId, $pedidoId]
        );

        foreach ($sinReceta as $item) {
            if ($this->referenciaTieneMovimientos('rest_item:' . (int)$item['pedido_item_id'])) {
                continue;
            }

            $this->ajustarStock(
                (int) $item['ingrediente_id'],
                -(float) $item['cantidad_pedida'],
                'salida',
                'Consumo pedido restaurante',
                'rest_pedido:' . $pedidoId,
                $restauranteId,
                $usuarioId
            );
        }
    }

    public function descontarPorItem(int $pedidoItemId, int $restauranteId, ?int $usuarioId = null): void
    {
        $ref = 'rest_item:' . $pedidoItemId;
        if ($this->referenciaTieneMovimientos($ref)) {
            return;
        }

        $item = $this->queryOne(
            "SELECT pi.id, pi.pedido_id, pi.platillo_id, pi.cantidad AS cantidad_pedida, pi.exclusiones
             FROM rest_pedido_items pi
             JOIN rest_pedidos p ON p.id = pi.pedido_id
             WHERE pi.id = ? AND p.restaurante_id = ?
             LIMIT 1",
            [$pedidoItemId, $restauranteId]
        );
        if (!$item) {
            return;
        }

        $ingredientes = $this->query(
            "SELECT ri.ingrediente_id, ri.cantidad AS cant_receta, ri.unidad,
                    rec.porciones_base, i.unidad_principal, i.nombre AS ingrediente_nombre
             FROM rest_recetas rec
             JOIN rest_receta_ingredientes ri ON ri.receta_id = rec.id
             JOIN rest_ingredientes i ON i.id = ri.ingrediente_id
             WHERE rec.platillo_id = ?
               AND COALESCE(ri.es_informativo, 0) = 0
               AND NOT EXISTS (
                   SELECT 1 FROM rest_pedido_item_modificadores pim
                   JOIN rest_modificadores m ON m.id = pim.modificador_id
                   WHERE pim.pedido_item_id = ? AND m.tipo = 'sin' AND m.ingrediente_id = ri.ingrediente_id
               )",
            [(int)$item['platillo_id'], $pedidoItemId]
        );

        foreach ($ingredientes as $ing) {
            if (!empty($item['exclusiones'])) {
                $excluidos = array_map('trim', explode(',', $item['exclusiones']));
                if (in_array($ing['ingrediente_nombre'], $excluidos, true)) {
                    continue;
                }
            }

            $cantidadEnUnidadReceta = ((float)$ing['cant_receta'] / max(1, (float)$ing['porciones_base'])) * (int)$item['cantidad_pedida'];
            $descuento = self::convertirUnidad($cantidadEnUnidadReceta, $ing['unidad'], $ing['unidad_principal']);
            $this->ajustarStock(
                (int)$ing['ingrediente_id'],
                -$descuento,
                'salida',
                'Preparacion pedido #' . (int)$item['pedido_id'],
                $ref,
                $restauranteId,
                $usuarioId
            );
        }

        $extras = $this->query(
            "SELECT pim.cantidad AS cantidad_extra,
                    m.ingrediente_id, m.cantidad_unidad, m.unidad, i.unidad_principal
             FROM rest_pedido_item_modificadores pim
             JOIN rest_modificadores m ON m.id = pim.modificador_id AND m.tipo = 'extra'
             JOIN rest_ingredientes i ON i.id = m.ingrediente_id
             WHERE pim.pedido_item_id = ?",
            [$pedidoItemId]
        );
        foreach ($extras as $extra) {
            $cantidad = (float)$extra['cantidad_unidad'] * (int)$extra['cantidad_extra'] * (int)$item['cantidad_pedida'];
            $descuento = self::convertirUnidad($cantidad, $extra['unidad'], $extra['unidad_principal']);
            $this->ajustarStock(
                (int)$extra['ingrediente_id'],
                -$descuento,
                'salida',
                'Extra pedido #' . (int)$item['pedido_id'],
                $ref,
                $restauranteId,
                $usuarioId
            );
        }

        if (!empty($ingredientes)) {
            return;
        }

        $sinReceta = $this->queryOne(
            "SELECT i.id AS ingrediente_id
             FROM rest_platillos pl
             JOIN rest_ingredientes i
                  ON i.restaurante_id = ?
                 AND TRIM(i.codigo) != ''
                 AND TRIM(i.codigo) = TRIM(COALESCE(pl.codigo, ''))
                 AND i.activo = 1
             LEFT JOIN rest_recetas rec
                  ON rec.platillo_id = pl.id
             LEFT JOIN rest_receta_ingredientes ri_check
                  ON ri_check.receta_id = rec.id
                 AND COALESCE(ri_check.es_informativo, 0) = 0
             WHERE pl.id = ?
               AND ri_check.id IS NULL
               AND COALESCE(TRIM(pl.codigo), '') != ''
             LIMIT 1",
            [$restauranteId, (int)$item['platillo_id']]
        );

        if ($sinReceta && !empty($sinReceta['ingrediente_id'])) {
            $this->ajustarStock(
                (int)$sinReceta['ingrediente_id'],
                -(float)$item['cantidad_pedida'],
                'salida',
                'Preparacion pedido #' . (int)$item['pedido_id'],
                $ref,
                $restauranteId,
                $usuarioId
            );
        }
    }

    public function getMovimientos(int $restauranteId, int $page = 1): array
    {
        $sql = "SELECT mv.*, i.nombre AS ingrediente_nombre, i.unidad_principal, u.nombre AS usuario_nombre
                FROM rest_movimientos_inventario mv
                JOIN rest_ingredientes i ON i.id = mv.ingrediente_id
                LEFT JOIN usuarios u ON u.id = mv.usuario_id
                WHERE mv.restaurante_id = ?
                ORDER BY mv.created_at DESC";
        return $this->paginate($sql, [$restauranteId], $page);
    }

    /**
     * Consumo histórico agrupado por ingrediente y día.
     * Alimenta al RestForecastService para los cálculos de forecast.
     * Solo considera tipo='salida' (consumo real, excluye entradas/ajustes).
     *
     * @return array  [['ingrediente_id', 'dia', 'total_consumido'], ...]
     */
    public function getConsumoHistorico(int $restauranteId, int $dias = 30): array
    {
        return $this->query(
            "SELECT ingrediente_id,
                    DATE(created_at) AS dia,
                    SUM(cantidad)    AS total_consumido
             FROM rest_movimientos_inventario
             WHERE restaurante_id = ?
               AND tipo           = 'salida'
               AND created_at    >= DATE_SUB(NOW(), INTERVAL ? DAY)
             GROUP BY ingrediente_id, DATE(created_at)
             ORDER BY ingrediente_id, dia DESC",
            [$restauranteId, $dias]
        );
    }

    public function getInactivos(int $restauranteId): array
    {
        return $this->query(
            "SELECT * FROM rest_ingredientes WHERE restaurante_id = ? AND activo = 0 ORDER BY nombre",
            [$restauranteId]
        );
    }
}
