<?php
class RestClienteModel extends BaseModel
{
    protected string $table = 'rest_comensales';

    public function getByRestaurante(int $restauranteId, int $page = 1): array
    {
        $sql = "SELECT * FROM rest_comensales WHERE restaurante_id = ? ORDER BY ultima_visita DESC";
        return $this->paginate($sql, [$restauranteId], $page);
    }

    public function buscarOCrear(int $restauranteId, ?string $nombre, ?string $telefono, ?string $email): int
    {
        if ($telefono) {
            $existing = $this->queryOne(
                "SELECT id FROM rest_comensales WHERE restaurante_id = ? AND telefono = ?",
                [$restauranteId, $telefono]
            );
            if ($existing) return (int) $existing['id'];
        }

        $this->execute(
            "INSERT INTO rest_comensales (restaurante_id, nombre, telefono, email) VALUES (?,?,?,?)",
            [$restauranteId, $nombre, $telefono, $email]
        );
        return (int) $this->db->lastInsertId();
    }

    public function registrarVisita(int $comensalId, float $gasto): void
    {
        $this->execute(
            "UPDATE rest_comensales
             SET total_visitas = total_visitas + 1,
                 total_gastado = total_gastado + ?,
                 ultima_visita = NOW()
             WHERE id = ?",
            [$gasto, $comensalId]
        );
    }

    public function topPorConsumo(int $restauranteId, int $limit = 20): array
    {
        return $this->query(
            "SELECT * FROM rest_comensales WHERE restaurante_id = ? ORDER BY total_gastado DESC LIMIT $limit",
            [$restauranteId]
        );
    }

    public function topPorVisitas(int $restauranteId, int $limit = 20): array
    {
        return $this->query(
            "SELECT * FROM rest_comensales WHERE restaurante_id = ? ORDER BY total_visitas DESC LIMIT $limit",
            [$restauranteId]
        );
    }

    public function getHistorialVisitas(int $comensalId): array
    {
        return $this->query(
            "SELECT v.*, m.nombre AS mesa_nombre, t.total AS ticket_total, t.metodo_pago
             FROM rest_visitas v
             LEFT JOIN rest_mesas m ON m.id = v.mesa_id
             LEFT JOIN rest_tickets t ON t.visita_id = v.id
             WHERE v.comensal_id = ?
             ORDER BY v.created_at DESC",
            [$comensalId]
        );
    }
}
