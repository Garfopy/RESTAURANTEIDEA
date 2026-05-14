<?php
/**
 * PayPalOrdenService
 * Integración con PayPal Orders API v2 para pagos únicos de tickets de restaurante.
 * Distinto de PayPalSuscripcionService (que maneja suscripciones recurrentes).
 */
class PayPalOrdenService
{
    private string $baseUrl;
    private string $clientId;
    private string $secret;

    public function __construct()
    {
        $config         = new ConfigModel();
        $mode           = $config->get('paypal_mode', 'sandbox');
        $this->clientId = $mode === 'live'
            ? $config->get('paypal_client_id_live',    $config->get('paypal_client_id', ''))
            : $config->get('paypal_client_id_sandbox', $config->get('paypal_client_id', ''));
        $this->secret   = $mode === 'live'
            ? $config->get('paypal_secret_live',    $config->get('paypal_secret', ''))
            : $config->get('paypal_secret_sandbox', $config->get('paypal_secret', ''));
        $this->baseUrl  = $mode === 'live'
            ? 'https://api-m.paypal.com'
            : 'https://api-m.sandbox.paypal.com';
    }

    // ── OAuth token (session-cached) ──────────────────────────────────────────
    private function getAccessToken(): string
    {
        $cacheKey = 'paypal_orden_access_token';
        if (!empty($_SESSION[$cacheKey]) && !empty($_SESSION[$cacheKey . '_exp'])
            && time() < $_SESSION[$cacheKey . '_exp']) {
            return $_SESSION[$cacheKey];
        }

        $ch = curl_init($this->baseUrl . '/v1/oauth2/token');
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST           => true,
            CURLOPT_USERPWD        => $this->clientId . ':' . $this->secret,
            CURLOPT_POSTFIELDS     => 'grant_type=client_credentials',
            CURLOPT_HTTPHEADER     => ['Content-Type: application/x-www-form-urlencoded'],
        ]);
        $body = curl_exec($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($code !== 200) {
            throw new \RuntimeException('PayPal auth error: ' . $body);
        }
        $data = json_decode($body, true);
        $_SESSION[$cacheKey]          = $data['access_token'];
        $_SESSION[$cacheKey . '_exp'] = time() + (int)($data['expires_in'] ?? 28800) - 60;

        return $data['access_token'];
    }

    // ── Helper cURL ───────────────────────────────────────────────────────────
    private function request(string $method, string $endpoint, array $body = [], string $idempotencyKey = ''): array
    {
        $token = $this->getAccessToken();
        $headers = [
            'Authorization: Bearer ' . $token,
            'Content-Type: application/json',
        ];
        if ($idempotencyKey) {
            $headers[] = 'PayPal-Request-Id: ' . $idempotencyKey;
        }

        $ch = curl_init($this->baseUrl . $endpoint);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CUSTOMREQUEST  => $method,
            CURLOPT_HTTPHEADER     => $headers,
        ]);
        if (!empty($body)) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($body));
        }
        $raw  = curl_exec($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        $data = json_decode($raw, true) ?? [];
        if ($code >= 400) {
            $msg = $data['message'] ?? $raw;
            throw new \RuntimeException("PayPal API error ($code): $msg");
        }
        return $data;
    }

    // ── Crear orden de pago único ─────────────────────────────────────────────
    /**
     * Crea una orden PayPal para un ticket de restaurante.
     *
     * @param float  $amount       Total a cobrar (con propina incluida)
     * @param string $currency     Código ISO: 'MXN', 'USD', etc.
     * @param string $returnUrl    URL a la que PayPal regresa al aprobar
     * @param string $cancelUrl    URL a la que PayPal regresa al cancelar
     * @param string $invoiceId    Folio del ticket (para referencia interna)
     *
     * @return array{id: string, approvalUrl: string}
     */
    public function crearOrden(
        float  $amount,
        string $currency,
        string $returnUrl,
        string $cancelUrl,
        string $invoiceId
    ): array {
        $payload = [
            'intent'         => 'CAPTURE',
            'purchase_units' => [[
                'reference_id' => $invoiceId,
                'invoice_id'   => $invoiceId,
                'amount'       => [
                    'currency_code' => $currency,
                    'value'         => number_format($amount, 2, '.', ''),
                ],
                'description'  => 'CarniHub Restaurante — ' . $invoiceId,
            ]],
            'application_context' => [
                'return_url'  => $returnUrl,
                'cancel_url'  => $cancelUrl,
                'brand_name'  => 'CarniHub Restaurantes',
                'user_action' => 'PAY_NOW',
            ],
        ];

        $data = $this->request('POST', '/v2/checkout/orders', $payload, 'orden-' . $invoiceId);

        // Encontrar el link de aprobación
        $approvalUrl = '';
        foreach ($data['links'] ?? [] as $link) {
            if ($link['rel'] === 'approve') {
                $approvalUrl = $link['href'];
                break;
            }
        }

        return [
            'id'          => $data['id'] ?? '',
            'approvalUrl' => $approvalUrl,
        ];
    }

    // ── Capturar una orden aprobada ───────────────────────────────────────────
    /**
     * Captura el pago después de que el usuario aprueba en PayPal.
     *
     * @param  string $orderId  ID de la orden PayPal (llega como ?token= en la returnUrl)
     * @return array            Datos del capture de PayPal
     */
    public function capturarOrden(string $orderId): array
    {
        return $this->request('POST', '/v2/checkout/orders/' . $orderId . '/capture');
    }
}
