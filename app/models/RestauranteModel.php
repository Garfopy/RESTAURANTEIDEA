<?php
class RestauranteModel extends BaseModel
{
    protected string $table = 'rest_restaurantes';
    private static array $columnCache = [];

    private function columnExists(string $column): bool
    {
        $key = $this->table . '.' . $column;
        if (array_key_exists($key, self::$columnCache)) {
            return self::$columnCache[$key];
        }

        try {
            $stmt = $this->db->query("SHOW COLUMNS FROM `{$this->table}`");
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

    private function indexExists(string $index): bool
    {
        $key = $this->table . '.idx.' . $index;
        if (array_key_exists($key, self::$columnCache)) {
            return self::$columnCache[$key];
        }

        try {
            $stmt = $this->db->query("SHOW INDEX FROM `{$this->table}`");
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                if (strcasecmp((string)($row['Key_name'] ?? ''), $index) === 0) {
                    return self::$columnCache[$key] = true;
                }
            }
            return self::$columnCache[$key] = false;
        } catch (\Throwable $e) {
            return self::$columnCache[$key] = false;
        }
    }

    private function mysqlErrorCode(\Throwable $e): int
    {
        $info = $e instanceof \PDOException ? ($e->errorInfo ?? []) : [];
        return (int)($info[1] ?? $e->getCode());
    }

    public function ensureMenuPrincipalColumn(): void
    {
        if (!$this->columnExists('menu_principal')) {
            try {
                $this->execute(
                    "ALTER TABLE rest_restaurantes
                     ADD COLUMN menu_principal TINYINT(1) NOT NULL DEFAULT 0"
                );
                self::$columnCache[$this->table . '.menu_principal'] = true;
            } catch (\Throwable $e) {
                if ($this->mysqlErrorCode($e) === 1060) {
                    self::$columnCache[$this->table . '.menu_principal'] = true;
                } else {
                    error_log('[RestauranteModel] No se pudo agregar menu_principal: ' . $e->getMessage());
                    return;
                }
            }
        }

        if (!$this->indexExists('idx_rest_menu_principal')) {
            try {
                $this->execute(
                    "ALTER TABLE rest_restaurantes
                     ADD KEY idx_rest_menu_principal (empresa_id, menu_principal)"
                );
                self::$columnCache[$this->table . '.idx.idx_rest_menu_principal'] = true;
            } catch (\Throwable $e) {
                if ($this->mysqlErrorCode($e) === 1061) {
                    self::$columnCache[$this->table . '.idx.idx_rest_menu_principal'] = true;
                } else {
                    error_log('[RestauranteModel] No se pudo agregar idx_rest_menu_principal: ' . $e->getMessage());
                }
            }
        }
    }

    public function getByComprador(int $compradorId): array
    {
        $this->ensureMenuPrincipalColumn();
        return $this->query(
            "SELECT * FROM rest_restaurantes WHERE comprador_id = ? ORDER BY nombre",
            [$compradorId]
        );
    }

    public function getByEmpresa(int $empresaId): array
    {
        $this->ensureMenuPrincipalColumn();
        return $this->query(
            "SELECT * FROM rest_restaurantes WHERE empresa_id = ? ORDER BY nombre",
            [$empresaId]
        );
    }

    public function marcarMenuPrincipal(int $restauranteId, int $empresaId): bool
    {
        $this->ensureMenuPrincipalColumn();
        if (!$this->columnExists('menu_principal')) {
            return false;
        }

        $this->db->beginTransaction();
        try {
            $this->execute(
                "UPDATE rest_restaurantes SET menu_principal = 0 WHERE empresa_id = ?",
                [$empresaId]
            );
            $this->execute(
                "UPDATE rest_restaurantes SET menu_principal = 1 WHERE id = ? AND empresa_id = ?",
                [$restauranteId, $empresaId]
            );
            $this->db->commit();
            return true;
        } catch (\Throwable $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            error_log('[RestauranteModel] No se pudo marcar menu principal: ' . $e->getMessage());
            return false;
        }
    }

    public function getMenuPrincipalPorEmpresa(int $empresaId): ?array
    {
        $this->ensureMenuPrincipalColumn();
        if (!$this->columnExists('menu_principal')) {
            return null;
        }

        return $this->queryOne(
            "SELECT * FROM rest_restaurantes
             WHERE empresa_id = ? AND menu_principal = 1 AND activo = 1
             ORDER BY id ASC
             LIMIT 1",
            [$empresaId]
        );
    }

    public function getBySlug(string $slug): ?array
    {
        return $this->queryOne(
            "SELECT * FROM rest_restaurantes WHERE slug = ? AND activo = 1",
            [$slug]
        );
    }

    public function appMovilHabilitada(int $restauranteId): bool
    {
        if ($restauranteId <= 0) return false;
        if (!$this->columnExists('app_movil_habilitada')) return true;

        $restaurante = $this->queryOne(
            "SELECT app_movil_habilitada FROM rest_restaurantes WHERE id = ? LIMIT 1",
            [$restauranteId]
        );

        return $restaurante !== null && !empty($restaurante['app_movil_habilitada']);
    }

    public function getAllConEmpresa(): array
    {
        $this->ensureMenuPrincipalColumn();
        return $this->query(
            "SELECT r.*, e.razon_social AS empresa_nombre
               FROM rest_restaurantes r
          LEFT JOIN empresas e ON e.id = r.empresa_id
              ORDER BY e.razon_social ASC, r.nombre ASC"
        );
    }

    /**
     * Listado global para el panel de Superadmin (todos los negocios, con su empresa y plan).
     * Requiere migrations/003_superadmin_universidades_planes.sql — si aun no corrio en este
     * entorno, degrada a estado_plataforma/plan_nombre nulos en vez de tronar.
     */
    public function getAllParaSuperadmin(): array
    {
        $tienePlataforma = $this->columnExists('estado_plataforma') && $this->columnExists('plan_id');
        $selectEstado = $tienePlataforma
            ? "r.estado_plataforma, r.plan_id, p.nombre AS plan_nombre"
            : "NULL AS estado_plataforma, NULL AS plan_id, NULL AS plan_nombre";
        $joinPlan = $tienePlataforma ? "LEFT JOIN planes_negocio p ON p.id = r.plan_id" : '';
        $orderEstado = $tienePlataforma ? "FIELD(r.estado_plataforma,'pendiente','activo','suspendido','baja'), " : '';

        return $this->query(
            "SELECT r.*, e.razon_social AS empresa_nombre, e.tipo_negocio, $selectEstado
               FROM rest_restaurantes r
          LEFT JOIN empresas e ON e.id = r.empresa_id
               $joinPlan
              ORDER BY $orderEstado e.razon_social ASC, r.nombre ASC"
        );
    }

    /** KPIs globales para el dashboard de Superadmin. Degrada si migration 003 no ha corrido. */
    public function getResumenPlataforma(): array
    {
        $tienePlataforma = $this->columnExists('estado_plataforma');

        $porEstado = ['pendiente' => 0, 'activo' => 0, 'suspendido' => 0, 'baja' => 0];
        if ($tienePlataforma) {
            foreach ($this->query("SELECT estado_plataforma, COUNT(*) AS total FROM rest_restaurantes GROUP BY estado_plataforma") as $row) {
                $porEstado[$row['estado_plataforma']] = (int)$row['total'];
            }
        } else {
            $porEstado['activo'] = (int)$this->count('activo = 1');
        }

        $ventas = $this->queryOne(
            "SELECT
                COUNT(*) AS pedidos_totales,
                COALESCE(SUM(total), 0) AS ventas_totales,
                COALESCE(SUM(CASE WHEN created_at >= DATE_FORMAT(NOW(),'%Y-%m-01') THEN total ELSE 0 END), 0) AS ventas_mes
             FROM rest_pedidos"
        );

        $usuariosNegocio = (int)$this->queryOne("SELECT COUNT(*) AS c FROM usuarios WHERE activo = 1")['c'];
        $usuariosApp     = (int)$this->queryOne("SELECT COUNT(*) AS c FROM mobile_usuarios WHERE activo = 1")['c'];

        $ranking = $this->query(
            "SELECT r.id, r.nombre, COALESCE(SUM(p.total), 0) AS ventas
               FROM rest_restaurantes r
          LEFT JOIN rest_pedidos p ON p.restaurante_id = r.id
           GROUP BY r.id, r.nombre
           ORDER BY ventas DESC
              LIMIT 5"
        );

        return [
            'negocios_total'      => array_sum($porEstado),
            'negocios_por_estado' => $porEstado,
            'pedidos_totales'     => (int)$ventas['pedidos_totales'],
            'ventas_totales'      => (float)$ventas['ventas_totales'],
            'ventas_mes'          => (float)$ventas['ventas_mes'],
            'usuarios_negocio'    => $usuariosNegocio,
            'usuarios_app'        => $usuariosApp,
            'ranking'             => $ranking,
        ];
    }

    /** Solo Superadmin debe llamar esto (ver BaseController::requireSuperAdmin). */
    public function setEstadoPlataforma(int $restauranteId, string $estado, ?int $planIdSiFaltaba = null): bool
    {
        if (!$this->columnExists('estado_plataforma')) return false;

        $ok = $this->execute(
            "UPDATE rest_restaurantes SET estado_plataforma = ? WHERE id = ?",
            [$estado, $restauranteId]
        );

        if ($ok && $planIdSiFaltaba !== null && $this->columnExists('plan_id')) {
            $this->execute(
                "UPDATE rest_restaurantes SET plan_id = ? WHERE id = ? AND plan_id IS NULL",
                [$planIdSiFaltaba, $restauranteId]
            );
        }

        return $ok;
    }

    public function getActiveByName(string $name): ?array
    {
        return $this->queryOne(
            "SELECT * FROM rest_restaurantes
             WHERE activo = 1 AND nombre LIKE ?
             ORDER BY id ASC
             LIMIT 1",
            ['%' . $name . '%']
        );
    }

    public function getLandingRestaurant(): ?array
    {
        $preferredSlugs = [
            'jungle-pizza-zihuatanejo',
            'jungle-pizza',
            'junglepizza',
            'jungle',
            'amare',
            'amare-restaurant',
            'amare-restaurante',
        ];

        foreach ($preferredSlugs as $slug) {
            $restaurant = $this->getBySlug($slug);
            if ($restaurant) {
                return $restaurant;
            }
        }

        $restaurant = $this->queryOne(
            "SELECT *
             FROM rest_restaurantes
             WHERE activo = 1 AND reservas_habilitadas = 1
             ORDER BY id ASC
             LIMIT 1"
        );

        if ($restaurant) {
            return $restaurant;
        }

        return $this->queryOne(
            "SELECT *
             FROM rest_restaurantes
             WHERE activo = 1
             ORDER BY id ASC
             LIMIT 1"
        );
    }

    public function verificarAcceso(int $restauranteId, int $compradorId): bool
    {
        $r = $this->queryOne(
            "SELECT id FROM rest_restaurantes WHERE id = ? AND comprador_id = ?",
            [$restauranteId, $compradorId]
        );
        return $r !== null;
    }

    public function getConStats(int $restauranteId): ?array
    {
        return $this->queryOne(
            "SELECT r.*,
                    (SELECT COUNT(*) FROM rest_mesas WHERE restaurante_id = r.id AND activo = 1) AS total_mesas,
                    (SELECT COUNT(*) FROM rest_mesas WHERE restaurante_id = r.id AND estado = 'ocupada') AS mesas_ocupadas,
                    (SELECT COUNT(*) FROM rest_pedidos WHERE restaurante_id = r.id AND estado IN ('pendiente','en_preparacion')) AS pedidos_activos,
                    (SELECT COUNT(*) FROM rest_platillos WHERE restaurante_id = r.id AND activo = 1) AS total_platillos,
                    (SELECT COUNT(*) FROM rest_staff WHERE restaurante_id = r.id AND activo = 1) AS total_staff
             FROM rest_restaurantes r WHERE r.id = ?",
            [$restauranteId]
        );
    }

    /**
     * Stats básicas para el dashboard de Admin en el modelo marketplace (pickup/delivery).
     * A diferencia de getConStats(), no depende de rest_mesas ni rest_staff — tablas que
     * no existen en el esquema recortado (idactivo_cafeteq.sql).
     */
    public function getStatsMarketplace(int $restauranteId): ?array
    {
        return $this->queryOne(
            "SELECT r.*,
                    (SELECT COUNT(*) FROM rest_pedidos WHERE restaurante_id = r.id AND estado IN ('pendiente','en_preparacion','listo','en_camino')) AS pedidos_activos,
                    (SELECT COUNT(*) FROM rest_platillos WHERE restaurante_id = r.id AND activo = 1) AS total_platillos
             FROM rest_restaurantes r WHERE r.id = ?",
            [$restauranteId]
        );
    }

    public function slugExiste(string $slug, int $excludeId = 0): bool
    {
        $r = $this->queryOne(
            "SELECT id FROM rest_restaurantes WHERE slug = ? AND id != ?",
            [$slug, $excludeId]
        );
        return $r !== null;
    }

    public function generarSlugUnico(string $nombre): string
    {
        $base = strtolower(trim(preg_replace('/[^a-z0-9]+/i', '-', iconv('UTF-8', 'ASCII//TRANSLIT', $nombre)), '-'));
        $slug = $base;
        $i = 1;
        while ($this->slugExiste($slug)) {
            $slug = $base . '-' . $i++;
        }
        return $slug;
    }

    /** Devuelve email y nombre de la empresa dueña del restaurante (para notificaciones). */
    public function getAdminEmail(int $restauranteId): ?array
    {
        return $this->queryOne(
            "SELECT e.email, e.razon_social AS nombre
             FROM rest_restaurantes r
             JOIN empresas e ON e.id = r.empresa_id
             WHERE r.id = ? LIMIT 1",
            [$restauranteId]
        );
    }
}
