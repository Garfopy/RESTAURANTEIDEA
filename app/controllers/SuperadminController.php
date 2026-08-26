<?php
require_once ROOT_PATH . '/app/controllers/BaseController.php';

class SuperadminController extends BaseController
{
    public function __construct()
    {
        parent::__construct();
        $this->requireSuperAdmin();
    }

    public function dashboard(?string $p = null): void
    {
        $db = Database::getInstance();

        $negocios = $db->query(
            "SELECT COUNT(*) AS total, SUM(activo) AS activos FROM rest_restaurantes"
        )->fetch(PDO::FETCH_ASSOC) ?: ['total' => 0, 'activos' => 0];

        $hoy = date('Y-m-d');
        $inicioMes = date('Y-m-01');

        $ventasHoy = $db->prepare(
            "SELECT COALESCE(SUM(total),0) AS v, COUNT(*) AS c FROM rest_pedidos
             WHERE estado <> 'cancelado' AND DATE(created_at) = ?"
        );
        $ventasHoy->execute([$hoy]);
        $kpisHoy = $ventasHoy->fetch(PDO::FETCH_ASSOC);

        $ventasMes = $db->prepare(
            "SELECT COALESCE(SUM(total),0) AS v, COUNT(*) AS c FROM rest_pedidos
             WHERE estado <> 'cancelado' AND DATE(created_at) BETWEEN ? AND ?"
        );
        $ventasMes->execute([$inicioMes, $hoy]);
        $kpisMes = $ventasMes->fetch(PDO::FETCH_ASSOC);

        $usuariosApp = (int)($db->query("SELECT COUNT(*) AS c FROM mobile_usuarios WHERE activo=1")->fetch(PDO::FETCH_ASSOC)['c'] ?? 0);

        $ranking = $db->query(
            "SELECT r.id, r.nombre, COALESCE(SUM(p.total),0) AS ventas, COUNT(p.id) AS pedidos
             FROM rest_restaurantes r
             LEFT JOIN rest_pedidos p ON p.restaurante_id = r.id AND p.estado <> 'cancelado'
                AND DATE(p.created_at) BETWEEN '{$inicioMes}' AND '{$hoy}'
             GROUP BY r.id, r.nombre
             ORDER BY ventas DESC
             LIMIT 10"
        )->fetchAll(PDO::FETCH_ASSOC);

        $pendientes = $db->query(
            "SELECT COUNT(*) AS c FROM rest_restaurantes WHERE activo = 0"
        )->fetch(PDO::FETCH_ASSOC)['c'] ?? 0;

        $flash = $this->getFlash();
        $pageTitle = 'Panel Superadmin';
        $this->render('superadmin/dashboard', compact(
            'negocios', 'kpisHoy', 'kpisMes', 'usuariosApp', 'ranking', 'pendientes', 'flash', 'pageTitle'
        ));
    }

    public function negocios(?string $p = null): void
    {
        $db = Database::getInstance();
        $q = trim((string)$this->get('q', ''));
        $estado = (string)$this->get('estado', 'todos');

        $where = [];
        $params = [];
        if ($q !== '') {
            $where[] = '(r.nombre LIKE ? OR e.razon_social LIKE ?)';
            $params[] = "%$q%";
            $params[] = "%$q%";
        }
        if ($estado === 'activos') $where[] = 'r.activo = 1';
        if ($estado === 'suspendidos') $where[] = 'r.activo = 0';
        $whereSql = $where ? ('WHERE ' . implode(' AND ', $where)) : '';

        $stmt = $db->prepare(
            "SELECT r.id, r.nombre, r.slug, r.activo, r.telefono, r.created_at,
                    e.razon_social AS empresa_nombre,
                    (SELECT COUNT(*) FROM rest_platillos WHERE restaurante_id = r.id AND activo=1) AS num_platillos,
                    (SELECT COUNT(*) FROM usuarios WHERE restaurante_id = r.id AND rol_id = 2) AS num_admins,
                    (SELECT COALESCE(SUM(total),0) FROM rest_pedidos WHERE restaurante_id = r.id AND estado <> 'cancelado') AS ventas_totales
             FROM rest_restaurantes r
             LEFT JOIN empresas e ON e.id = r.empresa_id
             {$whereSql}
             ORDER BY r.created_at DESC"
        );
        $stmt->execute($params);
        $negocios = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $flash = $this->getFlash();
        $csrf = $this->csrfToken();
        $pageTitle = 'Negocios';
        $this->render('superadmin/negocios', compact('negocios', 'q', 'estado', 'flash', 'csrf', 'pageTitle'));
    }

    public function nuevoNegocio(?string $p = null): void
    {
        $flash = $this->getFlash();
        $csrf = $this->csrfToken();
        $pageTitle = 'Nuevo Negocio';
        $this->render('superadmin/negocio_form', compact('flash', 'csrf', 'pageTitle'));
    }

    public function crearNegocio(?string $p = null): void
    {
        $this->requirePostWithCsrf('superadmin/nuevoNegocio');
        $db = Database::getInstance();

        $razonSocial = trim($this->post('razon_social', ''));
        $nombreNegocio = trim($this->post('nombre', ''));
        $adminNombre = trim($this->post('admin_nombre', ''));
        $adminEmail = strtolower(trim($this->post('admin_email', '')));
        $adminPassword = (string)$this->post('admin_password', '');

        if (!$razonSocial || !$nombreNegocio || !$adminNombre || !$adminEmail || !$adminPassword) {
            $this->flash('error', 'Todos los campos marcados con * son obligatorios.');
            $this->redirect('superadmin/nuevoNegocio');
        }
        if (strlen($adminPassword) < 6) {
            $this->flash('error', 'La contraseña del admin debe tener al menos 6 caracteres.');
            $this->redirect('superadmin/nuevoNegocio');
        }

        $st = $db->prepare("SELECT id FROM usuarios WHERE email = ? LIMIT 1");
        $st->execute([$adminEmail]);
        if ($st->fetch()) {
            $this->flash('error', 'Ya existe un usuario con ese correo.');
            $this->redirect('superadmin/nuevoNegocio');
        }

        $restModel = new RestauranteModel();
        $slug = $restModel->generarSlugUnico($nombreNegocio);
        $telefono = $this->normalizarTelefono($this->post('telefono', ''));
        $lat = $this->normalizeCoordinate($this->post('lat'), -90, 90);
        $lng = $this->normalizeCoordinate($this->post('lng'), -180, 180);

        $db->beginTransaction();
        try {
            $insEmpresa = $db->prepare(
                "INSERT INTO empresas (razon_social, tipo_negocio, email, telefono, activo, created_at)
                 VALUES (?, 'otro', ?, ?, 1, NOW())"
            );
            $insEmpresa->execute([$razonSocial, $adminEmail, $telefono ?: null]);
            $empresaId = (int)$db->lastInsertId();

            $planId = (int)($db->query("SELECT id FROM planes_negocio WHERE activo=1 ORDER BY id ASC LIMIT 1")->fetchColumn() ?: 0) ?: null;

            $insRest = $db->prepare(
                "INSERT INTO rest_restaurantes
                    (empresa_id, plan_id, comprador_id, nombre, slug, color_primario, color_secundario,
                     descripcion, telefono, direccion, lat, lng, activo, estado_plataforma, created_at, app_movil_habilitada)
                 VALUES (?, ?, 0, ?, ?, '#A97C3F', '#2B1B12', ?, ?, ?, ?, ?, 1, 'activo', NOW(), 1)"
            );
            $insRest->execute([
                $empresaId, $planId, $nombreNegocio, $slug,
                $this->post('descripcion') ?: null, $telefono ?: null, $this->post('direccion') ?: null,
                $lat, $lng,
            ]);
            $restauranteId = (int)$db->lastInsertId();

            $partes = preg_split('/\s+/', $adminNombre, 3);
            $hash = password_hash($adminPassword, PASSWORD_DEFAULT);
            $insUsuario = $db->prepare(
                "INSERT INTO usuarios
                    (nombre, apellido_paterno, apellido_materno, email, email_verificado,
                     primer_login_completado, password, rol_id, empresa_id, restaurante_id, activo, created_at)
                 VALUES (?,?,?,?,1,1,?,2,?,?,1,NOW())"
            );
            $insUsuario->execute([
                $partes[0] ?? $adminNombre, $partes[1] ?? 'Admin', $partes[2] ?? null,
                $adminEmail, $hash, $empresaId, $restauranteId,
            ]);

            $insConfig = $db->prepare(
                "INSERT INTO rest_configuracion (restaurante_id, metodos_pago, tipos_entrega, created_at)
                 VALUES (?, '[\"efectivo\",\"tarjeta\"]', '[\"pickup\",\"delivery\"]', NOW())"
            );
            $insConfig->execute([$restauranteId]);

            $db->commit();
        } catch (\Throwable $e) {
            $db->rollBack();
            error_log('[SuperadminController::crearNegocio] ' . $e->getMessage());
            $this->flash('error', 'No se pudo crear el negocio. Revisa los datos e intenta nuevamente.');
            $this->redirect('superadmin/nuevoNegocio');
        }

        $this->log('Negocio creado', 'superadmin', "{$nombreNegocio} (admin: {$adminEmail})");
        $this->flash('success', "Negocio \"{$nombreNegocio}\" creado. Admin: {$adminEmail}");
        $this->redirect('superadmin/negocios');
    }

    public function toggleActivo(?string $id = null): void
    {
        $this->requirePostWithCsrf('superadmin/negocios');
        $restauranteId = (int)$id;
        $db = Database::getInstance();
        $st = $db->prepare("SELECT activo, nombre FROM rest_restaurantes WHERE id = ?");
        $st->execute([$restauranteId]);
        $rest = $st->fetch(PDO::FETCH_ASSOC);
        if (!$rest) {
            $this->flash('error', 'Negocio no encontrado.');
            $this->redirect('superadmin/negocios');
        }

        $nuevoEstado = $rest['activo'] ? 0 : 1;
        $nuevoEstadoPlataforma = $nuevoEstado ? 'activo' : 'suspendido';
        $db->prepare("UPDATE rest_restaurantes SET activo = ?, estado_plataforma = ? WHERE id = ?")
           ->execute([$nuevoEstado, $nuevoEstadoPlataforma, $restauranteId]);
        $this->log($nuevoEstado ? 'Negocio reactivado' : 'Negocio suspendido', 'superadmin', $rest['nombre']);
        $this->flash('success', $rest['nombre'] . ($nuevoEstado ? ' reactivado.' : ' suspendido.'));
        $this->redirect('superadmin/negocios');
    }

    private function normalizarTelefono(?string $telefono): string
    {
        return substr(preg_replace('/\D+/', '', (string)$telefono), 0, 10);
    }

    private function normalizeCoordinate($value, float $min, float $max): ?float
    {
        $value = trim((string)$value);
        if ($value === '' || !is_numeric($value)) return null;
        $c = (float)$value;
        return ($c >= $min && $c <= $max) ? $c : null;
    }

    private function requirePostWithCsrf(string $fallback): void
    {
        if (!$this->isPost()) {
            http_response_code(405);
            $this->flash('error', 'La accion solicitada requiere confirmacion.');
            $this->redirect($fallback);
        }
        if (!$this->validarCsrf()) {
            http_response_code(419);
            $this->flash('error', 'La sesion expiro. Vuelve a intentarlo.');
            $this->redirect($fallback);
        }
    }
}
