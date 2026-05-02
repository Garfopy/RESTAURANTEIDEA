<?php
class EntregaModel extends BaseModel
{
    protected string $table = 'ruta_detalle';

    public function guardarEvidencia(int $rutaDetalleId, string $tipo, string $archivo, string $receptor): int
    {
        return (int)$this->execute(
            'INSERT INTO evidencias_entrega (ruta_detalle_id, tipo, archivo, receptor_nombre, timestamp_entrega)
             VALUES (?, ?, ?, ?, NOW())',
            [$rutaDetalleId, $tipo, $archivo, $receptor]
        );
    }

    public function getEvidencias(int $rutaDetalleId): array
    {
        return $this->query(
            'SELECT * FROM evidencias_entrega WHERE ruta_detalle_id = ? ORDER BY timestamp_entrega',
            [$rutaDetalleId]
        );
    }

    public function getHistorialChofer(int $choferId, int $limit = 20): array
    {
        return $this->query(
            'SELECT rd.*, r.fecha, p.folio, p.total,
                    s.nombre AS sucursal_nombre, s.direccion,
                    e.razon_social AS empresa_nombre
               FROM ruta_detalle rd
               JOIN rutas r ON r.id = rd.ruta_id
               JOIN pedidos p ON p.id = rd.pedido_id
               JOIN sucursales s ON s.id = rd.sucursal_id
               JOIN empresas e ON e.id = p.empresa_id
              WHERE r.chofer_id = ?
           ORDER BY r.fecha DESC, rd.orden_entrega
              LIMIT ?',
            [$choferId, $limit]
        );
    }
}
