<?php
class RestMenuModel extends BaseModel
{
    protected string $table = 'rest_platillos';
    private static array $columnCache = [];
    private static array $tableCache = [];

    private function tableExists(string $table): bool
    {
        if (array_key_exists($table, self::$tableCache)) {
            return self::$tableCache[$table];
        }
        if (!preg_match('/^[a-zA-Z0-9_]+$/', $table)) {
            return self::$tableCache[$table] = false;
        }

        try {
            $stmt = $this->db->prepare(
                "SELECT COUNT(*) FROM information_schema.TABLES
                 WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ?"
            );
            $stmt->execute([$table]);
            return self::$tableCache[$table] = (int)$stmt->fetchColumn() > 0;
        } catch (\Throwable $e) {
            return self::$tableCache[$table] = false;
        }
    }

    private function tableColumnExists(string $table, string $column): bool
    {
        $key = $table . '.' . $column;
        if (array_key_exists($key, self::$columnCache)) {
            return self::$columnCache[$key];
        }

        try {
            $stmt = $this->db->query("SHOW COLUMNS FROM `{$table}`");
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                if (strcasecmp((string)($row['Field'] ?? ''), $column) === 0) {
                    return self::$columnCache[$key] = true;
                }
            }
            return self::$columnCache[$key] = false;
        } catch (\Throwable $e) {
            return self::$columnCache[$key] = false;
        }
    }

    private function pedidoFechaFinancieraSql(string $alias = 'ped'): string
    {
        $columns = [];
        foreach (['pagado_at', 'cerrado_at', 'actualizado_at', 'updated_at', 'created_at'] as $column) {
            if ($this->tableColumnExists('rest_pedidos', $column)) {
                $columns[] = "{$alias}.{$column}";
            }
        }
        if (!$columns) return 'NULL';
        return count($columns) === 1 ? $columns[0] : 'COALESCE(' . implode(', ', $columns) . ')';
    }

    public function soportaSelectorUnificado(): bool
    {
        $stmt = $this->db->prepare(
            "SELECT COUNT(*) FROM information_schema.columns
             WHERE table_schema=DATABASE() AND table_name='rest_modificadores'
               AND column_name IN ('alcance','max_seleccion_global')"
        );
        $stmt->execute();
        return (int)$stmt->fetchColumn() === 2;
    }

    // ── Categorías ────────────────────────────────────────────────

    public function getCategorias(int $restauranteId, bool $soloActivas = false): array
    {
        $where = $soloActivas ? 'AND activo = 1' : '';
        return $this->query(
            "SELECT * FROM rest_categorias_menu WHERE restaurante_id = ? $where ORDER BY orden, nombre",
            [$restauranteId]
        );
    }

    public function findCategoria(int $id): ?array
    {
        return $this->queryOne("SELECT * FROM rest_categorias_menu WHERE id = ?", [$id]);
    }

    public function insertCategoria(array $data): int
    {
        $this->execute(
            "INSERT INTO rest_categorias_menu (restaurante_id, nombre, descripcion, imagen, orden) VALUES (?,?,?,?,?)",
            [$data['restaurante_id'], $data['nombre'], $data['descripcion'] ?? null, $data['imagen'] ?? null, $data['orden'] ?? 0]
        );
        return (int) $this->db->lastInsertId();
    }

    public function updateCategoria(int $id, array $data): bool
    {
        $sets = implode(', ', array_map(fn($k) => "`$k` = ?", array_keys($data)));
        $vals = array_values($data);
        $vals[] = $id;
        return $this->execute("UPDATE rest_categorias_menu SET $sets WHERE id = ?", $vals);
    }

    // ── Platillos ─────────────────────────────────────────────────

    public function getByRestaurante(int $restauranteId, bool $soloActivos = false): array
    {
        $where = $soloActivos ? 'AND p.activo = 1' : '';
        return $this->query(
            "SELECT p.*,
                    c.nombre AS categoria_nombre,
                    CASE WHEN EXISTS(
                        SELECT 1 FROM rest_recetas r
                        JOIN rest_receta_ingredientes ri ON ri.receta_id = r.id
                        WHERE r.platillo_id = p.id
                    ) THEN 1 ELSE 0 END AS tiene_receta,
                    {$this->sqlPlatilloBloqueadoPorInventario()} AS bloqueado_por_inventario,
                    {$this->sqlIngredientesNoDisponiblesResumen()} AS ingredientes_no_disponibles
             FROM rest_platillos p
             LEFT JOIN rest_categorias_menu c ON c.id = p.categoria_id
             WHERE p.restaurante_id = ? $where
             ORDER BY c.orden, c.nombre, p.nombre",
            [$restauranteId]
        );
    }

    public function getPlatillosDisponibles(int $restauranteId): array
    {
        return $this->query(
            "SELECT p.*, c.nombre AS categoria_nombre
             FROM rest_platillos p
             LEFT JOIN rest_categorias_menu c ON c.id = p.categoria_id
             WHERE p.restaurante_id = ? AND p.disponible = 1 AND p.activo = 1
               {$this->sqlSoloPlatillosConInventarioDisponible()}
             ORDER BY c.orden, p.nombre",
            [$restauranteId]
        );
    }

    public function ingredientesNoDisponiblesParaPlatillo(int $restauranteId, int $platilloId): array
    {
        $sources = [];
        $params = [];

        if ($this->tableExists('rest_recetas') && $this->tableExists('rest_receta_ingredientes')) {
            $sources[] = "SELECT ri.ingrediente_id
                            FROM rest_platillos p
                            JOIN rest_recetas r ON r.platillo_id = p.id
                            JOIN rest_receta_ingredientes ri ON ri.receta_id = r.id
                           WHERE p.id = ?
                             AND p.restaurante_id = ?";
            $params[] = $platilloId;
            $params[] = $restauranteId;
        }

        if ($this->tableColumnExists('rest_platillos', 'ingrediente_directo_id')) {
            $sources[] = "SELECT p.ingrediente_directo_id AS ingrediente_id
                            FROM rest_platillos p
                           WHERE p.id = ?
                             AND p.restaurante_id = ?
                             AND p.ingrediente_directo_id IS NOT NULL";
            $params[] = $platilloId;
            $params[] = $restauranteId;
        }

        if (!$sources || !$this->tableExists('rest_ingredientes')) {
            return [];
        }

        return $this->query(
            "SELECT DISTINCT i.id, i.nombre
               FROM (" . implode(' UNION ALL ', $sources) . ") src
               JOIN rest_ingredientes i ON i.id = src.ingrediente_id
              WHERE COALESCE(i.activo, 1) = 0
              ORDER BY i.nombre",
            $params
        );
    }

    public function platilloDisponibleParaVenta(int $restauranteId, int $platilloId): bool
    {
        $platillo = $this->find($platilloId);
        if (!$platillo || (int)($platillo['restaurante_id'] ?? 0) !== $restauranteId) {
            return false;
        }
        if (isset($platillo['activo']) && (int)$platillo['activo'] !== 1) {
            return false;
        }
        if (isset($platillo['disponible']) && (int)$platillo['disponible'] !== 1) {
            return false;
        }
        return !$this->ingredientesNoDisponiblesParaPlatillo($restauranteId, $platilloId);
    }

    private function sqlSoloPlatillosConInventarioDisponible(): string
    {
        $parts = [];
        if ($this->tableExists('rest_recetas') && $this->tableExists('rest_receta_ingredientes') && $this->tableExists('rest_ingredientes')) {
            $parts[] = "NOT EXISTS (
                SELECT 1
                  FROM rest_recetas r_inv
                  JOIN rest_receta_ingredientes ri_inv ON ri_inv.receta_id = r_inv.id
                  JOIN rest_ingredientes i_inv ON i_inv.id = ri_inv.ingrediente_id
                 WHERE r_inv.platillo_id = p.id
                   AND COALESCE(i_inv.activo, 1) = 0
            )";
        }
        if ($this->tableColumnExists('rest_platillos', 'ingrediente_directo_id') && $this->tableExists('rest_ingredientes')) {
            $parts[] = "(p.ingrediente_directo_id IS NULL OR EXISTS (
                SELECT 1
                  FROM rest_ingredientes i_dir
                 WHERE i_dir.id = p.ingrediente_directo_id
                   AND i_dir.restaurante_id = p.restaurante_id
                   AND COALESCE(i_dir.activo, 1) = 1
            ))";
        }

        return $parts ? ' AND ' . implode(' AND ', $parts) : '';
    }

    private function sqlPlatilloBloqueadoPorInventario(): string
    {
        $conditions = [];
        if ($this->tableExists('rest_recetas') && $this->tableExists('rest_receta_ingredientes') && $this->tableExists('rest_ingredientes')) {
            $conditions[] = "EXISTS (
                SELECT 1
                  FROM rest_recetas r_inv
                  JOIN rest_receta_ingredientes ri_inv ON ri_inv.receta_id = r_inv.id
                  JOIN rest_ingredientes i_inv ON i_inv.id = ri_inv.ingrediente_id
                 WHERE r_inv.platillo_id = p.id
                   AND COALESCE(i_inv.activo, 1) = 0
            )";
        }
        if ($this->tableColumnExists('rest_platillos', 'ingrediente_directo_id') && $this->tableExists('rest_ingredientes')) {
            $conditions[] = "(p.ingrediente_directo_id IS NOT NULL AND NOT EXISTS (
                SELECT 1
                  FROM rest_ingredientes i_dir
                 WHERE i_dir.id = p.ingrediente_directo_id
                   AND i_dir.restaurante_id = p.restaurante_id
                   AND COALESCE(i_dir.activo, 1) = 1
            ))";
        }

        return $conditions ? 'CASE WHEN ' . implode(' OR ', $conditions) . ' THEN 1 ELSE 0 END' : '0';
    }

    private function sqlIngredientesNoDisponiblesResumen(): string
    {
        $parts = [];
        if ($this->tableExists('rest_recetas') && $this->tableExists('rest_receta_ingredientes') && $this->tableExists('rest_ingredientes')) {
            $parts[] = "(SELECT GROUP_CONCAT(DISTINCT i_inv.nombre ORDER BY i_inv.nombre SEPARATOR ', ')
                           FROM rest_recetas r_inv
                           JOIN rest_receta_ingredientes ri_inv ON ri_inv.receta_id = r_inv.id
                           JOIN rest_ingredientes i_inv ON i_inv.id = ri_inv.ingrediente_id
                          WHERE r_inv.platillo_id = p.id
                            AND COALESCE(i_inv.activo, 1) = 0)";
        }
        if ($this->tableColumnExists('rest_platillos', 'ingrediente_directo_id') && $this->tableExists('rest_ingredientes')) {
            $parts[] = "(SELECT GROUP_CONCAT(DISTINCT i_dir.nombre ORDER BY i_dir.nombre SEPARATOR ', ')
                           FROM rest_ingredientes i_dir
                          WHERE i_dir.id = p.ingrediente_directo_id
                            AND i_dir.restaurante_id = p.restaurante_id
                            AND COALESCE(i_dir.activo, 1) = 0)";
        }

        if (!$parts) {
            return 'NULL';
        }

        return 'CONCAT_WS(\', \', ' . implode(', ', $parts) . ')';
    }

    public function importarMenuDesdePrincipal(int $origenRestauranteId, int $destinoRestauranteId): array
    {
        if ($origenRestauranteId <= 0 || $destinoRestauranteId <= 0 || $origenRestauranteId === $destinoRestauranteId) {
            throw new \InvalidArgumentException('Selecciona una sucursal principal diferente a la sucursal actual.');
        }

        $stats = [
            'categorias_creadas' => 0,
            'categorias_actualizadas' => 0,
            'platillos_creados' => 0,
            'platillos_actualizados' => 0,
            'platillos_incompletos_desactivados' => 0,
        ];

        $this->db->beginTransaction();
        try {
            $categoriasMap = $this->importarCategoriasMenu($origenRestauranteId, $destinoRestauranteId, $stats);
            $stats['platillos_incompletos_desactivados'] = $this->desactivarPlatillosSinNombre($destinoRestauranteId);
            $this->importarPlatillosMenu($origenRestauranteId, $destinoRestauranteId, $categoriasMap, $stats);
            $this->db->commit();
            return $stats;
        } catch (\Throwable $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            throw $e;
        }
    }

    private function importarCategoriasMenu(int $origenRestauranteId, int $destinoRestauranteId, array &$stats): array
    {
        $origen = $this->query(
            "SELECT id, nombre, descripcion, imagen, orden, activo
             FROM rest_categorias_menu
             WHERE restaurante_id = ? AND activo = 1
             ORDER BY orden, nombre",
            [$origenRestauranteId]
        );
        $destino = $this->query(
            "SELECT id, nombre
             FROM rest_categorias_menu
             WHERE restaurante_id = ?",
            [$destinoRestauranteId]
        );

        $destinoPorNombre = [];
        foreach ($destino as $cat) {
            $destinoPorNombre[mb_strtolower(trim((string)$cat['nombre']))] = (int)$cat['id'];
        }

        $map = [];
        foreach ($origen as $cat) {
            $key = mb_strtolower(trim((string)$cat['nombre']));
            if (isset($destinoPorNombre[$key])) {
                $destinoId = $destinoPorNombre[$key];
                $this->execute(
                    "UPDATE rest_categorias_menu
                     SET descripcion = ?, imagen = ?, orden = ?, activo = 1
                     WHERE id = ? AND restaurante_id = ?",
                    [
                        $cat['descripcion'] ?? null,
                        $cat['imagen'] ?? null,
                        (int)($cat['orden'] ?? 0),
                        $destinoId,
                        $destinoRestauranteId,
                    ]
                );
                $stats['categorias_actualizadas']++;
            } else {
                $this->execute(
                    "INSERT INTO rest_categorias_menu
                        (restaurante_id, nombre, descripcion, imagen, orden, activo)
                     VALUES (?, ?, ?, ?, ?, 1)",
                    [
                        $destinoRestauranteId,
                        (string)$cat['nombre'],
                        $cat['descripcion'] ?? null,
                        $cat['imagen'] ?? null,
                        (int)($cat['orden'] ?? 0),
                    ]
                );
                $destinoId = (int)$this->db->lastInsertId();
                $destinoPorNombre[$key] = $destinoId;
                $stats['categorias_creadas']++;
            }
            $map[(int)$cat['id']] = $destinoId;
        }

        return $map;
    }

    private function desactivarPlatillosSinNombre(int $restauranteId): int
    {
        if (!$this->tableColumnExists('rest_platillos', 'nombre') || !$this->tableColumnExists('rest_platillos', 'activo')) {
            return 0;
        }

        $stmt = $this->db->prepare(
            "UPDATE rest_platillos
             SET activo = 0
             WHERE restaurante_id = ?
               AND activo = 1
               AND (nombre IS NULL OR TRIM(nombre) = '')"
        );
        $stmt->execute([$restauranteId]);
        return $stmt->rowCount();
    }

    private function importarPlatillosMenu(int $origenRestauranteId, int $destinoRestauranteId, array $categoriasMap, array &$stats): void
    {
        $sourceColumns = [
            'codigo', 'es_armado', 'categoria_id', 'nombre', 'descripcion', 'alergenos', 'contiene',
            'precio', 'imagen', 'tiempo_preparacion_min', 'requiere_preparacion', 'disponible', 'activo',
            'ingrediente_directo_id', 'ingrediente_directo_cantidad',
        ];
        $selectColumns = ['id'];
        foreach ($sourceColumns as $column) {
            if ($this->tableColumnExists('rest_platillos', $column)) {
                $selectColumns[] = $column;
            }
        }

        $platillos = $this->query(
            "SELECT " . implode(', ', array_map(fn($column) => "`{$column}`", $selectColumns)) . "
             FROM rest_platillos
             WHERE restaurante_id = ? AND activo = 1
             ORDER BY nombre",
            [$origenRestauranteId]
        );
        $destinoSelect = ['id', 'nombre'];
        foreach (['codigo', 'categoria_id'] as $column) {
            if ($this->tableColumnExists('rest_platillos', $column)) {
                $destinoSelect[] = $column;
            }
        }
        $destino = $this->query(
            "SELECT " . implode(', ', array_map(fn($column) => "`{$column}`", $destinoSelect)) . "
             FROM rest_platillos
             WHERE restaurante_id = ?",
            [$destinoRestauranteId]
        );

        $destinoPorCodigo = [];
        $destinoPorNombreCategoria = [];
        foreach ($destino as $platillo) {
            $codigo = trim((string)($platillo['codigo'] ?? ''));
            if ($codigo !== '') {
                $destinoPorCodigo[mb_strtolower($codigo)] = (int)$platillo['id'];
            }
            $nombreDestino = trim((string)($platillo['nombre'] ?? ''));
            if ($nombreDestino !== '') {
                $destinoPorNombreCategoria[$this->platilloKey($nombreDestino, (int)($platillo['categoria_id'] ?? 0))] = (int)$platillo['id'];
            }
        }

        foreach ($platillos as $platillo) {
            $nombreOrigen = trim((string)($platillo['nombre'] ?? ''));
            if ($nombreOrigen === '') {
                continue;
            }
            $categoriaDestinoId = isset($platillo['categoria_id'])
                ? ($categoriasMap[(int)$platillo['categoria_id']] ?? null)
                : null;
            $codigo = trim((string)($platillo['codigo'] ?? ''));
            $destinoId = 0;
            if ($codigo !== '' && isset($destinoPorCodigo[mb_strtolower($codigo)])) {
                $destinoId = $destinoPorCodigo[mb_strtolower($codigo)];
            }
            if (!$destinoId) {
                $key = $this->platilloKey($nombreOrigen, (int)($categoriaDestinoId ?? 0));
                $destinoId = $destinoPorNombreCategoria[$key] ?? 0;
            }

            $data = $this->menuPlatilloImportData($platillo, $categoriaDestinoId, false);
            if ($destinoId > 0) {
                if (!$data) {
                    throw new \RuntimeException('No se pudieron detectar las columnas de platillos para actualizar el menu.');
                }
                $sets = implode(', ', array_map(fn($column) => "`{$column}` = ?", array_keys($data)));
                $values = array_values($data);
                $values[] = $destinoId;
                $values[] = $destinoRestauranteId;
                $this->execute("UPDATE rest_platillos SET {$sets} WHERE id = ? AND restaurante_id = ?", $values);
                $stats['platillos_actualizados']++;
                continue;
            }

            $data = ['restaurante_id' => $destinoRestauranteId] + $this->menuPlatilloImportData($platillo, $categoriaDestinoId, true);
            if (count($data) <= 1) {
                throw new \RuntimeException('No se pudieron detectar las columnas de platillos para importar el menu.');
            }
            $columns = array_keys($data);
            $this->execute(
                "INSERT INTO rest_platillos (" . implode(', ', array_map(fn($column) => "`{$column}`", $columns)) . ")
                 VALUES (" . implode(', ', array_fill(0, count($columns), '?')) . ")",
                array_values($data)
            );
            $stats['platillos_creados']++;
        }
    }

    private function menuPlatilloImportData(array $platillo, ?int $categoriaDestinoId, bool $nuevo): array
    {
        $data = [];
        $copyColumns = [
            'codigo', 'es_armado', 'nombre', 'descripcion', 'alergenos', 'contiene',
            'precio', 'imagen', 'tiempo_preparacion_min', 'requiere_preparacion',
            'ingrediente_directo_cantidad',
        ];
        foreach ($copyColumns as $column) {
            if ($this->tableColumnExists('rest_platillos', $column) && array_key_exists($column, $platillo)) {
                $data[$column] = $platillo[$column];
            }
        }
        if ($this->tableColumnExists('rest_platillos', 'categoria_id')) {
            $data['categoria_id'] = $categoriaDestinoId;
        }
        if ($nuevo && $this->tableColumnExists('rest_platillos', 'disponible')) {
            $data['disponible'] = (int)($platillo['disponible'] ?? 1);
        }
        if ($nuevo && $this->tableColumnExists('rest_platillos', 'activo')) {
            $data['activo'] = 1;
        }
        if ($nuevo && $this->tableColumnExists('rest_platillos', 'ingrediente_directo_id')) {
            $data['ingrediente_directo_id'] = null;
        }
        return $data;
    }

    private function platilloKey(string $nombre, int $categoriaId): string
    {
        return mb_strtolower(trim($nombre)) . '|' . $categoriaId;
    }

    // ── Recetas ───────────────────────────────────────────────────

    public function getReceta(int $platilloId): ?array
    {
        return $this->queryOne(
            "SELECT * FROM rest_recetas WHERE platillo_id = ?",
            [$platilloId]
        );
    }

    public function getIngredientesReceta(int $recetaId): array
    {
        return $this->query(
            "SELECT ri.*, i.nombre AS ingrediente_nombre, i.unidad_principal, i.costo_unitario,
                    i.tipo AS ingrediente_tipo,
                    COALESCE(ri.precio_extra, 0) AS precio_extra,
                    ri.tipo_componente, ri.codigo_display
             FROM rest_receta_ingredientes ri
             JOIN rest_ingredientes i ON i.id = ri.ingrediente_id
             WHERE ri.receta_id = ?",
            [$recetaId]
        );
    }

    /** Returns [platillo_id => [ingredients]] for all platillos of a restaurant. */
    public function getIngredientesPorRestaurante(int $restauranteId): array
    {
        $rows = $this->query(
            "SELECT rec.platillo_id, ri.ingrediente_id, i.nombre AS ingrediente_nombre,
                    ri.cantidad, ri.unidad, ri.es_informativo,
                    COALESCE(ri.precio_extra, 0) AS precio_extra,
                    ri.tipo_componente
             FROM rest_recetas rec
             JOIN rest_receta_ingredientes ri ON ri.receta_id = rec.id
             JOIN rest_ingredientes i ON i.id = ri.ingrediente_id
             JOIN rest_platillos p ON p.id = rec.platillo_id
             WHERE p.restaurante_id = ? AND p.activo = 1",
            [$restauranteId]
        );
        $result = [];
        foreach ($rows as $row) {
            $result[$row['platillo_id']][] = $row;
        }
        return $result;
    }

    public function upsertReceta(int $platilloId, int $porciones, ?string $notas): int
    {
        $existing = $this->getReceta($platilloId);
        if ($existing) {
            $this->execute(
                "UPDATE rest_recetas SET porciones_base = ?, notas = ? WHERE platillo_id = ?",
                [$porciones, $notas, $platilloId]
            );
            return $existing['id'];
        }
        $this->execute(
            "INSERT INTO rest_recetas (platillo_id, porciones_base, notas) VALUES (?,?,?)",
            [$platilloId, $porciones, $notas]
        );
        return (int) $this->db->lastInsertId();
    }

    public function syncIngredientesReceta(int $recetaId, array $ingredientes): void
    {
        $this->execute("DELETE FROM rest_receta_ingredientes WHERE receta_id = ?", [$recetaId]);
        foreach ($ingredientes as $ing) {
            $this->execute(
                "INSERT INTO rest_receta_ingredientes (receta_id, ingrediente_id, cantidad, unidad, notas, es_informativo, precio_extra, tipo_componente, codigo_display) VALUES (?,?,?,?,?,?,?,?,?)",
                [$recetaId, $ing['ingrediente_id'], $ing['cantidad'], $ing['unidad'], $ing['notas'] ?? null, $ing['es_informativo'] ?? 0, (float)($ing['precio_extra'] ?? 0), $ing['tipo_componente'] ?? 'materia_prima', $ing['codigo_display'] ?? null]
            );
        }
    }

    public function getPlatilloConReceta(int $platilloId): ?array
    {
        $platillo = $this->queryOne("SELECT * FROM rest_platillos WHERE id = ?", [$platilloId]);
        if (!$platillo) return null;
        $receta = $this->getReceta($platilloId);
        $platillo['receta'] = $receta;
        $platillo['ingredientes'] = $receta ? $this->getIngredientesReceta($receta['id']) : [];
        $platillo['modificadores'] = $this->getModificadoresPlatillo($platilloId, false);
        return $platillo;
    }

    public function getModificadoresPlatillo(int $platilloId, bool $soloActivos = true): array
    {
        $activo = $soloActivos ? ' AND m.activo = 1' : '';
        return $this->query(
            "SELECT m.*, pm.max_seleccion, i.nombre AS ingrediente_nombre,
                    i.unidad_principal AS ingrediente_unidad
             FROM rest_platillo_modificador pm
             JOIN rest_modificadores m ON m.id = pm.modificador_id
             LEFT JOIN rest_ingredientes i ON i.id = m.ingrediente_id
             WHERE pm.platillo_id = ?{$activo}
             ORDER BY FIELD(m.tipo, 'sin', 'extra', 'opcion'), m.nombre",
            [$platilloId]
        );
    }

    public function getModificadorValido(int $restauranteId, int $platilloId, int $modificadorId): ?array
    {
        return $this->queryOne(
            "SELECT m.*, pm.max_seleccion, i.nombre AS ingrediente_nombre
             FROM rest_platillo_modificador pm
             JOIN rest_modificadores m ON m.id = pm.modificador_id
             LEFT JOIN rest_ingredientes i ON i.id = m.ingrediente_id
             WHERE pm.platillo_id = ? AND m.id = ? AND m.restaurante_id = ? AND m.activo = 1",
            [$platilloId, $modificadorId, $restauranteId]
        );
    }

    public function syncModificadores(int $restauranteId, int $platilloId, array $modificadores): void
    {
        $actuales = $this->getModificadoresPlatillo($platilloId, false);
        $actualesPorId = array_column($actuales, null, 'id');
        $conservar = [];

        foreach ($modificadores as $mod) {
            $id = (int)($mod['id'] ?? 0);
            $params = [
                $restauranteId,
                (int)$mod['ingrediente_id'],
                $mod['nombre'],
                $mod['tipo'],
                (float)$mod['precio_extra'],
                (float)$mod['cantidad_unidad'],
                $mod['unidad'],
            ];
            if ($id && isset($actualesPorId[$id])) {
                $this->execute(
                    "UPDATE rest_modificadores SET restaurante_id=?, ingrediente_id=?, nombre=?, tipo=?, precio_extra=?, cantidad_unidad=?, unidad=?, activo=1 WHERE id=?",
                    array_merge($params, [$id])
                );
            } else {
                $this->execute(
                    "INSERT INTO rest_modificadores (restaurante_id, ingrediente_id, nombre, tipo, precio_extra, cantidad_unidad, unidad, activo) VALUES (?,?,?,?,?,?,?,1)",
                    $params
                );
                $id = (int)$this->db->lastInsertId();
            }
            $max = $mod['tipo'] === 'sin' ? 1 : max(1, (int)$mod['max_seleccion']);
            $this->execute(
                "INSERT INTO rest_platillo_modificador (platillo_id, modificador_id, obligatorio, max_seleccion)
                 VALUES (?,?,0,?) ON DUPLICATE KEY UPDATE max_seleccion=VALUES(max_seleccion)",
                [$platilloId, $id, $max]
            );
            $conservar[] = $id;
        }

        foreach ($actuales as $actual) {
            $id = (int)$actual['id'];
            if (!in_array($id, $conservar, true)) {
                $this->execute("UPDATE rest_modificadores SET activo=0 WHERE id=?", [$id]);
            }
        }
    }

    /** Normaliza guarniciones incluidas antiguas sin convertir extras ni materias primas. */
    public function clasificarGuarnicionesLegacy(int $restauranteId): int
    {
        $stmt = $this->db->prepare(
            "UPDATE rest_receta_ingredientes ri
             JOIN rest_recetas r ON r.id=ri.receta_id
             JOIN rest_platillos p ON p.id=r.platillo_id
             JOIN rest_ingredientes i ON i.id=ri.ingrediente_id
             SET ri.tipo_componente='guarnicion'
             WHERE p.restaurante_id=? AND p.activo=1
               AND i.restaurante_id=? AND i.tipo='guarnicion'
               AND COALESCE(ri.precio_extra, 0)=0
               AND COALESCE(ri.es_informativo, 0)=0
               AND COALESCE(ri.tipo_componente, 'materia_prima')<>'guarnicion'"
        );
        $stmt->execute([$restauranteId, $restauranteId]);
        return $stmt->rowCount();
    }

    /** Crea exclusiones faltantes a partir de guarniciones incluidas en recetas antiguas. */
    public function materializarModificadoresExistentes(int $restauranteId): int
    {
        $candidatos = $this->query(
            "SELECT p.id AS platillo_id, ri.ingrediente_id, i.nombre, ri.cantidad, ri.unidad,
                    COALESCE(ri.tipo_componente, 'materia_prima') AS tipo_componente,
                    COALESCE(ri.precio_extra, 0) AS precio_extra
             FROM rest_platillos p
             JOIN rest_recetas r ON r.platillo_id = p.id
             JOIN rest_receta_ingredientes ri ON ri.receta_id = r.id
             JOIN rest_ingredientes i ON i.id = ri.ingrediente_id
             WHERE p.restaurante_id = ? AND p.activo = 1
               AND COALESCE(ri.precio_extra, 0)=0
               AND (ri.tipo_componente='guarnicion'
                    OR (i.tipo='guarnicion' AND COALESCE(ri.es_informativo, 0)=0))",
            [$restauranteId]
        );
        $creados = 0;
        foreach ($candidatos as $row) {
            $tipos = ['sin'];
            foreach ($tipos as $tipo) {
                $existe = $this->queryOne(
                    "SELECT m.id FROM rest_platillo_modificador pm
                     JOIN rest_modificadores m ON m.id=pm.modificador_id
                     WHERE pm.platillo_id=? AND m.restaurante_id=? AND m.ingrediente_id=? AND m.tipo=? LIMIT 1",
                    [(int)$row['platillo_id'], $restauranteId, (int)$row['ingrediente_id'], $tipo]
                );
                if ($existe) {
                    $this->execute("UPDATE rest_modificadores SET activo=1 WHERE id=?", [(int)$existe['id']]);
                    continue;
                }
                $nombre = ($tipo === 'sin' ? 'Sin ' : 'Extra ') . $row['nombre'];
                $this->execute(
                    "INSERT INTO rest_modificadores (restaurante_id, ingrediente_id, nombre, tipo, precio_extra, cantidad_unidad, unidad, activo)
                     VALUES (?,?,?,?,?,?,?,1)",
                    [$restauranteId, (int)$row['ingrediente_id'], $nombre, $tipo,
                     $tipo === 'extra' ? (float)$row['precio_extra'] : 0.0,
                     max(0.001, (float)$row['cantidad']), $row['unidad'] ?: 'pza']
                );
                $modificadorId = (int)$this->db->lastInsertId();
                $this->execute(
                    "INSERT INTO rest_platillo_modificador (platillo_id, modificador_id, obligatorio, max_seleccion) VALUES (?,?,0,1)",
                    [(int)$row['platillo_id'], $modificadorId]
                );
                $creados++;
            }
        }
        return $creados;
    }

    public function getCatalogoExtras(int $restauranteId, bool $soloActivos = false): array
    {
        if (!$this->soportaSelectorUnificado()) return [];
        $activo = $soloActivos ? ' AND m.activo=1' : '';
        return $this->query(
            "SELECT m.*, i.nombre AS ingrediente_nombre, i.unidad_principal AS ingrediente_unidad
             FROM rest_modificadores m
             JOIN rest_ingredientes i ON i.id=m.ingrediente_id
             WHERE m.restaurante_id=? AND m.tipo='extra' AND m.alcance='restaurante'{$activo}
             ORDER BY m.activo DESC, m.nombre",
            [$restauranteId]
        );
    }

    public function guardarExtraGlobal(int $restauranteId, array $data): int
    {
        $ingrediente = $this->queryOne(
            "SELECT id, nombre, unidad_principal FROM rest_ingredientes WHERE id=? AND restaurante_id=? AND activo=1",
            [(int)$data['ingrediente_id'], $restauranteId]
        );
        if (!$ingrediente) throw new \InvalidArgumentException('Selecciona un ingrediente activo del restaurante.');
        $id = (int)($data['id'] ?? 0);
        $actual = $id ? $this->queryOne(
            "SELECT m.id, (SELECT COUNT(*) FROM rest_pedido_item_modificadores pim WHERE pim.modificador_id=m.id) AS usos
             FROM rest_modificadores m WHERE m.id=? AND m.restaurante_id=? AND m.tipo='extra' AND m.alcance='restaurante'",
            [$id, $restauranteId]
        ) : $this->queryOne(
            "SELECT id, 0 AS usos FROM rest_modificadores WHERE restaurante_id=? AND ingrediente_id=? AND tipo='extra' AND alcance='restaurante' AND activo=1 LIMIT 1",
            [$restauranteId, (int)$ingrediente['id']]
        );
        $id = (int)($actual['id'] ?? 0);
        if ($id) {
            $duplicado = $this->queryOne(
                "SELECT id FROM rest_modificadores WHERE restaurante_id=? AND ingrediente_id=? AND tipo='extra' AND alcance='restaurante' AND activo=1 AND id<>? LIMIT 1",
                [$restauranteId, (int)$ingrediente['id'], $id]
            );
            if ($duplicado) throw new \InvalidArgumentException('Ese ingrediente ya existe en el catalogo global de extras.');
        }
        if ($id && (int)($actual['usos'] ?? 0) > 0) {
            $this->execute("UPDATE rest_modificadores SET activo=0 WHERE id=?", [$id]);
            $id = 0;
        }
        $nombre = trim((string)($data['nombre'] ?? '')) ?: 'Extra ' . $ingrediente['nombre'];
        $params = [(int)$ingrediente['id'], mb_substr($nombre, 0, 120), max(0, (float)$data['precio_extra']),
            max(0.001, (float)$data['cantidad_unidad']), mb_substr(trim((string)($data['unidad'] ?: $ingrediente['unidad_principal'])), 0, 20),
            max(1, (int)$data['max_seleccion_global'])];
        if ($id) {
            $this->execute(
                "UPDATE rest_modificadores SET ingrediente_id=?, nombre=?, precio_extra=?, cantidad_unidad=?, unidad=?, max_seleccion_global=?, activo=1 WHERE id=?",
                array_merge($params, [$id])
            );
        } else {
            $this->execute(
                "INSERT INTO rest_modificadores (restaurante_id, ingrediente_id, nombre, tipo, alcance, precio_extra, cantidad_unidad, unidad, max_seleccion_global, activo)
                 VALUES (?,?,?,'extra','restaurante',?,?,?,?,1)",
                array_merge([$restauranteId], $params)
            );
            $id = (int)$this->db->lastInsertId();
        }
        $this->sincronizarCatalogoExtras($restauranteId);
        return $id;
    }

    public function toggleExtraGlobal(int $restauranteId, int $modificadorId): bool
    {
        $mod = $this->queryOne(
            "SELECT id, activo FROM rest_modificadores WHERE id=? AND restaurante_id=? AND tipo='extra' AND alcance='restaurante'",
            [$modificadorId, $restauranteId]
        );
        if (!$mod) return false;
        return $this->execute("UPDATE rest_modificadores SET activo=? WHERE id=?", [(int)!$mod['activo'], $modificadorId]);
    }

    /** Consolida extras locales antiguos en un catálogo global por ingrediente. */
    public function materializarCatalogoGlobal(int $restauranteId): int
    {
        if (!$this->soportaSelectorUnificado()) {
            throw new \RuntimeException('Falta ejecutar la migracion 070_selector_unificado_guarniciones.sql.');
        }
        $grupos = $this->query(
            "SELECT origen.ingrediente_id, MAX(origen.precio_extra) AS precio_extra,
                    MAX(origen.cantidad_unidad) AS cantidad_unidad,
                    MAX(origen.unidad) AS unidad, MAX(origen.max_seleccion) AS max_seleccion,
                    MAX(origen.nombre) AS nombre
             FROM (
                 SELECT m.ingrediente_id, m.precio_extra, m.cantidad_unidad, m.unidad,
                        COALESCE(pm.max_seleccion, 1) AS max_seleccion, m.nombre
                 FROM rest_modificadores m
                 LEFT JOIN rest_platillo_modificador pm ON pm.modificador_id=m.id
                 WHERE m.restaurante_id=? AND m.tipo='extra'
                   AND m.alcance='platillo' AND m.activo=1
                 UNION ALL
                 SELECT m.ingrediente_id, m.precio_extra, m.cantidad_unidad, m.unidad,
                        m.max_seleccion_global AS max_seleccion, m.nombre
                 FROM rest_modificadores m
                 WHERE m.restaurante_id=? AND m.tipo='extra'
                   AND m.alcance='restaurante' AND m.activo=1
                 UNION ALL
                 SELECT ri.ingrediente_id, ri.precio_extra, ri.cantidad, ri.unidad,
                        1 AS max_seleccion, CONCAT('Extra ', i.nombre) AS nombre
                 FROM rest_receta_ingredientes ri
                 JOIN rest_recetas r ON r.id=ri.receta_id
                 JOIN rest_platillos p ON p.id=r.platillo_id
                 JOIN rest_ingredientes i ON i.id=ri.ingrediente_id
                 WHERE p.restaurante_id=? AND p.activo=1 AND i.restaurante_id=?
                   AND COALESCE(ri.precio_extra, 0)>0
                 UNION ALL
                 SELECT ri.ingrediente_id, 0 AS precio_extra, ri.cantidad, ri.unidad,
                        1 AS max_seleccion, CONCAT('Extra ', i.nombre) AS nombre
                 FROM rest_receta_ingredientes ri
                 JOIN rest_recetas r ON r.id=ri.receta_id
                 JOIN rest_platillos p ON p.id=r.platillo_id
                 JOIN rest_ingredientes i ON i.id=ri.ingrediente_id
                 WHERE p.restaurante_id=? AND p.activo=1 AND i.restaurante_id=?
                   AND COALESCE(ri.precio_extra, 0)=0
                   AND (ri.tipo_componente='guarnicion'
                        OR (i.tipo='guarnicion' AND COALESCE(ri.es_informativo, 0)=0))
             ) origen
             WHERE origen.ingrediente_id IS NOT NULL
             GROUP BY origen.ingrediente_id",
            [$restauranteId, $restauranteId, $restauranteId, $restauranteId, $restauranteId, $restauranteId]
        );
        $creados = 0;
        foreach ($grupos as $grupo) {
            if (!(int)$grupo['ingrediente_id']) continue;
            $existente = $this->queryOne(
                "SELECT id FROM rest_modificadores WHERE restaurante_id=? AND ingrediente_id=? AND tipo='extra' AND alcance='restaurante' LIMIT 1",
                [$restauranteId, (int)$grupo['ingrediente_id']]
            );
            if (!$existente) {
                $this->guardarExtraGlobal($restauranteId, [
                    'ingrediente_id' => (int)$grupo['ingrediente_id'], 'nombre' => $grupo['nombre'],
                    'precio_extra' => (float)$grupo['precio_extra'], 'cantidad_unidad' => (float)$grupo['cantidad_unidad'],
                    'unidad' => $grupo['unidad'], 'max_seleccion_global' => max(1, (int)$grupo['max_seleccion']),
                ]);
                $creados++;
            } else {
                $this->execute(
                    "UPDATE rest_modificadores SET nombre=?, precio_extra=?, cantidad_unidad=?,
                     unidad=?, max_seleccion_global=?, activo=1 WHERE id=?",
                    [$grupo['nombre'], max(0, (float)$grupo['precio_extra']),
                     max(0.001, (float)$grupo['cantidad_unidad']), $grupo['unidad'] ?: 'pza',
                     max(1, (int)$grupo['max_seleccion']), (int)$existente['id']]
                );
            }
        }
        $this->execute(
            "UPDATE rest_modificadores SET activo=0
             WHERE restaurante_id=? AND tipo='extra' AND alcance='platillo' AND activo=1",
            [$restauranteId]
        );
        $this->sincronizarCatalogoExtras($restauranteId);
        return $creados;
    }

    public function sincronizarCatalogoExtras(int $restauranteId): void
    {
        if (!$this->soportaSelectorUnificado()) return;
        $this->execute(
            "DELETE pm FROM rest_platillo_modificador pm
             JOIN rest_platillos p ON p.id=pm.platillo_id
             JOIN rest_modificadores m ON m.id=pm.modificador_id
             WHERE p.restaurante_id=? AND m.restaurante_id=?
               AND m.tipo='extra' AND m.alcance='restaurante'
               AND NOT EXISTS (
                   SELECT 1 FROM rest_recetas r
                   JOIN rest_receta_ingredientes ri ON ri.receta_id=r.id
                   JOIN rest_ingredientes i ON i.id=ri.ingrediente_id
                   WHERE r.platillo_id=p.id AND ri.ingrediente_id=m.ingrediente_id
                     AND COALESCE(ri.precio_extra, 0)=0
                     AND (ri.tipo_componente='guarnicion'
                          OR (i.tipo='guarnicion' AND COALESCE(ri.es_informativo, 0)=0))
               )",
            [$restauranteId, $restauranteId]
        );
        $this->execute(
            "INSERT INTO rest_platillo_modificador
             (platillo_id, modificador_id, obligatorio, max_seleccion)
             SELECT DISTINCT p.id, m.id, 0, GREATEST(1, m.max_seleccion_global)
             FROM rest_platillos p
             JOIN rest_recetas r ON r.platillo_id=p.id
             JOIN rest_receta_ingredientes ri ON ri.receta_id=r.id
             JOIN rest_ingredientes i ON i.id=ri.ingrediente_id
             JOIN rest_modificadores m ON m.restaurante_id=p.restaurante_id
               AND m.ingrediente_id=ri.ingrediente_id
               AND m.tipo='extra' AND m.alcance='restaurante' AND m.activo=1
             WHERE p.restaurante_id=? AND p.activo=1
               AND COALESCE(ri.precio_extra, 0)=0
               AND (ri.tipo_componente='guarnicion'
                    OR (i.tipo='guarnicion' AND COALESCE(ri.es_informativo, 0)=0))
             ON DUPLICATE KEY UPDATE max_seleccion=VALUES(max_seleccion)",
            [$restauranteId]
        );
    }

    public function sincronizarExclusionesDesdeReceta(int $restauranteId, int $platilloId): void
    {
        if (!$this->soportaSelectorUnificado()) {
            $this->materializarModificadoresExistentes($restauranteId);
            return;
        }
        $guarniciones = $this->query(
            "SELECT ri.ingrediente_id, ri.cantidad, ri.unidad, i.nombre
             FROM rest_recetas r JOIN rest_receta_ingredientes ri ON ri.receta_id=r.id
             JOIN rest_ingredientes i ON i.id=ri.ingrediente_id
             WHERE r.platillo_id=? AND i.restaurante_id=?
               AND COALESCE(ri.precio_extra, 0)=0
               AND (ri.tipo_componente='guarnicion'
                    OR (i.tipo='guarnicion' AND COALESCE(ri.es_informativo, 0)=0))",
            [$platilloId, $restauranteId]
        );
        $conservar = [];
        foreach ($guarniciones as $guarnicion) {
            $mod = $this->queryOne(
                "SELECT m.id FROM rest_platillo_modificador pm JOIN rest_modificadores m ON m.id=pm.modificador_id
                 WHERE pm.platillo_id=? AND m.restaurante_id=? AND m.ingrediente_id=? AND m.tipo='sin' AND m.alcance='platillo' LIMIT 1",
                [$platilloId, $restauranteId, (int)$guarnicion['ingrediente_id']]
            );
            $id = (int)($mod['id'] ?? 0);
            if ($id) {
                $this->execute("UPDATE rest_modificadores SET nombre=?, cantidad_unidad=?, unidad=?, activo=1 WHERE id=?",
                    ['Sin ' . $guarnicion['nombre'], max(0.001, (float)$guarnicion['cantidad']), $guarnicion['unidad'], $id]);
            } else {
                $this->execute(
                    "INSERT INTO rest_modificadores (restaurante_id, ingrediente_id, nombre, tipo, alcance, precio_extra, cantidad_unidad, unidad, max_seleccion_global, activo)
                     VALUES (?,?,?,'sin','platillo',0,?,?,1,1)",
                    [$restauranteId, (int)$guarnicion['ingrediente_id'], 'Sin ' . $guarnicion['nombre'], max(0.001, (float)$guarnicion['cantidad']), $guarnicion['unidad']]
                );
                $id = (int)$this->db->lastInsertId();
                $this->execute("INSERT INTO rest_platillo_modificador (platillo_id, modificador_id, obligatorio, max_seleccion) VALUES (?,?,0,1)", [$platilloId, $id]);
            }
            $conservar[] = $id;
        }
        $actuales = $this->query(
            "SELECT m.id FROM rest_platillo_modificador pm JOIN rest_modificadores m ON m.id=pm.modificador_id
             WHERE pm.platillo_id=? AND m.restaurante_id=? AND m.tipo='sin' AND m.alcance='platillo'",
            [$platilloId, $restauranteId]
        );
        foreach ($actuales as $actual) {
            if (!in_array((int)$actual['id'], $conservar, true)) $this->execute("UPDATE rest_modificadores SET activo=0 WHERE id=?", [(int)$actual['id']]);
        }
        $this->sincronizarCatalogoExtras($restauranteId);
    }

    public function prepararSelectorUnificado(int $restauranteId): void
    {
        $this->clasificarGuarnicionesLegacy($restauranteId);
        $this->materializarCatalogoGlobal($restauranteId);
        $platillos = $this->query("SELECT id FROM rest_platillos WHERE restaurante_id=? AND activo=1", [$restauranteId]);
        foreach ($platillos as $platillo) {
            $this->sincronizarExclusionesDesdeReceta($restauranteId, (int)$platillo['id']);
        }
        $this->sincronizarCatalogoExtras($restauranteId);
    }

    // ── Estadísticas de ventas ────────────────────────────────────

    /**
     * Platillos más vendidos del restaurante (último año, ignora ítems cancelados).
     * Devuelve nombre, precio actual, unidades vendidas y revenue.
     */
    public function getTopVendidos(int $restauranteId, int $limit = 5, ?string $visibleDesde = null): array
    {
        $limit = max(1, min(20, $limit));
        $filtroVisible = $visibleDesde ? ' AND DATE(' . $this->pedidoFechaFinancieraSql('ped') . ') >= ?' : '';
        $params = $visibleDesde ? [$restauranteId, $visibleDesde] : [$restauranteId];
        return $this->query(
            "SELECT p.id, p.nombre, p.precio,
                    SUM(pi.cantidad)         AS unidades_vendidas,
                    SUM(pi.subtotal)         AS revenue
             FROM rest_pedido_items pi
             JOIN rest_pedidos ped ON ped.id = pi.pedido_id
             JOIN rest_platillos p ON p.id = pi.platillo_id
             WHERE ped.restaurante_id = ?
               AND pi.estado <> 'cancelado'
               AND ped.created_at >= DATE_SUB(NOW(), INTERVAL 365 DAY)
               {$filtroVisible}
             GROUP BY p.id, p.nombre, p.precio
             ORDER BY unidades_vendidas DESC
             LIMIT $limit",
            $params
        );
    }

    /**
     * Platillos menos vendidos entre los que SÍ están activos en menú,
     * incluye los que no se han vendido nunca (LEFT JOIN).
     */
    public function getMenosVendidos(int $restauranteId, int $limit = 5, ?string $visibleDesde = null): array
    {
        $limit = max(1, min(20, $limit));
        $filtroVisible = $visibleDesde ? ' AND DATE(' . $this->pedidoFechaFinancieraSql('ped') . ') >= ?' : '';
        $params = $visibleDesde ? [$visibleDesde, $restauranteId] : [$restauranteId];
        return $this->query(
            "SELECT p.id, p.nombre, p.precio,
                    COALESCE(SUM(CASE WHEN pi.estado <> 'cancelado' THEN pi.cantidad ELSE 0 END), 0) AS unidades_vendidas
             FROM rest_platillos p
             LEFT JOIN rest_pedido_items pi ON pi.platillo_id = p.id
             LEFT JOIN rest_pedidos ped ON ped.id = pi.pedido_id
                   AND ped.created_at >= DATE_SUB(NOW(), INTERVAL 365 DAY)
                   AND ped.restaurante_id = p.restaurante_id
                   {$filtroVisible}
             WHERE p.restaurante_id = ? AND p.activo = 1
             GROUP BY p.id, p.nombre, p.precio
             ORDER BY unidades_vendidas ASC, p.nombre ASC
             LIMIT $limit",
            $params
        );
    }
}
