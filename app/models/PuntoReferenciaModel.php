<?php
/**
 * Puntos de referencia (universidades, plazas, etc.) que Superadmin usa para curar
 * "negocios cerca de mí" en la app móvil. Ver plan-web-superadmin.md §3.1-3.2.
 */
class PuntoReferenciaModel extends BaseModel
{
    protected string $table = 'puntos_referencia';

    public function getAllConConteo(): array
    {
        return $this->query(
            "SELECT p.*, COUNT(rp.restaurante_id) AS negocios_asociados
               FROM puntos_referencia p
          LEFT JOIN rest_puntos_referencia rp ON rp.punto_referencia_id = p.id
           GROUP BY p.id
           ORDER BY p.nombre ASC"
        );
    }

    public static function haversineKm(float $lat1, float $lng1, float $lat2, float $lng2): float
    {
        $earthRadiusKm = 6371.0;
        $dLat = deg2rad($lat2 - $lat1);
        $dLng = deg2rad($lng2 - $lng1);
        $a = sin($dLat / 2) ** 2
            + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLng / 2) ** 2;
        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));
        return $earthRadiusKm * $c;
    }

    /**
     * Recalcula las asociaciones de UN negocio contra todos los puntos activos.
     * Se llama cuando el Admin guarda lat/lng en rest-config. Respeta destacado_manual=1:
     * esas filas no se tocan aunque el negocio ya no esté dentro del radio.
     */
    public function recalcularParaRestaurante(int $restauranteId): void
    {
        $restaurante = $this->queryOne(
            "SELECT lat, lng FROM rest_restaurantes WHERE id = ?",
            [$restauranteId]
        );
        if (!$restaurante || $restaurante['lat'] === null || $restaurante['lng'] === null) {
            $this->execute(
                "DELETE FROM rest_puntos_referencia WHERE restaurante_id = ? AND destacado_manual = 0",
                [$restauranteId]
            );
            return;
        }

        $this->sincronizarAsociaciones(
            $restauranteId,
            (float)$restaurante['lat'],
            (float)$restaurante['lng'],
            $this->query("SELECT * FROM puntos_referencia WHERE activo = 1")
        );
    }

    /**
     * Recalcula las asociaciones de UN punto de referencia contra todos los negocios
     * con ubicación conocida. Se llama al crear/editar un punto desde Superadmin.
     */
    public function recalcularParaPunto(int $puntoReferenciaId): void
    {
        $punto = $this->find($puntoReferenciaId);
        if (!$punto) return;

        if (!$punto['activo']) {
            // Punto inactivo: no debe seguir asociando negocios automáticamente. Las filas
            // con destacado_manual=1 se conservan por si se reactiva después.
            $this->execute(
                "DELETE FROM rest_puntos_referencia WHERE punto_referencia_id = ? AND destacado_manual = 0",
                [$puntoReferenciaId]
            );
            return;
        }

        $negocios = $this->query(
            "SELECT id, lat, lng FROM rest_restaurantes WHERE lat IS NOT NULL AND lng IS NOT NULL"
        );
        foreach ($negocios as $negocio) {
            $this->sincronizarAsociaciones(
                (int)$negocio['id'],
                (float)$negocio['lat'],
                (float)$negocio['lng'],
                [$punto]
            );
        }
    }

    private function sincronizarAsociaciones(int $restauranteId, float $lat, float $lng, array $puntos): void
    {
        foreach ($puntos as $punto) {
            if ($punto['lat'] === null || $punto['lng'] === null) continue;

            $distancia = self::haversineKm($lat, $lng, (float)$punto['lat'], (float)$punto['lng']);
            $dentroDeRadio = $distancia <= (float)$punto['radio_km'];

            $existente = $this->queryOne(
                "SELECT destacado_manual FROM rest_puntos_referencia
                  WHERE restaurante_id = ? AND punto_referencia_id = ?",
                [$restauranteId, $punto['id']]
            );

            if ($existente && (int)$existente['destacado_manual'] === 1) {
                // Override manual de Superadmin: no lo tocamos, solo refrescamos la distancia.
                $this->execute(
                    "UPDATE rest_puntos_referencia SET distancia_km = ?
                      WHERE restaurante_id = ? AND punto_referencia_id = ?",
                    [round($distancia, 2), $restauranteId, $punto['id']]
                );
                continue;
            }

            if ($dentroDeRadio) {
                $this->execute(
                    "INSERT INTO rest_puntos_referencia (restaurante_id, punto_referencia_id, distancia_km, destacado_manual)
                     VALUES (?, ?, ?, 0)
                     ON DUPLICATE KEY UPDATE distancia_km = VALUES(distancia_km)",
                    [$restauranteId, $punto['id'], round($distancia, 2)]
                );
            } elseif ($existente) {
                $this->execute(
                    "DELETE FROM rest_puntos_referencia WHERE restaurante_id = ? AND punto_referencia_id = ?",
                    [$restauranteId, $punto['id']]
                );
            }
        }
    }
}
