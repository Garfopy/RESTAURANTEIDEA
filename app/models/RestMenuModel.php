<?php
class RestMenuModel extends BaseModel
{
    protected string $table = 'rest_platillos';

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
                        JOIN rest_receta_ingredientes ri ON ri.receta_id = r.id AND ri.es_informativo = 0
                        WHERE r.platillo_id = p.id
                    ) THEN 1 ELSE 0 END AS tiene_receta
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
             ORDER BY c.orden, p.nombre",
            [$restauranteId]
        );
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

    /** Crea modificadores faltantes a partir de guarniciones/extras de recetas antiguas. */
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
               AND ri.tipo_componente = 'guarnicion'",
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
            "SELECT m.ingrediente_id, MAX(m.precio_extra) AS precio_extra,
                    MAX(m.cantidad_unidad) AS cantidad_unidad, MAX(m.unidad) AS unidad,
                    MAX(pm.max_seleccion) AS max_seleccion, MAX(m.nombre) AS nombre
             FROM rest_modificadores m
             LEFT JOIN rest_platillo_modificador pm ON pm.modificador_id=m.id
             WHERE m.restaurante_id=? AND m.tipo='extra' AND m.alcance='platillo' AND m.activo=1
             GROUP BY m.ingrediente_id",
            [$restauranteId]
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
            }
        }
        if ($grupos) {
            $this->execute(
                "UPDATE rest_modificadores SET activo=0
                 WHERE restaurante_id=? AND tipo='extra' AND alcance='platillo' AND activo=1",
                [$restauranteId]
            );
        }
        $this->sincronizarCatalogoExtras($restauranteId);
        return $creados;
    }

    public function sincronizarCatalogoExtras(int $restauranteId): void
    {
        if (!$this->soportaSelectorUnificado()) return;
        $extras = $this->getCatalogoExtras($restauranteId, true);
        $platillos = $this->query("SELECT id FROM rest_platillos WHERE restaurante_id=? AND activo=1", [$restauranteId]);
        foreach ($extras as $extra) {
            foreach ($platillos as $platillo) {
                $this->execute(
                    "INSERT INTO rest_platillo_modificador (platillo_id, modificador_id, obligatorio, max_seleccion)
                     VALUES (?,?,0,?) ON DUPLICATE KEY UPDATE max_seleccion=VALUES(max_seleccion)",
                    [(int)$platillo['id'], (int)$extra['id'], max(1, (int)$extra['max_seleccion_global'])]
                );
            }
        }
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
             WHERE r.platillo_id=? AND i.restaurante_id=? AND ri.tipo_componente='guarnicion'",
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
    public function getTopVendidos(int $restauranteId, int $limit = 5): array
    {
        $limit = max(1, min(20, $limit));
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
             GROUP BY p.id, p.nombre, p.precio
             ORDER BY unidades_vendidas DESC
             LIMIT $limit",
            [$restauranteId]
        );
    }

    /**
     * Platillos menos vendidos entre los que SÍ están activos en menú,
     * incluye los que no se han vendido nunca (LEFT JOIN).
     */
    public function getMenosVendidos(int $restauranteId, int $limit = 5): array
    {
        $limit = max(1, min(20, $limit));
        return $this->query(
            "SELECT p.id, p.nombre, p.precio,
                    COALESCE(SUM(CASE WHEN pi.estado <> 'cancelado' THEN pi.cantidad ELSE 0 END), 0) AS unidades_vendidas
             FROM rest_platillos p
             LEFT JOIN rest_pedido_items pi ON pi.platillo_id = p.id
             LEFT JOIN rest_pedidos ped ON ped.id = pi.pedido_id
                  AND ped.created_at >= DATE_SUB(NOW(), INTERVAL 365 DAY)
                  AND ped.restaurante_id = p.restaurante_id
             WHERE p.restaurante_id = ? AND p.activo = 1
             GROUP BY p.id, p.nombre, p.precio
             ORDER BY unidades_vendidas ASC, p.nombre ASC
             LIMIT $limit",
            [$restauranteId]
        );
    }
}
