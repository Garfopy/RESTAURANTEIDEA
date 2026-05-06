<?php
class PedidoModel extends BaseModel
{
    protected string $table = 'pedidos';

    public function generarFolio(): string
    {
        $anio = date('Y');
        $row  = $this->queryOne(
            "SELECT MAX(CAST(SUBSTRING_INDEX(folio, '-', -1) AS UNSIGNED)) AS ultimo
               FROM pedidos WHERE folio LIKE ?",
            ["CHB-{$anio}-%"]
        );
        $num = (int)($row['ultimo'] ?? 0) + 1;
        return sprintf('CHB-%s-%04d', $anio, $num);
    }

    /**
     * Crea pedido + detalle + pedido_sucursal en una transacción.
     *
     * $pedidoData: campos directos para la tabla pedidos (sin folio, subtotal, total).
     * $items: [['producto_id'=>, 'cantidad'=>, 'precio_unit'=>, 'subtotal'=>], ...]
     * $sucursalesIds: [sucursal_id, ...] — sucursales involucradas
     */
    public function crear(array $pedidoData, array $items, array $sucursalesIds = []): int
    {
        $this->db->beginTransaction();
        try {
            $subtotal = array_sum(array_column($items, 'subtotal'));
            $pedidoData['folio']    = $this->generarFolio();
            $pedidoData['subtotal'] = $subtotal;
            $pedidoData['total']    = $subtotal;

            $pedidoId = $this->insert($pedidoData);

            foreach ($items as $item) {
                $this->execute(
                    'INSERT INTO pedido_detalle (pedido_id, producto_id, cantidad, precio_unit, subtotal)
                     VALUES (?, ?, ?, ?, ?)',
                    [$pedidoId, $item['producto_id'], $item['cantidad'], $item['precio_unit'], $item['subtotal']]
                );
            }

            foreach (array_unique($sucursalesIds) as $sucursalId) {
                $this->execute(
                    'INSERT INTO pedido_sucursal (pedido_id, sucursal_id) VALUES (?, ?)',
                    [$pedidoId, $sucursalId]
                );
            }

            $this->db->commit();
            return $pedidoId;
        } catch (\Throwable $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    public function asignarEntrega(int $id, string $tipo, ?int $repartidorId, float $costoEnvio, string $notaEmpresa = ''): void
    {
        $this->execute(
            'UPDATE pedidos SET tipo_entrega = ?, repartidor_asignado_id = ?, costo_envio = ?, nota_empresa = ?
              WHERE id = ?',
            [$tipo, $repartidorId, $costoEnvio, $notaEmpresa ?: null, $id]
        );
    }

    public function aprobarPedido(int $id, int $aprobadoPorId, array $ajustes = []): void
    {
        $this->db->beginTransaction();
        try {
            // Apply price adjustments (admin can only lower prices)
            foreach ($ajustes as $detalleId => $precioNuevo) {
                $detalleId  = (int)$detalleId;
                $precioNuevo = (float)$precioNuevo;
                if ($detalleId <= 0 || $precioNuevo <= 0) continue;

                $linea = $this->queryOne(
                    'SELECT id, precio_unit, cantidad FROM pedido_detalle WHERE id = ? AND pedido_id = ?',
                    [$detalleId, $id]
                );
                if (!$linea) continue;
                $precioOriginal = (float)$linea['precio_unit'];
                if ($precioNuevo >= $precioOriginal) continue; // Only allow lowering

                $subtotalNuevo = round($precioNuevo * (float)$linea['cantidad'], 2);
                $this->execute(
                    'UPDATE pedido_detalle SET precio_original = ?, precio_unit = ?, subtotal = ? WHERE id = ?',
                    [$precioOriginal, $precioNuevo, $subtotalNuevo, $detalleId]
                );
            }

            // Recalculate subtotal
            $row = $this->queryOne(
                'SELECT SUM(subtotal) AS subtotal FROM pedido_detalle WHERE pedido_id = ?',
                [$id]
            );
            $nuevoSubtotal = (float)($row['subtotal'] ?? 0);

            $this->execute(
                "UPDATE pedidos
                    SET estado = 'confirmado', aprobado_por = ?, aprobado_at = NOW(),
                        subtotal = ?, total = ? + costo_envio
                  WHERE id = ?",
                [$aprobadoPorId, $nuevoSubtotal, $nuevoSubtotal, $id]
            );

            $this->db->commit();
        } catch (\Throwable $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    public function rechazarPedido(int $id, string $nota): void
    {
        $this->execute(
            "UPDATE pedidos SET estado = 'cancelado', nota_empresa = ? WHERE id = ?",
            [$nota ?: null, $id]
        );
    }

    public function subirComprobante(int $id, string $path): void
    {
        $this->execute(
            "UPDATE pedidos SET foto_comprobante_path = ?, estado = 'en_preparacion' WHERE id = ?",
            [$path, $id]
        );
    }

    public function subirFotoEntrega(int $id, string $path): void
    {
        $this->execute(
            "UPDATE pedidos SET foto_entrega_path = ?, estado = 'entregado' WHERE id = ?",
            [$path, $id]
        );
    }

    public function listadoEmpresa(int $empresaId, array $filtros = [], int $page = 1): array
    {
        $where  = ['p.empresa_id = ?'];
        $params = [$empresaId];

        if (!empty($filtros['estado'])) {
            $where[]  = 'p.estado = ?';
            $params[] = $filtros['estado'];
        }
        if (!empty($filtros['tipo'])) {
            $where[]  = 'p.tipo = ?';
            $params[] = $filtros['tipo'];
        }
        if (!empty($filtros['fecha_desde'])) {
            $where[]  = 'DATE(p.created_at) >= ?';
            $params[] = $filtros['fecha_desde'];
        }
        if (!empty($filtros['fecha_hasta'])) {
            $where[]  = 'DATE(p.created_at) <= ?';
            $params[] = $filtros['fecha_hasta'];
        }
        if (!empty($filtros['buscar'])) {
            $where[]  = '(p.folio LIKE ? OR u.nombre LIKE ? OR u.apellido_paterno LIKE ?)';
            $t = '%' . $filtros['buscar'] . '%';
            array_push($params, $t, $t, $t);
        }

        $sql = 'SELECT p.*, u.nombre AS comprador_nombre, u.apellido_paterno AS comprador_apellido
                  FROM pedidos p
                  JOIN usuarios u ON u.id = p.comprador_id
                 WHERE ' . implode(' AND ', $where) . '
              ORDER BY (p.estado = "pendiente") DESC, p.created_at DESC';

        return $this->paginate($sql, $params, $page);
    }

    public function crearPersonalizado(int $empresaId, int $compradorId, string $folio, string $nota, ?string $fechaEntrega, array $lineas, float $total, int $creadoPorId): int
    {
        $this->db->beginTransaction();
        try {
            $this->execute(
                'INSERT INTO pedidos (folio, empresa_id, comprador_id, estado, fecha_entrega, subtotal, total, notas, tipo, creado_por_id)
                 VALUES (?, ?, ?, "confirmado", ?, ?, ?, ?, "personalizado", ?)',
                [$folio, $empresaId, $compradorId, $fechaEntrega, $total, $total, $nota ?: null, $creadoPorId]
            );
            $pedidoId = (int)$this->db->lastInsertId();

            foreach ($lineas as $l) {
                $this->execute(
                    'INSERT INTO pedido_detalle (pedido_id, producto_id, cantidad, precio_unit, subtotal) VALUES (?, ?, ?, ?, ?)',
                    [$pedidoId, $l['producto_id'], $l['cantidad'], $l['precio_unit'], $l['subtotal']]
                );
            }
            $this->db->commit();
            return $pedidoId;
        } catch (\Throwable $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    public function countPendientes(int $empresaId): int
    {
        $row = $this->queryOne(
            "SELECT COUNT(*) AS n FROM pedidos WHERE empresa_id = ? AND estado = 'pendiente'",
            [$empresaId]
        );
        return (int)($row['n'] ?? 0);
    }

    public function countConComprobantePendiente(int $empresaId): int
    {
        $row = $this->queryOne(
            "SELECT COUNT(*) AS n FROM pedidos WHERE empresa_id = ? AND estado = 'en_preparacion' AND foto_comprobante_path IS NOT NULL",
            [$empresaId]
        );
        return (int)($row['n'] ?? 0);
    }

    public function pendientesAprobacion(int $empresaId): array
    {
        return $this->query(
            "SELECT p.*, u.nombre AS comprador_nombre, u.apellido_paterno AS comprador_apellido
               FROM pedidos p
               JOIN usuarios u ON u.id = p.comprador_id
              WHERE p.empresa_id = ? AND p.requiere_aprobacion = 1 AND p.estado = 'pendiente'
              ORDER BY p.created_at DESC",
            [$empresaId]
        );
    }

    public function conDetalle(int $id): ?array
    {
        $pedido = $this->queryOne(
            "SELECT p.*,
                    u.nombre AS comprador_nombre, u.apellido_paterno AS comprador_apellido,
                    ap.nombre AS aprobador_nombre,
                    e.razon_social AS empresa_nombre
               FROM pedidos p
               JOIN usuarios u ON u.id = p.comprador_id
               JOIN empresas e ON e.id = p.empresa_id
          LEFT JOIN usuarios ap ON ap.id = p.aprobado_por
              WHERE p.id = ?",
            [$id]
        );
        if (!$pedido) return null;

        $pedido['items'] = $this->query(
            'SELECT pd.*, pr.nombre AS producto_nombre, pr.presentacion
               FROM pedido_detalle pd
               JOIN productos pr ON pr.id = pd.producto_id
              WHERE pd.pedido_id = ?',
            [$id]
        );

        $pedido['sucursales'] = $this->query(
            'SELECT ps.*, s.nombre AS sucursal_nombre, s.direccion
               FROM pedido_sucursal ps
               JOIN sucursales s ON s.id = ps.sucursal_id
              WHERE ps.pedido_id = ?',
            [$id]
        );

        return $pedido;
    }

    public function aprobar(int $id, int $aprobadoPor): bool
    {
        return $this->execute(
            "UPDATE pedidos
                SET estado = 'confirmado', aprobado_por = ?, aprobado_at = NOW()
              WHERE id = ? AND estado = 'pendiente' AND requiere_aprobacion = 1",
            [$aprobadoPor, $id]
        );
    }

    public function rechazar(int $id, int $rechazadoPor, string $motivo): bool
    {
        return $this->execute(
            "UPDATE pedidos
                SET estado = 'cancelado', aprobado_por = ?, aprobado_at = NOW(),
                    notas = CONCAT(COALESCE(notas,''), IF(notas IS NULL OR notas='','','\n'), 'Rechazado: ', ?)
              WHERE id = ? AND estado = 'pendiente'",
            [$rechazadoPor, $motivo, $id]
        );
    }

    public function getTrackingActivo(int $pedidoId): ?array
    {
        return $this->queryOne(
            "SELECT rd.lat_actual, rd.lng_actual, rd.eta_minutos, rd.estado,
                    s.nombre AS sucursal_nombre, s.lat AS sucursal_lat, s.lng AS sucursal_lng,
                    u.nombre AS repartidor_nombre, p.estado AS pedido_estado
               FROM ruta_detalle rd
               JOIN rutas r        ON r.id = rd.ruta_id
               JOIN sucursales s   ON s.id = rd.sucursal_id
               JOIN usuarios u     ON u.id = r.repartidor_id
               JOIN pedidos p      ON p.id = rd.pedido_id
              WHERE rd.pedido_id = ? AND rd.tracking_activo = 1
              LIMIT 1",
            [$pedidoId]
        );
    }

    public function verificarPertenece(int $id, int $empresaId): bool
    {
        return $this->queryOne(
            'SELECT id FROM pedidos WHERE id = ? AND empresa_id = ?',
            [$id, $empresaId]
        ) !== null;
    }

    public function getItemsPedido(int $pedidoId): array
    {
        return $this->query(
            'SELECT pd.id, pd.producto_id, pd.cantidad, pd.precio_unit, pd.precio_original, pd.subtotal,
                    pr.nombre AS producto_nombre, pr.presentacion
               FROM pedido_detalle pd
               JOIN productos pr ON pr.id = pd.producto_id
              WHERE pd.pedido_id = ?
           ORDER BY pr.nombre',
            [$pedidoId]
        );
    }

    public function cancelar(int $id, int $usuarioId): bool
    {
        return $this->execute(
            "UPDATE pedidos
                SET estado = 'cancelado'
              WHERE id = ? AND comprador_id = ? AND estado IN ('pendiente')",
            [$id, $usuarioId]
        );
    }

    public function getUltimosPedidosComprador(int $compradorId, int $empresaId, int $limit = 5): array
    {
        return $this->query(
            "SELECT p.id, p.folio, p.total, p.estado, p.created_at
               FROM pedidos p
              WHERE p.comprador_id = ? AND p.empresa_id = ?
              ORDER BY p.created_at DESC LIMIT ?",
            [$compradorId, $empresaId, $limit]
        );
    }

    public function getPedidosEnRuta(int $compradorId, int $empresaId, int $limit = 3): array
    {
        return $this->query(
            "SELECT p.id, p.folio, p.estado
               FROM pedidos p
              WHERE p.comprador_id = ? AND p.empresa_id = ? AND p.estado = 'en_ruta'
              ORDER BY p.created_at DESC LIMIT ?",
            [$compradorId, $empresaId, $limit]
        );
    }

    public function getPedidosEnRutaEmpresa(int $empresaId, int $limit = 10): array
    {
        return $this->query(
            "SELECT p.id, p.folio, p.total, p.created_at,
                    u.nombre AS comprador_nombre
               FROM pedidos p
               JOIN usuarios u ON u.id = p.comprador_id
              WHERE p.empresa_id = ? AND p.estado = 'en_ruta'
              ORDER BY p.created_at DESC LIMIT ?",
            [$empresaId, $limit]
        );
    }

    public function getEntregadosHoy(int $empresaId): int
    {
        $row = $this->queryOne(
            "SELECT COUNT(*) AS total FROM pedidos
              WHERE empresa_id = ? AND estado = 'entregado' AND DATE(updated_at) = CURDATE()",
            [$empresaId]
        );
        return (int)($row['total'] ?? 0);
    }

    // ── Panel Admin ───────────────────────────────────────────────────────────

    public function listadoGlobal(array $filtros = [], int $page = 1): array
    {
        $where  = ['1=1'];
        $params = [];

        if (!empty($filtros['empresa_id'])) {
            $where[]  = 'p.empresa_id = ?';
            $params[] = $filtros['empresa_id'];
        }
        if (!empty($filtros['estado'])) {
            $where[]  = 'p.estado = ?';
            $params[] = $filtros['estado'];
        }
        if (!empty($filtros['buscar'])) {
            $where[]  = '(p.folio LIKE ? OR u.nombre LIKE ? OR e.razon_social LIKE ?)';
            $t = '%' . $filtros['buscar'] . '%';
            array_push($params, $t, $t, $t);
        }

        $sql = 'SELECT p.*, u.nombre AS comprador_nombre, u.apellido_paterno AS comprador_apellido,
                       e.razon_social AS empresa_nombre
                  FROM pedidos p
                  JOIN usuarios u ON u.id = p.comprador_id
                  JOIN empresas e ON e.id = p.empresa_id
                 WHERE ' . implode(' AND ', $where) . '
              ORDER BY p.created_at DESC';

        return $this->paginate($sql, $params, $page);
    }

    public function cambiarEstado(int $id, string $estado): bool
    {
        $validos = ['pendiente', 'confirmado', 'en_preparacion', 'en_ruta', 'entregado', 'cancelado'];
        if (!in_array($estado, $validos, true)) return false;

        return $this->execute(
            'UPDATE pedidos SET estado = ? WHERE id = ?',
            [$estado, $id]
        );
    }

    public function listadoConfirmadosPorEmpresa(int $empresaId): array
    {
        return $this->query(
            "SELECT p.id, p.folio, p.total,
                    u.nombre AS comprador_nombre, u.apellido_paterno AS comprador_apellido
               FROM pedidos p
               JOIN usuarios u ON u.id = p.comprador_id
              WHERE p.empresa_id = ? AND p.estado IN ('confirmado', 'aprobado')
              ORDER BY p.created_at DESC",
            [$empresaId]
        );
    }

    public function crearRuta(int $repartidorId, int $empresaId, string $fecha, array $pedidosIds): int
    {
        $this->db->beginTransaction();
        try {
            $stmt = $this->db->prepare(
                'INSERT INTO rutas (repartidor_id, empresa_id, fecha, estado) VALUES (?, ?, ?, "pendiente")'
            );
            $stmt->execute([$repartidorId, $empresaId, $fecha]);
            $rutaId = (int)$this->db->lastInsertId();

            foreach ($pedidosIds as $pedidoId) {
                $pedido = $this->conDetalle((int)$pedidoId);
                if (!$pedido) continue;
                foreach ($pedido['sucursales'] as $suc) {
                    $this->execute(
                        'INSERT INTO ruta_detalle (ruta_id, pedido_id, sucursal_id, orden, estado) VALUES (?, ?, ?, 0, "pendiente")',
                        [$rutaId, $pedidoId, $suc['sucursal_id']]
                    );
                }
                $this->update((int)$pedidoId, ['estado' => 'en_preparacion']);
            }

            $this->db->commit();
            return $rutaId;
        } catch (\Throwable $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    public function getRutasActivas(int $empresaId = 0): array
    {
        $filtroEmpresa = $empresaId > 0 ? 'AND r.empresa_id = ?' : '';
        $params = $empresaId > 0 ? [$empresaId] : [];

        return $this->query(
            "SELECT r.id, r.fecha, r.estado,
                    u.nombre AS repartidor_nombre, u.apellido_paterno AS repartidor_apellido,
                    e.razon_social AS empresa_nombre,
                    COUNT(rd.id) AS total_paradas,
                    SUM(rd.estado = 'entregado') AS entregadas
               FROM rutas r
               JOIN usuarios u ON u.id = r.repartidor_id
               JOIN empresas e ON e.id = r.empresa_id
               JOIN ruta_detalle rd ON rd.ruta_id = r.id
              WHERE r.estado IN ('planificada', 'en_curso')
                    $filtroEmpresa
              GROUP BY r.id
              ORDER BY r.fecha DESC
              LIMIT 50",
            $params
        );
    }

    public function getPosicionesActivas(int $empresaId = 0): array
    {
        $filtroEmpresa = $empresaId > 0 ? 'AND r.empresa_id = ?' : '';
        $params = $empresaId > 0 ? [$empresaId] : [];

        return $this->query(
            "SELECT DISTINCT rd.lat_actual, rd.lng_actual,
                    u.nombre AS repartidor_nombre,
                    r.id AS ruta_id
               FROM ruta_detalle rd
               JOIN rutas r ON r.id = rd.ruta_id
               JOIN usuarios u ON u.id = r.repartidor_id
              WHERE rd.tracking_activo = 1
                AND rd.lat_actual IS NOT NULL
                    $filtroEmpresa",
            $params
        );
    }
}
