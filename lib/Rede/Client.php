<?php

class AgRedeClient
{
    private $auth;
    private $environment;

    public function __construct(AgRedeAuth $auth, AgRedeEnvironment $environment)
    {
        $this->auth = $auth;
        $this->environment = $environment;
    }

    private function request(string $method, string $path, ?array $payload = null): array
    {
        $url = rtrim($this->environment->getBaseUrl(), '/') . '/' . ltrim($path, '/');
        $ch = curl_init($url);

        $log = new AgERedeRequest();
        $log->endpoint = $url;
        $log->method = strtoupper($method);
        $log->body = serialize($this->sanitizePayloadForLog($payload));


        $headers = [
            'Accept: application/json',
            'Transaction-Response: brand-return-opened',
            'Authorization: Bearer ' . $this->auth->getAccessToken(),
            'User-Agent: AGTIRede'
        ];

        $safeHeaders = $headers;
        foreach ($safeHeaders as &$h) {
            if (stripos($h, 'Authorization:') === 0) {
                $h = 'Authorization: Bearer ***';
            }
        }
        $log->headers = serialize($safeHeaders);

        if ($payload !== null) {
            $json = json_encode($payload);
            $headers[] = 'Content-Type: application/json; charset=utf-8';
            curl_setopt($ch, CURLOPT_POSTFIELDS, $json);
        }

        switch (strtoupper($method)) {
            case 'POST':
                curl_setopt($ch, CURLOPT_POST, true);
                break;
            case 'PUT':
                curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT');
                break;
            case 'GET':
                // default
                break;
            default:
                curl_setopt($ch, CURLOPT_CUSTOMREQUEST, strtoupper($method));
        }            $log->method = strtoupper($method);


        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);

        $response = curl_exec($ch);
        $status   = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        
        $log->http_code = (int) $status;
        $log->response = $response;
        $log->save();

        if (curl_errno($ch)) {
            $err = curl_error($ch);
            curl_close($ch);
            throw new RuntimeException('Erro cURL: ' . $err);
        }

        curl_close($ch);

        $decoded = json_decode($response, true);
        if ($decoded === null && $response !== '') {
            $decoded = ['raw' => $response];
        }

        $decoded['_http_status'] = $status;

        if ($status >= 400) {
            $code = isset($decoded['returnCode']) ? (int)$decoded['returnCode'] : (int)$status;
            $message = $decoded['returnMessage'] ?? $response;
            $ex = new RuntimeException('Erro API Rede ' . $status . ': ' . $message, $code);
            throw $ex;
        }

        return $decoded;
    }

    /**
     * Remove/obfusca dados sensíveis do payload antes de salvar no log.
     * - Não salva número completo do cartão: mantém apenas cardBin (6 primeiros dígitos) e substitui cardNumber por '***'
     * - Nunca salva securityCode (CVV): substitui por '***'
     */
    private function sanitizePayloadForLog(?array $payload): ?array
    {
        if ($payload === null) {
            return null;
        }

        $sanitize = function ($data) use (&$sanitize) {
            if (!is_array($data)) {
                return $data;
            }
            $out = [];
            foreach ($data as $k => $v) {
                if (is_array($v)) {
                    $out[$k] = $sanitize($v);
                    continue;
                }
                if ($k === 'cardNumber') {
                    $digits = preg_replace('/\D/', '', (string) $v);
                    $bin = substr($digits, 0, 6);
                    // registra somente o BIN em campo próprio e obfusca o número
                    $out['cardBin'] = $bin ?: null;
                    $out[$k] = '***';
                    continue;
                }
                if ($k === 'securityCode') {
                    $out[$k] = '***';
                    continue;
                }
                $out[$k] = $v;
            }
            return $out;
        };

        return $sanitize($payload);
    }

    // ---- Operações usadas pelo módulo ----

    public function createTransaction(array $transaction): array
    {
        return $this->request('POST', 'transactions', $transaction);
    }

    public function getTransaction(string $tid): array
    {
        return $this->request('GET', 'transactions/' . $tid);
    }

    public function getTransactionByReference(string $reference): array
    {
        return $this->request('GET', 'transactions?reference=' . urlencode($reference));
    }

    public function refundTransaction(string $tid, ?int $amount = null): array
    {
        $payload = [];
        if ($amount !== null) {
            $payload['amount'] = $amount; // já em centavos se fornecido
        }
        return $this->request('POST', 'transactions/' . $tid . '/refunds', $payload ?: null);
    }

    public function captureTransaction(string $tid, int $amount): array
    {
        return $this->request('PUT', 'transactions/' . $tid, ['amount' => $amount, 'capture' => 'true']);
    }

    public function getStore() {
        return null; // compatibilidade para chamadas antigas que esperavam um objeto Store
    }
}
