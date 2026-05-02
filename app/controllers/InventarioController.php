<?php
require_once ROOT_PATH . '/app/controllers/BaseController.php';

class InventarioController extends BaseController
{
    private InventarioModel $model;

    public function __construct()
    {
        parent::__construct();
        $this->requireAdmin();
        $this->model = new InventarioModel();
    }

    public function index(?string $p = null): void
    {
        $busqueda  = $this->get('q','');
        $page      = max(1,(int)$this->get('page',1));
        $inventario = $this->model->getAll($page, $busqueda ?: null);
        $resumen   = $this->model->getResumen();
        $alertas   = $this->model->getAlertas();
        $flash     = $this->getFlash();
        $pageTitle = 'Inventario';
        $ctrlSlug  = 'inventario';
        $this->render('admin/inventario/index', compact('inventario','resumen','alertas','busqueda','flash','pageTitle','ctrlSlug'));
    }

    public function actualizar(?string $id = null): void
    {
        if (!$this->isPost()) { $this->json(['ok'=>false]); }
        $campo = $this->post('campo', 'disponible');
        $valor = (float)$this->post('valor', 0);
        $campos = ['disponible','en_transito','reservado','minimo_alerta'];
        if (!in_array($campo, $campos)) { $this->json(['ok'=>false]); }

        Database::getInstance()
            ->prepare("UPDATE inventario SET $campo = ? WHERE producto_id = ?")
            ->execute([$valor, (int)$id]);

        $this->log("Inventario actualizado: producto #$id → $campo = $valor", 'inventario');
        $this->json(['ok'=>true]);
    }
}
