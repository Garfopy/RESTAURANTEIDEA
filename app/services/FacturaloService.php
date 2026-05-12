<?php
class FacturaloService {
    private string $apiUrl;
    private string $apiKey;
    private string $keyPem;
    private string $cerPem;
    private string $csdPass;
    private string $rfcEmisor;
    private string $nombreEmisor;
    private string $regimenEmisor;
    private string $cpEmisor;
    private string $plantilla;

    public function __construct() {
        $db  = Database::getInstance();
        $get = function(string $k) use ($db): string {
            $s = $db->prepare('SELECT valor FROM global_settings WHERE clave = ? LIMIT 1');
            $s->execute([$k]);
            return (string)($s->fetchColumn() ?: '');
        };

        $ambiente           = $get('facturalo_ambiente') ?: 'dev';
        $this->apiUrl       = "https://{$ambiente}.facturaloplus.com/api/rest/servicio/";
        $this->apiKey       = $get('facturalo_apikey') ?: $get('facturalo_api_key') ?: $get('facturalo_token');
        $this->keyPem       = $get('facturalo_key_pem');
        $this->cerPem       = $get('facturalo_cer_pem');
        $this->csdPass      = $get('facturalo_csd_pass');
        $this->rfcEmisor    = $get('facturalo_rfc');
        $this->nombreEmisor = $get('facturalo_nombre');
        $this->regimenEmisor= $get('facturalo_regimen') ?: '601';
        $this->cpEmisor     = $get('facturalo_cp') ?: '76000';
        $this->plantilla    = $get('facturalo_plantilla') ?: '1';
    }

    public function generarCFDI(int $pedidoId): array {
        if (!$this->apiKey || !$this->keyPem || !$this->cerPem) {
            return ['ok' => false, 'error' => 'Credenciales incompletas. Configura apikey, keyPEM y cerPEM en Panel → APIs.'];
        }

        $db   = Database::getInstance();
        $stmt = $db->prepare(
            'SELECT p.*, e.razon_social, e.rfc AS rfc_empresa, e.regimen_fiscal, e.direccion_fiscal
               FROM pedidos p JOIN empresas e ON e.id = p.empresa_id WHERE p.id = ?'
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

        $rfcReceptor = $pedido['rfc_empresa'] ?: 'XAXX010101000';
        $cpReceptor  = ltrim(substr($pedido['direccion_fiscal'] ?? '', 0, 5)) ?: '76000';

        $cfdi = [
            'Version'          => '4.0',
            'Serie'            => 'CHB',
            'Folio'            => $pedido['folio'],
            'Fecha'            => date('Y-m-d\TH:i:s'),
            'FormaPago'        => '03',
            'SubTotal'         => $subtotal,
            'Moneda'           => 'MXN',
            'Total'            => $total,
            'TipoDeComprobante'=> 'I',
            'Exportacion'      => '01',
            'MetodoPago'       => 'PPD',
            'LugarExpedicion'  => $this->cpEmisor,
            'Emisor'  => [
                'Rfc'          => $this->rfcEmisor,
                'Nombre'       => $this->nombreEmisor,
                'RegimenFiscal'=> $this->regimenEmisor,
            ],
            'Receptor' => [
                'Rfc'                     => $rfcReceptor,
                'Nombre'                  => $pedido['razon_social'],
                'DomicilioFiscalReceptor' => $cpReceptor,
                'RegimenFiscalReceptor'   => $pedido['regimen_fiscal'] ?: '616',
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

        $jsonB64  = base64_encode(json_encode($cfdi, JSON_UNESCAPED_UNICODE));
        $response = $this->post('timbrarJSON2', [
            'apikey'    => $this->apiKey,
            'jsonB64'   => $jsonB64,
            'keyPEM'    => $this->keyPem,
            'cerPEM'    => $this->cerPem,
            'plantilla' => $this->plantilla,
        ]);

        if (!$response || (string)($response['code'] ?? '') !== '200') {
            return ['ok' => false, 'error' => $response['message'] ?? 'Error al timbrar'];
        }

        $data = $response['data'] ?? null;
        if (is_string($data)) $data = json_decode($data, true);
        $xmlContent = $data['XML'] ?? '';
        $pdfBase64  = $data['PDF'] ?? '';

        // Extraer UUID del XML timbrado
        $uuid = '';
        if (preg_match('/UUID="([^"]+)"/i', $xmlContent, $m)) {
            $uuid = $m[1];
        }
        if (!$uuid) return ['ok' => false, 'error' => 'No se pudo extraer UUID del XML timbrado'];

        // Guardar archivos
        $dir = ROOT_PATH . '/public/uploads/facturas/';
        if (!is_dir($dir)) mkdir($dir, 0755, true);
        $xmlPath = 'public/uploads/facturas/' . $uuid . '.xml';
        $pdfPath = '';
        file_put_contents(ROOT_PATH . '/' . $xmlPath, $xmlContent);
        if ($pdfBase64) {
            $pdfPath = 'public/uploads/facturas/' . $uuid . '.pdf';
            file_put_contents(ROOT_PATH . '/' . $pdfPath, base64_decode($pdfBase64));
        }

        // Guardar en BD con los nombres de columna reales
        $ins = $db->prepare(
            'INSERT INTO facturas (pedido_id, empresa_id, uuid_cfdi, xml_path, pdf_path, serie, folio_fac, monto)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?)
             ON DUPLICATE KEY UPDATE
               xml_path=VALUES(xml_path), pdf_path=VALUES(pdf_path), uuid_cfdi=VALUES(uuid_cfdi)'
        );
        $ins->execute([
            $pedidoId,
            $pedido['empresa_id'],
            $uuid,
            $xmlPath,
            $pdfPath ?: null,
            'CHB',
            $pedido['folio'],
            $total,
        ]);

        return ['ok' => true, 'uuid' => $uuid, 'xml_path' => $xmlPath, 'pdf_path' => $pdfPath];
    }

    public function cancelarCFDI(string $uuid, string $rfcReceptor, float $total): bool {
        if (!$this->apiKey || !$this->keyPem) return false;

        // PEM → base64 DER (para keyCSD/cerCSD)
        $keyB64 = preg_replace('/\s+/', '', preg_replace('/-----[^-]+-----/', '', $this->keyPem));
        $cerB64 = preg_replace('/\s+/', '', preg_replace('/-----[^-]+-----/', '', $this->cerPem));

        $response = $this->post('cancelar2', [
            'apikey'          => $this->apiKey,
            'keyCSD'          => $keyB64,
            'cerCSD'          => $cerB64,
            'passCSD'         => $this->csdPass,
            'uuid'            => $uuid,
            'rfcEmisor'       => $this->rfcEmisor,
            'rfcReceptor'     => $rfcReceptor,
            'total'           => $total,
            'motivo'          => '02',
            'folioSustitucion'=> '',
        ]);

        $code = (string)($response['code'] ?? '');
        return in_array($code, ['201', '202'], true);
    }

    public function consultarCreditos(): int {
        $response = $this->post('consultarCreditosDisponibles', ['apikey' => $this->apiKey]);
        return (int)($response['data'] ?? 0);
    }

    private function post(string $endpoint, array $fields): ?array {
        if (!$this->apiKey) return null;
        $ctx = stream_context_create([
            'http' => [
                'method'  => 'POST',
                'header'  => "Content-Type: application/x-www-form-urlencoded\r\n",
                'content' => http_build_query($fields),
                'timeout' => 30,
                'ignore_errors' => true,
            ],
        ]);
        $resp = @file_get_contents($this->apiUrl . $endpoint, false, $ctx);
        if (!$resp) return null;
        return json_decode($resp, true);
    }
}
