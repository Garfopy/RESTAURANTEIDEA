<?php
require_once ROOT_PATH . '/app/controllers/BaseController.php';

class RestConfigController extends BaseController
{
    private RestauranteModel $model;

    /**
     * Normaliza texto de formularios para evitar mojibake (ej. QuerÃ©taro)
     * y guardar siempre UTF-8 válido.
     */
    private function normalizeUtf8Input(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        if (!mb_check_encoding($value, 'UTF-8')) {
            $value = mb_convert_encoding($value, 'UTF-8', 'Windows-1252,ISO-8859-1,UTF-8');
        }

        // Repara texto ya mojibakeado por doble conversión de encoding.
        if (preg_match('/Ã.|Â.|â./u', $value)) {
            $fixed = @iconv('UTF-8', 'ISO-8859-1//IGNORE', $value);
            if ($fixed !== false && mb_check_encoding($fixed, 'UTF-8')) {
                $value = $fixed;
            }
        }

        return $value;
    }

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

        // Google Maps API key from global_settings (superadmin-configured)
        $mapsApiKey = '';
        try {
            $db   = \Database::getInstance();
            $stmt = $db->prepare("SELECT valor FROM global_settings WHERE clave = 'google_maps_key' LIMIT 1");
            $stmt->execute();
            $row = $stmt->fetch(\PDO::FETCH_ASSOC);
            $mapsApiKey = $row['valor'] ?? '';
        } catch (\Exception $e) { /* table may not exist */ }

        $pageTitle  = 'Configuración del Restaurante';
        $activeMenu = 'rest_config';
        $this->render('restaurante/config/index',
            compact('restaurante','flash','pageTitle','activeMenu','mapsApiKey'));
    }

    public function guardar(?string $p = null): void
    {
        if (!$this->isPost()) $this->redirect('rest-config/index');
        $restauranteId = $this->restauranteId();

        $nombre      = trim((string)$this->normalizeUtf8Input($this->post('nombre', '')));
        $descripcion = $this->normalizeUtf8Input($this->post('descripcion'));
        $telefono    = $this->normalizeUtf8Input($this->post('telefono'));
        $direccion   = $this->normalizeUtf8Input($this->post('direccion'));

        $base = [
            'nombre'          => $nombre,
            'descripcion'     => $descripcion,
            'telefono'        => $telefono,
            'direccion'       => $direccion,
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

        // Banner upload
        if (!empty($_FILES['imagen_banner']['tmp_name'])) {
            $ext     = strtolower(pathinfo($_FILES['imagen_banner']['name'], PATHINFO_EXTENSION));
            $allowed = ['jpg','jpeg','png','webp'];
            if (in_array($ext, $allowed)) {
                $filename = 'rest_banner_' . $restauranteId . '_' . time() . '.' . $ext;
                $dest     = ROOT_PATH . '/public/uploads/restaurantes/' . $filename;
                @mkdir(dirname($dest), 0755, true);
                if (move_uploaded_file($_FILES['imagen_banner']['tmp_name'], $dest)) {
                    $base['imagen_banner'] = 'public/uploads/restaurantes/' . $filename;
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
