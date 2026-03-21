<?php

class AgRedeEnvironment
{
    private $baseUrl;
    private $sessionId;

    private function __construct(string $baseUrl, ?string $sessionId = null)
    {
        $this->baseUrl = rtrim($baseUrl, '/');
        $this->sessionId = $sessionId;
    }

    public static function production(string $sessionId = null): self
    {
        // Nova API v2 (produção oficial)
        return new self('https://api.userede.com.br/erede/v2', $sessionId);
    }

    public static function sandbox(string $sessionId = null): self
    {
        // Nova API v2
        return new self('https://sandbox-erede.useredecloud.com.br/v2', $sessionId);
    }

    public function getBaseUrl(): string
    {
        return $this->baseUrl;
    }

    public function getSessionId(): ?string
    {
        return $this->sessionId;
    }
}
