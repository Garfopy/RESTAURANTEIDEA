<?php

class RestFacturaSolicitudModel extends BaseModel
{
    protected string $table = 'facturacion_solicitudes';

    private const ESTADOS = ['pendiente', 'en_proceso', 'facturada', 'cancelada'];

    private static array $columnCache = [];

    private function hasColumn(string $column): bool
    {
        if (array_key_exists($column, self::$columnCache)) {
            return self::$columnCache[$column];
        }

        try {
            $stmt = $this->db->prepare(
                "SELECT COUNT(*)
                   FROM information_schema.columns
                  WHERE table_schema = DATABASE()
                    AND table_name = 'facturacion_solicitudes'
                    AND column_name = ?"
            );
            $stmt->execute([$column]);
            self::$columnCache[$column] = (int)$stmt->fetchColumn() > 0;
        } catch (\Throwable $e) {
            self::$columnCache[$column] = false;
        }

        return self::$columnCache[$column];
    }

    public function listar(int $restauranteId, array $filtros = []): array
    {
        $page = max(1, (int)($filtros['page'] ?? 1));
        $perPage = min(100, max(1, (int)($filtros['per_page'] ?? 20)));

        $where = ['fs.restaurante_id = ?'];
        $params = [$restauranteId];

        $estado = (string)($filtros['estado'] ?? '');
        if ($estado !== '' && in_array($estado, self::ESTADOS, true)) {
            $where[] = 'fs.estado = ?';
            $params[] = $estado;
        }

        $from = trim((string)($filtros['from'] ?? ''));
        if ($from !== '' && preg_match('/^\d{4}-\d{2}-\d{2}$/', $from)) {
            $where[] = 'DATE(fs.created_at) >= ?';
            $params[] = $from;
        }

        $to = trim((string)($filtros['to'] ?? ''));
        if ($to !== '' && preg_match('/^\d{4}-\d{2}-\d{2}$/', $to)) {
            $where[] = 'DATE(fs.created_at) <= ?';
            $params[] = $to;
        }

        $sql = "SELECT fs.*,
                       fs.receptor_nombre AS receptor_nombre_fiscal,
                       fs.uso_cfdi AS receptor_uso_cfdi,
                       NULL AS ticket_id,
                       NULL AS ticket_folio,
                       r.nombre AS restaurante_nombre,
                       m.nombre AS mesa_nombre,
                       p.folio AS pedido_folio
                  FROM facturacion_solicitudes fs
                  JOIN rest_restaurantes r ON r.id = fs.restaurante_id
             LEFT JOIN rest_mesas m ON m.id = fs.mesa_id
             LEFT JOIN rest_pedidos p ON p.id = fs.pedido_id
                 WHERE " . implode(' AND ', $where) . "
              ORDER BY fs.created_at DESC, fs.id DESC";

        return $this->paginate($sql, $params, $page, $perPage);
    }

    public function buscarParaRestaurante(int $id, int $restauranteId): ?array
    {
        return $this->queryOne(
            "SELECT fs.*,
                    fs.receptor_nombre AS receptor_nombre_fiscal,
                    fs.uso_cfdi AS receptor_uso_cfdi,
                    NULL AS ticket_id,
                    NULL AS ticket_folio,
                    r.nombre AS restaurante_nombre,
                    m.nombre AS mesa_nombre,
                    p.folio AS pedido_folio
               FROM facturacion_solicitudes fs
               JOIN rest_restaurantes r ON r.id = fs.restaurante_id
          LEFT JOIN rest_mesas m ON m.id = fs.mesa_id
          LEFT JOIN rest_pedidos p ON p.id = fs.pedido_id
              WHERE fs.id = ? AND fs.restaurante_id = ?
              LIMIT 1",
            [$id, $restauranteId]
        );
    }

    public function actualizarEstado(int $id, int $restauranteId, array $data): bool
    {
        $estado = (string)($data['estado'] ?? '');
        if (!in_array($estado, self::ESTADOS, true)) {
            throw new InvalidArgumentException('Estado de factura invalido.');
        }

        if ($estado === 'facturada') {
            foreach (['cfdi_uuid', 'pdf_url', 'xml_url'] as $field) {
                if (trim((string)($data[$field] ?? '')) === '') {
                    throw new InvalidArgumentException('UUID, PDF y XML son obligatorios para marcar como facturada.');
                }
            }
        }

        $sets = [
            'estado = ?',
            'cfdi_uuid = ?',
            'pdf_url = ?',
            'xml_url = ?',
            'notas = ?',
            "facturada_at = CASE WHEN ? = 'facturada' AND facturada_at IS NULL THEN NOW() ELSE facturada_at END",
        ];
        $params = [
            $estado,
            trim((string)($data['cfdi_uuid'] ?? '')) ?: null,
            trim((string)($data['pdf_url'] ?? '')) ?: null,
            trim((string)($data['xml_url'] ?? '')) ?: null,
            trim((string)($data['notas'] ?? '')) ?: null,
            $estado,
            $id,
            $restauranteId,
        ];

        foreach (['facturapi_invoice_id', 'facturapi_status', 'facturapi_livemode'] as $field) {
            if ($this->hasColumn($field) && array_key_exists($field, $data)) {
                $sets[] = $field . ' = ?';
                array_splice($params, -2, 0, [$data[$field]]);
            }
        }

        return $this->execute(
            "UPDATE facturacion_solicitudes SET " . implode(', ', $sets) . " WHERE id = ? AND restaurante_id = ?",
            $params
        );
    }

    public function normalizar(array $row): array
    {
        return [
            'id' => (int)$row['id'],
            'restaurante_id' => (int)$row['restaurante_id'],
            'restaurante_nombre' => $row['restaurante_nombre'] ?? null,
            'pedido_id' => $row['pedido_id'] !== null ? (int)$row['pedido_id'] : null,
            'mesa_id' => $row['mesa_id'] !== null ? (int)$row['mesa_id'] : null,
            'ticket_id' => $row['ticket_id'] !== null ? (int)$row['ticket_id'] : null,
            'division_id' => $row['division_id'] !== null ? (int)$row['division_id'] : null,
            'division_cuenta_id' => $row['division_cuenta_id'] !== null ? (int)$row['division_cuenta_id'] : null,
            'origen' => $row['origen'],
            'scope' => $row['scope'],
            'monto' => (float)$row['monto'],
            'metodo_pago' => $row['metodo_pago'],
            'estado' => $row['estado'],
            'receptor' => [
                'rfc' => $row['receptor_rfc'],
                'nombre_fiscal' => $row['receptor_nombre_fiscal'],
                'regimen_fiscal' => $row['receptor_regimen_fiscal'],
                'codigo_postal' => $row['receptor_codigo_postal'],
                'uso_cfdi' => $row['receptor_uso_cfdi'],
                'email' => $row['receptor_email'],
            ],
            'cfdi_uuid' => $row['cfdi_uuid'],
            'pdf_url' => $row['pdf_url'],
            'xml_url' => $row['xml_url'],
            'facturapi_invoice_id' => $row['facturapi_invoice_id'] ?? null,
            'facturapi_status' => $row['facturapi_status'] ?? null,
            'facturapi_livemode' => isset($row['facturapi_livemode']) ? (bool)$row['facturapi_livemode'] : null,
            'notas' => $row['notas'],
            'created_at' => $row['created_at'],
            'updated_at' => $row['updated_at'] ?? null,
        ];
    }
}
