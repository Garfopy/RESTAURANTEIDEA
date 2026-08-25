<?php

class LandingController
{
    public function index(): void
    {
        $filePath = ROOT_PATH . '/base/index.html';

        if (!file_exists($filePath)) {
            http_response_code(404);
            echo 'Landing page not found';
            return;
        }

        $html = file_get_contents($filePath);
        if ($html === false) {
            http_response_code(500);
            echo 'Unable to load landing page';
            return;
        }

        $restaurante = $this->getLandingRestaurant();
        $slug = trim((string)($restaurante['slug'] ?? ''));
        $reservasHabilitadas = $slug !== '' && !empty($restaurante['reservas_habilitadas']);

        $assetBase = BASE_URL . 'base/redesign-assets/';
        $reservationAction = $reservasHabilitadas
            ? BASE_URL . 'menu/' . rawurlencode($slug) . '/guardarReserva'
            : '';
        $availabilityUrl = $reservasHabilitadas
            ? BASE_URL . 'menu/' . rawurlencode($slug) . '/mesasDisponibles'
            : '';

        // /base también puede abrirse directamente durante el diseño. Al servirlo
        // desde la raíz, sus recursos deben apuntar al directorio público real.
        $html = str_replace('redesign-assets/', $assetBase, $html);
        $html = strtr($html, [
            '{{RESERVATION_ACTION}}' => htmlspecialchars($reservationAction, ENT_QUOTES, 'UTF-8'),
            '{{RESERVATION_AVAILABILITY_URL}}' => htmlspecialchars($availabilityUrl, ENT_QUOTES, 'UTF-8'),
            '{{RESERVATION_ENABLED}}' => $reservasHabilitadas ? 'true' : 'false',
            '{{RESERVATION_DISABLED}}' => $reservasHabilitadas ? '' : 'disabled',
            '{{RESERVATION_NOTICE_HIDDEN}}' => $reservasHabilitadas ? 'hidden' : '',
            '{{RESTAURANT_NAME}}' => htmlspecialchars((string)($restaurante['nombre'] ?? 'Jungle Pizza'), ENT_QUOTES, 'UTF-8'),
            '{{ADMIN_URL}}' => htmlspecialchars(BASE_URL . 'auth/login', ENT_QUOTES, 'UTF-8'),
        ]);

        header('Content-Type: text/html; charset=utf-8');
        echo $html;
    }

    private function getLandingRestaurant(): ?array
    {
        if (!class_exists('RestauranteModel')) {
            return null;
        }

        try {
            $model = new RestauranteModel();

            foreach (['jungle-pizza-zihuatanejo', 'jungle-pizza', 'junglepizza', 'jungle'] as $slug) {
                $restaurant = $model->getBySlug($slug);
                if ($restaurant) {
                    return $restaurant;
                }
            }

            $restaurant = $model->getActiveByName('Jungle Pizza');
            if ($restaurant) {
                return $restaurant;
            }

            return $model->getLandingRestaurant();
        } catch (Throwable $e) {
            error_log('[LandingController] No se pudo obtener el restaurante del landing: ' . $e->getMessage());
        }

        return null;
    }
}
