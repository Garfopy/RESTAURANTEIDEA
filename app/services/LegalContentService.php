<?php

class LegalContentService
{
    private const DEFAULT_VERSION = '2026-07-14';

    public function getTerms(?array $restaurante = null): array
    {
        $settings = $this->settings();
        $brand = $restaurante['nombre'] ?? ($settings['app_name'] ?? APP_NAME);
        $updatedAt = $settings['legal_terms_updated_at'] ?? self::DEFAULT_VERSION;

        $html = trim((string)($settings['legal_terms_html'] ?? ''));
        if ($html === '') {
            $html = $this->defaultTermsHtml($brand);
        }

        return [
            'title'      => $settings['legal_terms_title'] ?? 'Terminos y condiciones',
            'version'    => $settings['legal_terms_version'] ?? self::DEFAULT_VERSION,
            'updated_at' => $updatedAt,
            'brand'      => $brand,
            'html'       => $html,
            'plain_text' => $this->htmlToPlainText($html),
        ];
    }

    private function settings(): array
    {
        try {
            $cfg = new ConfigModel();
            return $cfg->getAll();
        } catch (\Throwable $e) {
            return [];
        }
    }

    private function defaultTermsHtml(string $brand): string
    {
        $brand = htmlspecialchars($brand, ENT_QUOTES, 'UTF-8');
        return <<<HTML
<h2>1. Aceptacion del servicio</h2>
<p>Al acceder o utilizar {$brand}, la persona usuaria acepta estos terminos y condiciones. Si no esta de acuerdo, debera abstenerse de usar la plataforma, el menu digital o la aplicacion movil vinculada.</p>

<h2>2. Uso de la plataforma</h2>
<p>La plataforma permite consultar menu, registrar pedidos, dar seguimiento a consumos, gestionar mesas, pagos, tickets y otros procesos operativos del restaurante. La disponibilidad de funciones puede variar segun la configuracion de cada sucursal.</p>

<h2>3. Informacion de pedidos</h2>
<p>Los precios, descripciones, imagenes, ingredientes, promociones y disponibilidad de productos pueden modificarse sin previo aviso. El restaurante es responsable de confirmar existencias, preparar los pedidos y atender aclaraciones relacionadas con el consumo.</p>

<h2>4. Pagos y facturacion</h2>
<p>Cuando existan pagos en linea, estos podran procesarse mediante proveedores externos. La emision de facturas dependera de la informacion fiscal proporcionada por la persona usuaria y de la configuracion activa del restaurante.</p>

<h2>5. Cuenta, acceso y seguridad</h2>
<p>La persona usuaria debera proporcionar informacion veraz cuando se solicite para pedidos, reservaciones, facturacion o identificacion de consumo. El uso indebido de cuentas, codigos QR, enlaces o tokens de acceso podra ocasionar la restriccion del servicio.</p>

<h2>6. Aplicacion movil</h2>
<p>La aplicacion movil puede mostrar estos terminos en una vista nativa o abrir la version web oficial. En ambos casos, el contenido vigente sera el publicado por la plataforma y podra consultarse desde el endpoint destinado a aplicaciones cliente.</p>

<h2>7. Limitacion de responsabilidad</h2>
<p>La plataforma busca facilitar la operacion del restaurante, pero no sustituye la supervision del personal. El restaurante conserva la responsabilidad sobre preparacion de alimentos, tiempos de servicio, atencion al cliente, cobros, reembolsos y cumplimiento normativo aplicable.</p>

<h2>8. Cambios a los terminos</h2>
<p>Estos terminos pueden actualizarse para reflejar cambios operativos, legales o tecnologicos. La version vigente sera la que se encuentre publicada en la plataforma al momento de uso del servicio.</p>

<h2>9. Contacto</h2>
<p>Para dudas, aclaraciones o solicitudes relacionadas con estos terminos, la persona usuaria debera comunicarse directamente con el restaurante o con el canal de soporte indicado por la plataforma.</p>
HTML;
    }

    private function htmlToPlainText(string $html): string
    {
        $text = preg_replace('/<\s*br\s*\/?>/i', "\n", $html);
        $text = preg_replace('/<\/(p|h2|li)>/i', "\n", $text ?? '');
        $text = html_entity_decode(strip_tags($text ?? ''), ENT_QUOTES, 'UTF-8');
        $text = preg_replace("/[ \t]+/", ' ', $text ?? '');
        $text = preg_replace("/\n{3,}/", "\n\n", $text ?? '');
        return trim($text ?? '');
    }
}
