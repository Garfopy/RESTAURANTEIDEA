<?php
class RestMenuModel extends BaseModel
{
    protected string $table = 'rest_platillos';

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
            "SELECT p.*, c.nombre AS categoria_nombre
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
            "SELECT ri.*, i.nombre AS ingrediente_nombre, i.unidad_principal, i.costo_unitario
             FROM rest_receta_ingredientes ri
             JOIN rest_ingredientes i ON i.id = ri.ingrediente_id
             WHERE ri.receta_id = ?",
            [$recetaId]
        );
    }

    /** Returns [platillo_id => [ingredients]] for all platillos of a restaurant (excludes informativo). */
    public function getIngredientesPorRestaurante(int $restauranteId): array
    {
        $rows = $this->query(
            "SELECT rec.platillo_id, ri.ingrediente_id, i.nombre AS ingrediente_nombre,
                    ri.cantidad, ri.unidad, ri.es_informativo
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
                "INSERT INTO rest_receta_ingredientes (receta_id, ingrediente_id, cantidad, unidad, notas, es_informativo) VALUES (?,?,?,?,?,?)",
                [$recetaId, $ing['ingrediente_id'], $ing['cantidad'], $ing['unidad'], $ing['notas'] ?? null, $ing['es_informativo'] ?? 0]
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
        return $platillo;
    }
}
