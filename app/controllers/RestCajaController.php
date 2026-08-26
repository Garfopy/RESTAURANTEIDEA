<?php
require_once ROOT_PATH . '/app/controllers/BaseController.php';

/**
 * RestCajaController — Punto de venta (POS).
 *
 * Documentación del módulo: plan-web-cajero.md (decisiones y reglas),
 * plan-web-cajero-bd.md (esquema y endpoints), plan-web-cajero-ui.md (pantallas).
 *
 * Invariante de seguridad: `cajero_id`, `turno_id` y `restaurante_id` salen
 * SIEMPRE de la sesión del servidor, nunca de un campo del formulario.
 */
class RestCajaController extends BaseController
{
    private CajaConfigModel $cfgModel;
    private TurnoCajaModel  $turnoModel;
    private PedidoPagoModel $pagoModel;
    private CajeroPinModel  $pinModel;
    private RestPedidoModel $pedidoModel;
    private array           $cfg;
    private int             $restauranteId;

    /** Vigencia del token de autorización de un descuento (decisión D9). */
    private const AUTORIZACION_SEGUNDOS = 120;

    public function __construct()
    {
        parent::__construct();
        $this->requireCajero();

        $this->restauranteId = (int)$this->restauranteId();
        $this->cfgModel    = new CajaConfigModel();
        $this->turnoModel  = new TurnoCajaModel();
        $this->pagoModel   = new PedidoPagoModel();
        $this->pinModel    = new CajeroPinModel();
        $this->pedidoModel = new RestPedidoModel();
        $this->cfg         = $this->cfgModel->get($this->restauranteId);

        if (!$this->cfg['pos_habilitado']) {
            $this->flash('warning', 'El punto de venta está apagado para este negocio.');
            $this->redirect('auth/login');
        }
    }

    // ═══════════════════════════════════════════════════════════
    //  Acceso: selección de cajero + PIN
    // ═══════════════════════════════════════════════════════════

    /** Puerta de entrada de la terminal. */
    public function index(?string $p = null): void
    {
        if ($this->cajeroActivo() && !$this->pantallaBloqueada()) {
            $this->redirect($this->turnoAbierto() ? 'rest-caja/venta' : 'rest-caja/apertura');
        }
        $this->pantallaAcceso();
    }

    /** Bloqueo de pantalla: mismo formulario, pero fijo en el cajero actual. */
    public function bloqueo(?string $p = null): void
    {
        $this->pantallaAcceso();
    }

    private function pantallaAcceso(): void
    {
        $cajeros    = $this->pinModel->cajerosActivos($this->restauranteId);
        $yo         = $this->usuarioId();
        $soyCajero  = $this->rolActual() === 'cajero';
        $bloqueada  = $this->pantallaBloqueada();
        $fijado     = $bloqueada ? (int)($_SESSION['caja']['cajero_id'] ?? 0) : 0;

        // Un cajero que entra por primera vez todavía no tiene PIN: como ya se
        // autenticó con su email y contraseña para abrir la terminal, se le
        // deja crear el suyo aquí mismo en lugar de dejarlo varado.
        $puedeDefinirPin = false;
        if ($soyCajero && $yo) {
            foreach ($cajeros as $c) {
                if ((int)$c['id'] === $yo && !(int)$c['tiene_pin']) {
                    $puedeDefinirPin = true;
                    break;
                }
            }
        }

        $this->render('caja/login', [
            'cajeros'         => $cajeros,
            'restaurante'     => $this->cfg['restaurante'],
            'bloqueada'       => $bloqueada,
            'cajeroFijado'    => $fijado,
            'yo'              => $yo,
            'puedeDefinirPin' => $puedeDefinirPin,
            'csrf'            => $this->csrfToken(),
            'flash'           => $this->getFlash(),
        ]);
    }

    /** POST — valida el PIN del cajero seleccionado. */
    public function pinLogin(?string $p = null): void
    {
        $this->requireCsrfJson();

        $datos     = $this->jsonInput();
        $cajeroId  = (int)($datos['cajero_id'] ?? 0);
        $pin       = (string)($datos['pin'] ?? '');

        // Si la pantalla está bloqueada, solo el mismo cajero puede abrirla:
        // cambiar de operador es otra acción, con su propio botón.
        if ($this->pantallaBloqueada()) {
            $cajeroId = (int)($_SESSION['caja']['cajero_id'] ?? 0);
        }

        $cajero = $this->pinModel->cajero($cajeroId, $this->restauranteId);
        if (!$cajero) {
            $this->json(['ok' => false, 'error' => 'Cajero no encontrado en este negocio.'], 404);
        }

        $r = $this->pinModel->verificar(
            $cajeroId,
            $pin,
            $this->cfg['pin_intentos_max'],
            $this->cfg['pin_bloqueo_minutos']
        );
        if (!$r['ok']) {
            $this->json(['ok' => false, 'error' => $r['error'], 'espera' => $r['espera'] ?? 0], 401);
        }

        $this->abrirSesionCaja($cajero);
        $this->log('Entrada a caja por PIN', 'caja', 'Cajero ' . $cajeroId);

        $this->json([
            'ok'       => true,
            'redirect' => BASE_URL . ($this->turnoAbierto() ? 'rest-caja/venta' : 'rest-caja/apertura'),
        ]);
    }

    /** POST — primer uso: el cajero define su propio PIN. */
    public function definirPin(?string $p = null): void
    {
        $this->requireCsrfJson();

        $yo = $this->usuarioId();
        if ($this->rolActual() !== 'cajero' || !$yo) {
            $this->json(['ok' => false, 'error' => 'Solo el cajero dueño de la cuenta puede crear su PIN aquí.'], 403);
        }

        $cajero = $this->pinModel->cajero($yo, $this->restauranteId);
        if (!$cajero) {
            $this->json(['ok' => false, 'error' => 'Cajero no encontrado en este negocio.'], 404);
        }
        if (!empty($cajero['pin_hash'])) {
            $this->json(['ok' => false, 'error' => 'Ya tienes un PIN. Si lo olvidaste, pídele a tu admin que lo reinicie.'], 409);
        }

        $datos = $this->jsonInput();
        $pin   = (string)($datos['pin'] ?? '');
        if ($error = $this->pinModel->validarFormato($pin)) {
            $this->json(['ok' => false, 'error' => $error], 422);
        }
        if ($pin !== (string)($datos['pin_confirmacion'] ?? '')) {
            $this->json(['ok' => false, 'error' => 'Los dos PIN no coinciden.'], 422);
        }

        $this->pinModel->asignar($yo, $pin);
        $this->abrirSesionCaja($cajero);
        $this->log('PIN de caja creado', 'caja');

        $this->json([
            'ok'       => true,
            'redirect' => BASE_URL . ($this->turnoAbierto() ? 'rest-caja/venta' : 'rest-caja/apertura'),
        ]);
    }

    /** POST — bloquea la pantalla sin cerrar sesión ni el turno. */
    public function bloquear(?string $p = null): void
    {
        $this->requireCsrfJson();
        $_SESSION['caja']['bloqueada'] = true;
        $this->json(['ok' => true, 'redirect' => BASE_URL . 'rest-caja/bloqueo']);
    }

    /** POST — cambia de operador. El turno del anterior NO se cierra. */
    public function salirCajero(?string $p = null): void
    {
        $this->requireCsrf();
        unset($_SESSION['caja']);
        $this->redirect('rest-caja/index');
    }

    // ═══════════════════════════════════════════════════════════
    //  Turno
    // ═══════════════════════════════════════════════════════════

    public function apertura(?string $p = null): void
    {
        $this->requireCajeroEnSesion();

        if ($this->turnoAbierto()) {
            $this->redirect('rest-caja/venta');
        }

        $this->render('caja/apertura', [
            'cajero'      => $this->cajeroSesion(),
            'restaurante' => $this->cfg['restaurante'],
            'ultimo'      => $this->turnoModel->historial((int)$this->cajeroActivo(), 1)[0] ?? null,
            'csrf'        => $this->csrfToken(),
            'flash'       => $this->getFlash(),
        ]);
    }

    /** POST — crea el turno con el fondo inicial. */
    public function abrirTurno(?string $p = null): void
    {
        $this->requireCajeroEnSesion();
        $this->requireCsrf();

        $fondo = (float)str_replace(',', '', (string)$this->post('fondo_inicial', '0'));
        if ($fondo < 0) {
            $this->flash('error', 'El fondo inicial no puede ser negativo.');
            $this->redirect('rest-caja/apertura');
        }

        try {
            $turnoId = $this->turnoModel->abrir(
                $this->restauranteId,
                (int)$this->cajeroActivo(),
                $fondo,
                $this->usuarioId(),
                trim((string)$this->post('notas', '')) ?: null
            );
        } catch (\RuntimeException $e) {
            $this->flash('error', $e->getMessage());
            $this->redirect('rest-caja/venta');
            return;
        }

        $_SESSION['caja']['turno_id'] = $turnoId;
        $this->log('Apertura de turno de caja', 'caja', 'Turno ' . $turnoId . ' fondo ' . $fondo);
        $this->flash('success', 'Turno abierto con $' . number_format($fondo, 2) . ' de fondo.');
        $this->redirect('rest-caja/venta');
    }

    public function cierre(?string $p = null): void
    {
        $turno = $this->requireTurno();

        $this->render('caja/cierre', [
            'turno'       => $turno,
            'totales'     => $this->turnoModel->totales((int)$turno['id']),
            'movimientos' => $this->turnoModel->movimientos((int)$turno['id']),
            'pendientes'  => $this->turnoModel->pendientesApp($this->restauranteId),
            'cajero'      => $this->cajeroSesion(),
            'cfg'         => $this->cfg,
            'restaurante' => $this->cfg['restaurante'],
            'csrf'        => $this->csrfToken(),
            'flash'       => $this->getFlash(),
        ]);
    }

    /** POST — conteo, diferencia y cierre definitivo. */
    public function cerrarTurno(?string $p = null): void
    {
        $turno = $this->requireTurno();
        $this->requireCsrf();

        $contado = (float)str_replace(',', '', (string)$this->post('efectivo_contado', ''));
        if ($this->post('efectivo_contado', '') === '') {
            $this->flash('error', 'Captura cuánto efectivo contaste antes de cerrar.');
            $this->redirect('rest-caja/cierre');
        }

        $totales    = $this->turnoModel->totales((int)$turno['id']);
        $diferencia = round($contado - $totales['efectivo_esperado'], 2);
        $notas      = trim((string)$this->post('notas', ''));

        if (abs($diferencia) > $this->cfg['diferencia_caja_alerta_mxn'] && $notas === '') {
            $this->flash('error', 'La diferencia es de $' . number_format($diferencia, 2) . '. Explica en las notas a qué se debió.');
            $this->redirect('rest-caja/cierre');
        }

        $denominaciones = array_filter(
            (array)$this->post('denominaciones', []),
            fn($v) => (int)$v > 0
        );

        $r = $this->turnoModel->cerrar(
            $turno,
            $contado,
            $denominaciones ?: null,
            $notas ?: null,
            $this->cfg['diferencia_caja_alerta_mxn']
        );

        unset($_SESSION['caja']['turno_id']);
        $this->log('Cierre de turno de caja', 'caja',
            'Turno ' . $turno['id'] . ' diferencia ' . $r['diferencia']);

        $this->redirect('rest-caja/reporte/' . $turno['id']);
    }

    public function reporte(?string $id = null): void
    {
        $this->requireCajeroEnSesion();

        $turno = $this->turnoModel->delRestaurante((int)$id, $this->restauranteId);
        if (!$turno) {
            $this->flash('error', 'Turno no encontrado.');
            $this->redirect('rest-caja/index');
        }

        $this->render('caja/reporte', [
            'turno'       => $turno,
            'movimientos' => $this->turnoModel->movimientos((int)$turno['id']),
            'ventas'      => $this->turnoModel->ventas((int)$turno['id']),
            'restaurante' => $this->cfg['restaurante'],
            'cfg'         => $this->cfg,
        ]);
    }

    /** Turnos cerrados del propio cajero (solo lectura). */
    public function turnos(?string $p = null): void
    {
        $this->requireCajeroEnSesion();

        $this->render('caja/turnos', [
            'turnos'      => $this->turnoModel->historial((int)$this->cajeroActivo(), 30),
            'cajero'      => $this->cajeroSesion(),
            'restaurante' => $this->cfg['restaurante'],
            'turnoActual' => $this->turnoAbierto(),
            'csrf'        => $this->csrfToken(),
        ]);
    }

    // ═══════════════════════════════════════════════════════════
    //  Venta
    // ═══════════════════════════════════════════════════════════

    public function venta(?string $p = null): void
    {
        $turno = $this->requireTurno();

        $this->render('caja/venta', [
            'turno'       => $turno,
            'totales'     => $this->turnoModel->totales((int)$turno['id']),
            'cajero'      => $this->cajeroSesion(),
            'cfg'         => $this->cfg,
            'restaurante' => $this->cfg['restaurante'],
            'walletOn'    => in_array('wallet', $this->cfg['metodos_pago'], true) && (new WalletModel())->disponible(),
            'hayAdminPin' => $this->pinModel->hayAdminConPin($this->restauranteId),
            'pendientes'  => $this->turnoModel->contarPendientesApp($this->restauranteId),
            'csrf'        => $this->csrfToken(),
            'flash'       => $this->getFlash(),
        ]);
    }

    /** GET JSON — catálogo completo para armar el carrito sin recargar. */
    public function catalogo(?string $p = null): void
    {
        $this->requireCajeroEnSesion();

        $menu       = new RestMenuModel();
        $categorias = $menu->getCategorias($this->restauranteId, true);
        $platillos  = $menu->getPlatillosDisponibles($this->restauranteId);

        $salida = [];
        foreach ($platillos as $pl) {
            $mods = [];
            foreach ($menu->getModificadoresPlatillo((int)$pl['id'], true) as $m) {
                $mods[] = [
                    'id'            => (int)$m['id'],
                    'nombre'        => $m['ingrediente_nombre'] ?: $m['nombre'],
                    'tipo'          => $m['tipo'],                       // extra | sin | opcion
                    'precio_extra'  => (float)$m['precio_extra'],
                    'max_seleccion' => max(1, (int)($m['max_seleccion'] ?? 1)),
                ];
            }
            $salida[] = [
                'id'           => (int)$pl['id'],
                'categoria_id' => (int)($pl['categoria_id'] ?? 0),
                'nombre'       => $pl['nombre'],
                'codigo'       => $pl['codigo'] ?? null,
                'precio'       => (float)$pl['precio'],
                'imagen'       => $pl['imagen'] ?? null,
                'modificadores'=> $mods,
            ];
        }

        $this->json([
            'ok'         => true,
            'version'    => $this->cfg['config_version'],
            'categorias' => array_map(fn($c) => [
                'id'     => (int)$c['id'],
                'nombre' => $c['nombre'],
                'orden'  => (int)($c['orden'] ?? 0),
            ], $categorias),
            'platillos'  => $salida,
        ]);
    }

    /** GET JSON — busca cliente por teléfono para vincular la venta o cobrar con saldo. */
    public function buscarCliente(?string $p = null): void
    {
        $this->requireCajeroEnSesion();

        $tel = preg_replace('/\D+/', '', (string)$this->get('telefono', ''));
        if (strlen($tel) < 7) {
            $this->json(['ok' => false, 'error' => 'Escribe al menos 7 dígitos del teléfono.'], 422);
        }

        $db = Database::getInstance();
        $resultados = [];

        $st = $db->prepare(
            "SELECT id, nombre, telefono FROM rest_comensales
              WHERE restaurante_id = ? AND REPLACE(REPLACE(telefono,' ',''),'-','') LIKE ?
              LIMIT 5"
        );
        $st->execute([$this->restauranteId, '%' . $tel]);
        foreach ($st->fetchAll(PDO::FETCH_ASSOC) as $c) {
            $resultados[] = [
                'origen' => 'comensal', 'id' => (int)$c['id'],
                'nombre' => $c['nombre'], 'telefono' => $c['telefono'],
                'saldo'  => null,
            ];
        }

        $wallet = new WalletModel();
        try {
            $st = $db->prepare(
                "SELECT id, nombre, telefono FROM mobile_usuarios
                  WHERE activo = 1 AND REPLACE(REPLACE(telefono,' ',''),'-','') LIKE ? LIMIT 5"
            );
            $st->execute(['%' . $tel]);
            foreach ($st->fetchAll(PDO::FETCH_ASSOC) as $c) {
                $resultados[] = [
                    'origen' => 'mobile', 'id' => (int)$c['id'],
                    'nombre' => $c['nombre'], 'telefono' => $c['telefono'],
                    'saldo'  => $wallet->disponible() ? $wallet->saldo((int)$c['id']) : null,
                ];
            }
        } catch (\Throwable $e) {
            // Sin app móvil instalada: solo se ofrecen los comensales locales.
        }

        $this->json(['ok' => true, 'clientes' => $resultados]);
    }

    /** POST JSON — valida un cupón vigente del negocio. */
    public function validarCupon(?string $p = null): void
    {
        $this->requireCajeroEnSesion();
        $this->requireCsrfJson();

        $code = strtoupper(trim((string)($this->jsonInput()['code'] ?? '')));
        if ($code === '') {
            $this->json(['ok' => false, 'error' => 'Escribe el código del cupón.'], 422);
        }

        $promo = $this->buscarPromocion($code);
        if (!$promo) {
            $this->json(['ok' => false, 'error' => 'Cupón no válido o vencido.'], 404);
        }

        $this->json(['ok' => true, 'cupon' => [
            'id'     => (int)$promo['id'],
            'code'   => $promo['code'],
            'titulo' => $promo['titulo'],
            'tipo'   => $promo['tipo'],
            'valor'  => (float)$promo['valor_descuento'],
        ]]);
    }

    /** POST JSON — un admin autoriza con su PIN un descuento sobre el límite. */
    public function autorizarPin(?string $p = null): void
    {
        $this->requireCajeroEnSesion();
        $this->requireCsrfJson();

        $pin = (string)($this->jsonInput()['pin'] ?? '');
        $adminId = $this->pinModel->verificarAdmin($this->restauranteId, $pin);
        if (!$adminId) {
            $this->json(['ok' => false, 'error' => 'PIN de administrador incorrecto.'], 401);
        }

        $token = bin2hex(random_bytes(16));
        $_SESSION['caja']['autorizacion'] = [
            'token'    => $token,
            'admin_id' => $adminId,
            'expira'   => time() + self::AUTORIZACION_SEGUNDOS,
        ];

        $this->json(['ok' => true, 'token' => $token, 'vigencia' => self::AUTORIZACION_SEGUNDOS]);
    }

    /**
     * POST JSON — cobra una venta de mostrador.
     * Todo el cálculo se rehace en el servidor; los totales del navegador
     * solo sirven para pintar la pantalla.
     */
    public function cobrar(?string $p = null): void
    {
        $turno = $this->requireTurno();
        $this->requireCsrfJson();

        $datos = $this->jsonInput();
        $uuid  = $this->uuidValido($datos['client_uuid'] ?? null);

        // Idempotencia (R11): el mismo cobro reintentado devuelve la venta original.
        if ($uuid && ($previo = $this->pedidoPorUuid($uuid))) {
            $this->json($this->respuestaCobro($previo));
        }

        $db = Database::getInstance();
        $db->beginTransaction();
        try {
            $items = $this->itemsDeEntrada($datos['items'] ?? []);
            if (!$items) {
                throw new \InvalidArgumentException('El carrito está vacío.');
            }

            $preparados = $this->pedidoModel->prepararItems($this->restauranteId, $items);
            $subtotal   = $preparados['subtotal'];

            $desc     = $this->calcularDescuento($datos, $subtotal);
            $propina  = $this->propinaValida($datos['propina_mxn'] ?? 0, $subtotal);
            $total    = round($subtotal - $desc['monto'] + $propina, 2);
            if ($total < 0) {
                throw new \InvalidArgumentException('El descuento no puede superar el total de la venta.');
            }

            $validados = $this->pagoModel->validar(
                (array)($datos['pagos'] ?? []),
                $total,
                $this->cfg['metodos_pago']
            );

            $cliente  = (array)($datos['cliente'] ?? []);
            $pedidoId = $this->pedidoModel->crear([
                'restaurante_id'  => $this->restauranteId,
                'estado'          => 'entregado',   // mostrador: se entrega en el acto
                'pedido_origen'   => 'cajero',
                'tipo_origen'     => 'caja',
                'tipo_pedido'     => 'take_out',
                'turno_caja_id'   => (int)$turno['id'],
                'cajero_id'       => (int)$this->cajeroActivo(),
                'descuento'       => $desc['monto'],
                'promo_code'      => $desc['promo_code'],
                'propina_mxn'     => $propina,
                'iva_mxn'         => $this->ivaContenido($total - $propina),
                'total'           => $total,
                'metodo_pago'     => $this->etiquetaMetodoPago($validados['pagos']),
                'pagado_at'       => date('Y-m-d H:i:s'),
                'cliente_nombre'  => $this->texto($cliente['nombre'] ?? null, 120),
                'comprador_telefono' => $this->texto($cliente['telefono'] ?? null, 30),
                'mobile_usuario_id'  => isset($cliente['mobile_usuario_id']) ? (int)$cliente['mobile_usuario_id'] : null,
                'notas'           => $this->texto($datos['notas'] ?? null, 500),
                'pos_client_uuid' => $uuid,
                'folio_prefijo'   => 'C',
            ], $preparados['items']);

            $this->pagoModel->registrar(
                $pedidoId,
                $this->restauranteId,
                (int)$turno['id'],
                (int)$this->cajeroActivo(),
                $validados['pagos']
            );

            $this->cobrarWalletSiAplica($validados['pagos'], $cliente, $pedidoId);
            $this->registrarDescuento($pedidoId, $desc);
            $this->marcarUsoCupon($desc, $pedidoId, $cliente);

            $db->commit();
        } catch (\InvalidArgumentException | \RuntimeException $e) {
            $db->rollBack();
            $this->json(['ok' => false, 'error' => $e->getMessage()], 422);
            return;
        } catch (\Throwable $e) {
            $db->rollBack();
            error_log('[caja] Error al cobrar: ' . $e->getMessage());
            $this->json(['ok' => false, 'error' => 'No se pudo registrar la venta. Vuelve a intentar.'], 500);
            return;
        }

        // Fuera de la transacción, igual que el resto del sistema: que falte
        // una receta no debe deshacer un cobro que el cliente ya pagó.
        $this->pedidoModel->descontarStockEntrega($pedidoId);

        $this->consumirAutorizacion();
        $this->log('Venta de mostrador', 'caja', 'Pedido ' . $pedidoId . ' total ' . $total);

        $pedido = $this->pedidoModel->find($pedidoId);
        $this->json($this->respuestaCobro($pedido, $validados['cambio']));
    }

    /** POST JSON — cancela una venta ya cobrada del turno actual. */
    public function cancelarVenta(?string $id = null): void
    {
        $turno = $this->requireTurno();
        $this->requireCsrfJson();

        $motivo = trim((string)($this->jsonInput()['motivo'] ?? ''));
        if (mb_strlen($motivo) < 5) {
            $this->json(['ok' => false, 'error' => 'Escribe el motivo de la cancelación (mínimo 5 caracteres).'], 422);
        }

        $pedido = $this->pedidoDelRestaurante((int)$id);
        if (!$pedido) {
            $this->json(['ok' => false, 'error' => 'Venta no encontrada.'], 404);
        }
        if ($pedido['estado'] === 'cancelado') {
            $this->json(['ok' => false, 'error' => 'Esta venta ya estaba cancelada.'], 409);
        }

        $db = Database::getInstance();
        $db->beginTransaction();
        try {
            $devolucion = $this->pagoModel->devolver(
                (int)$pedido['id'],
                $this->restauranteId,
                (int)$turno['id'],            // la devolución afecta al turno actual (R13)
                (int)$this->cajeroActivo()
            );

            $this->pedidoModel->cancelarDesdeCaja(
                (int)$pedido['id'],
                $motivo,
                (int)$this->cajeroActivo(),
                !empty($devolucion['pendientes'])
            );

            $db->commit();
        } catch (\Throwable $e) {
            $db->rollBack();
            error_log('[caja] Error al cancelar: ' . $e->getMessage());
            $this->json(['ok' => false, 'error' => 'No se pudo cancelar la venta.'], 500);
            return;
        }

        $this->log('Cancelación de venta', 'caja', 'Pedido ' . $pedido['id'] . ': ' . $motivo);

        $aviso = null;
        if ($devolucion['efectivo'] > 0) {
            $aviso = 'Devuelve $' . number_format($devolucion['efectivo'], 2) . ' en efectivo al cliente.';
        }
        if ($devolucion['pendientes']) {
            $aviso = trim(($aviso ? $aviso . ' ' : '') .
                'El reembolso de ' . implode(' y ', array_map([PedidoPagoModel::class, 'etiqueta'], $devolucion['pendientes'])) .
                ' lo procesa el administrador.');
        }

        $this->json(['ok' => true, 'devuelto' => $devolucion['devuelto'], 'aviso' => $aviso]);
    }

    /** POST JSON — retiro o ingreso de efectivo dentro del turno. */
    public function movimiento(?string $p = null): void
    {
        $turno = $this->requireTurno();
        $this->requireCsrfJson();

        $datos = $this->jsonInput();
        try {
            $this->turnoModel->movimiento(
                $turno,
                (int)$this->cajeroActivo(),
                (string)($datos['tipo'] ?? ''),
                (float)($datos['monto'] ?? 0),
                (string)($datos['motivo'] ?? '')
            );
        } catch (\InvalidArgumentException $e) {
            $this->json(['ok' => false, 'error' => $e->getMessage()], 422);
            return;
        }

        $this->log('Movimiento de caja', 'caja', ($datos['tipo'] ?? '') . ' ' . ($datos['monto'] ?? 0));
        $this->json(['ok' => true, 'totales' => $this->turnoModel->totales((int)$turno['id'])]);
    }

    public function historial(?string $p = null): void
    {
        $turno = $this->requireTurno();

        $this->render('caja/historial', [
            'turno'       => $turno,
            'ventas'      => $this->turnoModel->ventas((int)$turno['id']),
            'movimientos' => $this->turnoModel->movimientos((int)$turno['id']),
            'totales'     => $this->turnoModel->totales((int)$turno['id']),
            'cajero'      => $this->cajeroSesion(),
            'restaurante' => $this->cfg['restaurante'],
            'csrf'        => $this->csrfToken(),
            'flash'       => $this->getFlash(),
        ]);
    }

    // ═══════════════════════════════════════════════════════════
    //  Pedidos que llegan de la app
    // ═══════════════════════════════════════════════════════════

    public function pedidos(?string $p = null): void
    {
        $turno = $this->requireTurno();

        $this->render('caja/pedidos', [
            'turno'       => $turno,
            'cajero'      => $this->cajeroSesion(),
            'cfg'         => $this->cfg,
            'restaurante' => $this->cfg['restaurante'],
            'csrf'        => $this->csrfToken(),
            'flash'       => $this->getFlash(),
        ]);
    }

    /** GET JSON — cola de pedidos sin atender, separada por si ya está pagada. */
    public function pedidosEntrantes(?string $p = null): void
    {
        $this->requireCajeroEnSesion();

        $prepagados = [];
        $porCobrar  = [];
        foreach ($this->turnoModel->pendientesApp($this->restauranteId) as $pedido) {
            $fila = [
                'id'       => (int)$pedido['id'],
                'folio'    => $pedido['folio'],
                'total'    => (float)$pedido['total'],
                'estado'   => $pedido['estado'],
                'cliente'  => $pedido['cliente_nombre'] ?: 'Cliente',
                'telefono' => $pedido['comprador_telefono'],
                'items'    => (int)$pedido['items'],
                'tipo'     => $pedido['tipo_pedido'],
                'pickup_at'=> $pedido['pickup_at'],
                'creado'   => $pedido['created_at'],
                'pagado'   => !empty($pedido['pagado_at']),
                'metodo'   => $pedido['metodo_pago'],
            ];
            if ($fila['pagado']) { $prepagados[] = $fila; } else { $porCobrar[] = $fila; }
        }

        $this->json([
            'ok'         => true,
            'prepagados' => $prepagados,
            'por_cobrar' => $porCobrar,
            'total'      => count($prepagados) + count($porCobrar),
            'ultimo_id'  => max(array_merge([0], array_column(array_merge($prepagados, $porCobrar), 'id'))),
        ]);
    }

    /** GET JSON — detalle de un pedido de la cola. */
    public function pedido(?string $id = null): void
    {
        $this->requireCajeroEnSesion();

        $pedido = $this->pedidoModel->getConItemsSinMesas((int)$id, $this->restauranteId);
        if (!$pedido) {
            $this->json(['ok' => false, 'error' => 'Pedido no encontrado.'], 404);
        }

        $this->json([
            'ok'     => true,
            'pedido' => [
                'id'      => (int)$pedido['id'],
                'folio'   => $pedido['folio'],
                'estado'  => $pedido['estado'],
                'total'   => (float)$pedido['total'],
                'subtotal'=> (float)$pedido['subtotal'],
                'descuento' => (float)$pedido['descuento'],
                'propina' => (float)($pedido['propina_mxn'] ?? 0),
                'cliente' => $pedido['cliente_nombre'],
                'telefono'=> $pedido['comprador_telefono'],
                'pagado'  => !empty($pedido['pagado_at']),
                'notas'   => $pedido['notas'],
                'items'   => array_map(fn($i) => [
                    'cantidad'    => (int)$i['cantidad'],
                    'nombre'      => $i['platillo_nombre'],
                    'subtotal'    => (float)$i['subtotal'],
                    'notas'       => $i['notas'],
                    'exclusiones' => $i['exclusiones'],
                ], $pedido['items'] ?? []),
            ],
        ]);
    }

    /** POST JSON — cobra en caja un pedido de la app que venía sin pagar. */
    public function cobrarPedido(?string $id = null): void
    {
        $turno = $this->requireTurno();
        $this->requireCsrfJson();

        $pedido = $this->pedidoDelRestaurante((int)$id);
        if (!$pedido) {
            $this->json(['ok' => false, 'error' => 'Pedido no encontrado.'], 404);
        }
        if ($pedido['estado'] === 'cancelado') {
            $this->json(['ok' => false, 'error' => 'Ese pedido está cancelado.'], 409);
        }
        if (!empty($pedido['pagado_at'])) {
            $this->json(['ok' => false, 'error' => 'Ese pedido ya venía pagado desde la app.'], 409);
        }

        $datos   = $this->jsonInput();
        $propina = $this->propinaValida($datos['propina_mxn'] ?? 0, (float)$pedido['total']);
        $total   = round((float)$pedido['total'] + $propina, 2);

        $db = Database::getInstance();
        $db->beginTransaction();
        try {
            $validados = $this->pagoModel->validar((array)($datos['pagos'] ?? []), $total, $this->cfg['metodos_pago']);

            $this->pagoModel->registrar(
                (int)$pedido['id'], $this->restauranteId,
                (int)$turno['id'], (int)$this->cajeroActivo(),
                $validados['pagos']
            );
            $this->cobrarWalletSiAplica($validados['pagos'], [
                'mobile_usuario_id' => $pedido['mobile_usuario_id'] ?? null,
            ], (int)$pedido['id']);

            $this->pedidoModel->tomarEnCaja((int)$pedido['id'], [
                'turno_caja_id' => (int)$turno['id'],
                'cajero_id'     => (int)$this->cajeroActivo(),
                'estado'        => 'entregado',
                'propina_mxn'   => $propina,
                'total'         => $total,
                'iva_mxn'       => $this->ivaContenido($total - $propina),
                'metodo_pago'   => $this->etiquetaMetodoPago($validados['pagos']),
                'pagado_at'     => date('Y-m-d H:i:s'),
            ]);

            $db->commit();
        } catch (\InvalidArgumentException | \RuntimeException $e) {
            $db->rollBack();
            $this->json(['ok' => false, 'error' => $e->getMessage()], 422);
            return;
        } catch (\Throwable $e) {
            $db->rollBack();
            error_log('[caja] Error al cobrar pedido de app: ' . $e->getMessage());
            $this->json(['ok' => false, 'error' => 'No se pudo cobrar el pedido.'], 500);
            return;
        }

        $this->pedidoModel->descontarStockEntrega((int)$pedido['id']);
        $this->log('Cobro de pedido de app', 'caja', 'Pedido ' . $pedido['id']);
        $this->json([
            'ok'         => true,
            'pedido_id'  => (int)$pedido['id'],
            'folio'      => $pedido['folio'],
            'cambio'     => $validados['cambio'],
            'ticket_url' => BASE_URL . 'rest-caja/ticket/' . $pedido['id'],
        ]);
    }

    /**
     * POST JSON — entrega un pedido que ya venía pagado desde la app.
     * Se registra su pago como `stripe_app` para que aparezca en el corte
     * como prepagado, sin contaminar el efectivo esperado (R12).
     */
    public function entregarPedido(?string $id = null): void
    {
        $turno = $this->requireTurno();
        $this->requireCsrfJson();

        $pedido = $this->pedidoDelRestaurante((int)$id);
        if (!$pedido) {
            $this->json(['ok' => false, 'error' => 'Pedido no encontrado.'], 404);
        }
        if ($pedido['estado'] === 'cancelado') {
            $this->json(['ok' => false, 'error' => 'Ese pedido está cancelado.'], 409);
        }

        $db = Database::getInstance();
        $db->beginTransaction();
        try {
            if (!empty($pedido['pagado_at']) && !$this->pagoModel->porPedido((int)$pedido['id'])) {
                $this->pagoModel->registrar(
                    (int)$pedido['id'], $this->restauranteId,
                    (int)$turno['id'], (int)$this->cajeroActivo(),
                    [[
                        'metodo'     => 'stripe_app',
                        'monto'      => (float)$pedido['total'],
                        'recibido'   => null,
                        'cambio'     => null,
                        'referencia' => $pedido['stripe_payment_intent_id'] ?? $pedido['metodo_pago'] ?? null,
                    ]]
                );
            }

            $this->pedidoModel->tomarEnCaja((int)$pedido['id'], [
                'turno_caja_id' => (int)$turno['id'],
                'cajero_id'     => (int)$this->cajeroActivo(),
                'estado'        => 'entregado',
            ]);

            $db->commit();
        } catch (\Throwable $e) {
            $db->rollBack();
            error_log('[caja] Error al entregar pedido: ' . $e->getMessage());
            $this->json(['ok' => false, 'error' => 'No se pudo marcar como entregado.'], 500);
            return;
        }

        $this->pedidoModel->descontarStockEntrega((int)$pedido['id']);
        $this->log('Entrega de pedido de app', 'caja', 'Pedido ' . $pedido['id']);
        $this->json(['ok' => true, 'ticket_url' => BASE_URL . 'rest-caja/ticket/' . $pedido['id']]);
    }

    // ═══════════════════════════════════════════════════════════
    //  Ticket
    // ═══════════════════════════════════════════════════════════

    /** Vista térmica lista para imprimir (58mm / 80mm). */
    public function ticket(?string $id = null): void
    {
        $this->requireCajeroEnSesion();

        $payload = $this->armarTicket((int)$id);
        if (!$payload) {
            $this->flash('error', 'Ticket no encontrado.');
            $this->redirect('rest-caja/venta');
        }

        $this->render('caja/ticket', ['t' => $payload]);
    }

    /**
     * GET JSON — mismo ticket como datos.
     * Este es el contrato que consumirán los adaptadores de impresión que
     * faltan (QZ Tray y la app de escritorio). Ver plan-web-cajero-ui.md §4.
     */
    public function ticketPayload(?string $id = null): void
    {
        $this->requireCajeroEnSesion();

        $payload = $this->armarTicket((int)$id);
        if (!$payload) {
            $this->json(['ok' => false, 'error' => 'Ticket no encontrado.'], 404);
        }
        $this->json(['ok' => true, 'ticket' => $payload]);
    }

    private function armarTicket(int $pedidoId): ?array
    {
        $pedido = $this->pedidoModel->getConItemsSinMesas($pedidoId, $this->restauranteId);
        if (!$pedido) return null;

        $rest  = $this->cfg['restaurante'];
        $pagos = $this->pagoModel->porPedido($pedidoId);
        $subtotal = (float)$pedido['subtotal'];
        $propina  = (float)($pedido['propina_mxn'] ?? 0);
        $total    = (float)$pedido['total'];
        $ivaMxn   = (float)($pedido['iva_mxn'] ?? 0);

        $items = [];
        foreach ($pedido['items'] ?? [] as $item) {
            // Los modificadores salen de su propia tabla y no del JSON `extras`:
            // un pedido creado por la app puede traer la relación sin el JSON.
            $mods = array_values(array_filter(
                $item['modificadores'] ?? [],
                fn($m) => ($m['tipo'] ?? '') !== 'sin'
            ));

            $items[] = [
                'cantidad'      => (int)$item['cantidad'],
                'nombre'        => $item['platillo_nombre'],
                'precio_unit'   => (float)$item['precio_unit'],
                'subtotal'      => (float)$item['subtotal'],
                'nota'          => $item['notas'] ?: null,
                'exclusiones'   => $item['exclusiones'] ?: null,
                'modificadores' => array_map(fn($m) => [
                    'nombre'       => $m['nombre'] ?? '',
                    'precio_extra' => (float)($m['precio_extra'] ?? 0),
                    'cantidad'     => (int)($m['cantidad'] ?? 1),
                ], $mods),
            ];
        }

        return [
            'negocio' => [
                'nombre'    => $rest['nombre'] ?? '',
                'direccion' => $rest['direccion'] ?? null,
                'telefono'  => $rest['telefono'] ?? null,
            ],
            'ticket' => [
                'folio'       => $pedido['folio'],
                'pedido_id'   => (int)$pedido['id'],
                'fecha'       => $pedido['pagado_at'] ?: $pedido['created_at'],
                'cajero'      => $this->cajeroSesion()['nombre'] ?? '',
                'turno_id'    => (int)($pedido['turno_caja_id'] ?? 0),
                'ancho'       => in_array($this->get('w'), ['58', '80'], true)
                                    ? $this->get('w') . 'mm'
                                    : $this->cfg['impresora_ancho_ticket'],
                'reimpresion' => (bool)$this->get('reimpresion'),
                'cancelado'   => $pedido['estado'] === 'cancelado',
            ],
            'cliente' => [
                'nombre'   => $pedido['cliente_nombre'] ?: null,
                'telefono' => $pedido['comprador_telefono'] ?: null,
            ],
            'items'   => $items,
            'totales' => [
                'subtotal'       => $subtotal,
                'descuento'      => (float)$pedido['descuento'],
                'propina'        => $propina,
                'total'          => $total,
                'iva_habilitado' => $this->cfg['iva_habilitado'] && $ivaMxn > 0,
                'iva_porcentaje' => $this->cfg['iva_porcentaje'],
                'iva_mxn'        => $ivaMxn,
                'base_gravable'  => round($total - $propina - $ivaMxn, 2),
            ],
            'pagos'   => array_map(fn($p) => [
                'metodo'   => $p['metodo'],
                'etiqueta' => PedidoPagoModel::etiqueta($p['metodo']),
                'tipo'     => $p['tipo'],
                'monto'    => (float)$p['monto'],
                'recibido' => $p['recibido'] !== null ? (float)$p['recibido'] : null,
                'cambio'   => $p['cambio'] !== null ? (float)$p['cambio'] : null,
            ], $pagos),
            'leyenda' => $this->cfg['ticket_leyenda'],
        ];
    }

    // ═══════════════════════════════════════════════════════════
    //  Guards y helpers de sesión
    // ═══════════════════════════════════════════════════════════

    private function cajeroActivo(): ?int
    {
        $id = (int)($_SESSION['caja']['cajero_id'] ?? 0);
        if ($id) return $id;

        // Un admin que opera la caja ya se autenticó con su contraseña:
        // no tiene sentido pedirle además un PIN.
        if (in_array($this->rolActual(), ['admin_restaurante', 'admin_local', 'comprador', 'superadmin'], true)) {
            $this->abrirSesionCaja([
                'id'     => $this->usuarioId(),
                'nombre' => $_SESSION['usuario']['nombre'] ?? 'Admin',
            ]);
            return $this->usuarioId();
        }
        return null;
    }

    private function cajeroSesion(): array
    {
        return [
            'id'     => (int)($_SESSION['caja']['cajero_id'] ?? 0),
            'nombre' => (string)($_SESSION['caja']['cajero_nombre'] ?? ''),
        ];
    }

    private function abrirSesionCaja(array $cajero): void
    {
        $_SESSION['caja'] = [
            'cajero_id'     => (int)$cajero['id'],
            'cajero_nombre' => trim(($cajero['nombre'] ?? '') . ' ' . ($cajero['apellido_paterno'] ?? '')),
            'turno_id'      => null,
            'bloqueada'     => false,
        ];
        if ($turno = $this->turnoModel->abierto((int)$cajero['id'])) {
            $_SESSION['caja']['turno_id'] = (int)$turno['id'];
        }
    }

    private function pantallaBloqueada(): bool
    {
        return !empty($_SESSION['caja']['bloqueada']);
    }

    private function requireCajeroEnSesion(): void
    {
        if (!$this->cajeroActivo() || $this->pantallaBloqueada()) {
            if ($this->esPeticionJson()) {
                $this->json(['ok' => false, 'error' => 'Sesión de caja bloqueada.', 'redirect' => BASE_URL . 'rest-caja/index'], 401);
            }
            $this->redirect('rest-caja/index');
        }
    }

    /** No se puede vender sin turno abierto (R1). Se valida en servidor, no en la UI. */
    private function requireTurno(): array
    {
        $this->requireCajeroEnSesion();

        $turno = $this->turnoAbierto();
        if (!$turno) {
            if ($this->esPeticionJson()) {
                $this->json(['ok' => false, 'error' => 'No tienes un turno de caja abierto.', 'redirect' => BASE_URL . 'rest-caja/apertura'], 409);
            }
            $this->redirect('rest-caja/apertura');
        }
        return $turno;
    }

    private function turnoAbierto(): ?array
    {
        $cajeroId = $this->cajeroActivo();
        if (!$cajeroId) return null;

        $turno = $this->turnoModel->abierto($cajeroId);
        $_SESSION['caja']['turno_id'] = $turno ? (int)$turno['id'] : null;
        return $turno;
    }

    // ═══════════════════════════════════════════════════════════
    //  Cálculo de la venta
    // ═══════════════════════════════════════════════════════════

    /** @return array{monto:float, tipo:?string, valor:float, motivo:?string, promo_code:?string, promocion:?array, autorizado_por:?int} */
    private function calcularDescuento(array $datos, float $subtotal): array
    {
        $resultado = [
            'monto' => 0.0, 'tipo' => null, 'valor' => 0.0, 'motivo' => null,
            'promo_code' => null, 'promocion' => null, 'autorizado_por' => null,
        ];

        // 1. Cupón
        $code = strtoupper(trim((string)($datos['cupon_code'] ?? '')));
        if ($code !== '') {
            $promo = $this->buscarPromocion($code);
            if (!$promo) {
                throw new \InvalidArgumentException('El cupón no es válido o ya venció.');
            }
            $monto = $promo['tipo'] === 'porcentaje'
                ? round($subtotal * (float)$promo['valor_descuento'] / 100, 2)
                : round((float)$promo['valor_descuento'], 2);
            // 'envio_gratis' no aplica en mostrador: no hay envío que descontar.
            if ($promo['tipo'] === 'envio_gratis') {
                $monto = 0.0;
            }
            $resultado['monto']     += min($monto, $subtotal);
            $resultado['promo_code'] = $promo['code'];
            $resultado['promocion']  = $promo;
        }

        // 2. Descuento manual
        $manual = (array)($datos['descuento'] ?? []);
        $valor  = round((float)($manual['valor'] ?? 0), 2);
        if ($valor > 0) {
            $tipo = ($manual['tipo'] ?? 'porcentaje') === 'monto_fijo' ? 'monto_fijo' : 'porcentaje';
            $monto = $tipo === 'porcentaje'
                ? round($subtotal * min($valor, 100) / 100, 2)
                : min($valor, $subtotal);

            $pctEquivalente = $subtotal > 0 ? ($monto / $subtotal) * 100 : 0;
            if ($pctEquivalente > $this->cfg['descuento_max_cajero_pct'] + 0.001) {
                $admin = $this->autorizacionVigente((string)($manual['autorizacion_token'] ?? ''));
                if (!$admin) {
                    throw new \RuntimeException(
                        'Ese descuento pasa del ' . rtrim(rtrim(number_format($this->cfg['descuento_max_cajero_pct'], 2), '0'), '.') .
                        '% permitido. Pide autorización de un administrador.'
                    );
                }
                $resultado['autorizado_por'] = $admin;
            }

            $resultado['monto'] += $monto;
            $resultado['tipo']   = $tipo;
            $resultado['valor']  = $valor;
            $resultado['motivo'] = $this->texto($manual['motivo'] ?? null, 255);
        }

        $resultado['monto'] = round(min($resultado['monto'], $subtotal), 2);
        return $resultado;
    }

    private function buscarPromocion(string $code): ?array
    {
        return (new RestPromocionModel())->porCodigoVigente($this->restauranteId, $code);
    }

    private function registrarDescuento(int $pedidoId, array $desc): void
    {
        if (!$desc['tipo'] || $desc['monto'] <= 0) return;

        (new RestPromocionModel())->registrarDescuentoManual([
            'pedido_id'             => $pedidoId,
            'restaurante_id'        => $this->restauranteId,
            'cajero_id'             => (int)$this->cajeroActivo(),
            'tipo'                  => $desc['tipo'],
            'valor'                 => $desc['valor'],
            'monto_aplicado'        => $desc['monto'],
            'motivo'                => $desc['motivo'],
            'requirio_autorizacion' => $desc['autorizado_por'] ? 1 : 0,
            'autorizado_por_id'     => $desc['autorizado_por'],
        ]);
    }

    private function marcarUsoCupon(array $desc, int $pedidoId, array $cliente): void
    {
        if (empty($desc['promocion'])) return;

        (new RestPromocionModel())->registrarUso(
            (int)$desc['promocion']['id'],
            (string)$desc['promo_code'],
            $pedidoId,
            isset($cliente['mobile_usuario_id']) ? (int)$cliente['mobile_usuario_id'] : null,
            $desc['monto']
        );
    }

    private function propinaValida(mixed $valor, float $referencia): float
    {
        $propina = round((float)$valor, 2);
        if ($propina < 0) {
            throw new \InvalidArgumentException('La propina no puede ser negativa.');
        }
        if (!$this->cfg['propinas_pos_habilitadas']) {
            return 0.0;
        }
        // Tope de cordura: atrapa un dedazo tipo 1500 en vez de 15.
        if ($referencia > 0 && $propina > $referencia * 2) {
            throw new \InvalidArgumentException('Esa propina se ve fuera de rango. Revísala.');
        }
        return $propina;
    }

    /** IVA contenido en un importe que YA lo incluye (decisión D6). */
    private function ivaContenido(float $importe): float
    {
        if (!$this->cfg['iva_habilitado'] || $this->cfg['iva_porcentaje'] <= 0) {
            return 0.0;
        }
        $pct = $this->cfg['iva_porcentaje'];
        return round($importe * $pct / (100 + $pct), 2);
    }

    private function cobrarWalletSiAplica(array $pagos, array $cliente, int $pedidoId): void
    {
        $monto = 0.0;
        foreach ($pagos as $pago) {
            if ($pago['metodo'] === 'wallet') {
                $monto += (float)$pago['monto'];
            }
        }
        if ($monto <= 0) return;

        $mobileId = (int)($cliente['mobile_usuario_id'] ?? 0);
        if (!$mobileId) {
            throw new \InvalidArgumentException('Para cobrar con saldo primero hay que identificar al cliente.');
        }

        (new WalletModel())->debitar($mobileId, $monto, $pedidoId, 'Compra en caja');
    }

    private function etiquetaMetodoPago(array $pagos): string
    {
        if (count($pagos) > 1) return 'mixto';
        return (string)($pagos[0]['metodo'] ?? 'efectivo');
    }

    private function autorizacionVigente(string $token): ?int
    {
        $auth = $_SESSION['caja']['autorizacion'] ?? null;
        if (!$auth || $token === '' || !hash_equals((string)$auth['token'], $token)) {
            return null;
        }
        if (time() > (int)$auth['expira']) {
            unset($_SESSION['caja']['autorizacion']);
            return null;
        }
        return (int)$auth['admin_id'];
    }

    /** La autorización es de un solo uso: se quema al cobrar (R7). */
    private function consumirAutorizacion(): void
    {
        unset($_SESSION['caja']['autorizacion']);
    }

    // ═══════════════════════════════════════════════════════════
    //  Utilidades
    // ═══════════════════════════════════════════════════════════

    private function itemsDeEntrada(mixed $items): array
    {
        $limpios = [];
        foreach ((array)$items as $item) {
            $platilloId = (int)($item['platillo_id'] ?? 0);
            if ($platilloId <= 0) continue;

            $limpios[] = [
                'platillo_id'   => $platilloId,
                'cantidad'      => max(1, min(99, (int)($item['cantidad'] ?? 1))),
                'notas'         => $this->texto($item['notas'] ?? null, 255),
                'modificadores' => array_map(fn($m) => [
                    'modificador_id' => (int)($m['modificador_id'] ?? 0),
                    'cantidad'       => max(1, (int)($m['cantidad'] ?? 1)),
                ], (array)($item['modificadores'] ?? [])),
            ];
        }
        return $limpios;
    }

    private function pedidoDelRestaurante(int $pedidoId): ?array
    {
        return $this->pedidoModel->delRestaurante($pedidoId, $this->restauranteId);
    }

    private function pedidoPorUuid(string $uuid): ?array
    {
        return $this->pedidoModel->porUuidPos($uuid, $this->restauranteId);
    }

    private function respuestaCobro(array $pedido, ?float $cambio = null): array
    {
        return [
            'ok'         => true,
            'pedido_id'  => (int)$pedido['id'],
            'folio'      => $pedido['folio'],
            'total'      => (float)$pedido['total'],
            'cambio'     => $cambio,
            'ticket_url' => BASE_URL . 'rest-caja/ticket/' . $pedido['id'],
        ];
    }

    private function uuidValido(mixed $uuid): ?string
    {
        $uuid = trim((string)($uuid ?? ''));
        return preg_match('/^[0-9a-fA-F-]{16,36}$/', $uuid) ? $uuid : null;
    }

    private function texto(mixed $valor, int $max): ?string
    {
        $txt = trim((string)($valor ?? ''));
        return $txt === '' ? null : mb_substr($txt, 0, $max);
    }

    private function jsonInput(): array
    {
        static $cache = null;
        if ($cache !== null) return $cache;

        $crudo = file_get_contents('php://input') ?: '';
        $datos = json_decode($crudo, true);
        return $cache = is_array($datos) ? $datos : $_POST;
    }

    private function esPeticionJson(): bool
    {
        return str_contains(strtolower($_SERVER['HTTP_ACCEPT'] ?? ''), 'application/json')
            || strtolower($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') === 'xmlhttprequest'
            || str_contains(strtolower($_SERVER['CONTENT_TYPE'] ?? ''), 'application/json');
    }

    private function requireCsrfJson(): void
    {
        if (!$this->isPost()) {
            $this->json(['ok' => false, 'error' => 'Método no permitido.'], 405);
        }
        $token = $this->jsonInput()['_csrf'] ?? null;
        if (!$this->validarCsrf(is_string($token) ? $token : null)) {
            $this->json(['ok' => false, 'error' => 'La sesión expiró. Recarga la pantalla.'], 419);
        }
    }

    private function requireCsrf(): void
    {
        if (!$this->isPost() || !$this->validarCsrf()) {
            $this->flash('error', 'La sesión expiró. Vuelve a intentarlo.');
            $this->redirect('rest-caja/index');
        }
    }
}
