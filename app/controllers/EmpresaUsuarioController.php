<?php
require_once ROOT_PATH . '/app/controllers/BaseController.php';

/**
 * EmpresaUsuarioController
 * Admin Empresa crea y gestiona: supervisor, comprador, repartidor de su empresa.
 */
class EmpresaUsuarioController extends BaseController
{
    private UsuarioModel $model;

    public function __construct()
    {
        parent::__construct();
        $this->requireAdminEmpresa();
        $this->model = new UsuarioModel();
    }

    public function index(?string $p = null): void
    {
        $empresaId = $this->empresaId();
        $usuarios  = $this->model->getByEmpresa($empresaId);
        $flash     = $this->getFlash();
        $pageTitle = 'Mi equipo';
        $activeMenu = 'usuarios';

        ob_start();
        require ROOT_PATH . '/app/views/empresa/usuarios/index.php';
        $content = ob_get_clean();
        require ROOT_PATH . '/app/views/empresa/layouts/main.php';
    }

    public function nuevo(?string $p = null): void
    {
        $roles     = $this->model->rolesPermitidosPorAdminEmpresa();
        $flash     = $this->getFlash();
        $pageTitle = 'Agregar usuario';
        $activeMenu = 'usuarios';

        ob_start();
        require ROOT_PATH . '/app/views/empresa/usuarios/form.php';
        $content = ob_get_clean();
        require ROOT_PATH . '/app/views/empresa/layouts/main.php';
    }

    public function guardar(?string $p = null): void
    {
        if (!$this->isPost()) $this->redirect('empresa-usuario/index');

        $empresaId = $this->empresaId();
        $rolId     = (int)$this->post('rol_id');
        $nombre    = trim($this->post('nombre', ''));
        $apellido  = trim($this->post('apellido_paterno', ''));
        $apellidoM = trim($this->post('apellido_materno', ''));
        $email     = trim($this->post('email', ''));
        $telefono  = trim($this->post('telefono', ''));

        // Validar que el rol esté permitido
        $rolesPermitidos = array_column($this->model->rolesPermitidosPorAdminEmpresa(), 'id');
        if (!in_array($rolId, $rolesPermitidos, true)) {
            $this->flash('error', 'Rol no permitido.');
            $this->redirect('empresa-usuario/nuevo');
        }

        // Validar email único
        if ($this->model->getByEmail($email)) {
            $this->flash('error', 'El correo ya está registrado en el sistema.');
            $this->redirect('empresa-usuario/nuevo');
        }

        // Generar contraseña temporal
        $passwordTemporal = 'Ch' . rand(1000, 9999) . '!';

        $usuarioId = $this->model->crear([
            'nombre'           => $nombre,
            'apellido_paterno' => $apellido,
            'apellido_materno' => $apellidoM ?: null,
            'email'            => $email,
            'telefono'         => $telefono,
            'rol_id'           => $rolId,
            'empresa_id'       => $empresaId,
            'activo'           => 1,
            'created_by'       => $this->usuarioId(),
        ], $passwordTemporal);

        // Detectar el slug del rol recién creado
        $rolInfo = $this->model->getRolPorId($rolId);
        $rolSlug = $rolInfo['slug'] ?? '';

        // Si es comprador: crear su sucursal de entrega automáticamente
        if ($rolSlug === 'comprador') {
            $nombreNegocio  = trim($this->post('nombre_negocio', ''));
            $direccion      = trim($this->post('direccion_entrega', ''));
            $ciudad         = trim($this->post('ciudad', ''));
            $cp             = trim($this->post('codigo_postal', ''));
            $responsable    = trim($this->post('responsable_entrega', ''));
            $horario        = trim($this->post('horario_entrega', ''));

            if ($nombreNegocio && $direccion) {
                $dirCompleta = $direccion;
                if ($ciudad)  $dirCompleta .= ', ' . $ciudad;
                if ($cp)      $dirCompleta .= ' C.P. ' . $cp;
                if ($horario) $dirCompleta .= ' (Horario: ' . $horario . ')';

                $db = Database::getInstance();
                $stmt = $db->prepare(
                    "INSERT INTO sucursales (empresa_id, nombre, direccion, responsable, telefono, activo)
                     VALUES (?, ?, ?, ?, ?, 1)"
                );
                $stmt->execute([
                    $empresaId,
                    $nombreNegocio,
                    $dirCompleta,
                    $responsable ?: ($nombre . ' ' . $apellido),
                    $telefono,
                ]);
            }
        }

        // Si es repartidor: guardar datos del vehículo en el log para referencia
        if ($rolSlug === 'repartidor') {
            $tipoVehiculo  = trim($this->post('tipo_vehiculo', ''));
            $placas        = trim($this->post('placas_vehiculo', ''));
            $modelo        = trim($this->post('vehiculo_modelo', ''));
            $licencia      = trim($this->post('licencia', ''));

            if ($tipoVehiculo || $placas) {
                $this->log(
                    'Repartidor con vehículo',
                    'empresa_usuario',
                    "Usuario: $usuarioId | Tipo: $tipoVehiculo | Placas: $placas | Modelo: $modelo | Licencia: $licencia"
                );
            }
        }

        $this->log('Crear usuario empresa', 'empresa_usuario', "Email: $email, Rol: $rolSlug");

        $this->flash('success', "Usuario creado. Contraseña temporal: <strong>$passwordTemporal</strong> — comunícala al usuario.");
        $this->redirect('empresa-usuario/index');
    }

    public function editar(?string $id = null): void
    {
        $userId = (int)$id;
        $usuario = $this->model->find($userId);

        if (!$usuario || (int)$usuario['empresa_id'] !== $this->empresaId()) {
            $this->redirect('empresa-usuario/index');
        }

        $roles     = $this->model->rolesPermitidosPorAdminEmpresa();
        $flash     = $this->getFlash();
        $pageTitle = 'Editar usuario';
        $activeMenu = 'usuarios';

        ob_start();
        require ROOT_PATH . '/app/views/empresa/usuarios/form.php';
        $content = ob_get_clean();
        require ROOT_PATH . '/app/views/empresa/layouts/main.php';
    }

    public function actualizar(?string $id = null): void
    {
        if (!$this->isPost()) $this->redirect('empresa-usuario/index');

        $userId  = (int)$id;
        $usuario = $this->model->find($userId);

        if (!$usuario || (int)$usuario['empresa_id'] !== $this->empresaId()) {
            $this->redirect('empresa-usuario/index');
        }

        $data = [
            'nombre'           => trim($this->post('nombre', '')),
            'apellido_paterno' => trim($this->post('apellido_paterno', '')),
            'telefono'         => trim($this->post('telefono', '')),
            'activo'           => (int)$this->post('activo', 1),
        ];

        $this->model->update($userId, $data);
        $this->log('Actualizar usuario empresa', 'empresa_usuario', "ID: $userId");
        $this->flash('success', 'Usuario actualizado.');
        $this->redirect('empresa-usuario/index');
    }

    public function toggleActivo(?string $id = null): void
    {
        $userId  = (int)$id;
        $usuario = $this->model->find($userId);

        if (!$usuario || (int)$usuario['empresa_id'] !== $this->empresaId()) {
            $this->json(['ok' => false], 403);
        }

        $nuevo = $usuario['activo'] ? 0 : 1;
        $this->model->update($userId, ['activo' => $nuevo]);
        $this->json(['ok' => true, 'activo' => $nuevo]);
    }
}
