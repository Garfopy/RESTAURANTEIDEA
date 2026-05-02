<?php
class FacturaloService {
    private string $apiUrl = 'https://www.factura-lo.mx/api/v1';
    private string $token;
    private string $rfc;

    public function __construct() {
        $db = Database::getInstance();
        $get = fn(string $k) => $db->query("SELECT valor FROM global_settings WHERE clave = '$k' LIMIT 1")->fetchColumn() ?: '';
        $this->token = $get('facturalo_token');
        $this->rfc   = $get('facturalo_rfc');
    }

    public function generarCFDI(int $pedidoId): array {
        $db   = Database::getInstance();
        $stmt = $db->prepare(
            'SELECT p.*, e.razon_social, e.rfc, e.regimen_fiscal, e.direccion_fiscal, e.email
               FROM pedidos p JOIN empresas e ON e.id = p.empresa_id WHERE p.id = ?'
        );
        $stmt->execute([$pedidoId]);
        $pedido = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$pedido) return ['ok' => false, 'error' => 'Pedido no encontrado'];

        $detalleStmt = $db->prepare(
            'SELECT pd.*, pr.nombre FROM pedido_detalle pd JOIN productos pr ON pr.id = pd.producto_id WHERE pd.pedido_id = ?'
        );
        $detalleStmt->execute([$pedidoId]);
        $items = $detalleStmt->fetchAll(PDO::FETCH_ASSOC);

        $conceptos = array_map(fn($i) => [
            'clave_prod_serv'    => '50201506',
            'descripcion'        => $i['nombre'],
            'cantidad'           => (float)$i['cantidad'],
            'clave_unidad'       => 'KGM',
            'unidad'             => 'Kilogramo',
            'valor_unitario'     => (float)$i['precio_unitario'],
            'importe'            => (float)$i['subtotal'],
            '_traslados'         => [['base' => $i['subtotal'], 'impuesto' => '002', 'tipo_factor' => 'Tasa', 'tasa_cuota' => 0.16]],
        ], $items);

        $payload = json_encode([
            'serie'             => 'CHB',
            'folio'             => $pedido['folio'],
            'tipo_comprobante'  => 'I',
            'metodo_pago'       => 'PPD',
            'forma_pago'        => '03',
            'moneda'            => 'MXN',
            'receptor'          => [
                'rfc'                    => $pedido['rfc'],
                'nombre'                 => $pedido['razon_social'],
                'domicilio_fiscal_receptor' => substr($pedido['direccion_fiscal'] ?? '76000', 0, 5),
                'regimen_fiscal_receptor'   => $pedido['regimen_fiscal'] ?: '601',
                'uso_cfdi'               => 'G01',
            ],
            'conceptos' => $conceptos,
        ]);

        $response = $this->post('/cfdi', $payload);
        if (!$response || empty($response['uuid'])) {
            return ['ok' => false, 'error' => $response['message'] ?? 'Error al timbrar'];
        }

        $stmt = $db->prepare(
            'INSERT INTO facturas (pedido_id, empresa_id, uuid_cfdi, xml_url, pdf_url, total, fecha_emision, estado)
             VALUES (?, ?, ?, ?, ?, ?, NOW(), ?) ON DUPLICATE KEY UPDATE uuid_cfdi=VALUES(uuid_cfdi)'
        );
        $stmt->execute([
            $pedidoId, $pedido['empresa_id'],
            $response['uuid'], $response['xml_url'] ?? '', $response['pdf_url'] ?? '',
            $pedido['total'], 'timbrada'
        ]);

        return ['ok' => true, 'uuid' => $response['uuid'], 'xml_url' => $response['xml_url'] ?? '', 'pdf_url' => $response['pdf_url'] ?? ''];
    }

    public function cancelarCFDI(string $uuid): bool {
        $response = $this->post('/cfdi/' . $uuid . '/cancel', '{}');
        return !empty($response['ok']);
    }

    public function getFactura(string $uuid): array {
        return $this->get('/cfdi/' . $uuid) ?: [];
    }

    private function post(string $path, string $json): ?array {
        if (!$this->token) return null;
        $ctx = stream_context_create([
            'http' => [
                'method'  => 'POST',
                'header'  => "Content-Type: application/json\r\nAuthorization: Bearer {$this->token}",
                'content' => $json,
                'timeout' => 15,
            ]
        ]);
        $resp = @file_get_contents($this->apiUrl . $path, false, $ctx);
        return $resp ? json_decode($resp, true) : null;
    }

    private function get(string $path): ?array {
        if (!$this->token) return null;
        $ctx = stream_context_create([
            'http' => [
                'header'  => "Authorization: Bearer {$this->token}",
                'timeout' => 10,
            ]
        ]);
        $resp = @file_get_contents($this->apiUrl . $path, false, $ctx);
        return $resp ? json_decode($resp, true) : null;
    }
}
