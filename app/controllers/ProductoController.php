<?php
require_once ROOT_PATH . '/app/controllers/BaseController.php';

class ProductoController extends BaseController
{
    private ProductoModel $model;

    public function __construct()
    {
        parent::__construct();
        $this->model = new ProductoModel();
    }

    public function index(?string $p = null): void
    {
        $this->requireAdmin();
        $filtros   = ['categoria_id' => $this->get('cat',''), 'busqueda' => $this->get('q','')];
        $page      = max(1,(int)$this->get('page',1));
        $productos  = $this->model->getAllAdmin($page, $filtros);
        $categorias = (new SucursalModel())->db ?? [];

        $catModel  = new class extends BaseModel { protected string $table='categorias';
            public function all(): array { return $this->query('SELECT * FROM categorias WHERE activo=1 ORDER BY nombre'); }
        };
        $categorias = $catModel->all();

        $flash     = $this->getFlash();
        $pageTitle = 'Productos';
        $ctrlSlug  = 'producto';
        $this->render('admin/productos/index', compact('productos','categorias','filtros','flash','pageTitle','ctrlSlug'));
    }

    public function catalogo(?string $p = null): void
    {
        // Vista catálogo para cliente
        $filtros   = ['categoria' => $this->get('cat','todos'), 'busqueda' => $this->get('q','')];
        $productos  = $this->model->getCatalogo($filtros['categoria'] !== 'todos' ? $filtros['categoria'] : null, $filtros['busqueda'] ?: null);
        $categorias = Database::getInstance()->query('SELECT * FROM categorias WHERE activo=1 ORDER BY nombre')->fetchAll();
        $pageTitle  = 'Catálogo';
        $ctrlSlug   = 'catalogo';
        $this->render('cliente/catalogo/index', compact('productos','categorias','filtros','pageTitle','ctrlSlug'));
    }

    public function detalle(?string $id = null): void
    {
        $producto = $this->model->getConPreciosEscalonados((int)$id);
        if (!$producto) { $this->redirect('producto/catalogo'); }
        $pageTitle = $producto['nombre'];
        $ctrlSlug  = 'catalogo';
        $this->render('cliente/catalogo/producto', compact('producto','pageTitle','ctrlSlug'));
    }

    public function crear(?string $p = null): void
    {
        $this->requireAdmin();
        $pageTitle  = 'Nuevo Producto';
        $ctrlSlug   = 'producto';
        $producto   = [];
        $categorias = Database::getInstance()->query('SELECT * FROM categorias WHERE activo=1')->fetchAll();
        $this->render('admin/productos/form', compact('producto','categorias','pageTitle','ctrlSlug'));
    }

    public function editar(?string $id = null): void
    {
        $this->requireAdmin();
        $producto = $this->model->find((int)$id);
        if (!$producto) { $this->redirect('producto/index'); }
        $categorias = Database::getInstance()->query('SELECT * FROM categorias WHERE activo=1')->fetchAll();
        $pageTitle  = 'Editar: ' . $producto['nombre'];
        $ctrlSlug   = 'producto';
        $this->render('admin/productos/form', compact('producto','categorias','pageTitle','ctrlSlug'));
    }

    public function guardar(?string $p = null): void
    {
        $this->requireAdmin();
        if (!$this->isPost()) { $this->redirect('producto/index'); }

        $id   = (int)$this->post('id', 0);
        $data = [
            'nombre'       => trim($this->post('nombre')),
            'categoria_id' => (int)$this->post('categoria_id'),
            'descripcion'  => $this->post('descripcion'),
            'presentacion' => $this->post('presentacion','kg'),
            'precio_base'  => (float)$this->post('precio_base'),
            'activo'       => (int)$this->post('activo', 1),
        ];

        // Handle image upload
        if (!empty($_FILES['imagen']['name'])) {
            $ext      = strtolower(pathinfo($_FILES['imagen']['name'], PATHINFO_EXTENSION));
            $allowed  = ['jpg','jpeg','png','webp'];
            if (in_array($ext, $allowed)) {
                $filename = 'prod_' . time() . '.' . $ext;
                $dest     = UPLOAD_PATH . 'productos/' . $filename;
                @mkdir(dirname($dest), 0755, true);
                if (move_uploaded_file($_FILES['imagen']['tmp_name'], $dest)) {
                    $data['imagen'] = $filename;
                }
            }
        }

        if ($id > 0) {
            $this->model->update($id, $data);
            $this->flash('success', 'Producto actualizado.');
            $this->redirect("producto/precios/$id");
        } else {
            $newId = $this->model->insert($data);
            $this->flash('success', 'Producto creado. Ahora configura los precios escalonados.');
            $this->redirect("producto/precios/$newId");
        }
    }

    public function precios(?string $id = null): void
    {
        $this->requireAdmin();
        $producto = $this->model->getConPreciosEscalonados((int)$id);
        if (!$producto) { $this->redirect('producto/index'); }
        $flash     = $this->getFlash();
        $pageTitle = 'Precios: ' . $producto['nombre'];
        $ctrlSlug  = 'producto';
        $this->render('admin/productos/precios', compact('producto','flash','pageTitle','ctrlSlug'));
    }

    public function guardarPrecio(?string $p = null): void
    {
        $this->requireAdmin();
        if (!$this->isPost()) { $this->json(['ok'=>false]); }

        $db        = Database::getInstance();
        $productoId = (int)$this->post('producto_id');
        $id        = (int)$this->post('id', 0);
        $data = [
            'producto_id'       => $productoId,
            'rango_min'         => (float)$this->post('rango_min'),
            'rango_max'         => $this->post('rango_max') !== '' ? (float)$this->post('rango_max') : null,
            'precio_por_unidad' => (float)$this->post('precio_por_unidad'),
            'activo'            => 1,
        ];

        if ($id > 0) {
            $sets = 'rango_min=?, rango_max=?, precio_por_unidad=?';
            $db->prepare("UPDATE precios_escalonados SET $sets WHERE id=?")
               ->execute([$data['rango_min'], $data['rango_max'], $data['precio_por_unidad'], $id]);
        } else {
            $db->prepare('INSERT INTO precios_escalonados (producto_id,rango_min,rango_max,precio_por_unidad,activo) VALUES (?,?,?,?,1)')
               ->execute([$productoId, $data['rango_min'], $data['rango_max'], $data['precio_por_unidad']]);
        }

        $this->json(['ok' => true]);
    }

    public function eliminarPrecio(?string $id = null): void
    {
        $this->requireAdmin();
        Database::getInstance()->prepare('DELETE FROM precios_escalonados WHERE id=?')->execute([(int)$id]);
        $this->json(['ok' => true]);
    }
}
