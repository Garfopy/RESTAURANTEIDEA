<?php
class FacturaloService {
    private string $baseUrl;
    private string $token;
    private string $keyPem;
    private string $cerPem;
    private string $csdPass;
    private string $rfcEmisor;
    private string $nombreEmisor;
    private string $regimenEmisor;
    private string $cpEmisor;

    public function __construct(int $empresaId) {
        $db   = Database::getInstance();
        $stmt = $db->prepare(
            'SELECT facturalo_apikey, facturalo_ambiente, facturalo_rfc, facturalo_nombre,
                    facturalo_regimen, facturalo_cp, facturalo_plantilla,
                    facturalo_key_pem, facturalo_cer_pem, facturalo_csd_pass
               FROM empresas WHERE id = ? LIMIT 1'
        );
        $stmt->execute([$empresaId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];

        $ambiente        = $row['facturalo_ambiente'] ?? 'dev';
        $this->baseUrl   = $ambiente === 'app'
            ? 'https://services.sw.com.mx'
            : 'https://services.test.sw.com.mx';
        $this->token          = (string)($row['facturalo_apikey'] ?? '');
        $this->keyPem         = (string)($row['facturalo_key_pem'] ?? '');
        $this->cerPem         = (string)($row['facturalo_cer_pem'] ?? '');
        $this->csdPass        = (string)($row['facturalo_csd_pass'] ?? '');
        $this->rfcEmisor      = (string)($row['facturalo_rfc'] ?? '');
        $this->nombreEmisor   = (string)($row['facturalo_nombre'] ?? '');
        $this->regimenEmisor  = (string)($row['facturalo_regimen'] ?? '601');
        $this->cpEmisor       = (string)($row['facturalo_cp'] ?? '76000');
    }

    public function credencialesCompletas(): bool {
        return !empty($this->token) && !empty($this->rfcEmisor);
    }

    public function generarCFDI(int $pedidoId): array {
        if (!$this->credencialesCompletas()) {
            return ['ok' => false, 'error' => 'Credenciales incompletas. Configura el token y RFC en Facturación → Configuración.'];
        }

        $db   = Database::getInstance();
        $stmt = $db->prepare(
            'SELECT p.*, u.nombre AS comprador_nombre
               FROM pedidos p
               LEFT JOIN usuarios u ON u.id = p.comprador_id
              WHERE p.id = ?'
        );
        $stmt->execute([$pedidoId]);
        $pedido = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$pedido) return ['ok' => false, 'error' => 'Pedido no encontrado'];

        $itemsStmt = $db->prepare(
            'SELECT pd.cantidad, pd.precio_unit, pd.subtotal, pr.nombre
               FROM pedido_detalle pd
               JOIN productos pr ON pr.id = pd.producto_id
              WHERE pd.pedido_id = ?'
        );
        $itemsStmt->execute([$pedidoId]);
        $items = $itemsStmt->fetchAll(PDO::FETCH_ASSOC);
        if (empty($items)) return ['ok' => false, 'error' => 'El pedido no tiene productos'];

        $subtotal  = 0.0;
        $totalIva  = 0.0;
        $conceptos = [];
        foreach ($items as $i) {
            $importe   = round((float)$i['subtotal'], 2);
            $ivaItem   = round($importe * 0.16, 2);
            $subtotal += $importe;
            $totalIva += $ivaItem;
            $conceptos[] = [
                'ClaveProdServ' => '50201506',
                'Cantidad'      => (float)$i['cantidad'],
                'ClaveUnidad'   => 'KGM',
                'Unidad'        => 'Kilogramo',
                'Descripcion'   => $i['nombre'],
                'ValorUnitario' => (float)$i['precio_unit'],
                'Importe'       => $importe,
                'ObjetoImp'     => '02',
                'Impuestos'     => [
                    'Traslados' => [[
                        'Base'       => $importe,
                        'Impuesto'   => '002',
                        'TipoFactor' => 'Tasa',
                        'TasaOCuota' => '0.160000',
                        'Importe'    => $ivaItem,
                    ]],
                ],
            ];
        }
        $subtotal = round($subtotal, 2);
        $totalIva = round($totalIva, 2);
        $total    = round($subtotal + $totalIva, 2);

        $cfdi = [
            'Version'           => '4.0',
            'Sello'             => '',
            'Certificado'       => '',
            'NoCertificado'     => '',
            'Serie'             => 'CHB',
            'Folio'             => $pedido['folio'],
            'Fecha'             => date('Y-m-d\TH:i:s'),
            'FormaPago'         => '03',
            'SubTotal'          => $subtotal,
            'Moneda'            => 'MXN',
            'Total'             => $total,
            'TipoDeComprobante' => 'I',
            'Exportacion'       => '01',
            'MetodoPago'        => 'PPD',
            'LugarExpedicion'   => $this->cpEmisor,
            'Emisor'  => [
                'Rfc'           => $this->rfcEmisor,
                'Nombre'        => $this->nombreEmisor,
                'RegimenFiscal' => $this->regimenEmisor,
            ],
            'Receptor' => [
                'Rfc'                     => 'XAXX010101000',
                'Nombre'                  => $pedido['comprador_nombre'] ?: 'Público en General',
                'DomicilioFiscalReceptor' => '06600',
                'RegimenFiscalReceptor'   => '616',
                'UsoCFDI'                 => 'G01',
            ],
            'Conceptos' => $conceptos,
            'Impuestos' => [
                'TotalImpuestosTrasladados' => $totalIva,
                'Traslados' => [[
                    'Base'       => $subtotal,
                    'Impuesto'   => '002',
                    'TipoFactor' => 'Tasa',
                    'TasaOCuota' => '0.160000',
                    'Importe'    => $totalIva,
                ]],
            ],
        ];

        $response = $this->callApi('/cfdi33/stamp/json/v4/', json_encode($cfdi, JSON_UNESCAPED_UNICODE));

        if (!$response || ($response['status'] ?? '') !== 'success') {
            $msg = $response['message'] ?? ($response['messageDetail'] ?? 'Error al timbrar con SW Sapien');
            return ['ok' => false, 'error' => $msg];
        }

        $data       = $response['data'] ?? [];
        $xmlContent = $data['cfdi'] ?? '';
        $uuid       = $data['uuid'] ?? '';

        if (!$uuid && preg_match('/UUID="([^"]+)"/i', $xmlContent, $m)) {
            $uuid = $m[1];
        }
        if (!$uuid) return ['ok' => false, 'error' => 'No se pudo extraer UUID del XML timbrado'];

        $dir = ROOT_PATH . '/public/uploads/facturas/';
        if (!is_dir($dir)) mkdir($dir, 0755, true);
        $xmlPath = 'public/uploads/facturas/' . $uuid . '.xml';
        file_put_contents(ROOT_PATH . '/' . $xmlPath, $xmlContent);

        $db->prepare(
            'INSERT INTO facturas (pedido_id, empresa_id, uuid_cfdi, xml_path, pdf_path, serie, folio_fac, monto)
             VALUES (?, ?, ?, ?, NULL, ?, ?, ?)
             ON DUPLICATE KEY UPDATE
               xml_path=VALUES(xml_path), uuid_cfdi=VALUES(uuid_cfdi)'
        )->execute([
            $pedidoId,
            $pedido['empresa_id'],
            $uuid,
            $xmlPath,
            'CHB',
            $pedido['folio'],
            $total,
        ]);

        return ['ok' => true, 'uuid' => $uuid, 'xml_path' => $xmlPath, 'pdf_path' => ''];
    }

    public function cancelarCFDI(string $uuid, string $rfcReceptor, float $total): bool {
        if (!$this->credencialesCompletas() || empty($this->keyPem) || empty($this->cerPem)) {
            return false;
        }

        // Strip PEM headers and encode as base64 for SW cancel endpoint
        $b64Key = base64_encode(base64_decode(
            preg_replace('/\s+/', '', preg_replace('/-----[^-]+-----/', '', $this->keyPem))
        ));
        $b64Cer = base64_encode(base64_decode(
            preg_replace('/\s+/', '', preg_replace('/-----[^-]+-----/', '', $this->cerPem))
        ));

        $body = json_encode([
            'rfc'              => $this->rfcEmisor,
            'password'         => $this->csdPass,
            'b64Cer'           => $b64Cer,
            'b64Key'           => $b64Key,
            'motivo'           => '02',
            'folioSustitucion' => '',
        ], JSON_UNESCAPED_UNICODE);

        $response = $this->callApi('/cfdi33/cancel/csd/' . urlencode($uuid), $body);

        $status = $response['status'] ?? '';
        if ($status === 'success') return true;

        // SW also returns success via cancelStatus codes 201/202
        $cancelStatus = $response['data']['cancelStatus'] ?? '';
        return in_array($cancelStatus, ['201', '202'], true);
    }

    public function consultarCreditos(): int {
        // SW Sapien does not expose a simple credit-count endpoint via token auth
        return -1;
    }

    private function callApi(string $path, string $jsonBody): ?array {
        if (!$this->token) return null;
        $ctx = stream_context_create([
            'http' => [
                'method'  => 'POST',
                'header'  => implode("\r\n", [
                    'Authorization: Bearer ' . $this->token,
                    'Content-Type: application/json',
                    'Accept: application/json',
                ]),
                'content' => $jsonBody,
                'timeout' => 30,
                'ignore_errors' => true,
            ],
        ]);
        $resp = @file_get_contents($this->baseUrl . $path, false, $ctx);
        if (!$resp) return null;
        return json_decode($resp, true);
    }
}
