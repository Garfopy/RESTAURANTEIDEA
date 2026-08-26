<?php
class RestPromocionModel extends BaseModel
{
    protected string $table = 'rest_promociones';

    /**
     * Lista promociones de un restaurante con conteo de comensales asignados.
     */
    public function listar(int $restauranteId, int $page = 1): array
    {
        $sql = "SELECT p.*,
                       COALESCE(COUNT(pc.id), 0) AS total_asignados,
                       COALESCE(SUM(pc.usado), 0) AS total_usados
                FROM rest_promociones p
                LEFT JOIN rest_promocion_comensales pc ON pc.promocion_id = p.id
                WHERE p.restaurante_id = ?
                GROUP BY p.id
                ORDER BY p.created_at DESC";
        return $this->paginate($sql, [$restauranteId], $page);
    }

    /**
     * Obtiene una promoción por ID, restringida al restaurante.
     */
    public function findByRestaurant(int $id, int $restauranteId): ?array
    {
        return $this->queryOne(
            "SELECT * FROM rest_promociones
            WHERE id = ? AND restaurante_id = ?",
            [$id, $restauranteId]
        );
    }

    /**
     * Crea una nueva promoción. Devuelve el ID insertado.
     */
    public function crear(array $data): int
    {
        $this->execute(
            "INSERT INTO rest_promociones (restaurante_id, titulo, descripcion, tipo, valor_descuento, fecha_inicio, fecha_fin, activo)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?)",
            [
                $data['restaurante_id'],
                $data['titulo'],
                $data['descripcion'] ?? null,
                $data['tipo'],
                $data['valor_descuento'],
                $data['fecha_inicio'],
                $data['fecha_fin'],
                $data['activo'] ? 1 : 0,
            ]
        );
        return (int) $this->db->lastInsertId();
    }

    /**
     * Actualiza una promoción existente.
     */
    public function actualizar(int $id, array $data): bool
    {
        return $this->execute(
            "UPDATE rest_promociones
             SET titulo = ?, descripcion = ?, tipo = ?, valor_descuento = ?,
                 fecha_inicio = ?, fecha_fin = ?, activo = ?
             WHERE id = ?",
            [
                $data['titulo'],
                $data['descripcion'] ?? null,
                $data['tipo'],
                $data['valor_descuento'],
                $data['fecha_inicio'],
                $data['fecha_fin'],
                $data['activo'] ? 1 : 0,
                $id,
            ]
        );
    }

    /**
     * Elimina una promoción y sus relaciones con comensales (CASCADE en BD).
     */
    public function eliminar(int $id, int $restauranteId): bool
    {
        return $this->execute(
            "DELETE FROM rest_promociones WHERE id = ? AND restaurante_id = ?",
            [$id, $restauranteId]
        );
    }

    /**
     * Asigna comensales a una promoción. Reemplaza la lista anterior.
     * @param int[] $comensalIds
     */
    public function asignarComensales(int $promocionId, array $comensalIds): void
    {
        // Eliminar asignaciones anteriores
        $this->execute(
            "DELETE FROM rest_promocion_comensales WHERE promocion_id = ?",
            [$promocionId]
        );

        if (empty($comensalIds)) {
            return;
        }

        // Insertar nuevas asignaciones
        $sql = "INSERT INTO rest_promocion_comensales (promocion_id, comensal_id) VALUES (?, ?)";
        foreach ($comensalIds as $cid) {
            $this->execute($sql, [$promocionId, (int)$cid]);
        }
    }

    /**
     * Obtiene los IDs de comensales asignados a una promoción.
     * @return int[]
     */
    public function getComensalesAsignados(int $promocionId): array
    {
        $rows = $this->query(
            "SELECT comensal_id FROM rest_promocion_comensales WHERE promocion_id = ?",
            [$promocionId]
        );
        return array_map(fn($row) => (int)$row['comensal_id'], $rows);
    }

    /**
     * Obtiene los comensales con nombre para mostrar en el detalle.
     */
    public function getComensalesConNombre(int $promocionId): array
    {
        return $this->query(
            "SELECT c.id, c.nombre, c.email, pc.usado, pc.fecha_uso
             FROM rest_promocion_comensales pc
             JOIN rest_comensales c ON c.id = pc.comensal_id
             WHERE pc.promocion_id = ?
             ORDER BY c.nombre ASC",
            [$promocionId]
        );
    }

    /**
     * Lista comensales del restaurante para el selector.
     */
    public function listarComensales(int $restauranteId): array
    {
        return $this->query(
            "SELECT id, nombre, email, telefono
             FROM rest_comensales
             WHERE restaurante_id = ?
             ORDER BY nombre ASC",
            [$restauranteId]
        );
    }

    // ── Caja (POS) ───────────────────────────────────────────────

    /** Cupón por código, solo si es de este negocio y está vigente hoy. */
    public function porCodigoVigente(int $restauranteId, string $code): ?array
    {
        return $this->queryOne(
            "SELECT * FROM rest_promociones
              WHERE restaurante_id = ? AND UPPER(code) = UPPER(?) AND activo = 1
                AND CURDATE() BETWEEN fecha_inicio AND fecha_fin
                AND (expires_at IS NULL OR expires_at > NOW())
              LIMIT 1",
            [$restauranteId, $code]
        );
    }

    /**
     * Deja constancia de que un cupón se usó en una venta.
     * `mobile_promocion_usos` tiene UNIQUE por (usuario, promoción), así que
     * un cliente identificado que repite cupón choca ahí: se ignora el error
     * porque el descuento ya se aplicó y la venta no debe caerse por la bitácora.
     */
    public function registrarUso(int $promocionId, string $code, int $pedidoId, ?int $mobileUsuarioId, float $descuento): void
    {
        try {
            $this->execute(
                "INSERT IGNORE INTO mobile_promocion_usos
                   (promocion_id, usuario_id, pedido_id, codigo, descuento_mxn, estado)
                 VALUES (?,?,?,?,?, 'usado')",
                [$promocionId, $mobileUsuarioId ?? 0, $pedidoId, $code, $descuento]
            );
        } catch (\Throwable $e) {
            error_log('[caja] No se pudo registrar el uso del cupón ' . $code . ': ' . $e->getMessage());
        }
    }

    /** Auditoría de descuentos manuales hechos en caja (decisión D9). */
    public function registrarDescuentoManual(array $datos): void
    {
        try {
            $this->execute(
                "INSERT INTO rest_descuentos_log
                   (pedido_id, restaurante_id, cajero_id, tipo, valor, monto_aplicado,
                    motivo, requirio_autorizacion, autorizado_por_id)
                 VALUES (?,?,?,?,?,?,?,?,?)",
                [
                    $datos['pedido_id'], $datos['restaurante_id'], $datos['cajero_id'],
                    $datos['tipo'], $datos['valor'], $datos['monto_aplicado'],
                    $datos['motivo'], $datos['requirio_autorizacion'], $datos['autorizado_por_id'],
                ]
            );
        } catch (\Throwable $e) {
            error_log('[caja] No se pudo registrar el descuento manual: ' . $e->getMessage());
        }
    }

    /**
     * Devuelve promociones activas hoy para un comensal específico.
     * Útil para el menú público.
     */
    public function getActivasParaComensal(int $comensalId, int $restauranteId): array
    {
        return $this->query(
            "SELECT p.*
             FROM rest_promociones p
             JOIN rest_promocion_comensales pc ON pc.promocion_id = p.id
             WHERE p.restaurante_id = ?
               AND p.activo = 1
               AND pc.comensal_id = ?
               AND pc.usado = 0
               AND CURDATE() BETWEEN p.fecha_inicio AND p.fecha_fin
             ORDER BY p.valor_descuento DESC",
            [$restauranteId, $comensalId]
        );
    }
}