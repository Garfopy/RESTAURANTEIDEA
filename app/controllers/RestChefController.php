<?php
require_once ROOT_PATH . '/app/controllers/BaseController.php';

class RestChefController extends BaseController
{
    protected RestPedidoModel $model;
    private static array $columnCache = [];
    protected string $kdsArea = 'cocina';
    protected string $kdsBaseRoute = 'rest-chef';
    protected string $kdsLogoutRol = 'chef';
    protected string $kdsTitle = 'KDS - Cocina';
    protected string $kdsBrand = 'KDS Cocina';
    protected string $kdsIcon = 'Cocina';

    public function __construct()
    {
        parent::__construct();
        $this->authorize();
        $this->model = new RestPedidoModel();
    }

    protected function authorize(): void
    {
        $this->requireChef();
    }

    private function hasPedidoColumn(string $column): bool
    {
        if (array_key_exists($column, self::$columnCache)) {
            return self::$columnCache[$column];
        }

        try {
            $db = Database::getInstance();
            $stmt = $db->prepare(
                "SELECT COUNT(*)
                   FROM information_schema.columns
                  WHERE table_schema = DATABASE()
                    AND table_name = 'rest_pedidos'
                    AND column_name = ?"
            );
            $stmt->execute([$column]);
            self::$columnCache[$column] = (int)$stmt->fetchColumn() > 0;
        } catch (\Throwable $e) {
            self::$columnCache[$column] = false;
        }

        return self::$columnCache[$column];
    }

    private function pedidoSelectMeta(string $alias): string
    {
        $select = [$this->hasPedidoColumn('tipo_origen') ? "{$alias}.tipo_origen" : "NULL AS tipo_origen"];
        $select[] = $this->hasPedidoColumn('es_regalo') ? "{$alias}.es_regalo" : "0 AS es_regalo";
        $select[] = $this->hasPedidoColumn('tipo_entrega') ? "{$alias}.tipo_entrega" : "NULL AS tipo_entrega";
        $select[] = $this->hasPedidoColumn('tipo_pedido') ? "{$alias}.tipo_pedido" : "NULL AS tipo_pedido";
        return implode(', ', $select);
    }

    private function debeIgnorarPorStore(array $pedido): bool
    {
        $tipoOrigen = strtolower((string)($pedido['tipo_origen'] ?? ''));
        $tipoEntrega = strtolower((string)($pedido['tipo_entrega'] ?? ''));
        $tipoPedido = strtolower((string)($pedido['tipo_pedido'] ?? ''));
        $platilloCodigo = strtolower((string)($pedido['platillo_codigo'] ?? ''));
        $platilloNombre = strtolower((string)($pedido['platillo_nombre'] ?? ''));
        $itemNotas = strtolower((string)($pedido['item_notas'] ?? ''));
        $esRegalo = (int)($pedido['es_regalo'] ?? 0) === 1
            || in_array($tipoOrigen, ['gift', 'regalo', 'regalos'], true)
            || in_array($tipoEntrega, ['gift', 'regalo', 'regalos'], true)
            || in_array($tipoPedido, ['gift', 'regalo', 'regalos'], true)
            || str_starts_with($platilloCodigo, 'sg-')
            || str_starts_with($platilloNombre, 'regalo:')
            || str_contains($itemNotas, 'regalo para');

        return $tipoOrigen === 'store' || $esRegalo;
    }

    public function dashboard(?string $p = null): void
    {
        $restauranteId = $this->restauranteId();
        $restaurante   = (new RestauranteModel())->find($restauranteId);
        $pageTitle     = 'Cocina — KDS';
        $pageTitle     = $this->kdsTitle;
        $kdsBaseRoute  = $this->kdsBaseRoute;
        $kdsLogoutRol  = $this->kdsLogoutRol;
        $kdsTitle      = $this->kdsTitle;
        $kdsBrand      = $this->kdsBrand;
        $kdsIcon       = $this->kdsIcon;
        $this->render('chef/dashboard', compact(
            'restaurante',
            'pageTitle',
            'kdsBaseRoute',
            'kdsLogoutRol',
            'kdsTitle',
            'kdsBrand',
            'kdsIcon'
        ));
    }

    public function queue(?string $p = null): void
    {
        $restauranteId = $this->restauranteId();
        $items = $this->model->getKitchenQueue($restauranteId, $this->kdsArea);
        $this->json($items);
    }

    public function marcarPreparacion(?string $itemId = null): void
    {
        $itemId = (int)$itemId;

        // ── Leer estado actual ANTES de cambiar (idempotencia) ────────────────
        try {
            $db       = Database::getInstance();
            $pedidoMetaSelect = $this->pedidoSelectMeta('p');
            $stmtItem = $db->prepare(
                "SELECT pi.platillo_id, pi.cantidad, pi.pedido_id, pi.estado,
                        pi.notas AS item_notas,
                        COALESCE(pl.codigo, '') AS platillo_codigo,
                        pl.nombre AS platillo_nombre,
                        p.restaurante_id, {$pedidoMetaSelect}
                 FROM rest_pedido_items pi
                 JOIN rest_pedidos p ON p.id = pi.pedido_id
                 JOIN rest_platillos pl ON pl.id = pi.platillo_id
                 WHERE pi.id = ? LIMIT 1"
            );
            $stmtItem->execute([$itemId]);
            $item = $stmtItem->fetch(\PDO::FETCH_ASSOC);

            if ($item && $this->debeIgnorarPorStore($item)) {
                $this->json(['ok' => true, 'store_ignored' => true]);
                return;
            }

            // Si ya estaba en preparación, no descontar stock de nuevo
            if ($item && $item['estado'] === 'en_preparacion') {
                $this->json(['ok' => true]);
                return;
            }
        } catch (\Throwable $e) {
            // Si falla la lectura previa, dejar que continúe el flujo normal
        }

        $this->model->cambiarEstadoItem($itemId, 'en_preparacion');

        // ── Descontar ingredientes de inventario al iniciar preparación ───────
        try {
            $db       = Database::getInstance();
            $stmtItem = $db->prepare(
                "SELECT pi.platillo_id, pi.cantidad, pi.pedido_id, pi.exclusiones, p.restaurante_id
                 FROM rest_pedido_items pi
                 JOIN rest_pedidos p ON p.id = pi.pedido_id
                 WHERE pi.id = ? LIMIT 1"
            );
            $stmtItem->execute([$itemId]);
            $item = $stmtItem->fetch(\PDO::FETCH_ASSOC);

            if ($item && $item['restaurante_id']) {
                $restauranteId  = (int)$item['restaurante_id'];
                $platilloId     = (int)$item['platillo_id'];
                $cantidadPlatos = max(1, (int)$item['cantidad']);
                $pedidoId       = (int)$item['pedido_id'];
                $ref            = 'rest_item:' . $itemId;
                $invModel       = new RestInventarioModel();

                // Ingredientes de la receta que sí descuentan stock (es_informativo = 0)
                $stmtRec = $db->prepare(
                    "SELECT ri.ingrediente_id, ri.cantidad, i.nombre
                     FROM rest_receta_ingredientes ri
                     JOIN rest_recetas rec ON rec.id = ri.receta_id
                     JOIN rest_ingredientes i ON i.id = ri.ingrediente_id
                     WHERE rec.platillo_id = ?
                       AND ri.es_informativo = 0
                       AND NOT EXISTS (
                           SELECT 1 FROM rest_pedido_item_modificadores pim
                           JOIN rest_modificadores m ON m.id=pim.modificador_id
                           WHERE pim.pedido_item_id=? AND m.tipo='sin' AND m.ingrediente_id=ri.ingrediente_id
                       )"
                );
                $stmtRec->execute([$platilloId, $itemId]);
                $recIngredientes = $stmtRec->fetchAll(\PDO::FETCH_ASSOC);

                if (!empty($recIngredientes)) {
                    // Platillo con receta → descontar ingredientes
                    foreach ($recIngredientes as $ri) {
                        $excluidos = array_map('trim', explode(',', (string)($item['exclusiones'] ?? '')));
                        if (in_array($ri['nombre'], $excluidos, true)) continue;
                        $delta = (float)$ri['cantidad'] * $cantidadPlatos;
                        $invModel->ajustarStock(
                            (int)$ri['ingrediente_id'],
                            -$delta,
                            'salida',
                            'Preparación (pedido #' . $pedidoId . ')',
                            $ref,
                            $restauranteId,
                            null
                        );
                    }
                } else {
                    // Sin receta → deducir por código (bebidas, postres, etc.)
                    // Busca el ingrediente cuyo codigo coincide con el del platillo.
                    $stmtCod = $db->prepare(
                        "SELECT i.id
                         FROM rest_ingredientes i
                         JOIN rest_platillos pl ON TRIM(pl.codigo) = TRIM(i.codigo)
                         WHERE pl.id = ?
                           AND i.restaurante_id = ?
                           AND i.activo = 1
                           AND TRIM(COALESCE(i.codigo,'')) != ''
                         LIMIT 1"
                    );
                    $stmtCod->execute([$platilloId, $restauranteId]);
                    $ingId = (int)$stmtCod->fetchColumn();
                    if ($ingId) {
                        $invModel->ajustarStock(
                            $ingId,
                            -(float)$cantidadPlatos,
                            'salida',
                            'Preparación (pedido #' . $pedidoId . ')',
                            $ref,
                            $restauranteId,
                            null
                        );
                    }
                }

                $stmtExtras = $db->prepare(
                    "SELECT pim.cantidad, m.ingrediente_id, m.cantidad_unidad, m.unidad, i.unidad_principal
                     FROM rest_pedido_item_modificadores pim
                     JOIN rest_modificadores m ON m.id=pim.modificador_id AND m.tipo='extra'
                     JOIN rest_ingredientes i ON i.id=m.ingrediente_id
                     WHERE pim.pedido_item_id=?"
                );
                $stmtExtras->execute([$itemId]);
                foreach ($stmtExtras->fetchAll(\PDO::FETCH_ASSOC) as $extra) {
                    $cantidadExtra = (float)$extra['cantidad_unidad'] * (int)$extra['cantidad'] * $cantidadPlatos;
                    $delta = RestInventarioModel::convertirUnidad($cantidadExtra, $extra['unidad'], $extra['unidad_principal']);
                    $invModel->ajustarStock((int)$extra['ingrediente_id'], -$delta, 'salida', 'Extra de pedido #' . $pedidoId, $ref, $restauranteId, null);
                }
            }
        } catch (\Throwable $e) {
            // No bloquea el flujo — el ítem ya quedó marcado como en preparación
        }

        $this->json(['ok' => true]);
    }

    public function marcarListo(?string $itemId = null): void
    {
        $itemId = (int)$itemId;
        $db   = Database::getInstance();
        try {
            $pedidoMetaSelect = $this->pedidoSelectMeta('p');
            $stmtItem = $db->prepare(
                "SELECT pi.notas AS item_notas,
                        COALESCE(pl.codigo, '') AS platillo_codigo,
                        pl.nombre AS platillo_nombre,
                        {$pedidoMetaSelect}
                   FROM rest_pedido_items pi
                   JOIN rest_pedidos p ON p.id = pi.pedido_id
                   JOIN rest_platillos pl ON pl.id = pi.platillo_id
                  WHERE pi.id = ? LIMIT 1"
            );
            $stmtItem->execute([$itemId]);
            $item = $stmtItem->fetch(\PDO::FETCH_ASSOC);
            if ($item && $this->debeIgnorarPorStore($item)) {
                $this->json(['ok' => true, 'store_ignored' => true]);
                return;
            }
        } catch (\Throwable $e) {
            // Si la columna tipo_origen no existe, continuar con el flujo normal.
        }

        $this->model->cambiarEstadoItem($itemId, 'listo');

        $stmt = $db->prepare(
            "SELECT pedido_id FROM rest_pedido_items WHERE id = ?"
        );
        $stmt->execute([$itemId]);
        $pedidoId = (int)$stmt->fetchColumn();

        if ($pedidoId) {
            // Si todos los ítems están listos/entregados → marcar pedido como listo
            $stmt2 = $db->prepare(
                "SELECT COUNT(*) FROM rest_pedido_items
                 WHERE pedido_id = ? AND estado NOT IN ('listo','entregado','cancelado')"
            );
            $stmt2->execute([$pedidoId]);
            if ((int)$stmt2->fetchColumn() === 0) {
                $this->model->cambiarEstadoPedido($pedidoId, 'listo');
            }
        }

        $this->json(['ok' => true]);
    }

    // GET /rest-chef/armado/{platillo_id}
    // Devuelve ingredientes (con codigo_display) y pasos de preparación para el KDS
    public function armado(?string $platilloId = null): void
    {
        $platilloId    = (int)$platilloId;
        $restauranteId = $this->restauranteId();
        $db            = Database::getInstance();

        $stmtIng = $db->prepare(
            "SELECT ri.codigo_display, ri.tipo_componente, ri.cantidad, ri.unidad,
                    i.nombre
             FROM rest_receta_ingredientes ri
             JOIN rest_recetas            re ON re.id = ri.receta_id
             JOIN rest_ingredientes        i  ON i.id  = ri.ingrediente_id
             WHERE re.platillo_id   = ?
               AND i.restaurante_id = ?
             ORDER BY ri.tipo_componente, ri.codigo_display, i.nombre"
        );
        $stmtIng->execute([$platilloId, $restauranteId]);
        $ingredientes = $stmtIng->fetchAll(PDO::FETCH_ASSOC);

        $stmtPasos = $db->prepare(
            "SELECT orden_paso, descripcion
             FROM rest_pasos_preparacion
             WHERE platillo_id    = ?
               AND restaurante_id = ?
               AND activo         = 1
             ORDER BY orden_paso ASC"
        );
        $stmtPasos->execute([$platilloId, $restauranteId]);
        $pasos = $stmtPasos->fetchAll(PDO::FETCH_ASSOC);

        $this->json(['ingredientes' => $ingredientes, 'pasos' => $pasos]);
    }
}
