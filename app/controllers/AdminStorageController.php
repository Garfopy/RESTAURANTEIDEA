<?php
require_once ROOT_PATH . '/app/controllers/BaseController.php';

class AdminStorageController extends BaseController
{
    const RETENTION_DAYS = 60;
    const MANAGED_DIRS = [
        'entregas' => 'Fotos de entrega',
        'firmas'   => 'Firmas de repartidor',
    ];

    public function __construct()
    {
        parent::__construct();
        $this->requireAdmin();
    }

    public function index(?string $p = null): void
    {
        $dirs = [];
        $totalSize  = 0;
        $totalOld   = 0;

        foreach (self::MANAGED_DIRS as $slug => $label) {
            $info = $this->scanDir(UPLOAD_PATH . $slug, self::RETENTION_DAYS);
            $info['slug']  = $slug;
            $info['label'] = $label;
            $dirs[$slug]   = $info;
            $totalSize    += $info['total_size'];
            $totalOld     += $info['old_count'];
        }

        $db = Database::getInstance();
        $historial = $db->query(
            "SELECT al.accion, al.descripcion, al.created_at, u.nombre
               FROM action_logs al
          LEFT JOIN usuarios u ON u.id = al.usuario_id
              WHERE al.modulo = 'almacenamiento'
           ORDER BY al.created_at DESC LIMIT 15"
        )->fetchAll();

        $flash      = $this->getFlash();
        $pageTitle  = 'Gestión de almacenamiento';
        $activeMenu = 'almacenamiento';

        ob_start();
        require ROOT_PATH . '/app/views/panel/storage/index.php';
        $content = ob_get_clean();
        require ROOT_PATH . '/app/views/panel/layouts/main.php';
    }

    public function preview(?string $p = null): void
    {
        if (!$this->isPost()) { $this->json(['error' => 'method'], 405); }

        $dir   = (string)$this->post('directorio', '');
        $desde = (string)$this->post('fecha_desde', '');
        $hasta = (string)$this->post('fecha_hasta', date('Y-m-d'));

        if (!array_key_exists($dir, self::MANAGED_DIRS) && $dir !== 'ambos') {
            $this->json(['error' => 'directorio inválido'], 400);
        }
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $desde)) {
            $this->json(['error' => 'fecha inválida'], 400);
        }

        $slugs = $dir === 'ambos' ? array_keys(self::MANAGED_DIRS) : [$dir];
        $count = 0;
        $size  = 0;
        $oldest = PHP_INT_MAX;
        $newest = 0;

        foreach ($slugs as $slug) {
            $files = $this->getFilesInRange(UPLOAD_PATH . $slug, $desde, $hasta);
            foreach ($files as $f) {
                $count++;
                $size += $f['size'];
                if ($f['mtime'] < $oldest) $oldest = $f['mtime'];
                if ($f['mtime'] > $newest) $newest = $f['mtime'];
            }
        }

        $this->json([
            'count'       => $count,
            'size_bytes'  => $size,
            'size_label'  => $this->formatSize($size),
            'oldest'      => $oldest !== PHP_INT_MAX ? date('d/m/Y', $oldest) : null,
            'newest'      => $newest > 0             ? date('d/m/Y', $newest) : null,
        ]);
    }

    public function exportarZip(?string $p = null): void
    {
        if (!$this->isPost()) { $this->redirect('admin-storage/index'); }

        $dir   = (string)$this->post('directorio', '');
        $desde = (string)$this->post('fecha_desde', '');
        $hasta = (string)$this->post('fecha_hasta', date('Y-m-d'));

        if (!array_key_exists($dir, self::MANAGED_DIRS) && $dir !== 'ambos') {
            $this->flash('error', 'Directorio inválido.');
            $this->redirect('admin-storage/index');
        }
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $desde) || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $hasta)) {
            $this->flash('error', 'Fechas inválidas.');
            $this->redirect('admin-storage/index');
        }
        if ($desde > $hasta) [$desde, $hasta] = [$hasta, $desde];

        $slugs = $dir === 'ambos' ? array_keys(self::MANAGED_DIRS) : [$dir];
        $allFiles = [];
        foreach ($slugs as $slug) {
            foreach ($this->getFilesInRange(UPLOAD_PATH . $slug, $desde, $hasta) as $f) {
                $f['dir'] = $slug;
                $allFiles[] = $f;
            }
        }

        if (empty($allFiles)) {
            $this->flash('error', 'No se encontraron archivos en ese rango.');
            $this->redirect('admin-storage/index');
        }

        $zipPath = sys_get_temp_dir() . '/carnihub_storage_' . date('Ymd_His') . '.zip';
        $zip = new ZipArchive();
        if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            $this->flash('error', 'No se pudo crear el archivo ZIP.');
            $this->redirect('admin-storage/index');
        }

        foreach ($allFiles as $f) {
            $zip->addFile($f['path'], $f['dir'] . '/' . $f['name']);
        }

        $resumenHtml = $this->buildResumenHtml($allFiles, $desde, $hasta);
        $zip->addFromString('_resumen.html', $resumenHtml);
        $zip->close();

        $this->log('exportar_zip', 'almacenamiento',
            "ZIP exportado: dirs=$dir, desde=$desde, hasta=$hasta, archivos=" . count($allFiles));

        $filename = 'carnihub_storage_' . $desde . '_' . $hasta . '.zip';
        header('Content-Type: application/zip');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Content-Length: ' . filesize($zipPath));
        header('Pragma: no-cache');
        readfile($zipPath);
        unlink($zipPath);
        exit;
    }

    public function eliminar(?string $p = null): void
    {
        if (!$this->isPost()) { $this->redirect('admin-storage/index'); }

        $confirmacion = (string)$this->post('confirmacion', '');
        if ($confirmacion !== 'ELIMINAR') {
            $this->flash('error', 'Confirmación incorrecta. Escribe ELIMINAR para continuar.');
            $this->redirect('admin-storage/index');
        }

        $dir   = (string)$this->post('directorio', '');
        $desde = (string)$this->post('fecha_desde', '');
        $hasta = (string)$this->post('fecha_hasta', date('Y-m-d'));

        if (!array_key_exists($dir, self::MANAGED_DIRS) && $dir !== 'ambos') {
            $this->flash('error', 'Directorio inválido.');
            $this->redirect('admin-storage/index');
        }
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $desde) || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $hasta)) {
            $this->flash('error', 'Fechas inválidas.');
            $this->redirect('admin-storage/index');
        }
        if ($desde > $hasta) [$desde, $hasta] = [$hasta, $desde];

        $slugs = $dir === 'ambos' ? array_keys(self::MANAGED_DIRS) : [$dir];
        $deleted = 0;
        $sizeFreed = 0;

        foreach ($slugs as $slug) {
            $files = $this->getFilesInRange(UPLOAD_PATH . $slug, $desde, $hasta);
            foreach ($files as $f) {
                $sizeFreed += $f['size'];
                if (@unlink($f['path'])) $deleted++;
            }
        }

        $this->log('eliminar_archivos', 'almacenamiento',
            "Eliminados: dirs=$dir, desde=$desde, hasta=$hasta, count=$deleted, liberado=" . $this->formatSize($sizeFreed));

        $this->flash('success', "$deleted archivo(s) eliminados — " . $this->formatSize($sizeFreed) . " liberados.");
        $this->redirect('admin-storage/index');
    }

    // ── Helpers privados ─────────────────────────────────────────────

    private function scanDir(string $path, int $retentionDays): array
    {
        $cutoff     = time() - $retentionDays * 86400;
        $totalSize  = 0;
        $count      = 0;
        $oldCount   = 0;
        $oldestTs   = PHP_INT_MAX;
        $newestTs   = 0;
        $oldest10   = [];

        if (!is_dir($path)) {
            return ['total_size'=>0,'count'=>0,'old_count'=>0,'oldest'=>null,'newest'=>null,'oldest10'=>[],'label_size'=>'0 B'];
        }

        foreach (glob($path . '/*') as $file) {
            if (!is_file($file)) continue;
            $mtime = filemtime($file);
            $size  = filesize($file);
            $count++;
            $totalSize += $size;
            if ($mtime < $cutoff) $oldCount++;
            if ($mtime < $oldestTs) $oldestTs = $mtime;
            if ($mtime > $newestTs) $newestTs = $mtime;
            $oldest10[] = ['name' => basename($file), 'size' => $size, 'mtime' => $mtime];
        }

        usort($oldest10, fn($a, $b) => $a['mtime'] - $b['mtime']);
        $oldest10 = array_slice($oldest10, 0, 10);

        return [
            'total_size' => $totalSize,
            'label_size' => $this->formatSize($totalSize),
            'count'      => $count,
            'old_count'  => $oldCount,
            'oldest'     => $oldestTs !== PHP_INT_MAX ? date('Y-m-d', $oldestTs) : null,
            'newest'     => $newestTs > 0             ? date('Y-m-d', $newestTs) : null,
            'oldest10'   => $oldest10,
        ];
    }

    private function getFilesInRange(string $path, string $desde, string $hasta): array
    {
        $files  = [];
        $tsFrom = strtotime($desde . ' 00:00:00');
        $tsTo   = strtotime($hasta . ' 23:59:59');

        if (!is_dir($path)) return $files;

        foreach (glob($path . '/*') as $file) {
            if (!is_file($file)) continue;
            $mtime = filemtime($file);
            if ($mtime >= $tsFrom && $mtime <= $tsTo) {
                $files[] = [
                    'path'  => $file,
                    'name'  => basename($file),
                    'size'  => filesize($file),
                    'mtime' => $mtime,
                ];
            }
        }
        return $files;
    }

    private function formatSize(int $bytes): string
    {
        if ($bytes >= 1073741824) return round($bytes / 1073741824, 1) . ' GB';
        if ($bytes >= 1048576)    return round($bytes / 1048576, 1)    . ' MB';
        if ($bytes >= 1024)       return round($bytes / 1024, 1)       . ' KB';
        return $bytes . ' B';
    }

    private function buildResumenHtml(array $files, string $desde, string $hasta): string
    {
        $now       = date('d/m/Y H:i');
        $totalSize = (int)array_sum(array_column($files, 'size'));
        $labelSize = $this->formatSize($totalSize);
        $fileCount = count($files);
        $rows = '';
        foreach ($files as $f) {
            $fecha    = date('d/m/Y H:i', $f['mtime']);
            $label    = $this->formatSize($f['size']);
            $fileRef  = htmlspecialchars($f['dir'] . '/' . $f['name']);
            $rows .= "<tr><td style=\"padding:6px 10px;border-bottom:1px solid #E5E7EB\">{$fileRef}</td>"
                   . "<td style=\"padding:6px 10px;border-bottom:1px solid #E5E7EB;text-align:right\">{$label}</td>"
                   . "<td style=\"padding:6px 10px;border-bottom:1px solid #E5E7EB\">{$fecha}</td></tr>";
        }
        return <<<HTML
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>Resumen de exportación — CarniHub</title>
<style>
  body{font-family:Arial,sans-serif;font-size:13px;color:#111827;padding:30px}
  h1{font-size:18px;margin-bottom:4px}
  .meta{color:#6B7280;font-size:12px;margin-bottom:20px}
  table{width:100%;border-collapse:collapse}
  thead tr{background:#F3F4F6}
  th{padding:8px 10px;text-align:left;font-size:12px;color:#374151;border-bottom:2px solid #E5E7EB}
  .footer{margin-top:20px;font-size:11px;color:#9CA3AF}
</style>
</head>
<body>
<h1>Resumen de exportación de archivos</h1>
<div class="meta">Período: {$desde} al {$hasta} &nbsp;|&nbsp; Generado: {$now} &nbsp;|&nbsp; Total: {$labelSize} ({$fileCount} archivos)</div>
<table>
<thead><tr><th>Archivo</th><th style="text-align:right">Tamaño</th><th>Fecha modificación</th></tr></thead>
<tbody>{$rows}</tbody>
</table>
<div class="footer">Este archivo forma parte de la exportación de almacenamiento de CarniHub. Puede imprimirse como PDF desde el navegador.</div>
</body>
</html>
HTML;
    }
}
