<?php

class EmpresaModel extends BaseModel
{
    protected string $table = 'empresas';

    /**
     * Obtiene una empresa activa. Útil para validar el acceso al sistema.
     */
    public function findActiva(int $id): ?array
    {
        return $this->queryOne(
            'SELECT * FROM empresas WHERE id = ? AND activo = 1 LIMIT 1',
            [$id]
        );
    }

    /**
     * Listado administrativo usando únicamente tablas de la estructura Jungle.
     */
    public function listado(array $filtros = [], int $page = 1): array
    {
        $where = ['1 = 1'];
        $params = [];

        if (array_key_exists('activo', $filtros) && $filtros['activo'] !== '') {
            $where[] = 'e.activo = ?';
            $params[] = (int)$filtros['activo'];
        }

        $buscar = trim((string)($filtros['buscar'] ?? ''));
        if ($buscar !== '') {
            $where[] = '(e.razon_social LIKE ? OR e.rfc LIKE ? OR e.email LIKE ? OR e.telefono LIKE ?)';
            $termino = '%' . $buscar . '%';
            array_push($params, $termino, $termino, $termino, $termino);
        }

        $tipoNegocio = trim((string)($filtros['tipo_negocio'] ?? ''));
        if ($tipoNegocio !== '') {
            $where[] = 'e.tipo_negocio = ?';
            $params[] = $tipoNegocio;
        }

        $sql = 'SELECT e.*,
                       COUNT(DISTINCT u.id) AS total_usuarios,
                       COUNT(DISTINCT s.id) AS total_sucursales,
                       COUNT(DISTINCT r.id) AS total_restaurantes
                  FROM empresas e
             LEFT JOIN usuarios u
                    ON u.empresa_id = e.id AND u.activo = 1
             LEFT JOIN sucursales s
                    ON s.empresa_id = e.id AND s.activo = 1
             LEFT JOIN rest_restaurantes r
                    ON r.empresa_id = e.id AND r.activo = 1
                 WHERE ' . implode(' AND ', $where) . '
              GROUP BY e.id
              ORDER BY e.activo DESC, e.razon_social ASC';

        return $this->paginate($sql, $params, max(1, $page));
    }

    /**
     * Devuelve la empresa con cifras calculadas desde restaurantes y pedidos.
     */
    public function conEstadisticas(int $id): ?array
    {
        return $this->queryOne(
            'SELECT e.*,
                    (SELECT COUNT(*)
                       FROM usuarios u
                      WHERE u.empresa_id = e.id AND u.activo = 1) AS total_usuarios,
                    (SELECT COUNT(*)
                       FROM sucursales s
                      WHERE s.empresa_id = e.id AND s.activo = 1) AS total_sucursales,
                    (SELECT COUNT(*)
                       FROM rest_restaurantes r
                      WHERE r.empresa_id = e.id AND r.activo = 1) AS total_restaurantes,
                    (SELECT COUNT(*)
                       FROM rest_pedidos p
                       JOIN rest_restaurantes r ON r.id = p.restaurante_id
                      WHERE r.empresa_id = e.id
                        AND p.estado <> \'cancelado\') AS total_pedidos,
                    (SELECT COALESCE(SUM(p.total), 0)
                       FROM rest_pedidos p
                       JOIN rest_restaurantes r ON r.id = p.restaurante_id
                      WHERE r.empresa_id = e.id
                        AND p.estado <> \'cancelado\'
                        AND p.pagado_at IS NOT NULL) AS venta_total
               FROM empresas e
              WHERE e.id = ?
              LIMIT 1',
            [$id]
        );
    }

    public function listadoSimple(): array
    {
        return $this->query(
            'SELECT id, razon_social
               FROM empresas
              WHERE activo = 1
              ORDER BY razon_social ASC'
        );
    }

    /**
     * Restaurantes pertenecientes a la empresa, priorizando el menú principal.
     */
    public function restaurantes(int $empresaId, bool $soloActivos = true): array
    {
        $sql = 'SELECT id, nombre, slug, logo, sucursal_id, menu_principal, activo
                  FROM rest_restaurantes
                 WHERE empresa_id = ?';
        $params = [$empresaId];

        if ($soloActivos) {
            $sql .= ' AND activo = 1';
        }

        $sql .= ' ORDER BY menu_principal DESC, activo DESC, nombre ASC';

        return $this->query($sql, $params);
    }

    public function existeRFCValor(string $rfc, ?int $excluirId = null): bool
    {
        $rfc = strtoupper(trim($rfc));
        if ($rfc === '') {
            return false;
        }

        return $this->existeValorUnico('UPPER(rfc)', $rfc, $excluirId);
    }

    public function existeEmailValor(string $email, ?int $excluirId = null): bool
    {
        $email = strtolower(trim($email));
        if ($email === '') {
            return false;
        }

        return $this->existeValorUnico('LOWER(email)', $email, $excluirId);
    }

    private function existeValorUnico(string $columna, string $valor, ?int $excluirId): bool
    {
        $sql = "SELECT COUNT(*) FROM empresas WHERE {$columna} = ?";
        $params = [$valor];

        if ($excluirId !== null) {
            $sql .= ' AND id <> ?';
            $params[] = $excluirId;
        }

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);

        return (int)$stmt->fetchColumn() > 0;
    }
}
