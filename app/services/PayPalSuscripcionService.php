<?php
class PayPalSuscripcionService
{
    private string $baseUrl;
    private string $clientId;
    private string $secret;

    public function __construct()
    {
        $config      = new ConfigModel();
        $this->clientId = $config->get('paypal_client_id', '');
        $this->secret   = $config->get('paypal_secret', '');
        $mode           = $config->get('paypal_mode', 'sandbox');
        $this->baseUrl  = $mode === 'live'
            ? 'https://api-m.paypal.com'
            : 'https://api-m.sandbox.paypal.com';
    }

    // ── OAuth token ───────────────────────────────────────────────────────────
    private function getAccessToken(): string
    {
        $cacheKey = 'paypal_access_token';
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
    private function request(string $method, string $endpoint, array $body = []): array
    {
        $token = $this->getAccessToken();
        $ch    = curl_init($this->baseUrl . $endpoint);
        $opts  = [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER     => [
                'Authorization: Bearer ' . $token,
                'Content-Type: application/json',
                'PayPal-Request-Id: carnihub-' . uniqid(),
            ],
        ];
        if ($method === 'POST') {
            $opts[CURLOPT_POST]       = true;
            $opts[CURLOPT_POSTFIELDS] = json_encode($body);
        } elseif ($method === 'GET') {
            $opts[CURLOPT_HTTPGET] = true;
        }
        curl_setopt_array($ch, $opts);
        $response = curl_exec($ch);
        $code     = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        $decoded = json_decode($response, true) ?? [];
        if ($code >= 400) {
            $msg = $decoded['message'] ?? $decoded['error_description'] ?? $response;
            throw new \RuntimeException("PayPal API error ($code): $msg");
        }
        return $decoded;
    }

    // ── Crear suscripción ─────────────────────────────────────────────────────
    public function crearSuscripcion(
        string $paypalPlanId,
        string $returnUrl,
        string $cancelUrl
    ): array {
        $data = $this->request('POST', '/v1/billing/subscriptions', [
            'plan_id'             => $paypalPlanId,
            'application_context' => [
                'brand_name'          => 'CarniHub',
                'locale'              => 'es-MX',
                'shipping_preference' => 'NO_SHIPPING',
                'user_action'         => 'SUBSCRIBE_NOW',
                'return_url'          => $returnUrl,
                'cancel_url'          => $cancelUrl,
            ],
        ]);

        $approveLink = '';
        foreach ($data['links'] ?? [] as $link) {
            if ($link['rel'] === 'approve') {
                $approveLink = $link['href'];
                break;
            }
        }

        return [
            'id'           => $data['id']     ?? '',
            'status'       => $data['status'] ?? '',
            'approve_link' => $approveLink,
        ];
    }

    // ── Obtener estado de suscripción ─────────────────────────────────────────
    public function obtenerSuscripcion(string $subscriptionId): array
    {
        return $this->request('GET', '/v1/billing/subscriptions/' . $subscriptionId);
    }

    // ── Cancelar suscripción ──────────────────────────────────────────────────
    public function cancelarSuscripcion(
        string $subscriptionId,
        string $reason = 'Cancelado por usuario'
    ): void {
        $ch = curl_init($this->baseUrl . '/v1/billing/subscriptions/' . $subscriptionId . '/cancel');
        $token = $this->getAccessToken();
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => json_encode(['reason' => $reason]),
            CURLOPT_HTTPHEADER     => [
                'Authorization: Bearer ' . $token,
                'Content-Type: application/json',
            ],
        ]);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        if ($code !== 204 && $code !== 200) {
            throw new \RuntimeException("Error al cancelar suscripción PayPal ($code)");
        }
    }

    // ── Verificar webhook ─────────────────────────────────────────────────────
    public function verificarWebhook(array $headers, string $rawBody, string $webhookId): bool
    {
        try {
            $data = $this->request('POST', '/v1/notifications/verify-webhook-signature', [
                'transmission_id'   => $headers['PAYPAL-TRANSMISSION-ID']   ?? '',
                'transmission_time' => $headers['PAYPAL-TRANSMISSION-TIME'] ?? '',
                'cert_url'          => $headers['PAYPAL-CERT-URL']          ?? '',
                'auth_algo'         => $headers['PAYPAL-AUTH-ALGO']         ?? 'SHA256withRSA',
                'transmission_sig'  => $headers['PAYPAL-TRANSMISSION-SIG']  ?? '',
                'webhook_id'        => $webhookId,
                'webhook_event'     => json_decode($rawBody, true),
            ]);
            return ($data['verification_status'] ?? '') === 'SUCCESS';
        } catch (\Throwable $e) {
            return false;
        }
    }

    // ── Normalizar headers HTTP ───────────────────────────────────────────────
    public static function getRequestHeaders(): array
    {
        $headers = [];
        foreach ($_SERVER as $key => $val) {
            if (str_starts_with($key, 'HTTP_')) {
                $name = str_replace('_', '-', substr($key, 5));
                $headers[$name] = $val;
            }
        }
        return $headers;
    }
}
