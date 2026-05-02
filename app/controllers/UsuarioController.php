<?php
require_once ROOT_PATH . '/app/controllers/BaseController.php';

class UsuarioController extends BaseController
{
    private UsuarioModel $model;

    public function __construct()
    {
        parent::__construct();
        $this->requireAdmin();
        $this->model = new UsuarioModel();
    }

    public function index(?string $p = null): void
    {
        $busqueda = $this->get('q', '');
        $page     = max(1, (int)$this->get('page', 1));
        $usuarios = $busqueda
            ? $this->model->search($busqueda, $page)
            : $this->model->getAll($page);
        $roles     = Database::getInstance()->query('SELECT * FROM roles ORDER BY id')->fetchAll();
        $flash     = $this->getFlash();
        $pageTitle = 'Usuarios';
        $ctrlSlug  = 'usuario';
        $this->render('admin/usuarios/index', compact('usuarios', 'roles', 'busqueda', 'flash', 'pageTitle', 'ctrlSlug'));
    }

    public function crear(?string $p = null): void
    {
        $usuario   = [];
        $roles     = Database::getInstance()->query('SELECT * FROM roles ORDER BY id')->fetchAll();
        $empresas  = Database::getInstance()->query('SELECT id, razon_social FROM empresas WHERE activo=1 ORDER BY razon_social')->fetchAll();
        $pageTitle = 'Nuevo Usuario';
        $ctrlSlug  = 'usuario';
        $this->render('admin/usuarios/form', compact('usuario', 'roles', 'empresas', 'pageTitle', 'ctrlSlug'));
    }

    public function editar(?string $id = null): void
    {
        $usuario = $this->model->find((int)$id);
        if (!$usuario) { $this->redirect('usuario/index'); }
        $roles    = Database::getInstance()->query('SELECT * FROM roles ORDER BY id')->fetchAll();
        $empresas = Database::getInstance()->query('SELECT id, razon_social FROM empresas WHERE activo=1 ORDER BY razon_social')->fetchAll();
        $pageTitle = 'Editar: ' . $usuario['nombre'] . ' ' . $usuario['apellido_paterno'];
        $ctrlSlug  = 'usuario';
        $this->render('admin/usuarios/form', compact('usuario', 'roles', 'empresas', 'pageTitle', 'ctrlSlug'));
    }

    public function guardar(?string $p = null): void
    {
        if (!$this->isPost()) { $this->redirect('usuario/index'); }

        $id   = (int)$this->post('id', 0);
        $data = [
            'nombre'           => trim($this->post('nombre', '')),
            'apellido_paterno' => trim($this->post('apellido_paterno', '')),
            'apellido_materno' => trim($this->post('apellido_materno', '')) ?: null,
            'email'            => trim($this->post('email', '')),
            'rol_id'           => (int)$this->post('rol_id'),
            'empresa_id'       => $this->post('empresa_id') ?: null,
            'activo'           => (int)$this->post('activo', 1),
        ];

        $pass = $this->post('password', '');
        if ($pass) {
            $data['password'] = password_hash($pass, PASSWORD_DEFAULT);
        }

        // Procesar avatar
        $avatarDir = ROOT_PATH . '/public/uploads/avatares/';
        if (!empty($_FILES['avatar']['tmp_name']) && $_FILES['avatar']['error'] === UPLOAD_ERR_OK) {
            $allowedExts = ['jpg', 'jpeg', 'png', 'webp'];
            $ext = strtolower(pathinfo($_FILES['avatar']['name'], PATHINFO_EXTENSION));
            if (in_array($ext, $allowedExts, true) && $_FILES['avatar']['size'] <= 2097152) {
                if (!is_dir($avatarDir)) mkdir($avatarDir, 0755, true);
                $tmpId    = $id > 0 ? $id : 'tmp_' . time();
                $filename = 'avatar_' . $tmpId . '_' . time() . '.' . $ext;
                if (move_uploaded_file($_FILES['avatar']['tmp_name'], $avatarDir . $filename)) {
                    // Eliminar avatar anterior
                    if ($id > 0) {
                        $old = $this->model->find($id);
                        if (!empty($old['avatar'])) {
                            $oldPath = ROOT_PATH . '/public/' . $old['avatar'];
                            if (file_exists($oldPath)) @unlink($oldPath);
                        }
                    }
                    $data['avatar'] = 'uploads/avatares/' . $filename;
                }
            }
        } elseif ($this->post('borrar_avatar') === '1' && $id > 0) {
            $old = $this->model->find($id);
            if (!empty($old['avatar'])) {
                $oldPath = ROOT_PATH . '/public/' . $old['avatar'];
                if (file_exists($oldPath)) @unlink($oldPath);
            }
            $data['avatar'] = null;
        }

        if ($id > 0) {
            $this->model->update($id, $data);
            // Si el avatar era tmp y se creó con nuevo ID, renombrar
            if (!empty($data['avatar']) && strpos($data['avatar'], 'tmp_') !== false) {
                $oldPath = ROOT_PATH . '/public/' . $data['avatar'];
                $newFilename = str_replace('tmp_', $id . '_', basename($data['avatar']));
                $newPath = $avatarDir . $newFilename;
                if (file_exists($oldPath)) {
                    rename($oldPath, $newPath);
                    $this->model->update($id, ['avatar' => 'uploads/avatares/' . $newFilename]);
                }
            }
            $this->flash('success', 'Usuario actualizado.');
        } else {
            if (!$pass) {
                $this->flash('error', 'La contraseña es requerida.');
                $this->redirect('usuario/crear');
            }
            $data['password'] = password_hash($pass, PASSWORD_DEFAULT);
            $newId = $this->model->insert($data);
            // Si avatar fue subido con tmp ID, renombrar al ID real
            if (!empty($data['avatar']) && strpos($data['avatar'], 'tmp_') !== false) {
                $oldPath = ROOT_PATH . '/public/' . $data['avatar'];
                $newFilename = preg_replace('/avatar_tmp_\d+_/', 'avatar_' . $newId . '_', basename($data['avatar']));
                $newPath = $avatarDir . $newFilename;
                if (file_exists($oldPath)) {
                    rename($oldPath, $newPath);
                    $this->model->update($newId, ['avatar' => 'uploads/avatares/' . $newFilename]);
                }
            }
            $this->flash('success', 'Usuario creado.');
        }

        $this->log('Usuario guardado: ' . $data['nombre'] . ' ' . $data['apellido_paterno'], 'usuarios');
        $this->redirect('usuario/index');
    }

    public function eliminar(?string $id = null): void
    {
        $this->model->update((int)$id, ['activo' => 0]);
        $this->flash('success', 'Usuario desactivado.');
        $this->redirect('usuario/index');
    }

    public function toggleActivo(?string $p = null): void
    {
        $id      = (int)$this->post('id', 0);
        $usuario = $this->model->find($id);
        if (!$usuario) {
            $this->json(['ok' => false, 'msg' => 'Usuario no encontrado.']);
        }
        $nuevoEstado = $usuario['activo'] ? 0 : 1;
        $this->model->update($id, ['activo' => $nuevoEstado]);
        $this->log(($nuevoEstado ? 'Activar' : 'Desactivar') . ' usuario ID ' . $id, 'usuarios');
        $this->json(['ok' => true, 'activo' => $nuevoEstado]);
    }
}
