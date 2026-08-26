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
        $csrf       = $this->csrfToken();
        $pageTitle  = 'Menú';
        $activeMenu = 'rest_menu';
        $restModel = new RestauranteModel();
        $sucursales = in_array($this->rolActual(), ['admin_restaurante', 'superadmin'], true)
            ? $restModel->getByEmpresa((int)$this->empresaId())
            : $restModel->getByComprador((int)$this->usuarioId());
        $restaurante = $restModel->find((int)$restauranteId) ?: [];
        $menuPrincipal = !empty($restaurante['empresa_id'])
            ? $restModel->getMenuPrincipalPorEmpresa((int)$restaurante['empresa_id'])
            : null;
        try {
            $this->model->prepararSelectorUnificado($restauranteId);
        } catch (\Throwable $e) {
            if (!$flash) $flash = ['type' => 'error', 'message' => $e->getMessage()];
        }
        $this->render('restaurante/menu/index', compact('platillos','categorias','flash','csrf','pageTitle','activeMenu','sucursales','restaurante','menuPrincipal'));
    }

    public function importarPrincipal(?string $p = null): void
    {
        if (!$this->isPost()) $this->redirect('rest-menu/index');
        if (!$this->validarCsrf()) {
            $this->flash('error', 'La sesión venció. Vuelve a intentarlo.');
            $this->redirect('rest-menu/index');
        }
        $destinoId = (int)$this->restauranteId();
        $restModel = new RestauranteModel();
        $destino = $restModel->find($destinoId);
        if (!$destino || !$this->usuarioPuedeAdministrarRestaurante($destino)) {
            $this->flash('error', 'No tienes permiso para importar menu en esta sucursal.');
            $this->redirect('rest-menu/index');
        }

        $principal = $restModel->getMenuPrincipalPorEmpresa((int)$destino['empresa_id']);
        if (!$principal || (int)$principal['id'] === $destinoId) {
            $this->flash('error', 'Marca otra sucursal como principal antes de importar.');
            $this->redirect('rest-menu/index');
        }

        try {
            $stats = $this->model->importarMenuDesdePrincipal((int)$principal['id'], $destinoId);
            $limpieza = !empty($stats['platillos_incompletos_desactivados'])
                ? ' Se desactivaron ' . (int)$stats['platillos_incompletos_desactivados'] . ' registros incompletos del intento anterior.'
                : '';
            $this->flash(
                'success',
                'Menu importado desde ' . (string)$principal['nombre'] . ': '
                . (int)$stats['platillos_creados'] . ' platillos nuevos, '
                . (int)$stats['platillos_actualizados'] . ' actualizados.'
                . $limpieza
            );
        } catch (\Throwable $e) {
            $this->flash('error', 'No se pudo importar el menu principal: ' . $e->getMessage());
        }
        $this->redirect('rest-menu/index');
    }

    private function usuarioPuedeAdministrarRestaurante(array $restaurante): bool
    {
        $rol = $this->rolActual();
        if (in_array($rol, ['admin_restaurante', 'superadmin'], true)) {
            return (int)($restaurante['empresa_id'] ?? 0) === (int)$this->empresaId();
        }
        if ($rol === 'admin_local') {
            return (int)($restaurante['id'] ?? 0) === (int)$this->restauranteId();
        }
        return (int)($restaurante['comprador_id'] ?? 0) === (int)$this->usuarioId();
    }

    public function form(?string $id = null): void
    {
        $restauranteId = $this->restauranteId();
        $platillo   = $id ? $this->model->getPlatilloConReceta((int)$id) : null;
        if ($id && (!$platillo || (int)($platillo['restaurante_id'] ?? 0) !== $restauranteId)) {
            $this->flash('error', 'El platillo solicitado no pertenece a este restaurante.');
            $this->redirect('rest-menu/index');
        }
        $categorias = $this->model->getCategorias($restauranteId, true);
        $ingredientes = (new RestInventarioModel())->getByRestaurante($restauranteId, true);
        $flash      = $this->getFlash();
        $csrf       = $this->csrfToken();
        $pageTitle  = $platillo ? 'Editar Platillo' : 'Nuevo Platillo';
        $activeMenu = 'rest_menu';
        $this->render('restaurante/menu/form', compact('platillo','categorias','ingredientes','flash','csrf','pageTitle','activeMenu'));
    }

    public function guardar(?string $p = null): void
    {
        if (!$this->isPost()) $this->redirect('rest-menu/index');
        if (!$this->validarCsrf()) {
            $this->flash('error', 'La sesión del formulario venció. Vuelve a intentarlo.');
            $this->redirect('rest-menu/index');
        }
        $restauranteId = $this->restauranteId();

        $id = (int)$this->post('id');
        $existente = $id ? $this->model->find($id) : null;
        if ($id && (!$existente || (int)($existente['restaurante_id'] ?? 0) !== $restauranteId)) {
            $this->flash('error', 'No tienes permiso para modificar ese platillo.');
            $this->redirect('rest-menu/index');
        }

        $nombre = trim((string)$this->post('nombre', ''));
        $precio = (float)$this->post('precio', 0);
        if ($nombre === '' || mb_strlen($nombre) > 200 || $precio <= 0 || $precio > 99999999.99) {
            $this->flash('error', 'Captura un nombre y un precio mayor a cero.');
            $this->redirect($id ? 'rest-menu/form/' . $id : 'rest-menu/form');
        }

        $categoriaId = (int)$this->post('categoria_id', 0);
        $nuevaCategoria = trim((string)$this->post('nueva_categoria', ''));
        if ($categoriaId > 0) {
            $categoria = $this->model->findCategoria($categoriaId);
            if (!$categoria || (int)($categoria['restaurante_id'] ?? 0) !== $restauranteId) {
                $this->flash('error', 'La categoría seleccionada no es válida para este restaurante.');
                $this->redirect($id ? 'rest-menu/form/' . $id : 'rest-menu/form');
            }
        } elseif ($nuevaCategoria !== '') {
            if (mb_strlen($nuevaCategoria) > 100) {
                $this->flash('error', 'El nombre de la categoría no puede superar 100 caracteres.');
                $this->redirect($id ? 'rest-menu/form/' . $id : 'rest-menu/form');
            }
            $stmtCategoria = \Database::getInstance()->prepare(
                'SELECT id FROM rest_categorias_menu WHERE restaurante_id = ? AND nombre = ? LIMIT 1'
            );
            $stmtCategoria->execute([$restauranteId, $nuevaCategoria]);
            $categoriaId = (int)($stmtCategoria->fetchColumn() ?: 0);
            if ($categoriaId === 0) {
                $categoriaId = $this->model->insertCategoria([
                    'restaurante_id' => $restauranteId,
                    'nombre' => $nuevaCategoria,
                    'descripcion' => null,
                    'orden' => count($this->model->getCategorias($restauranteId)),
                ]);
            }
        }

        $ingredientesEnviados = $this->post('ingrediente_id', []);
        $ingredientesEnviados = is_array($ingredientesEnviados) ? array_filter($ingredientesEnviados) : [];
        $modoInventario = (string)$this->post('inventory_mode', '');
        if (!in_array($modoInventario, ['none', 'recipe', 'unit'], true)) {
            // Compatibilidad con formularios anteriores: inferir el modo por sus campos.
            $modoInventario = (int)$this->post('ingrediente_directo_id', 0) > 0
                ? 'unit'
                : ($ingredientesEnviados ? 'recipe' : 'none');
        }

        $ingredienteDirectoId = $modoInventario === 'unit'
            ? (int)$this->post('ingrediente_directo_id', 0)
            : 0;
        if ($modoInventario === 'unit' && $ingredienteDirectoId <= 0) {
            $this->flash('error', 'Selecciona el producto de inventario que se descontará por unidad.');
            $this->redirect($id ? 'rest-menu/form/' . $id : 'rest-menu/form');
        }
        if ($ingredienteDirectoId > 0) {
            $stmtDirecto = \Database::getInstance()->prepare(
                'SELECT id FROM rest_ingredientes WHERE id = ? AND restaurante_id = ? AND activo = 1 LIMIT 1'
            );
            $stmtDirecto->execute([$ingredienteDirectoId, $restauranteId]);
            if (!$stmtDirecto->fetchColumn()) {
                $this->flash('error', 'El ingrediente de inventario seleccionado no es válido.');
                $this->redirect($id ? 'rest-menu/form/' . $id : 'rest-menu/form');
            }
        }

        // Alérgenos: array of checkbox values → comma-separated string
        $alergenosArr = $this->post('alergenos', []);
        $alergenosPermitidos = ['Gluten', 'Lactosa', 'Mariscos', 'Frutos secos', 'Huevo', 'Soya', 'Cacahuate', 'Mostaza'];
        $alergenosStr = is_array($alergenosArr)
            ? implode(',', array_values(array_intersect($alergenosPermitidos, array_map('trim', $alergenosArr))))
            : '';

        $data = [
            'restaurante_id'         => $restauranteId,
            'categoria_id'           => $categoriaId ?: null,
            'nombre'                 => $nombre,
            'descripcion'            => trim((string)$this->post('descripcion', '')) ?: null,
            'precio'                 => $precio,
            'tiempo_preparacion_min' => min(127, max(1, (int)$this->post('tiempo_preparacion_min', 15))),
            'disponible'             => $this->post('disponible', 0) == '1' ? 1 : 0,
            'alergenos'              => $alergenosStr ?: null,
            'contiene'               => trim((string)$this->post('contiene', '')) ?: null,
            'ingrediente_directo_id' => $ingredienteDirectoId ?: null,
        ];

        // Imagen del platillo (subida)
        try {
            $imagenPath = $this->procesarImagenPlatillo($restauranteId, $id);
        } catch (\InvalidArgumentException $e) {
            $this->flash('error', $e->getMessage());
            $this->redirect($id ? 'rest-menu/form/' . $id : 'rest-menu/form');
        }
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
        $ingredientesIds  = $modoInventario === 'recipe' ? $this->post('ingrediente_id', []) : [];
        $cantidades       = $this->post('cantidad', []);
        $unidades         = $this->post('unidad', []);
        $ingredientesIds  = is_array($ingredientesIds) ? $ingredientesIds : [];
        $cantidades       = is_array($cantidades) ? $cantidades : [];
        $unidades         = is_array($unidades) ? $unidades : [];
        $tipoIngredienteStmt = \Database::getInstance()->prepare(
            "SELECT tipo, unidad_principal FROM rest_ingredientes WHERE id=? AND restaurante_id=? AND activo=1 LIMIT 1"
        );

        $ings = [];
        $ingredientesAgregados = [];
        $unidadesPermitidas = ['g', 'kg', 'mg', 'L', 'ml', 'mL', 'pza', 'caja', 'bolsa'];
        foreach ($ingredientesIds as $k => $ingId) {
            if (!$ingId) continue;
            $ingId = (int)$ingId;
            if (isset($ingredientesAgregados[$ingId])) continue;
            $tipoIngredienteStmt->execute([$ingId, $restauranteId]);
            $ingredienteValido = $tipoIngredienteStmt->fetch(\PDO::FETCH_ASSOC);
            $cantidad = (float)($cantidades[$k] ?? 0);
            if (!$ingredienteValido || $cantidad <= 0) continue;
            $ingredientesAgregados[$ingId] = true;
            $tipoIngrediente = (string)($ingredienteValido['tipo'] ?? 'materia_prima');
            $unidadPrincipal = trim((string)($ingredienteValido['unidad_principal'] ?? 'kg')) ?: 'kg';
            $unidad = trim((string)($unidades[$k] ?? $unidadPrincipal));
            if (!in_array($unidad, $unidadesPermitidas, true)
                && strcasecmp($unidad, $unidadPrincipal) !== 0) {
                $unidad = $unidadPrincipal;
            }
            $ings[] = [
                'ingrediente_id'  => $ingId,
                'cantidad'        => $cantidad,
                'unidad'          => mb_substr($unidad, 0, 20),
                'es_informativo'  => 0,
                'tipo_componente' => $tipoIngrediente === 'guarnicion' ? 'guarnicion' : 'materia_prima',
                'codigo_display'  => null,
                'precio_extra'    => 0.0,
            ];
        }

        $recetaExistente = $this->model->getReceta($platilloId);
        if ($ings || $recetaExistente) {
            $recetaId = $this->model->upsertReceta(
                $platilloId,
                min(127, max(1, (int)$this->post('porciones_base', 1))),
                trim((string)$this->post('receta_notas', '')) ?: null
            );
            $this->model->syncIngredientesReceta($recetaId, $ings);
        }

        // Nota: se quitó a propósito la sincronización de modificadores/exclusiones para
        // la app (personalización "sin X" / "extra Y") — es una función avanzada que no
        // hace falta para el flujo simple de menú + receta. La receta de arriba ya es lo
        // que descuenta inventario automático al vender, eso sí sigue funcionando igual.
        $mensaje = match ($modoInventario) {
            'unit' => 'Platillo guardado. Se descontará una unidad del producto elegido por cada venta.',
            'recipe' => $ings
                ? 'Platillo y receta guardados.'
                : 'Platillo guardado sin ingredientes válidos; puedes completar la receta después.',
            default => 'Platillo guardado sin descuento automático de inventario.',
        };
        $this->flash('success', $mensaje);
        $this->redirect('rest-menu/index');
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
        if (!$this->isPost() || !$this->validarCsrf()) {
            $this->flash('error', 'La acción no es válida o la sesión venció.');
            $this->redirect('rest-menu/index');
        }
        $platillo = $this->model->find((int)$id);
        if (!$platillo || (int)($platillo['restaurante_id'] ?? 0) !== (int)$this->restauranteId()) {
            $this->flash('error', 'No tienes permiso para desactivar ese platillo.');
            $this->redirect('rest-menu/index');
        }
        $this->model->update((int)$id, ['activo' => 0]);
        $this->flash('success', 'Platillo desactivado.');
        $this->redirect('rest-menu/index');
    }

    public function toggleDisponible(?string $id = null): void
    {
        if (!$this->isPost() || !$this->validarCsrf()) {
            $this->flash('error', 'La acción no es válida o la sesión venció.');
            $this->redirect('rest-menu/index');
        }
        $platillo = $this->model->find((int)$id);
        if ($platillo && (int)($platillo['restaurante_id'] ?? 0) === (int)$this->restauranteId()) {
            $this->model->update((int)$id, ['disponible' => $platillo['disponible'] ? 0 : 1]);
        } else {
            $this->flash('error', 'No tienes permiso para modificar ese platillo.');
        }
        $this->redirect('rest-menu/index');
    }

    // ── Categorías ────────────────────────────────────────────────

    public function guardarCategoria(?string $p = null): void
    {
        if (!$this->isPost()) $this->redirect('rest-menu/index');
        if (!$this->validarCsrf()) {
            $this->flash('error', 'La sesión venció. Vuelve a intentarlo.');
            $this->redirect('rest-menu/index');
        }
        $id = (int)$this->post('id');
        $nombre = trim((string)$this->post('nombre', ''));
        if ($nombre === '') {
            $this->flash('error', 'El nombre de la categoría es obligatorio.');
            $this->redirect('rest-menu/index');
        }
        if ($id) {
            $categoria = $this->model->findCategoria($id);
            if (!$categoria || (int)($categoria['restaurante_id'] ?? 0) !== (int)$this->restauranteId()) {
                $this->flash('error', 'No tienes permiso para modificar esa categoría.');
                $this->redirect('rest-menu/index');
            }
        }
        $data = [
            'nombre'      => $nombre,
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
            throw new \InvalidArgumentException('No se pudo recibir la imagen. Intenta con otro archivo.');
        }
        if ($_FILES['imagen']['size'] > 3 * 1024 * 1024) {
            throw new \InvalidArgumentException('La imagen debe pesar menos de 3 MB.');
        }
        $allowed = ['jpg' => 'image/jpeg', 'jpeg' => 'image/jpeg', 'png' => 'image/png', 'webp' => 'image/webp'];
        $ext = strtolower(pathinfo($_FILES['imagen']['name'], PATHINFO_EXTENSION));
        $imageInfo = @getimagesize($_FILES['imagen']['tmp_name']);
        $mime = is_array($imageInfo) ? (string)($imageInfo['mime'] ?? '') : '';
        if (!isset($allowed[$ext]) || $mime !== $allowed[$ext]) {
            throw new \InvalidArgumentException('La imagen debe ser un archivo JPG, PNG o WebP válido.');
        }

        $filename = 'platillo_' . $restauranteId . '_' . ($platilloId ?: 'new') . '_' . bin2hex(random_bytes(8)) . '.' . $ext;
        $dest     = ROOT_PATH . '/public/uploads/platillos/' . $filename;
        @mkdir(dirname($dest), 0755, true);
        if (move_uploaded_file($_FILES['imagen']['tmp_name'], $dest)) {
            return 'public/uploads/platillos/' . $filename;
        }
        throw new \InvalidArgumentException('No se pudo guardar la imagen. Revisa los permisos de la carpeta de cargas.');
    }
}
