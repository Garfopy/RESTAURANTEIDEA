<?php
require_once ROOT_PATH . '/app/controllers/BaseController.php';

class EmpresaConfigController extends BaseController
{
    private PDO $db;

    public function __construct()
    {
        parent::__construct();
        $this->requireAdminEmpresa();
        $this->db = Database::getInstance();
    }

    public function facturacion(?string $p = null): void
    {
        $empresaId = $this->empresaId();
        $usuario   = $_SESSION['usuario'] ?? [];

        if ($this->isPost()) {
            $campos = [
                'facturalo_apikey', 'facturalo_ambiente', 'facturalo_rfc',
                'facturalo_nombre', 'facturalo_regimen', 'facturalo_cp',
                'facturalo_plantilla',
            ];

            $set    = [];
            $values = [];
            foreach ($campos as $c) {
                $set[]    = "`{$c}` = ?";
                $values[] = trim($this->post($c, ''));
            }

            // Always save password (empty = clear it)
            $csdPass = trim($this->post('facturalo_csd_pass', ''));
            $set[]    = '`facturalo_csd_pass` = ?';
            $values[] = $csdPass;

            // Handle CSD file uploads (.cer and .key → PEM)
            $errors = [];

            $cerPem = $this->convertirCer($_FILES['cer_file'] ?? [], $errors);
            $keyPem = $this->convertirKey($_FILES['key_file'] ?? [], $csdPass, $errors);

            if ($cerPem !== null) {
                $set[]    = '`facturalo_cer_pem` = ?';
                $values[] = $cerPem;
            }
            if ($keyPem !== null) {
                $set[]    = '`facturalo_key_pem` = ?';
                $values[] = $keyPem;
            }

            // Fallback: manual PEM pasted in textarea (only if no file uploaded)
            if ($cerPem === null && trim($this->post('facturalo_cer_pem', '')) !== '') {
                $set[]    = '`facturalo_cer_pem` = ?';
                $values[] = trim($this->post('facturalo_cer_pem', ''));
            }
            if ($keyPem === null && trim($this->post('facturalo_key_pem', '')) !== '') {
                $set[]    = '`facturalo_key_pem` = ?';
                $values[] = trim($this->post('facturalo_key_pem', ''));
            }

            if (!empty($errors)) {
                $this->flash('error', implode(' | ', $errors));
                $this->redirect('empresa-config/facturacion');
            }

            $values[] = $empresaId;
            $this->db->prepare(
                'UPDATE empresas SET ' . implode(', ', $set) . ' WHERE id = ?'
            )->execute($values);

            $this->log('Guardar config facturación', 'configuracion');
            $this->flash('success', 'Configuración de facturación guardada correctamente.');
            $this->redirect('empresa-config/facturacion');
        }

        $stmt = $this->db->prepare(
            'SELECT facturalo_apikey, facturalo_ambiente, facturalo_rfc, facturalo_nombre,
                    facturalo_regimen, facturalo_cp, facturalo_plantilla,
                    facturalo_key_pem, facturalo_cer_pem, facturalo_csd_pass
               FROM empresas WHERE id = ? LIMIT 1'
        );
        $stmt->execute([$empresaId]);
        $cfg = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];

        // Determine completion state for progress indicator
        $pasos = [
            'apikey'  => !empty($cfg['facturalo_apikey']),
            'datos'   => !empty($cfg['facturalo_rfc']) && !empty($cfg['facturalo_nombre']) && !empty($cfg['facturalo_cp']),
            'csd'     => !empty($cfg['facturalo_key_pem']) && !empty($cfg['facturalo_cer_pem']),
        ];

        $flash      = $this->getFlash();
        $pageTitle  = 'Configuración de facturación';
        $activeMenu = 'config_facturacion';

        ob_start();
        require ROOT_PATH . '/app/views/empresa/config/facturacion.php';
        $content = ob_get_clean();
        require ROOT_PATH . '/app/views/empresa/layouts/main.php';
    }

    // Convert uploaded .cer (DER) to PEM
    private function convertirCer(array $file, array &$errors): ?string
    {
        if (empty($file['name']) || $file['error'] === UPLOAD_ERR_NO_FILE) {
            return null;
        }
        if ($file['error'] !== UPLOAD_ERR_OK) {
            $errors[] = 'Error al subir el archivo .cer (código ' . $file['error'] . ')';
            return null;
        }

        $derData = file_get_contents($file['tmp_name']);
        $pem     = "-----BEGIN CERTIFICATE-----\n"
                 . chunk_split(base64_encode($derData), 64, "\n")
                 . "-----END CERTIFICATE-----\n";

        $cert = @openssl_x509_read($pem);
        if (!$cert) {
            $errors[] = 'El archivo .cer no es válido o está dañado.';
            return null;
        }
        openssl_x509_export($cert, $cerPem);
        return $cerPem;
    }

    // Convert uploaded .key (DER PKCS#8) to unencrypted PEM
    private function convertirKey(array $file, string $pass, array &$errors): ?string
    {
        if (empty($file['name']) || $file['error'] === UPLOAD_ERR_NO_FILE) {
            return null;
        }
        if ($file['error'] !== UPLOAD_ERR_OK) {
            $errors[] = 'Error al subir el archivo .key (código ' . $file['error'] . ')';
            return null;
        }

        $derData = file_get_contents($file['tmp_name']);

        // Try as encrypted PKCS#8 (most SAT keys)
        $pem = "-----BEGIN ENCRYPTED PRIVATE KEY-----\n"
             . chunk_split(base64_encode($derData), 64, "\n")
             . "-----END ENCRYPTED PRIVATE KEY-----\n";

        $key = @openssl_pkey_get_private($pem, $pass);

        // Fallback: try as unencrypted PKCS#8
        if (!$key) {
            $pem = "-----BEGIN PRIVATE KEY-----\n"
                 . chunk_split(base64_encode($derData), 64, "\n")
                 . "-----END PRIVATE KEY-----\n";
            $key = @openssl_pkey_get_private($pem);
        }

        if (!$key) {
            $errors[] = 'No se pudo leer el archivo .key. Verifica que la contraseña sea correcta.';
            return null;
        }

        openssl_pkey_export($key, $keyPem);
        return $keyPem;
    }
}
