<?php

class AgRedeAuth
{
    const CONF_TOKEN_KEY = 'AGEREDE_OAUTH_ACCESS_TOKEN';
    const CONF_TS_KEY    = 'AGEREDE_OAUTH_TOKEN_TS';
    const TOKEN_TTL      = 1200; // 20 minutos
    const OAUTH_ENDPOINT_PRODUCTION = 'https://api.userede.com.br/redelabs/oauth2/token';
    const OAUTH_ENDPOINT_SANDBOX    = 'https://rl7-sandbox-api.useredecloud.com.br/oauth2/token';

    private $pv;
    private $secret;
    private $endpoint;

    public function __construct(string $pv, string $secret, bool $sandbox = false)
    {
        $this->pv = $pv;
        $this->secret = $secret;
        $this->endpoint = $sandbox ? self::OAUTH_ENDPOINT_SANDBOX : self::OAUTH_ENDPOINT_PRODUCTION;
    }

    public function getAccessToken(): string
    {
        $cached   = \Configuration::get(self::CONF_TOKEN_KEY);
        $cachedTs = (int) \Configuration::get(self::CONF_TS_KEY);
        $now      = time();

        if ($cached && $cachedTs && ($now - $cachedTs) < self::TOKEN_TTL) {
            return $cached;
        }

        $token = $this->requestNewToken();
        \Configuration::updateValue(self::CONF_TOKEN_KEY, $token);
        \Configuration::updateValue(self::CONF_TS_KEY, (string) $now);
        return $token;
    }

    private function requestNewToken(): string
    {
        $ch = curl_init($this->endpoint);

        $headers = [
            'Accept: application/json',
            'Content-Type: application/x-www-form-urlencoded',
            'Authorization: Basic ' . base64_encode($this->pv . ':' . $this->secret),
            'User-Agent: AGTIRede'
        ];

        $postData = [
            'grant_type' => 'client_credentials'
        ];

        $body = http_build_query($postData);

        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);

        $resp = curl_exec($ch);
        $status = curl_getinfo($ch, CURLINFO_HTTP_CODE) ?: 0;
        $curlError = curl_errno($ch) ? curl_error($ch) : null;
        curl_close($ch);

        $this->logOAuthAttempt($headers, $body, $status, $resp, $curlError);

        if ($curlError !== null) {
            throw new RuntimeException('Erro cURL OAuth: ' . $curlError);
        }

        if ($status < 200 || $status >= 300) {
            throw new RuntimeException('Falha ao obter token OAuth (HTTP ' . $status . '): ' . $resp);
        }

        $json = json_decode((string) $resp, true);
        if (!$json || empty($json['access_token'])) {
            throw new RuntimeException('Resposta inválida ao obter token OAuth: ' . $resp);
        }

        return $json['access_token'];
    }

    private function logOAuthAttempt(array $headers, string $body, int $status, $resp, ?string $error = null): void
    {
        try {
            $log = new AgERedeRequest();
            $log->endpoint = $this->endpoint;
            $log->headers = serialize($this->maskAuthorizationHeader($headers));
            $log->method = 'POST';
            $log->body = $this->maskClientSecret($body);
            $log->http_code = $status;
            $log->response = $error ? $error : (string) $resp;
            $log->save();
        } catch (\Exception $e) {
            // Falha de log não deve interromper fluxo
        }
    }

    private function maskClientSecret(string $body): string
    {
        return preg_replace('/(client_secret=)[^&]+/', '$1***', $body);
    }

    private function maskAuthorizationHeader(array $headers): array
    {
        foreach ($headers as &$header) {
            if (stripos($header, 'Authorization:') === 0) {
                $header = 'Authorization: Basic ***';
            }
        }
        return $headers;
    }

    public static function invalidateCache(): void
    {
        \Configuration::updateValue(self::CONF_TOKEN_KEY, '');
        \Configuration::updateValue(self::CONF_TS_KEY, '0');
    }
}
