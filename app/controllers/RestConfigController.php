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

        $base = [
            'nombre'          => trim($this->post('nombre', '')),
            'descripcion'     => $this->post('descripcion'),
            'telefono'        => $this->post('telefono'),
            'direccion'       => $this->post('direccion'),
            'color_primario'  => $this->post('color_primario', '#C8102E'),
            'color_secundario'=> $this->post('color_secundario', '#1f2937'),
            'horario_apertura'=> $this->post('horario_apertura') ?: null,
            'horario_cierre'  => $this->post('horario_cierre') ?: null,
        ];

        $modos = [
            'mesas_habilitadas'       => $this->post('mesas_habilitadas')       ? 1 : 0,
            'reservas_habilitadas'    => $this->post('reservas_habilitadas')    ? 1 : 0,
            'portero_habilitado'      => $this->post('portero_habilitado')      ? 1 : 0,
            'requiere_login_comensal' => $this->post('requiere_login_comensal') ? 1 : 0,
            'propinas_sugeridas'      => trim($this->post('propinas_sugeridas', '0,10,15,20')) ?: '0,10,15,20',
            'horarios_json'           => $this->post('horarios_json') ?: null,
        ];

        // Logo upload
        if (!empty($_FILES['logo']['tmp_name'])) {
            $ext     = strtolower(pathinfo($_FILES['logo']['name'], PATHINFO_EXTENSION));
            $allowed = ['jpg','jpeg','png','webp','svg'];
            if (in_array($ext, $allowed)) {
                $filename = 'rest_logo_' . $restauranteId . '_' . time() . '.' . $ext;
                $dest     = ROOT_PATH . '/public/uploads/restaurantes/' . $filename;
                @mkdir(dirname($dest), 0755, true);
                if (move_uploaded_file($_FILES['logo']['tmp_name'], $dest)) {
                    $base['logo'] = 'public/uploads/restaurantes/' . $filename;
                }
            }
        }

        $this->model->update($restauranteId, $base);

        // Migration-026 fields (may not exist on older installs — skip silently)
        try {
            $this->model->update($restauranteId, $modos);
        } catch (PDOException $e) {
            // Columns from migration 026 not yet applied — ignore
        }
        $this->flash('success', 'Configuración guardada.');
        $this->redirect('rest-config/index');
    }

    public function qr(?string $p = null): void
    {
        $restauranteId = $this->restauranteId();
        $restaurante   = $this->model->find($restauranteId);
        $pageTitle     = 'QR del local';
        $activeMenu    = 'rest_qr';
        $this->render('restaurante/config/qr', compact('restaurante','pageTitle','activeMenu'));
    }
}
