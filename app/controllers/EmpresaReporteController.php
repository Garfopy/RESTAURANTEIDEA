<?php
require_once ROOT_PATH . '/app/controllers/BaseController.php';

class EmpresaReporteController extends BaseController
{
    public function __construct()
    {
        parent::__construct();
        $this->requireAuth();
    }

    public function index(?string $p = null): void
    {
        $rol      = $this->rolActual();
        $empresaId = $this->empresaId();
        $usuarioId = $this->usuarioId() ?? 0;

        $hoy = date('Y-m-d');
        $desde = (string)$this->get('fecha_desde', date('Y-m-d', strtotime('-29 days')));
        $hasta = (string)$this->get('fecha_hasta', $hoy);

        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $desde)) {
            $desde = date('Y-m-d', strtotime('-29 days'));
        }
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $hasta)) {
            $hasta = $hoy;
        }
        if ($desde > $hasta) {
            [$desde, $hasta] = [$hasta, $desde];
        }

        $mostrarInput = $this->get('mostrar', ['logo', 'kpis', 'graficas', 'tabla', 'notas']);
        $mostrar = is_array($mostrarInput) ? $mostrarInput : [$mostrarInput];
        $mostrar = array_values(array_intersect($mostrar, ['logo', 'kpis', 'graficas', 'tabla', 'notas']));
        if (empty($mostrar)) {
            $mostrar = ['logo', 'kpis', 'graficas', 'tabla', 'notas'];
        }

        $reporte = $this->armarReporte($rol, (int)$empresaId, $usuarioId, $desde, $hasta);

        $configModel = new ConfigModel();
        $logoUrl = $configModel->get('app_logo', BASE_URL . 'public/img/logo.svg');
        if (!$logoUrl) {
            $logoUrl = BASE_URL . 'public/img/logo.svg';
        }

        $fechaReporte = $this->fechaEspanol(date('Y-m-d'));
        $reportId = '#CH-' . date('Ymd-His') . '-' . str_pad((string)$usuarioId, 3, '0', STR_PAD_LEFT) . '-' . strtoupper(bin2hex(random_bytes(2)));

        $filtros = [
            'fecha_desde' => $desde,
            'fecha_hasta' => $hasta,
            'mostrar' => $mostrar,
        ];

        $tituloReporte = $reporte['titulo'];
        $kpis = $reporte['kpis'];
        $columnas = $reporte['columnas'];
        $filas = $reporte['filas'];
        $notas = $reporte['notas'];
        $graficas = $reporte['graficas'] ?? [];

        if (in_array($rol, ['superadmin', 'admin'], true)) {
            $flash = $this->getFlash();
            $pageTitle = 'Reportes';
            $activeMenu = 'reportes';
            ob_start();
            require ROOT_PATH . '/app/views/reportes/tecnico.php';
            $content = ob_get_clean();
            require ROOT_PATH . '/app/views/panel/layouts/main.php';
            return;
        }

        if (in_array($rol, ['admin_empresa', 'supervisor', 'comprador'], true)) {
            $flash = $this->getFlash();
            $pageTitle = 'Reportes';
            $activeMenu = 'reportes';
            if ($rol === 'supervisor') {
                $pendientes = (new PedidoModel())->pendientesAprobacion((int)$empresaId);
                $countPendientesSidebar = count($pendientes);
            }
            ob_start();
            require ROOT_PATH . '/app/views/reportes/tecnico.php';
            $content = ob_get_clean();
            require ROOT_PATH . '/app/views/empresa/layouts/main.php';
            return;
        }

        if ($rol === 'repartidor') {
            require ROOT_PATH . '/app/views/repartidor/reportes.php';
            return;
        }

        $this->redirectSegunRol($rol);
    }

    public function descargarPdf(?string $p = null): void
    {
        $this->redirect('empresa-reporte/index');
    }

    private function armarReporte(string $rol, int $empresaId, int $usuarioId, string $desde, string $hasta): array
    {
        $db = Database::getInstance();

        if (in_array($rol, ['superadmin', 'admin'], true)) {
            $kpiRow = $db->prepare(
                "SELECT
                    (SELECT COUNT(*) FROM empresas WHERE activo = 1) AS empresas_activas,
                    (SELECT COUNT(*) FROM usuarios WHERE activo = 1) AS usuarios_activos,
                    COUNT(*) AS pedidos,
                    COALESCE(SUM(CASE WHEN estado != 'cancelado' THEN total ELSE 0 END),0) AS ingresos
                 FROM pedidos
                 WHERE DATE(created_at) BETWEEN ? AND ?"
            );
            $kpiRow->execute([$desde, $hasta]);
            $r = $kpiRow->fetch() ?: [];

            $stmt = $db->prepare(
                "SELECT DATE(p.created_at) AS fecha, e.razon_social, p.folio, p.estado, p.total,
                        CONCAT(u.nombre, ' ', u.apellido_paterno) AS responsable
                   FROM pedidos p
                   JOIN empresas e ON e.id = p.empresa_id
                   JOIN usuarios u ON u.id = p.comprador_id
                  WHERE DATE(p.created_at) BETWEEN ? AND ?
               ORDER BY p.created_at DESC
                  LIMIT 120"
            );
            $stmt->execute([$desde, $hasta]);
            $rows = $stmt->fetchAll();

            $trendStmt = $db->prepare(
                "SELECT DATE(created_at) AS d, COUNT(*) AS pedidos, COALESCE(SUM(CASE WHEN estado != 'cancelado' THEN total ELSE 0 END),0) AS ingresos
                   FROM pedidos
                  WHERE DATE(created_at) BETWEEN ? AND ?
               GROUP BY DATE(created_at)
               ORDER BY DATE(created_at)"
            );
            $trendStmt->execute([$desde, $hasta]);
            $trend = $trendStmt->fetchAll();

            $estStmt = $db->prepare(
                "SELECT estado, COUNT(*) AS c FROM pedidos
                  WHERE DATE(created_at) BETWEEN ? AND ?
               GROUP BY estado"
            );
            $estStmt->execute([$desde, $hasta]);
            $est = $estStmt->fetchAll();

            return [
                'titulo' => 'Reporte Ejecutivo SaaS',
                'kpis' => [
                    ['label' => 'Empresas activas', 'valor' => number_format((int)($r['empresas_activas'] ?? 0)), 'hint' => 'Clientes vigentes'],
                    ['label' => 'Usuarios activos', 'valor' => number_format((int)($r['usuarios_activos'] ?? 0)), 'hint' => 'Plataforma completa'],
                    ['label' => 'Pedidos del período', 'valor' => number_format((int)($r['pedidos'] ?? 0)), 'hint' => 'Transacciones'],
                    ['label' => 'Ingresos del período', 'valor' => '$' . number_format((float)($r['ingresos'] ?? 0), 2), 'hint' => 'Sin cancelados'],
                ],
                'columnas' => ['Fecha', 'Empresa', 'Folio', 'Estado', 'Total', 'Responsable'],
                'filas' => array_map(fn($x) => [
                    $x['fecha'],
                    $x['razon_social'],
                    $x['folio'],
                    strtoupper((string)$x['estado']),
                    '$' . number_format((float)$x['total'], 2),
                    trim((string)$x['responsable']),
                ], $rows),
                'notas' => [
                    'Monitorear variaciones de demanda por empresa para ajustar capacidad de distribución.',
                    'Validar stock crítico en centros de mayor rotación antes del siguiente corte operativo.',
                    'Rango técnico recomendado de conservación para cadena fría: 0°C a 4°C.',
                ],
                'graficas' => [
                    [
                        'tipo' => 'line',
                        'titulo' => 'Tendencia diaria de pedidos',
                        'labels' => array_map(fn($x) => $x['d'], $trend),
                        'data' => array_map(fn($x) => (int)$x['pedidos'], $trend),
                        'label' => 'Pedidos',
                    ],
                    [
                        'tipo' => 'doughnut',
                        'titulo' => 'Distribución por estado',
                        'labels' => array_map(fn($x) => strtoupper((string)$x['estado']), $est),
                        'data' => array_map(fn($x) => (int)$x['c'], $est),
                        'label' => 'Pedidos',
                    ],
                ],
            ];
        }

        if ($rol === 'comprador') {
            $kpiStmt = $db->prepare(
                "SELECT COUNT(*) AS pedidos,
                        COALESCE(SUM(total),0) AS gasto,
                        COALESCE(AVG(total),0) AS ticket,
                        SUM(estado = 'en_ruta') AS en_ruta
                   FROM pedidos
                  WHERE empresa_id = ? AND comprador_id = ?
                    AND DATE(created_at) BETWEEN ? AND ?"
            );
            $kpiStmt->execute([$empresaId, $usuarioId, $desde, $hasta]);
            $r = $kpiStmt->fetch() ?: [];

            $stmt = $db->prepare(
                "SELECT DATE(created_at) AS fecha, folio, estado, total,
                        COALESCE(tipo_entrega, 'n/d') AS tipo_entrega,
                        COALESCE(DATE(fecha_entrega), '-') AS fecha_entrega
                   FROM pedidos
                  WHERE empresa_id = ? AND comprador_id = ?
                    AND DATE(created_at) BETWEEN ? AND ?
               ORDER BY created_at DESC
                  LIMIT 120"
            );
            $stmt->execute([$empresaId, $usuarioId, $desde, $hasta]);
            $rows = $stmt->fetchAll();

            $trendStmt = $db->prepare(
                "SELECT DATE(created_at) AS d, COUNT(*) AS pedidos, COALESCE(SUM(total),0) AS gasto
                   FROM pedidos
                  WHERE empresa_id = ? AND comprador_id = ?
                    AND DATE(created_at) BETWEEN ? AND ?
               GROUP BY DATE(created_at)
               ORDER BY DATE(created_at)"
            );
            $trendStmt->execute([$empresaId, $usuarioId, $desde, $hasta]);
            $trend = $trendStmt->fetchAll();

            $estStmt = $db->prepare(
                "SELECT estado, COUNT(*) AS c FROM pedidos
                  WHERE empresa_id = ? AND comprador_id = ?
                    AND DATE(created_at) BETWEEN ? AND ?
               GROUP BY estado"
            );
            $estStmt->execute([$empresaId, $usuarioId, $desde, $hasta]);
            $est = $estStmt->fetchAll();

            return [
                'titulo' => 'Reporte de Compras y Abasto',
                'kpis' => [
                    ['label' => 'Pedidos generados', 'valor' => number_format((int)($r['pedidos'] ?? 0)), 'hint' => 'Período seleccionado'],
                    ['label' => 'Gasto total', 'valor' => '$' . number_format((float)($r['gasto'] ?? 0), 2), 'hint' => 'Monto acumulado'],
                    ['label' => 'Ticket promedio', 'valor' => '$' . number_format((float)($r['ticket'] ?? 0), 2), 'hint' => 'Costo por pedido'],
                    ['label' => 'Pedidos en ruta', 'valor' => number_format((int)($r['en_ruta'] ?? 0)), 'hint' => 'Logística activa'],
                ],
                'columnas' => ['Fecha', 'Folio', 'Estado', 'Total', 'Tipo entrega', 'Entrega programada'],
                'filas' => array_map(fn($x) => [
                    $x['fecha'],
                    $x['folio'],
                    strtoupper((string)$x['estado']),
                    '$' . number_format((float)$x['total'], 2),
                    strtoupper((string)$x['tipo_entrega']),
                    $x['fecha_entrega'],
                ], $rows),
                'notas' => [
                    'Programar compras de alto volumen en ventanas de menor saturación logística.',
                    'Revisar productos recurrentes para activar pedidos automatizados de reposición.',
                    'Parámetro técnico recomendado de conservación: 0°C a 4°C en transporte y recepción.',
                ],
                'graficas' => [
                    [
                        'tipo' => 'line',
                        'titulo' => 'Gasto diario',
                        'labels' => array_map(fn($x) => $x['d'], $trend),
                        'data' => array_map(fn($x) => (float)$x['gasto'], $trend),
                        'label' => 'Gasto ($)',
                    ],
                    [
                        'tipo' => 'doughnut',
                        'titulo' => 'Pedidos por estado',
                        'labels' => array_map(fn($x) => strtoupper((string)$x['estado']), $est),
                        'data' => array_map(fn($x) => (int)$x['c'], $est),
                        'label' => 'Pedidos',
                    ],
                ],
            ];
        }

        if ($rol === 'repartidor') {
            $kpiStmt = $db->prepare(
                "SELECT COUNT(*) AS paradas,
                        SUM(rd.estado = 'entregado') AS entregadas,
                        SUM(rd.estado != 'entregado') AS pendientes
                   FROM ruta_detalle rd
                   JOIN rutas r ON r.id = rd.ruta_id
                  WHERE r.repartidor_id = ?
                    AND DATE(r.fecha) BETWEEN ? AND ?"
            );
            $kpiStmt->execute([$usuarioId, $desde, $hasta]);
            $r = $kpiStmt->fetch() ?: [];
            $paradas = (int)($r['paradas'] ?? 0);
            $cumplimiento = $paradas > 0
                ? (((int)($r['entregadas'] ?? 0) / $paradas) * 100)
                : 0.0;

            $stmt = $db->prepare(
                "SELECT DATE(r.fecha) AS fecha, CONCAT('RUTA-', r.id) AS ruta,
                        p.folio, s.nombre AS sucursal,
                        rd.estado,
                        COALESCE(DATE_FORMAT(rd.hora_entrega, '%H:%i'), '-') AS hora_entrega
                   FROM ruta_detalle rd
                   JOIN rutas r ON r.id = rd.ruta_id
                   JOIN pedidos p ON p.id = rd.pedido_id
                   JOIN sucursales s ON s.id = rd.sucursal_id
                  WHERE r.repartidor_id = ?
                    AND DATE(r.fecha) BETWEEN ? AND ?
               ORDER BY r.fecha DESC, rd.orden ASC
                  LIMIT 120"
            );
            $stmt->execute([$usuarioId, $desde, $hasta]);
            $rows = $stmt->fetchAll();

            $trendStmt = $db->prepare(
                "SELECT DATE(r.fecha) AS d, COUNT(*) AS paradas, SUM(rd.estado='entregado') AS entregadas
                   FROM ruta_detalle rd JOIN rutas r ON r.id = rd.ruta_id
                  WHERE r.repartidor_id = ? AND DATE(r.fecha) BETWEEN ? AND ?
               GROUP BY DATE(r.fecha) ORDER BY DATE(r.fecha)"
            );
            $trendStmt->execute([$usuarioId, $desde, $hasta]);
            $trend = $trendStmt->fetchAll();

            $estStmt = $db->prepare(
                "SELECT rd.estado, COUNT(*) AS c
                   FROM ruta_detalle rd JOIN rutas r ON r.id = rd.ruta_id
                  WHERE r.repartidor_id = ? AND DATE(r.fecha) BETWEEN ? AND ?
               GROUP BY rd.estado"
            );
            $estStmt->execute([$usuarioId, $desde, $hasta]);
            $est = $estStmt->fetchAll();

            return [
                'titulo' => 'Reporte de Distribución Diaria',
                'kpis' => [
                    ['label' => 'Paradas del período', 'valor' => number_format((int)($r['paradas'] ?? 0)), 'hint' => 'Carga logística'],
                    ['label' => 'Entregadas', 'valor' => number_format((int)($r['entregadas'] ?? 0)), 'hint' => 'Completadas'],
                    ['label' => 'Pendientes', 'valor' => number_format((int)($r['pendientes'] ?? 0)), 'hint' => 'En proceso'],
                    ['label' => 'Cumplimiento', 'valor' => number_format($cumplimiento, 1) . '%', 'hint' => 'Entregas / paradas'],
                ],
                'columnas' => ['Fecha', 'Ruta', 'Pedido', 'Sucursal', 'Estado', 'Hora entrega'],
                'filas' => array_map(fn($x) => [
                    $x['fecha'],
                    $x['ruta'],
                    $x['folio'],
                    $x['sucursal'],
                    strtoupper((string)$x['estado']),
                    $x['hora_entrega'],
                ], $rows),
                'notas' => [
                    'Priorizar entregas de perecederos en la primera mitad de la ruta.',
                    'Mantener monitoreo de tiempos de arribo para evitar desvíos de SLA.',
                    'Rango de conservación recomendado para cadena fría: 0°C a 4°C durante la distribución.',
                ],
                'graficas' => [
                    [
                        'tipo' => 'line',
                        'titulo' => 'Entregas diarias',
                        'labels' => array_map(fn($x) => $x['d'], $trend),
                        'data' => array_map(fn($x) => (int)$x['entregadas'], $trend),
                        'label' => 'Entregadas',
                    ],
                    [
                        'tipo' => 'doughnut',
                        'titulo' => 'Estado de paradas',
                        'labels' => array_map(fn($x) => strtoupper((string)$x['estado']), $est),
                        'data' => array_map(fn($x) => (int)$x['c'], $est),
                        'label' => 'Paradas',
                    ],
                ],
            ];
        }

        $kpiStmt = $db->prepare(
            "SELECT COUNT(*) AS pedidos,
                    COALESCE(SUM(total),0) AS monto,
                    SUM(estado = 'entregado') AS entregados,
                    COALESCE(AVG(total),0) AS ticket
               FROM pedidos
              WHERE empresa_id = ?
                AND DATE(created_at) BETWEEN ? AND ?"
        );
        $kpiStmt->execute([$empresaId, $desde, $hasta]);
        $r = $kpiStmt->fetch() ?: [];

        $stockCriticoStmt = $db->prepare(
            "SELECT COUNT(*)
               FROM inventario inv
               JOIN productos p ON p.id = inv.producto_id
              WHERE p.empresa_id = ? AND p.activo = 1
                AND inv.stock <= inv.umbral_minimo"
        );
        $stockCriticoStmt->execute([$empresaId]);
        $stockCritico = (int)$stockCriticoStmt->fetchColumn();

        $pendientesStmt = $db->prepare(
            "SELECT COUNT(*) FROM pedidos
              WHERE empresa_id = ? AND estado = 'pendiente'
                AND DATE(created_at) BETWEEN ? AND ?"
        );
        $pendientesStmt->execute([$empresaId, $desde, $hasta]);
        $pendientes = (int)$pendientesStmt->fetchColumn();

        $stmt = $db->prepare(
            "SELECT DATE(p.created_at) AS fecha, p.folio,
                    CONCAT(u.nombre, ' ', u.apellido_paterno) AS comprador,
                    p.estado, p.total,
                    COUNT(ps.id) AS sucursales
               FROM pedidos p
               JOIN usuarios u ON u.id = p.comprador_id
          LEFT JOIN pedido_sucursal ps ON ps.pedido_id = p.id
              WHERE p.empresa_id = ?
                AND DATE(p.created_at) BETWEEN ? AND ?
           GROUP BY p.id
           ORDER BY p.created_at DESC
              LIMIT 120"
        );
        $stmt->execute([$empresaId, $desde, $hasta]);
        $rows = $stmt->fetchAll();

        $trendStmt = $db->prepare(
            "SELECT DATE(created_at) AS d, COUNT(*) AS pedidos, COALESCE(SUM(total),0) AS monto
               FROM pedidos
              WHERE empresa_id = ? AND DATE(created_at) BETWEEN ? AND ?
           GROUP BY DATE(created_at) ORDER BY DATE(created_at)"
        );
        $trendStmt->execute([$empresaId, $desde, $hasta]);
        $trend = $trendStmt->fetchAll();

        $estStmt = $db->prepare(
            "SELECT estado, COUNT(*) AS c FROM pedidos
              WHERE empresa_id = ? AND DATE(created_at) BETWEEN ? AND ?
           GROUP BY estado"
        );
        $estStmt->execute([$empresaId, $desde, $hasta]);
        $est = $estStmt->fetchAll();

        $titulo = $rol === 'supervisor'
            ? 'Reporte Operativo de Supervisión'
            : 'Reporte Ejecutivo de Empresa';

        $kpi4Label = $rol === 'supervisor' ? 'Stock crítico' : 'Ticket promedio';
        $kpi4Valor = $rol === 'supervisor'
            ? number_format($stockCritico)
            : '$' . number_format((float)($r['ticket'] ?? 0), 2);
        $kpi4Hint = $rol === 'supervisor' ? 'Productos en alerta' : 'Monto por pedido';

        return [
            'titulo' => $titulo,
            'kpis' => [
                ['label' => 'Pedidos del período', 'valor' => number_format((int)($r['pedidos'] ?? 0)), 'hint' => 'Operación total'],
                ['label' => 'Monto del período', 'valor' => '$' . number_format((float)($r['monto'] ?? 0), 2), 'hint' => 'Venta acumulada'],
                ['label' => 'Entregados', 'valor' => number_format((int)($r['entregados'] ?? 0)), 'hint' => 'Pedidos cerrados'],
                ['label' => $kpi4Label, 'valor' => $kpi4Valor, 'hint' => $kpi4Hint],
            ],
            'columnas' => ['Fecha', 'Folio', 'Comprador', 'Estado', 'Total', 'Sucursales'],
            'filas' => array_map(fn($x) => [
                $x['fecha'],
                $x['folio'],
                trim((string)$x['comprador']),
                strtoupper((string)$x['estado']),
                '$' . number_format((float)$x['total'], 2),
                (string)$x['sucursales'],
            ], $rows),
            'notas' => [
                'Pedidos pendientes en el período: ' . number_format($pendientes) . '.',
                'Mantener abastecimiento preventivo para productos de mayor rotación y alertas de stock.',
                'Parámetro técnico de conservación recomendado en operación: 0°C a 4°C.',
            ],
            'graficas' => [
                [
                    'tipo' => 'line',
                    'titulo' => 'Pedidos diarios',
                    'labels' => array_map(fn($x) => $x['d'], $trend),
                    'data' => array_map(fn($x) => (int)$x['pedidos'], $trend),
                    'label' => 'Pedidos',
                ],
                [
                    'tipo' => 'doughnut',
                    'titulo' => 'Pedidos por estado',
                    'labels' => array_map(fn($x) => strtoupper((string)$x['estado']), $est),
                    'data' => array_map(fn($x) => (int)$x['c'], $est),
                    'label' => 'Pedidos',
                ],
            ],
        ];
    }

    private function fechaEspanol(string $fecha): string
    {
        static $meses = [
            '01' => 'Enero', '02' => 'Febrero', '03' => 'Marzo', '04' => 'Abril',
            '05' => 'Mayo', '06' => 'Junio', '07' => 'Julio', '08' => 'Agosto',
            '09' => 'Septiembre', '10' => 'Octubre', '11' => 'Noviembre', '12' => 'Diciembre',
        ];
        $ts = strtotime($fecha);
        if (!$ts) {
            return $fecha;
        }
        $d = date('d', $ts);
        $m = $meses[date('m', $ts)] ?? date('m', $ts);
        $y = date('Y', $ts);
        return $d . ' de ' . $m . ', ' . $y;
    }
}
