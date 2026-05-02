<?php
require_once BASE_PATH . '/app/controllers/BaseController.php';
require_once BASE_PATH . '/app/models/UsuarioModel.php';
require_once BASE_PATH . '/app/models/PedidoModel.php';
require_once BASE_PATH . '/app/models/EntregaModel.php';
require_once BASE_PATH . '/app/models/RutaModel.php';
require_once BASE_PATH . '/app/models/LogModel.php';

class RepartidorController extends BaseController {

    public function __construct() {
        $this->requireRole(['repartidor', 'superadmin']);
    }

    public function inicio($param = null) {
        $chofer = $this->getChoferActual();
        $modelo = new RutaModel();
        $rutaHoy = $modelo->getRutaHoyChofer($chofer['id'] ?? 0);
        $pendientes = 0;
        $entregados = 0;
        if ($rutaHoy) {
            $detalle = $modelo->getDetalle($rutaHoy['id']);
            foreach ($detalle as $d) {
                if ($d['estado'] === 'entregado') $entregados++;
                else $pendientes++;
            }
        }
        $this->render('repartidor/inicio', [
            'chofer'     => $chofer,
            'rutaHoy'    => $rutaHoy,
            'pendientes' => $pendientes,
            'entregados' => $entregados,
        ]);
    }

    public function entregas($param = null) {
        $chofer = $this->getChoferActual();
        $modelo = new RutaModel();
        $rutaHoy = $modelo->getRutaHoyChofer($chofer['id'] ?? 0);
        $entregas = $rutaHoy ? $modelo->getDetalle($rutaHoy['id']) : [];
        $this->render('repartidor/entregas', [
            'chofer'   => $chofer,
            'ruta'     => $rutaHoy,
            'entregas' => $entregas,
        ]);
    }

    public function detalle($id = null) {
        $modelo = new RutaModel();
        $entrega = $modelo->getDetalleItem($id);
        if (!$entrega) { $this->redirect('repartidor/entregas'); return; }

        $pedidoMod = new PedidoModel();
        $pedido = $pedidoMod->getDetalle($entrega['pedido_id']);
        $this->render('repartidor/detalle_entrega', [
            'entrega' => $entrega,
            'pedido'  => $pedido,
        ]);
    }

    public function iniciarEntrega($id = null) {
        $this->json(['ok' => false, 'error' => 'No implemented']);
        $modelo = new RutaModel();
        $resultado = $modelo->cambiarEstadoDetalle($id, 'en_ruta');
        $this->log('iniciar_entrega', 'repartidor', "Entrega #$id iniciada");
        $this->json(['ok' => (bool)$resultado]);
    }

    public function completarEntrega($param = null) {
        if (!$this->isPost()) { $this->redirect('repartidor/entregas'); return; }

        $detalleId    = $this->post('detalle_id');
        $receptorNombre = $this->post('receptor_nombre','');
        $firmaData    = $this->post('firma_data','');
        $fotoData     = $this->post('foto_data','');

        $modelo = new RutaModel();

        $archivos = [];
        $uploadDir = BASE_PATH . '/public/uploads/evidencias/';
        if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);

        if ($firmaData) {
            $firmaFile = 'firma_' . $detalleId . '_' . time() . '.png';
            $firmaBase64 = preg_replace('/^data:image\/\w+;base64,/', '', $firmaData);
            file_put_contents($uploadDir . $firmaFile, base64_decode($firmaBase64));
            $archivos['firma'] = $firmaFile;
        }

        if ($fotoData) {
            $fotoFile = 'foto_' . $detalleId . '_' . time() . '.jpg';
            $fotoBase64 = preg_replace('/^data:image\/\w+;base64,/', '', $fotoData);
            file_put_contents($uploadDir . $fotoFile, base64_decode($fotoBase64));
            $archivos['foto'] = $fotoFile;
        }

        $modelo->completarEntrega($detalleId, $receptorNombre, $archivos);
        $this->log('completar_entrega', 'repartidor', "Entrega #$detalleId completada por $receptorNombre");
        $this->json(['ok' => true]);
    }

    public function mapa($param = null) {
        $chofer = $this->getChoferActual();
        $modelo = new RutaModel();
        $rutaHoy = $modelo->getRutaHoyChofer($chofer['id'] ?? 0);
        $entregas = $rutaHoy ? $modelo->getDetalle($rutaHoy['id']) : [];
        $this->render('repartidor/mapa', [
            'ruta'     => $rutaHoy,
            'entregas' => $entregas,
        ]);
    }

    public function historial($param = null) {
        $chofer = $this->getChoferActual();
        $modelo = new RutaModel();
        $rutas = $modelo->getRutasPorChofer($chofer['id'] ?? 0, 30);
        $this->render('repartidor/historial', [
            'chofer' => $chofer,
            'rutas'  => $rutas,
        ]);
    }

    public function perfil($param = null) {
        $usuMod = new UsuarioModel();
        $usuario = $usuMod->find($_SESSION['usuario']['id']);
        $chofer = $this->getChoferActual();

        if ($this->isPost()) {
            $nombre = $this->post('nombre','');
            $email  = $this->post('email','');
            $usuMod->update($_SESSION['usuario']['id'], ['nombre'=>$nombre,'email'=>$email]);
            $_SESSION['usuario']['nombre'] = $nombre;
            $this->flash('Perfil actualizado', 'success');
            $this->redirect('repartidor/perfil');
            return;
        }

        $this->render('repartidor/perfil', [
            'usuario' => $usuario,
            'chofer'  => $chofer,
        ]);
    }

    // ── helpers ──

    private function getChoferActual() {
        $db = \Database::getInstance();
        $stmt = $db->prepare('SELECT c.*, v.placa, v.modelo, v.marca FROM choferes c LEFT JOIN vehiculos v ON v.id = c.vehiculo_id WHERE c.usuario_id = ? LIMIT 1');
        $stmt->execute([$_SESSION['usuario']['id']]);
        return $stmt->fetch(\PDO::FETCH_ASSOC) ?: [];
    }
}
