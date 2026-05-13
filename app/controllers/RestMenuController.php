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
        $platillos  = $this->model->getByRestaurante($restauranteId);
        $categorias = $this->model->getCategorias($restauranteId);
        $flash      = $this->getFlash();
        $pageTitle  = 'Menú';
        $activeMenu = 'rest_menu';
        $this->render('restaurante/menu/index', compact('platillos','categorias','flash','pageTitle','activeMenu'));
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
        $data = [
            'restaurante_id'         => $restauranteId,
            'categoria_id'           => $this->post('categoria_id') ?: null,
            'nombre'                 => trim($this->post('nombre', '')),
            'descripcion'            => $this->post('descripcion'),
            'precio'                 => (float)$this->post('precio', 0),
            'tiempo_preparacion_min' => (int)$this->post('tiempo_preparacion_min', 15),
            'disponible'             => $this->post('disponible', 1),
        ];

        if ($id) {
            $this->model->update($id, array_diff_key($data, ['restaurante_id' => '']));
            $platilloId = $id;
        } else {
            $platilloId = $this->model->insert($data);
        }

        // Guardar receta si vienen ingredientes
        $ingredientesIds  = $this->post('ingrediente_id', []);
        $cantidades       = $this->post('cantidad', []);
        $unidades         = $this->post('unidad', []);

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
                    'ingrediente_id' => (int)$ingId,
                    'cantidad'       => (float)($cantidades[$k] ?? 0),
                    'unidad'         => $unidades[$k] ?? 'kg',
                ];
            }
            $this->model->syncIngredientesReceta($recetaId, $ings);
        }

        $this->flash('success', 'Platillo guardado.');
        $this->redirect('rest-menu/index');
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
        $this->json(['ok' => true]);
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
}
