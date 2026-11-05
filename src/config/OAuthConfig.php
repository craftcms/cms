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
    /**
     * The number of clients each IP address can register within [[registrationRateLimitDuration]], or `false` to not
     * limit registrations beyond the OAuth endpoints’ general rate limit.
     */
    public int|false $registrationRateLimit = 10;
    /** The registration rate limit window, in seconds. */
    public int $registrationRateLimitDuration = 3600;
    /** Resource definitions keyed by resource ID. */
    public array $resources = [];
}
