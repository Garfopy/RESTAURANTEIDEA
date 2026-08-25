<?php

class RestVisibilidadFinancieraModel extends BaseModel
{
    protected string $table = 'rest_visibilidad_financiera';

    public function getConfiguracion(int $restauranteId): array
    {
        $row = $this->queryOne(
            "SELECT vf.*, u.nombre AS actualizado_por_nombre
               FROM rest_visibilidad_financiera vf
          LEFT JOIN usuarios u ON u.id = vf.actualizado_por
              WHERE vf.restaurante_id = ?",
            [$restauranteId]
        );

        return $row ?: [
            'restaurante_id' => $restauranteId,
            'activo' => 0,
            'ocultar_hasta' => null,
            'actualizado_por' => null,
            'actualizado_por_nombre' => null,
            'updated_at' => null,
        ];
    }

    public function fechaVisibleDesde(int $restauranteId, string $rol): ?string
    {
        if ($rol === 'superadmin') {
            return null;
        }

        $config = $this->getConfiguracion($restauranteId);
        if (empty($config['activo']) || empty($config['ocultar_hasta'])) {
            return null;
        }

        $fecha = DateTimeImmutable::createFromFormat('!Y-m-d', (string)$config['ocultar_hasta']);
        return $fecha ? $fecha->modify('+1 day')->format('Y-m-d') : null;
    }

    public function guardarOcultamiento(int $restauranteId, string $ocultarHasta, int $usuarioId): void
    {
        $fecha = DateTimeImmutable::createFromFormat('!Y-m-d', $ocultarHasta);
        $errores = DateTimeImmutable::getLastErrors();
        if (!$fecha || ($errores !== false && ($errores['warning_count'] || $errores['error_count']))) {
            throw new InvalidArgumentException('La fecha seleccionada no es valida.');
        }
        if ($fecha->format('Y-m-d') > date('Y-m-d')) {
            throw new InvalidArgumentException('La fecha no puede estar en el futuro.');
        }

        $this->db->beginTransaction();
        try {
            $this->execute(
                "INSERT INTO rest_visibilidad_financiera
                    (restaurante_id, activo, ocultar_hasta, actualizado_por)
                 VALUES (?, 1, ?, ?)
                 ON DUPLICATE KEY UPDATE
                    activo = 1,
                    ocultar_hasta = VALUES(ocultar_hasta),
                    actualizado_por = VALUES(actualizado_por)",
                [$restauranteId, $fecha->format('Y-m-d'), $usuarioId]
            );
            $this->registrarHistorial($restauranteId, 'ocultar', $fecha->format('Y-m-d'), $usuarioId);
            $this->db->commit();
        } catch (Throwable $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    public function restaurarVisibilidad(int $restauranteId, int $usuarioId): void
    {
        $config = $this->getConfiguracion($restauranteId);

        $this->db->beginTransaction();
        try {
            $this->execute(
                "INSERT INTO rest_visibilidad_financiera
                    (restaurante_id, activo, ocultar_hasta, actualizado_por)
                 VALUES (?, 0, NULL, ?)
                 ON DUPLICATE KEY UPDATE
                    activo = 0,
                    ocultar_hasta = NULL,
                    actualizado_por = VALUES(actualizado_por)",
                [$restauranteId, $usuarioId]
            );
            $restaurada = $this->queryOne(
                "SELECT activo, ocultar_hasta
                   FROM rest_visibilidad_financiera
                  WHERE restaurante_id = ?
                  FOR UPDATE",
                [$restauranteId]
            );
            if (!$restaurada || (int)$restaurada['activo'] !== 0 || $restaurada['ocultar_hasta'] !== null) {
                throw new RuntimeException('No se pudo desactivar completamente el filtro de visibilidad.');
            }
            $this->registrarHistorial(
                $restauranteId,
                'restaurar',
                $config['ocultar_hasta'] ?: null,
                $usuarioId
            );
            $this->db->commit();
        } catch (Throwable $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    public function getHistorial(int $restauranteId, int $limit = 20): array
    {
        $limit = max(1, min(100, $limit));
        return $this->query(
            "SELECT h.*, u.nombre AS usuario_nombre
               FROM rest_visibilidad_financiera_historial h
          LEFT JOIN usuarios u ON u.id = h.usuario_id
              WHERE h.restaurante_id = ?
              ORDER BY h.created_at DESC, h.id DESC
              LIMIT {$limit}",
            [$restauranteId]
        );
    }

    private function registrarHistorial(
        int $restauranteId,
        string $accion,
        ?string $ocultarHasta,
        int $usuarioId
    ): void {
        $this->execute(
            "INSERT INTO rest_visibilidad_financiera_historial
                (restaurante_id, accion, ocultar_hasta, usuario_id)
             VALUES (?, ?, ?, ?)",
            [$restauranteId, $accion, $ocultarHasta, $usuarioId]
        );
    }
}
