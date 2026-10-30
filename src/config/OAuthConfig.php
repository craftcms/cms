<?php

namespace craft\config;

/** OAuth authorization server configuration. @since 5.12.0 */
class OAuthConfig extends BaseConfig
{
    public ?string $privateKey = null;
    public ?string $publicKey = null;
    public string $accessTokenDuration = 'PT1H';
    public string $refreshTokenDuration = 'P30D';
    public string $authorizationCodeDuration = 'PT10M';
    public array $redirectDomains = ['*'];
    public array $customSchemes = [];
    /** Resource definitions keyed by resource ID. */
    public array $resources = [];
}
