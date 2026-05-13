<?php
require_once ROOT_PATH . '/app/controllers/BaseController.php';

class RestConfigController extends BaseController
{
    private RestauranteModel $model;

    public function __construct()
    {
        parent::__construct();
        $this->requireRestaurante();
        $this->model = new RestauranteModel();
    }

    public function index(?string $p = null): void
    {
        $restauranteId = $this->restauranteId();
        $restaurante   = $this->model->find($restauranteId);
        $flash         = $this->getFlash();
        $pageTitle     = 'Configuración del Restaurante';
        $activeMenu    = 'rest_config';
        $this->render('restaurante/config/index', compact('restaurante','flash','pageTitle','activeMenu'));
    }

    public function guardar(?string $p = null): void
    {
        if (!$this->isPost()) $this->redirect('rest-config/index');
        $restauranteId = $this->restauranteId();

        $data = [
            'nombre'          => trim($this->post('nombre', '')),
            'descripcion'     => $this->post('descripcion'),
            'telefono'        => $this->post('telefono'),
            'direccion'       => $this->post('direccion'),
            'color_primario'  => $this->post('color_primario', '#C8102E'),
            'color_secundario'=> $this->post('color_secundario', '#1f2937'),
            'horario_apertura'=> $this->post('horario_apertura') ?: null,
            'horario_cierre'  => $this->post('horario_cierre') ?: null,
        ];

        // Logo upload
        if (!empty($_FILES['logo']['tmp_name'])) {
            $ext      = strtolower(pathinfo($_FILES['logo']['name'], PATHINFO_EXTENSION));
            $allowed  = ['jpg','jpeg','png','webp','svg'];
            if (in_array($ext, $allowed)) {
                $filename = 'rest_logo_' . $restauranteId . '_' . time() . '.' . $ext;
                $dest     = ROOT_PATH . '/public/uploads/restaurantes/' . $filename;
                @mkdir(dirname($dest), 0755, true);
                if (move_uploaded_file($_FILES['logo']['tmp_name'], $dest)) {
                    $data['logo'] = 'public/uploads/restaurantes/' . $filename;
                }
            }
        }

        $this->model->update($restauranteId, $data);
        $this->flash('success', 'Configuración guardada.');
        $this->redirect('rest-config/index');
    }
}
