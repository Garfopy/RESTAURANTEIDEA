<?php

class FacturApiService
{
    private const API_BASE = 'https://www.facturapi.io/v2';

    private \PDO $db;
    private string $secretKey;

    public function __construct(?string $secretKey = null)
    {
        $this->db = Database::getInstance();
        $this->secretKey = trim((string)($secretKey ?? $this->config('FACTURAPI_SECRET_KEY', FACTURAPI_SECRET_KEY)));
    }

    public function stampInvoiceRequest(int $solicitudId, int $empresaId, array $options = []): array
    {
        if ($this->secretKey === '') {
            throw new \RuntimeException('FACTURAPI_SECRET_KEY no esta configurada.');
        }

        $row = $this->findRequestForEmpresa($solicitudId, $empresaId);
        if (!$row) {
            throw new \RuntimeException('Solicitud de factura no encontrada.');
        }

        $payload = $this->buildInvoicePayload($row, $options);
        $invoice = $this->requestJson('POST', '/invoices', $payload);
        $invoiceId = (string)($invoice['id'] ?? '');
        if ($invoiceId === '') {
            throw new \RuntimeException('FacturAPI no regreso id de factura.');
        }

        $pdfUrl = $this->downloadInvoiceFile($invoiceId, 'pdf', $solicitudId);
        $xmlUrl = $this->downloadInvoiceFile($invoiceId, 'xml', $solicitudId);

        $uuid = (string)($invoice['uuid'] ?? $invoice['cfdi_uuid'] ?? '');
        $status = (string)($invoice['status'] ?? 'valid');
        $livemode = !empty($invoice['livemode']) ? 1 : 0;

        $this->markStamped($solicitudId, (int)$row['restaurante_id'], [
            'cfdi_uuid' => $uuid,
            'pdf_url' => $pdfUrl,
            'xml_url' => $xmlUrl,
            'facturapi_invoice_id' => $invoiceId,
            'facturapi_status' => $status,
            'facturapi_livemode' => $livemode,
        ]);

        return [
            'invoice' => $invoice,
            'pdf_url' => $pdfUrl,
            'xml_url' => $xmlUrl,
        ];
    }

    private function findRequestForEmpresa(int $solicitudId, int $empresaId): ?array
    {
        $stmt = $this->db->prepare(
            "SELECT fs.*,
                    fs.receptor_nombre AS receptor_nombre_fiscal,
                    fs.uso_cfdi AS receptor_uso_cfdi,
                    r.nombre AS restaurante_nombre
               FROM facturacion_solicitudes fs
               JOIN rest_restaurantes r ON r.id = fs.restaurante_id
              WHERE fs.id = ? AND r.empresa_id = ?
              LIMIT 1"
        );
        $stmt->execute([$solicitudId, $empresaId]);
        $row = $stmt->fetch(\PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    private function buildInvoicePayload(array $row, array $options): array
    {
        $taxRate = isset($options['tax_rate'])
            ? (float)$options['tax_rate']
            : (float)$this->config('FACTURAPI_TAX_RATE', (string)FACTURAPI_TAX_RATE);
        $taxIncluded = array_key_exists('tax_included', $options)
            ? filter_var($options['tax_included'], FILTER_VALIDATE_BOOLEAN)
            : filter_var($this->config('FACTURAPI_TAX_INCLUDED', FACTURAPI_TAX_INCLUDED ? 'true' : 'false'), FILTER_VALIDATE_BOOLEAN);
        $total = max(0.01, (float)$row['monto']);

        return [
            'customer' => [
                'legal_name' => (string)$row['receptor_nombre_fiscal'],
                'tax_id' => strtoupper((string)$row['receptor_rfc']),
                'tax_system' => (string)$row['receptor_regimen_fiscal'],
                'email' => (string)$row['receptor_email'],
                'address' => [
                    'zip' => (string)$row['receptor_codigo_postal'],
                ],
            ],
            'items' => [[
                'quantity' => 1,
                'product' => [
                    'description' => trim((string)($options['description'] ?? 'Consumo en restaurante')),
                    'product_key' => (string)$this->config('FACTURAPI_PRODUCT_KEY', FACTURAPI_PRODUCT_KEY),
                    'unit_key' => (string)$this->config('FACTURAPI_UNIT_KEY', FACTURAPI_UNIT_KEY),
                    'price' => $total,
                    'tax_included' => $taxIncluded,
                    'taxes' => [[
                        'type' => 'IVA',
                        'rate' => $taxRate,
                    ]],
                ],
            ]],
            'payment_form' => $this->paymentForm($options['payment_form'] ?? null, $row['metodo_pago'] ?? null),
            'payment_method' => 'PUE',
            'use' => trim((string)($options['use'] ?? $row['receptor_uso_cfdi'] ?? 'G03')) ?: 'G03',
        ];
    }

    private function paymentForm(?string $override, ?string $metodoPago): string
    {
        $override = trim((string)$override);
        if ($override !== '') {
            return $override;
        }

        return match (strtolower(trim((string)$metodoPago))) {
            'card', 'tarjeta', 'stripe' => '04',
            'cash', 'efectivo' => '01',
            'transfer', 'transferencia', 'spei' => '03',
            default => '99',
        };
    }

    private function requestJson(string $method, string $path, ?array $body = null): array
    {
        $ch = curl_init(self::API_BASE . $path);
        $headers = [
            'Authorization: Bearer ' . $this->secretKey,
            'Accept: application/json',
        ];
        curl_setopt_array($ch, [
            CURLOPT_CUSTOMREQUEST => $method,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 45,
            CURLOPT_CONNECTTIMEOUT => 10,
            CURLOPT_HTTPHEADER => $headers,
        ]);
        if ($body !== null) {
            $headers[] = 'Content-Type: application/json';
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($body));
        }

        $response = curl_exec($ch);
        $httpCode = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        if ($error) {
            throw new \RuntimeException('FacturAPI: ' . $error);
        }

        $data = json_decode((string)$response, true);
        if ($httpCode < 200 || $httpCode >= 300) {
            $message = is_array($data)
                ? (string)($data['message'] ?? $data['error'] ?? json_encode($data))
                : trim((string)$response);
            throw new \RuntimeException('FacturAPI HTTP ' . $httpCode . ': ' . mb_substr($message, 0, 220));
        }

        return is_array($data) ? $data : [];
    }

    private function downloadInvoiceFile(string $invoiceId, string $format, int $solicitudId): string
    {
        $ch = curl_init(self::API_BASE . '/invoices/' . rawurlencode($invoiceId) . '/' . $format);
        curl_setopt_array($ch, [
            CURLOPT_HTTPHEADER => ['Authorization: Bearer ' . $this->secretKey],
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 45,
            CURLOPT_CONNECTTIMEOUT => 10,
        ]);
        $contents = curl_exec($ch);
        $httpCode = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        if ($error) {
            throw new \RuntimeException('Descarga ' . strtoupper($format) . ': ' . $error);
        }
        if ($httpCode < 200 || $httpCode >= 300 || $contents === false || $contents === '') {
            throw new \RuntimeException('FacturAPI no permitio descargar ' . strtoupper($format) . ' (HTTP ' . $httpCode . ').');
        }

        $dir = rtrim(UPLOAD_PATH, '/\\') . DIRECTORY_SEPARATOR . 'facturas';
        if (!is_dir($dir) && !mkdir($dir, 0775, true) && !is_dir($dir)) {
            throw new \RuntimeException('No se pudo crear public/uploads/facturas.');
        }

        $filename = 'factura_' . $solicitudId . '_' . date('YmdHis') . '.' . $format;
        $path = $dir . DIRECTORY_SEPARATOR . $filename;
        if (file_put_contents($path, $contents) === false) {
            throw new \RuntimeException('No se pudo guardar ' . strtoupper($format) . ' de la factura.');
        }

        return rtrim(BASE_URL, '/') . '/public/uploads/facturas/' . $filename;
    }

    private function markStamped(int $solicitudId, int $restauranteId, array $data): void
    {
        $sets = [
            'estado = ?',
            'cfdi_uuid = ?',
            'pdf_url = ?',
            'xml_url = ?',
            'notas = ?',
            'facturada_at = COALESCE(facturada_at, NOW())',
        ];
        $params = [
            'facturada',
            $data['cfdi_uuid'] ?: $data['facturapi_invoice_id'],
            $data['pdf_url'],
            $data['xml_url'],
            'Timbrada con FacturAPI.',
        ];

        foreach (['facturapi_invoice_id', 'facturapi_status', 'facturapi_livemode'] as $column) {
            if ($this->hasColumn('facturacion_solicitudes', $column)) {
                $sets[] = $column . ' = ?';
                $params[] = $data[$column];
            }
        }

        $params[] = $solicitudId;
        $params[] = $restauranteId;
        $stmt = $this->db->prepare(
            'UPDATE facturacion_solicitudes SET ' . implode(', ', $sets) . ' WHERE id = ? AND restaurante_id = ?'
        );
        $stmt->execute($params);
    }

    private function config(string $key, $default = ''): string
    {
        $env = getenv($key);
        if ($env !== false && $env !== '') {
            return (string)$env;
        }

        try {
            $stmt = $this->db->prepare('SELECT valor FROM global_settings WHERE clave = ? LIMIT 1');
            $stmt->execute([$key]);
            $value = $stmt->fetchColumn();
            if ($value !== false && $value !== null && $value !== '') {
                return (string)$value;
            }
        } catch (\Throwable $e) {
            // Configuracion en BD es opcional.
        }

        return (string)$default;
    }

    private function hasColumn(string $table, string $column): bool
    {
        $stmt = $this->db->prepare(
            "SELECT COUNT(*)
               FROM information_schema.columns
              WHERE table_schema = DATABASE()
                AND table_name = ?
                AND column_name = ?"
        );
        $stmt->execute([$table, $column]);
        return (int)$stmt->fetchColumn() > 0;
    }
}
