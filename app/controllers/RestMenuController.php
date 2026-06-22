<?php
require_once ROOT_PATH . '/app/controllers/BaseController.php';

class RestMenuController extends BaseController
{
    private RestMenuModel $model;

    public function __construct()
    {
        parent::__construct();
        $this->requireRestaurante();
        $this->model = new RestMenuModel();
    }

    public function index(?string $p = null): void
    {
        $restauranteId = $this->restauranteId();
        $platillos  = $this->model->getByRestaurante($restauranteId, true);
        $categorias = $this->model->getCategorias($restauranteId);
        $flash      = $this->getFlash();
        $pageTitle  = 'Menú';
        $activeMenu = 'rest_menu';
        $sucursales = (new RestauranteModel())->getByComprador($this->usuarioId());
        $this->render('restaurante/menu/index', compact('platillos','categorias','flash','pageTitle','activeMenu','sucursales'));
    }

    public function form(?string $id = null): void
    {
        $restauranteId = $this->restauranteId();
        $platillo   = $id ? $this->model->getPlatilloConReceta((int)$id) : null;
        $categorias = $this->model->getCategorias($restauranteId, true);
        $ingredientes = (new RestInventarioModel())->getByRestaurante($restauranteId, true);
        $flash      = $this->getFlash();
        $pageTitle  = $platillo ? 'Editar Platillo' : 'Nuevo Platillo';
        $activeMenu = 'rest_menu';
        $this->render('restaurante/menu/form', compact('platillo','categorias','ingredientes','flash','pageTitle','activeMenu'));
    }

    public function guardar(?string $p = null): void
    {
        if (!$this->isPost()) $this->redirect('rest-menu/index');
        $restauranteId = $this->restauranteId();

        $id = (int)$this->post('id');

        // Alérgenos: array of checkbox values → comma-separated string
        $alergenosArr = $this->post('alergenos', []);
        $alergenosStr = is_array($alergenosArr) ? implode(',', array_filter(array_map('trim', $alergenosArr))) : '';

        $data = [
            'restaurante_id'         => $restauranteId,
            'categoria_id'           => $this->post('categoria_id') ?: null,
            'nombre'                 => trim($this->post('nombre', '')),
            'descripcion'            => $this->post('descripcion'),
            'precio'                 => (float)$this->post('precio', 0),
            'tiempo_preparacion_min' => (int)$this->post('tiempo_preparacion_min', 15),
            'disponible'             => $this->post('disponible', 1),
            'alergenos'              => $alergenosStr ?: null,
            'contiene'               => $this->post('contiene') ?: null,
            'ingrediente_directo_id' => $this->post('ingrediente_directo_id') ?: null,
        ];

        try {
            $modificadores = $this->normalizarModificadores(
                $restauranteId,
                array_map('intval', (array)$this->post('ingrediente_id', []))
            );
        } catch (\InvalidArgumentException $e) {
            $this->flash('error', $e->getMessage());
            $this->redirect('rest-menu/form/' . ($id ?: ''));
            return;
        }

        // Imagen del platillo (subida)
        $imagenPath = $this->procesarImagenPlatillo($restauranteId, $id);
        if ($imagenPath !== null) {
            $data['imagen'] = $imagenPath ?: null;
        }

        if ($id) {
            try {
                $this->model->update($id, array_diff_key($data, ['restaurante_id' => '']));
            } catch (\PDOException $e) {
                // Fallback: si las columnas ingrediente_directo_id o imagen aún no existen (migración pendiente)
                $safeData = array_diff_key($data, ['restaurante_id' => '', 'ingrediente_directo_id' => '', 'imagen' => '']);
                $this->model->update($id, $safeData);
            }
            $platilloId = $id;
        } else {
            try {
                $platilloId = $this->model->insert($data);
            } catch (\PDOException $e) {
                $safeData = array_diff_key($data, ['ingrediente_directo_id' => '', 'imagen' => '']);
                $platilloId = $this->model->insert($safeData);
            }
        }

        // Guardar receta si vienen ingredientes
        $ingredientesIds  = $this->post('ingrediente_id', []);
        $cantidades       = $this->post('cantidad', []);
        $unidades         = $this->post('unidad', []);
        $informativos     = $this->post('es_informativo', []);

        if (!empty($ingredientesIds)) {
            $recetaId = $this->model->upsertReceta(
                $platilloId,
                (int)$this->post('porciones_base', 1),
                $this->post('receta_notas')
            );
            $ings = [];
            foreach ($ingredientesIds as $k => $ingId) {
                if (!$ingId) continue;
                $ings[] = [
                    'ingrediente_id'  => (int)$ingId,
                    'cantidad'        => (float)($cantidades[$k] ?? 0),
                    'unidad'          => $unidades[$k] ?? 'kg',
                    'es_informativo'  => in_array((string)$ingId, (array)$informativos) ? 1 : 0,
                    'tipo_componente' => 'materia_prima',
                    'codigo_display'  => null,
                    'precio_extra'    => 0.0,
                ];
            }
            $this->model->syncIngredientesReceta($recetaId, $ings);
        }

        $this->model->syncModificadores($restauranteId, $platilloId, $modificadores);
        $syncError = $this->syncModificadoresAmare($restauranteId, $platilloId);

        $this->flash($syncError ? 'error' : 'success', $syncError ?: 'Platillo guardado.');
        $this->redirect('rest-menu/index');
    }

    private function normalizarModificadores(int $restauranteId, array $ingredientesReceta): array
    {
        $ids = (array)$this->post('modificador_id', []);
        $tipos = (array)$this->post('modificador_tipo', []);
        $ingredientes = (array)$this->post('modificador_ingrediente_id', []);
        $nombres = (array)$this->post('modificador_nombre', []);
        $cantidades = (array)$this->post('modificador_cantidad', []);
        $unidades = (array)$this->post('modificador_unidad', []);
        $precios = (array)$this->post('modificador_precio', []);
        $maximos = (array)$this->post('modificador_max', []);
        $resultado = [];
        $db = Database::getInstance();

        foreach ($tipos as $k => $tipo) {
            if (!in_array($tipo, ['sin', 'extra'], true)) continue;
            $ingredienteId = (int)($ingredientes[$k] ?? 0);
            if ($ingredienteId <= 0) throw new \InvalidArgumentException('Selecciona un ingrediente para cada modificador.');
            $stmt = $db->prepare("SELECT id, nombre, unidad_principal FROM rest_ingredientes WHERE id=? AND restaurante_id=? AND activo=1");
            $stmt->execute([$ingredienteId, $restauranteId]);
            $ingrediente = $stmt->fetch(\PDO::FETCH_ASSOC);
            if (!$ingrediente) throw new \InvalidArgumentException('Uno de los ingredientes de los modificadores no pertenece a este restaurante.');
            if ($tipo === 'sin' && !in_array($ingredienteId, $ingredientesReceta, true)) {
                throw new \InvalidArgumentException('Las guarniciones removibles deben formar parte de la receta del platillo.');
            }
            $precio = $tipo === 'extra' ? max(0, (float)($precios[$k] ?? 0)) : 0.0;
            $cantidad = max(0.001, (float)($cantidades[$k] ?? 1));
            $maximo = $tipo === 'extra' ? max(1, (int)($maximos[$k] ?? 1)) : 1;
            $nombre = trim((string)($nombres[$k] ?? ''));
            $resultado[] = [
                'id' => (int)($ids[$k] ?? 0),
                'tipo' => $tipo,
                'ingrediente_id' => $ingredienteId,
                'nombre' => $nombre !== '' ? mb_substr($nombre, 0, 120) : ($tipo === 'sin' ? 'Sin ' : 'Extra ') . $ingrediente['nombre'],
                'cantidad_unidad' => $cantidad,
                'unidad' => mb_substr(trim((string)($unidades[$k] ?? $ingrediente['unidad_principal'])), 0, 20),
                'precio_extra' => $precio,
                'max_seleccion' => $maximo,
            ];
        }
        return $resultado;
    }

    private function syncModificadoresAmare(int $restauranteId, int $platilloId): ?string
    {
        $result = (new AmareModifierSyncService())->syncPlatillo($restauranteId, $platilloId);
        if (!empty($result['ok'])) return null;
        return 'Platillo guardado localmente, pero Amare-App rechazo los modificadores (HTTP '
            . (int)($result['http_code'] ?? 0) . '): ' . mb_substr((string)($result['message'] ?? ''), 0, 180);
    }

    public function detalle(?string $id = null): void
    {
        $restauranteId = $this->restauranteId();
        $platillo = $this->model->getPlatilloConReceta((int)$id);
        if (!$platillo || ($platillo['restaurante_id'] ?? 0) != $restauranteId) {
            $this->redirect('rest-menu/index');
        }

        $porciones  = (int)($platillo['receta']['porciones_base'] ?? 1);
        $costoTotal = 0.0;

        foreach ($platillo['ingredientes'] as &$ing) {
            $costoUnit = (float)($ing['costo_unitario'] ?? 0);
            $mainUnit  = $ing['unidad_principal'] ?? $ing['unidad'];
            $cantConv  = $this->convertirUnidad((float)$ing['cantidad'], $ing['unidad'], $mainUnit);
            $costoIng  = $cantConv * $costoUnit;
            $ing['costo_por_unidad_receta'] = $costoUnit;
            $ing['costo_total_ing']         = $costoIng;
            $costoTotal += $costoIng;
        }
        unset($ing);

        $platillo['costo_total']      = $costoTotal;
        $platillo['costo_por_porcion'] = $porciones > 0 ? $costoTotal / $porciones : 0;

        $pageTitle  = $platillo['nombre'];
        $activeMenu = 'rest_menu';
        $this->render('restaurante/menu/detalle', compact('platillo','porciones','pageTitle','activeMenu'));
    }

    private function convertirUnidad(float $q, string $desde, string $hasta): float
    {
        $d = strtolower(trim($desde));
        $h = strtolower(trim($hasta));
        if ($d === $h) return $q;
        $map = [
            'g_kg'  => 1e-3, 'kg_g'  => 1e3,
            'mg_g'  => 1e-3, 'g_mg'  => 1e3,
            'mg_kg' => 1e-6, 'kg_mg' => 1e6,
            'ml_l'  => 1e-3, 'l_ml'  => 1e3,
        ];
        return $q * ($map[$d.'_'.$h] ?? 1.0);
    }

    public function eliminar(?string $id = null): void
    {
        $this->model->update((int)$id, ['activo' => 0]);
        $this->flash('success', 'Platillo desactivado.');
        $this->redirect('rest-menu/index');
    }

    public function toggleDisponible(?string $id = null): void
    {
        $platillo = $this->model->find((int)$id);
        if ($platillo) {
            $this->model->update((int)$id, ['disponible' => $platillo['disponible'] ? 0 : 1]);
        }
        $this->redirect('rest-menu/index');
    }

    // ── Categorías ────────────────────────────────────────────────

    public function guardarCategoria(?string $p = null): void
    {
        if (!$this->isPost()) $this->redirect('rest-menu/index');
        $id = (int)$this->post('id');
        $data = [
            'nombre'      => trim($this->post('nombre', '')),
            'descripcion' => $this->post('descripcion'),
            'orden'       => (int)$this->post('orden', 0),
        ];
        if ($id) {
            $this->model->updateCategoria($id, $data);
        } else {
            $this->model->insertCategoria(array_merge($data, ['restaurante_id' => $this->restauranteId()]));
        }
        $this->flash('success', 'Categoría guardada.');
        $this->redirect('rest-menu/index');
    }

    /**
     * Procesa upload de imagen de platillo.
     * Returns: ruta nueva (string), '' si se solicitó quitar imagen, null si no hay cambio.
     */
    private function procesarImagenPlatillo(int $restauranteId, int $platilloId): ?string
    {
        if ($this->post('quitar_imagen') == '1') {
            return '';
        }
        if (empty($_FILES['imagen']) || ($_FILES['imagen']['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
            return null;
        }
        if ($_FILES['imagen']['error'] !== UPLOAD_ERR_OK) {
            return null;
        }
        if ($_FILES['imagen']['size'] > 3 * 1024 * 1024) {
            return null;
        }
        $allowed = ['jpg' => 'image/jpeg', 'jpeg' => 'image/jpeg', 'png' => 'image/png', 'webp' => 'image/webp'];
        $ext = strtolower(pathinfo($_FILES['imagen']['name'], PATHINFO_EXTENSION));
        if (!isset($allowed[$ext])) return null;

        $filename = 'platillo_' . $restauranteId . '_' . ($platilloId ?: 'new') . '_' . time() . '.' . $ext;
        $dest     = ROOT_PATH . '/public/uploads/platillos/' . $filename;
        @mkdir(dirname($dest), 0755, true);
        if (move_uploaded_file($_FILES['imagen']['tmp_name'], $dest)) {
            return 'public/uploads/platillos/' . $filename;
        }
        return null;
    }
}
