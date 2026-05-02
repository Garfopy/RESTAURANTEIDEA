<?php
class DispositivoModel extends BaseModel
{
    protected string $table = 'dispositivos_hikvision';

    public function getHikvision(): array
    {
        return $this->query('SELECT * FROM dispositivos_hikvision ORDER BY nombre');
    }

    public function getShelly(): array
    {
        return $this->query('SELECT * FROM dispositivos_shelly ORDER BY nombre');
    }

    public function guardarHikvision(array $data): int
    {
        if (!empty($data['password'])) {
            $data['password_enc'] = base64_encode($data['password']);
            unset($data['password']);
        }
        $this->table = 'dispositivos_hikvision';
        if (!empty($data['id'])) {
            $id = (int)$data['id'];
            unset($data['id']);
            $this->update($id, $data);
            return $id;
        }
        return $this->insert($data);
    }

    public function guardarShelly(array $data): int
    {
        $this->table = 'dispositivos_shelly';
        if (!empty($data['id'])) {
            $id = (int)$data['id'];
            unset($data['id']);
            $this->update($id, $data);
            return $id;
        }
        return $this->insert($data);
    }

    public function eliminarHikvision(int $id): bool
    {
        return $this->execute('DELETE FROM dispositivos_hikvision WHERE id = ?', [$id]);
    }

    public function eliminarShelly(int $id): bool
    {
        return $this->execute('DELETE FROM dispositivos_shelly WHERE id = ?', [$id]);
    }

    public function toggleHikvision(int $id, int $activo): bool
    {
        return $this->execute(
            'UPDATE dispositivos_hikvision SET activo = ? WHERE id = ?',
            [$activo, $id]
        );
    }

    public function toggleShelly(int $id, int $activo): bool
    {
        return $this->execute(
            'UPDATE dispositivos_shelly SET activo = ? WHERE id = ?',
            [$activo, $id]
        );
    }

    public function findHikvision(int $id): ?array
    {
        return $this->queryOne('SELECT * FROM dispositivos_hikvision WHERE id = ?', [$id]);
    }

    public function findShelly(int $id): ?array
    {
        return $this->queryOne('SELECT * FROM dispositivos_shelly WHERE id = ?', [$id]);
    }
}
