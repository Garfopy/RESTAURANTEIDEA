<?php

class LandingController
{
    public function index(): void
    {
        $filePath = ROOT_PATH . '/app/views/public/landing_amare.html';

        if (!file_exists($filePath)) {
            http_response_code(404);
            echo "Landing page not found";
            return;
        }

        $html = file_get_contents($filePath);
        if ($html === false) {
            http_response_code(500);
            echo "Unable to load landing page";
            return;
        }

        $restaurante = $this->getLandingRestaurant();
        $reservarUrl = BASE_URL;
        $qrUrl = 'https://api.qrserver.com/v1/create-qr-code/?size=280x280&data='
            . rawurlencode($reservarUrl)
            . '&ecc=M';

        if (!empty($restaurante['slug'])) {
            $reservarUrl = BASE_URL . 'menu/' . rawurlencode((string) $restaurante['slug']) . '/reservar';
            $qrUrl = 'https://api.qrserver.com/v1/create-qr-code/?size=280x280&data='
                . rawurlencode($reservarUrl)
                . '&ecc=M';
        }

        $html = str_replace(
            'href="#reservar" class="hidden rounded-full border border-amareGold/40 px-6 py-3 text-[11px] font-bold uppercase tracking-[.22em] text-amareGold transition hover:bg-amareGold hover:text-amareBlack lg:inline-flex"',
            'href="{{RESERVAR_URL}}" class="hidden rounded-full border border-amareGold/40 px-6 py-3 text-[11px] font-bold uppercase tracking-[.22em] text-amareGold transition hover:bg-amareGold hover:text-amareBlack lg:inline-flex"',
            $html
        );

        $html = str_replace(
            'href="#reservar" class="menuLink mobile-link mt-8 flex w-full items-center justify-center rounded-full bg-amareGold px-6 py-4 text-xs font-black uppercase tracking-[.26em] text-amareBlack shadow-[0_20px_50px_rgba(0,0,0,.35)] transition hover:scale-[1.02]"',
            'href="{{RESERVAR_URL}}" class="menuLink mobile-link mt-8 flex w-full items-center justify-center rounded-full bg-amareGold px-6 py-4 text-xs font-black uppercase tracking-[.26em] text-amareBlack shadow-[0_20px_50px_rgba(0,0,0,.35)] transition hover:scale-[1.02]"',
            $html
        );

        $html = str_replace(
            '<a class="hover:text-amareGold" href="#reservar">Reservar</a>',
            '<a class="hover:text-amareGold" href="{{RESERVAR_URL}}">Reservar</a>',
            $html
        );

        $landingReservaStart = '<form class="reveal rounded-[2rem] border border-amareGold/15 bg-amareIvory/[.045] p-6 shadow-soft backdrop-blur md:p-10">';
        $landingReservaEnd = '        </form>';
        $startPos = strpos($html, $landingReservaStart);

        if ($startPos !== false) {
            $endPos = strpos($html, $landingReservaEnd, $startPos);

            if ($endPos !== false) {
                $endPos += strlen($landingReservaEnd);
                $replacement = <<<'HTML'
<div class="reveal rounded-[2rem] border border-amareGold/15 bg-amareIvory/[.045] p-6 shadow-soft backdrop-blur md:p-10">
          <div class="grid gap-8 lg:grid-cols-[1.15fr_.85fr] lg:items-center">
            <div>
              <p class="text-xs font-semibold uppercase tracking-[.35em] text-amareGoldSoft">Reserva directa</p>
              <h3 class="mt-4 font-display text-4xl leading-none text-amareIvory md:text-5xl">Elige fecha, horario y tu mesa en unos segundos.</h3>
              <p class="mt-5 text-base font-light leading-8 text-amareIvory/72">Accede al sistema de reservaciones de AMARE para consultar disponibilidad real y asegurar tu visita con una experiencia mas cuidada.</p>
              <div class="mt-8 flex flex-col gap-4 sm:flex-row">
                <a href="{{RESERVAR_URL}}" class="btn-gold inline-flex items-center justify-center rounded-full px-8 py-5 text-xs font-black uppercase tracking-[.25em] transition duration-500">
                  Reservar mesa
                </a>
                <a href="#galeria" class="btn-ghost inline-flex items-center justify-center rounded-full border border-amareIvory/15 px-8 py-5 text-xs font-bold uppercase tracking-[.25em] text-amareIvory transition duration-500">
                  Ver ambiente
                </a>
              </div>
            </div>

            <div class="rounded-[1.8rem] border border-amareGold/20 bg-amareBlack/55 p-5 text-center shadow-[0_20px_70px_rgba(0,0,0,.35)]">
              <p class="text-xs uppercase tracking-[.35em] text-amareGold">QR de reservaciones</p>
              <div class="mx-auto mt-5 w-full max-w-[250px] rounded-[1.5rem] bg-amareIvory p-4">
                <img src="{{RESERVAR_QR_IMG}}" alt="QR para reservar en AMARE" class="mx-auto h-auto w-full rounded-[1rem]" />
              </div>
            </div>
          </div>
        </div>
HTML;

                $html = substr_replace($html, $replacement, $startPos, $endPos - $startPos);
            }
        }

        $html = strtr($html, [
            '{{RESERVAR_URL}}' => htmlspecialchars($reservarUrl, ENT_QUOTES, 'UTF-8'),
            '{{RESERVAR_QR_IMG}}' => htmlspecialchars($qrUrl, ENT_QUOTES, 'UTF-8'),
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

            if (method_exists($model, 'getLandingRestaurant')) {
                return $model->getLandingRestaurant();
            }

            if (method_exists($model, 'getBySlug')) {
                foreach (['amare', 'amare-restaurant', 'amare-restaurante'] as $slug) {
                    $restaurant = $model->getBySlug($slug);
                    if ($restaurant) {
                        return $restaurant;
                    }
                }
            }
        } catch (Throwable $e) {
            error_log('[LandingController] No se pudo obtener restaurante landing: ' . $e->getMessage());
        }

        return null;
    }
}
