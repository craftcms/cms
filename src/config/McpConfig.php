<?php

namespace craft\config;

/** MCP transport and OAuth configuration. @since 5.12.0 */
class McpConfig extends BaseConfig
{
    public string $endpoint = 'mcp';
    public string $instructions = '';
    public ?string $privateKey = null;
    public ?string $publicKey = null;
    public string $accessTokenDuration = 'PT1H';
    public string $refreshTokenDuration = 'P30D';
    public string $authorizationCodeDuration = 'PT10M';
    public array $redirectDomains = ['*'];
    public array $customSchemes = [];
    /** Yii action filter definitions applied to authenticated MCP requests. */
    public array $filters = [];

    public function endpoint(string $value): self
    {
        $this->endpoint = trim($value, '/');
        return $this;
    }

    public function instructions(string $value): self
    {
        $this->instructions = $value;
        return $this;
    }
}
