<?php
require_once ROOT_PATH . '/app/controllers/BaseController.php';

/**
 * CatalogoController — Ver catálogo de productos con precios escalonados.
 * Accesible por: admin_empresa, supervisor, comprador.
 */
class CatalogoController extends BaseController
{
    private ProductoModel $model;

    public function __construct()
    {
        parent::__construct();
        $this->requireEmpresa();
        $this->model = new ProductoModel();
    }

    public function index(?string $p = null): void
    {
        $filtros = [
            'empresa_id'   => $this->empresaId(),
            'buscar'       => $this->get('buscar', ''),
            'categoria_id' => (int)$this->get('categoria_id', 0) ?: null,
        ];
        $page = max(1, (int)$this->get('page', 1));

        $resultado   = $this->model->listadoConPrecio($filtros, $page);
        $productos   = $resultado['data'];
        $paginacion  = $resultado;

        $db = Database::getInstance();
        $categorias = $db->query('SELECT * FROM categorias WHERE activo = 1 ORDER BY nombre')->fetchAll();

        $flash     = $this->getFlash();
        $pageTitle = 'Catálogo de productos';
        $activeMenu = 'catalogo';

        ob_start();
        require ROOT_PATH . '/app/views/empresa/catalogo/index.php';
        $content = ob_get_clean();
        require ROOT_PATH . '/app/views/empresa/layouts/main.php';
    }

    public function detalle(?string $id = null): void
    {
        $productoId = (int)$id;
        $producto = $this->model->conDetalle($productoId);

        if (!$producto || !$producto['activo']) {
            $this->redirect('catalogo/index');
        }

        $flash     = $this->getFlash();
        $pageTitle = htmlspecialchars($producto['nombre']);
        $activeMenu = 'catalogo';

        ob_start();
        require ROOT_PATH . '/app/views/empresa/catalogo/detalle.php';
        $content = ob_get_clean();
        require ROOT_PATH . '/app/views/empresa/layouts/main.php';
    }
}
