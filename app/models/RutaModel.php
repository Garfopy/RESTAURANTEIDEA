<?php
class RutaModel extends BaseModel
{
    protected string $table = 'rutas';

    public function getDelDia(string $fecha): array
    {
        return $this->query(
            'SELECT r.*,
                    u.nombre AS chofer_nombre,
                    v.placa,
                    v.marca,
                    v.modelo
               FROM rutas r
          LEFT JOIN choferes c ON c.id = r.chofer_id
          LEFT JOIN usuarios u ON u.id = c.usuario_id
          LEFT JOIN vehiculos v ON v.id = r.vehiculo_id
              WHERE r.fecha = ?
           ORDER BY r.id',
            [$fecha]
        );
    }

    public function getConDetalle(int $id): ?array
    {
        $ruta = $this->queryOne(
            'SELECT r.*, u.nombre AS chofer_nombre, v.placa, v.marca, v.modelo
               FROM rutas r
          LEFT JOIN choferes c ON c.id = r.chofer_id
          LEFT JOIN usuarios u ON u.id = c.usuario_id
          LEFT JOIN vehiculos v ON v.id = r.vehiculo_id
              WHERE r.id = ?',
            [$id]
        );
        if (!$ruta) return null;

        $ruta['paradas'] = $this->query(
            'SELECT rd.*, p.folio, p.total,
                    s.nombre AS sucursal_nombre, s.direccion, s.lat, s.lng,
                    s.contacto_nombre, s.contacto_telefono
               FROM ruta_detalle rd
               JOIN pedidos p ON p.id = rd.pedido_id
               JOIN sucursales s ON s.id = rd.sucursal_id
              WHERE rd.ruta_id = ?
           ORDER BY rd.orden_entrega',
            [$id]
        );
        return $ruta;
    }

    public function getEntregasChofer(int $choferId, string $fecha): array
    {
        return $this->query(
            'SELECT rd.*, p.folio, p.total, p.notas,
                    s.nombre AS sucursal_nombre, s.direccion, s.lat, s.lng,
                    s.contacto_nombre, s.contacto_telefono,
                    e.razon_social AS empresa_nombre
               FROM rutas r
               JOIN ruta_detalle rd ON rd.ruta_id = r.id
               JOIN pedidos p ON p.id = rd.pedido_id
               JOIN sucursales s ON s.id = rd.sucursal_id
               JOIN empresas e ON e.id = p.empresa_id
              WHERE r.chofer_id = ? AND r.fecha = ?
           ORDER BY rd.orden_entrega',
            [$choferId, $fecha]
        );
    }

    public function actualizarEstadoParada(int $rutaDetalleId, string $estado): bool
    {
        return $this->execute(
            'UPDATE ruta_detalle SET estado = ? WHERE id = ?',
            [$estado, $rutaDetalleId]
        );
    }

    public function getRutaHoyChofer(int $choferId): ?array
    {
        return $this->queryOne(
            'SELECT r.* FROM rutas r WHERE r.chofer_id = ? AND r.fecha = CURDATE() LIMIT 1',
            [$choferId]
        );
    }

    public function getDetalle(int $rutaId): array
    {
        return $this->query(
            'SELECT rd.*, p.folio, p.total, p.notas, p.ventana_entrega,
                    e.razon_social empresa_nombre,
                    s.nombre sucursal_nombre, s.direccion sucursal_dir, s.lat, s.lng,
                    s.contacto_nombre, s.contacto_telefono
               FROM ruta_detalle rd
               JOIN pedidos p  ON p.id  = rd.pedido_id
               JOIN empresas e ON e.id  = p.empresa_id
               JOIN sucursales s ON s.id = rd.sucursal_id
              WHERE rd.ruta_id = ?
           ORDER BY rd.orden_entrega',
            [$rutaId]
        );
    }

    public function getDetalleItem(int $id): ?array
    {
        return $this->queryOne(
            'SELECT rd.*, p.folio, p.total, p.notas, p.ventana_entrega,
                    e.razon_social empresa_nombre,
                    s.nombre sucursal_nombre, s.direccion sucursal_dir, s.lat, s.lng,
                    s.contacto_nombre, s.contacto_telefono
               FROM ruta_detalle rd
               JOIN pedidos p  ON p.id  = rd.pedido_id
               JOIN empresas e ON e.id  = p.empresa_id
               JOIN sucursales s ON s.id = rd.sucursal_id
              WHERE rd.id = ?',
            [$id]
        );
    }

    public function getRutasPorChofer(int $choferId, int $limite = 20): array
    {
        return $this->query(
            'SELECT r.* FROM rutas r WHERE r.chofer_id = ? ORDER BY r.fecha DESC LIMIT ?',
            [$choferId, $limite]
        );
    }

    public function completarEntrega(int $detalleId, string $receptorNombre, array $archivos): void
    {
        $this->execute(
            'UPDATE ruta_detalle SET estado = ? WHERE id = ?',
            ['entregado', $detalleId]
        );

        $item = $this->getDetalleItem($detalleId);
        if ($item) {
            $this->execute(
                'UPDATE pedidos SET estado = ? WHERE id = ?',
                ['entregado', $item['pedido_id']]
            );
        }

        foreach ($archivos as $tipo => $archivo) {
            $this->execute(
                'INSERT INTO evidencias_entrega (ruta_detalle_id, tipo, archivo, receptor_nombre, timestamp_entrega)
                 VALUES (?,?,?,?,NOW())',
                [$detalleId, $tipo, $archivo, $receptorNombre]
            );
        }
    }

    public function getRutasConDetalle(array $filtros = []): array
    {
        $where  = '1=1';
        $params = [];
        if (!empty($filtros['fecha']))    { $where .= ' AND r.fecha = ?';      $params[] = $filtros['fecha']; }
        if (!empty($filtros['chofer_id'])){ $where .= ' AND r.chofer_id = ?';  $params[] = $filtros['chofer_id']; }
        if (!empty($filtros['estado']))   { $where .= ' AND r.estado = ?';     $params[] = $filtros['estado']; }

        return $this->query(
            "SELECT r.*, u.nombre chofer_nombre, v.placa vehiculo_placa
               FROM rutas r
          LEFT JOIN choferes c  ON c.id = r.chofer_id
          LEFT JOIN usuarios u  ON u.id = c.usuario_id
          LEFT JOIN vehiculos v ON v.id = r.vehiculo_id
              WHERE $where
           ORDER BY r.fecha DESC",
            $params
        );
    }
}
