<?php
require_once ROOT_PATH . '/app/models/BaseModel.php';

class RecurrenteModel extends BaseModel
{
    protected string $table = 'pedidos_recurrentes';

    /** Resumen: activos, inactivos, próxima fecha de ejecución */
    public function getResumen(int $empresaId): array
    {
        $row = $this->queryOne(
            "SELECT
                COALESCE(SUM(activo = 1), 0) AS activos,
                COALESCE(SUM(activo = 0), 0) AS inactivos,
                MIN(CASE WHEN activo = 1 AND proximo_pedido >= CURDATE() THEN proximo_pedido END) AS proxima_fecha
             FROM pedidos_recurrentes
             WHERE empresa_id = ?",
            [$empresaId]
        );
        return [
            'activos'       => (int)($row['activos']       ?? 0),
            'inactivos'     => (int)($row['inactivos']     ?? 0),
            'proxima_fecha' => $row['proxima_fecha']        ?? null,
        ];
    }

    /** Conteo agrupado por frecuencia (solo activos) */
    public function getTopPorFrecuencia(int $empresaId): array
    {
        return $this->query(
            "SELECT frecuencia, COUNT(*) AS total
               FROM pedidos_recurrentes
              WHERE empresa_id = ? AND activo = 1
              GROUP BY frecuencia
              ORDER BY FIELD(frecuencia, 'diario', 'semanal', 'quincenal')",
            [$empresaId]
        );
    }

    /** Top productos más solicitados en plantillas activas */
    public function getTopProductos(int $empresaId, int $limit = 8): array
    {
        return $this->query(
            "SELECT pr.nombre, pr.presentacion,
                    SUM(d.cantidad) AS cantidad_acumulada,
                    COUNT(DISTINCT d.recurrente_id) AS en_plantillas
               FROM plantilla_recurrente_detalle d
               JOIN productos pr ON pr.id = d.producto_id
               JOIN pedidos_recurrentes r ON r.id = d.recurrente_id
              WHERE r.empresa_id = ? AND r.activo = 1
              GROUP BY pr.id, pr.nombre, pr.presentacion
              ORDER BY cantidad_acumulada DESC
              LIMIT ?",
            [$empresaId, $limit]
        );
    }

    /** Listado completo de plantillas con conteo de líneas */
    public function getListado(int $empresaId): array
    {
        return $this->query(
            "SELECT r.id, r.nombre, r.frecuencia, r.dia_semana, r.activo,
                    r.proximo_pedido, r.created_at,
                    COUNT(d.id) AS total_productos,
                    u.nombre AS creado_por
               FROM pedidos_recurrentes r
               LEFT JOIN plantilla_recurrente_detalle d ON d.recurrente_id = r.id
               LEFT JOIN usuarios u ON u.id = r.created_by
              WHERE r.empresa_id = ?
              GROUP BY r.id, r.nombre, r.frecuencia, r.dia_semana, r.activo,
                       r.proximo_pedido, r.created_at, u.nombre
              ORDER BY r.activo DESC, r.proximo_pedido ASC",
            [$empresaId]
        );
    }

    /** Próximas N ejecuciones programadas (activas, ordenadas por fecha) */
    public function getProximasEjecuciones(int $empresaId, int $limit = 5): array
    {
        return $this->query(
            "SELECT r.nombre, r.frecuencia, r.proximo_pedido,
                    COUNT(d.id) AS total_productos
               FROM pedidos_recurrentes r
               LEFT JOIN plantilla_recurrente_detalle d ON d.recurrente_id = r.id
              WHERE r.empresa_id = ? AND r.activo = 1
              GROUP BY r.id, r.nombre, r.frecuencia, r.proximo_pedido
              ORDER BY r.proximo_pedido ASC
              LIMIT ?",
            [$empresaId, $limit]
        );
    }
}
