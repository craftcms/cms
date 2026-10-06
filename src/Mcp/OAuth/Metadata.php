<?php

declare(strict_types=1);

namespace CraftCms\Cms\Mcp\OAuth;

use CraftCms\Cms\Cms;
use CraftCms\Cms\Support\Env;
use Illuminate\Support\Uri;

/** @since 6.0.0 */
readonly class Metadata
{
    public const string SCOPE = 'mcp:use';

    public function baseUrl(): string
    {
        return Env::parse(Cms::config()->baseCpUrl) ?? config('app.url');
    }

    public function resource(): string
    {
        return $this->url('craft.cp.mcp.server');
    }

    public function issuer(): string
    {
        return $this->resource();
    }

    public function resourceMetadataUrl(): string
    {
        return (string) Uri::of($this->resource())
            ->withPath('/.well-known/oauth-protected-resource'.parse_url($this->resource(), PHP_URL_PATH));
    }

    /** @return array<string, mixed> */
    public function protectedResource(): array
    {
        return [
            'resource' => $this->resource(),
            'authorization_servers' => [$this->issuer()],
            'scopes_supported' => [self::SCOPE],
            'bearer_methods_supported' => ['header'],
        ];
    }

    /** @return array<string, mixed> */
    public function authorizationServer(): array
    {
        return [
            'issuer' => $this->issuer(),
            'authorization_endpoint' => $this->url('craft.cp.mcp.oauth.authorize'),
            'token_endpoint' => $this->url('passport.token'),
            'registration_endpoint' => $this->url('craft.cp.mcp.oauth.register'),
            'response_types_supported' => ['code'],
            'grant_types_supported' => ['authorization_code', 'refresh_token'],
            'scopes_supported' => [self::SCOPE],
            'code_challenge_methods_supported' => ['S256'],
        ];
    }

    private function url(string $name): string
    {
        return rtrim($this->baseUrl(), '/').'/'.ltrim(route($name, absolute: false), '/');
    }
}
