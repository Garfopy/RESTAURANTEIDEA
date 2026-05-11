<?php
require_once ROOT_PATH . '/app/controllers/BaseController.php';

class RepartidorController extends BaseController
{
    private string $colorPrimary = '#111827';

    public function __construct()
    {
        parent::__construct();
        $this->requireRepartidor();
    }

    public function index(?string $p = null): void
    {
        $this->redirect('repartidor/inicio');
    }

    public function inicio(?string $p = null): void
    {
        $repartidorId = $this->usuarioId();
        $db = Database::getInstance();

        // Ruta del día asignada a este repartidor
        $stmt = $db->prepare(
            "SELECT r.*, COUNT(rd.id) AS total_paradas,
                    SUM(CASE WHEN rd.estado = 'entregado' THEN 1 ELSE 0 END) AS entregadas
               FROM rutas r
               JOIN ruta_detalle rd ON rd.ruta_id = r.id
              WHERE r.repartidor_id = ? AND r.fecha = CURDATE() AND r.estado IN ('planificada','en_curso')
           GROUP BY r.id
           ORDER BY r.estado DESC LIMIT 1"
        );
        $stmt->execute([$repartidorId]);
        $rutaHoy = $stmt->fetch() ?: null;

        $paradas = [];
        if ($rutaHoy) {
            $stmt = $db->prepare(
                "SELECT rd.*, s.nombre AS sucursal_nombre, s.direccion, s.lat, s.lng,
                        p.folio AS pedido_folio, e.razon_social AS empresa_nombre
                   FROM ruta_detalle rd
                   JOIN sucursales s ON s.id = rd.sucursal_id
                   JOIN pedidos p ON p.id = rd.pedido_id
                   JOIN empresas e ON e.id = p.empresa_id
                  WHERE rd.ruta_id = ?
               ORDER BY rd.orden"
            );
            $stmt->execute([$rutaHoy['id']]);
            $paradas = $stmt->fetchAll();
        }

        // ── KPIs operativos del Repartidor ─────────────────────────────────
        $pedidoModel = new PedidoModel();
        $hoy   = date('Y-m-d');
        $desde = date('Y-m-d', strtotime('-29 days'));

        $resumenHoy        = $pedidoModel->paradasHoyRepartidor($repartidorId);
        $kilosPendientes   = $pedidoModel->kilosPendientesHoy($repartidorId);
        $proximaParada     = $pedidoModel->proximaParadaRepartidor($repartidorId);
        $evidencia         = $pedidoModel->cumplimientoEvidencia($repartidorId, $desde, $hoy);
        $incidencias       = $pedidoModel->incidenciasRutaRepartidor($repartidorId, $desde, $hoy);
        $tiempoProm        = $pedidoModel->tiempoPromedioPorParada($repartidorId, $desde, $hoy);
        $prodSemanal       = $pedidoModel->productividadSemanalRepartidor($repartidorId, 6);

        // SLA estimado: 30 min por parada (umbral configurable a futuro)
        $slaMinutosParada  = 30;

        $flash     = $this->getFlash();
        $pageTitle = 'Mis entregas de hoy';

        require ROOT_PATH . '/app/views/repartidor/inicio.php';
    }

    public function entrega(?string $paradaId = null): void
    {
        if (!$paradaId) {
            $this->redirect('repartidor/inicio');
        }

        $repartidorId = $this->usuarioId();
        $db = Database::getInstance();

        $stmt = $db->prepare(
            "SELECT rd.*, s.nombre AS sucursal_nombre, s.direccion, s.lat, s.lng,
                    p.folio, p.notas, e.razon_social AS empresa_nombre
               FROM ruta_detalle rd
               JOIN rutas r ON r.id = rd.ruta_id
               JOIN sucursales s ON s.id = rd.sucursal_id
               JOIN pedidos p ON p.id = rd.pedido_id
               JOIN empresas e ON e.id = p.empresa_id
              WHERE rd.id = ? AND r.repartidor_id = ?"
        );
        $stmt->execute([$paradaId, $repartidorId]);
        $parada = $stmt->fetch();

        if (!$parada) {
            $this->redirect('repartidor/inicio');
        }

        $flash     = $this->getFlash();
        $pageTitle = 'Detalle de entrega';

        require ROOT_PATH . '/app/views/repartidor/entrega.php';
    }

    public function confirmarEntrega(?string $paradaId = null): void
    {
        if (!$this->isPost() || !$paradaId) {
            $this->redirect('repartidor/inicio');
        }

        $repartidorId = $this->usuarioId();
        $db = Database::getInstance();

        // Verificar que la parada pertenece a este repartidor
        $stmt = $db->prepare(
            'SELECT rd.id FROM ruta_detalle rd
               JOIN rutas r ON r.id = rd.ruta_id
              WHERE rd.id = ? AND r.repartidor_id = ?'
        );
        $stmt->execute([$paradaId, $repartidorId]);
        if (!$stmt->fetch()) {
            $this->redirect('repartidor/inicio');
        }

        // Procesar firma (base64 → archivo)
        $firmaPath = null;
        if (!empty($_POST['firma_data'])) {
            $firmaPath = $this->guardarFirma($_POST['firma_data'], $paradaId);
        }

        // Procesar foto (upload)
        $fotoPath = null;
        if (!empty($_FILES['foto']['tmp_name'])) {
            $fotoPath = $this->guardarFoto($_FILES['foto'], $paradaId);
        }

        // Guardar evidencia
        $db->prepare(
            'INSERT INTO evidencias_entrega (ruta_detalle_id, nombre_receptor, firma_path, foto_path)
             VALUES (?, ?, ?, ?)'
        )->execute([$paradaId, $this->post('nombre_receptor'), $firmaPath, $fotoPath]);

        // Actualizar estado de parada
        $db->prepare(
            "UPDATE ruta_detalle SET estado = 'entregado', hora_entrega = NOW(), tracking_activo = 0 WHERE id = ?"
        )->execute([$paradaId]);

        // Actualizar estado del pedido si todas las paradas están entregadas
        $stmt = $db->prepare(
            'SELECT pedido_id FROM ruta_detalle WHERE id = ?'
        );
        $stmt->execute([$paradaId]);
        $pedidoId = (int)($stmt->fetch()['pedido_id'] ?? 0);

        if ($pedidoId) {
            $stmt2 = $db->prepare(
                "SELECT COUNT(*) FROM ruta_detalle WHERE pedido_id = ? AND estado != 'entregado'"
            );
            $stmt2->execute([$pedidoId]);
            if ((int)$stmt2->fetchColumn() === 0) {
                $db->prepare("UPDATE pedidos SET estado = 'entregado' WHERE id = ?")
                   ->execute([$pedidoId]);
            }
        }

        $this->log('Entrega confirmada', 'repartidor', "Parada ID: $paradaId");
        $this->flash('success', 'Entrega registrada correctamente.');
        $this->redirect('repartidor/inicio');
    }

    public function historial(?string $p = null): void
    {
        $repartidorId = $this->usuarioId();
        $db = Database::getInstance();

        $stmt = $db->prepare(
            "SELECT rd.*, s.nombre AS sucursal_nombre, p.folio,
                    r.fecha, e.razon_social AS empresa_nombre
               FROM ruta_detalle rd
               JOIN rutas r ON r.id = rd.ruta_id
               JOIN sucursales s ON s.id = rd.sucursal_id
               JOIN pedidos p ON p.id = rd.pedido_id
               JOIN empresas e ON e.id = p.empresa_id
              WHERE r.repartidor_id = ? AND rd.estado = 'entregado'
           ORDER BY rd.hora_entrega DESC LIMIT 50"
        );
        $stmt->execute([$repartidorId]);
        $historial = $stmt->fetchAll();

        $flash     = $this->getFlash();
        $pageTitle = 'Historial de entregas';

        require ROOT_PATH . '/app/views/repartidor/historial.php';
    }

    private function guardarFirma(string $base64, string $paradaId): ?string
    {
        if (!str_starts_with($base64, 'data:image/')) return null;
        $data   = explode(',', $base64)[1] ?? '';
        $bytes  = base64_decode($data);
        $nombre = 'firma_' . $paradaId . '_' . time() . '.png';
        $ruta   = UPLOAD_PATH . 'firmas/';
        if (!is_dir($ruta)) mkdir($ruta, 0755, true);
        file_put_contents($ruta . $nombre, $bytes);
        return UPLOAD_URL . 'firmas/' . $nombre;
    }

    private function guardarFoto(array $file, string $paradaId): ?string
    {
        $ext    = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $allow  = ['jpg','jpeg','png','webp'];
        if (!in_array($ext, $allow, true)) return null;
        $nombre = 'foto_' . $paradaId . '_' . time() . '.' . $ext;
        $ruta   = UPLOAD_PATH . 'entregas/';
        if (!is_dir($ruta)) mkdir($ruta, 0755, true);
        move_uploaded_file($file['tmp_name'], $ruta . $nombre);
        return UPLOAD_URL . 'entregas/' . $nombre;
    }
}
