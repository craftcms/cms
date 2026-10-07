<?php

namespace craft\mcp\oauth;

use Craft;
use craft\elements\User;
use craft\helpers\App;
use craft\helpers\FileHelper;
use craft\helpers\UrlHelper;
use DateInterval;
use GuzzleHttp\Psr7\Uri;
use League\OAuth2\Server\AuthorizationServer;
use League\OAuth2\Server\CryptKey;
use League\OAuth2\Server\Exception\OAuthServerException;
use League\OAuth2\Server\Grant\AuthCodeGrant;
use League\OAuth2\Server\Grant\RefreshTokenGrant;
use League\OAuth2\Server\ResourceServer;
use Psr\Http\Message\ServerRequestInterface;
use yii\base\InvalidConfigException;

class OAuth
{
    public const SCOPE = 'mcp:use';

    public function server(): AuthorizationServer
    {
        $config = Craft::$app->getConfig()->getMcp();
        $repository = new Repositories();
        $securityKey = Craft::$app->getConfig()->getGeneral()->securityKey;
        if ($securityKey === '') {
            throw new InvalidConfigException('A Craft security key is required for MCP OAuth.');
        }

        $server = new AuthorizationServer($repository, $repository, $repository, $this->key('private'), hash('sha256', "craft-mcp-oauth\0$securityKey"));
        $server->setDefaultScope(self::SCOPE);
        $server->revokeRefreshTokens(true);

        $code = new AuthCodeGrant($repository, $repository, new DateInterval($config->authorizationCodeDuration));
        $code->setRefreshTokenTTL(new DateInterval($config->refreshTokenDuration));
        $server->enableGrantType($code, new DateInterval($config->accessTokenDuration));

        $refresh = new RefreshTokenGrant($repository);
        $refresh->setRefreshTokenTTL(new DateInterval($config->refreshTokenDuration));
        $server->enableGrantType($refresh, new DateInterval($config->accessTokenDuration));

        return $server;
    }

    public function resourceServer(): ResourceServer
    {
        return new ResourceServer(new Repositories(), $this->key('public'));
    }

    public function authenticate(ServerRequestInterface $request): User
    {
        $request = $this->resourceServer()->validateAuthenticatedRequest($request);
        if (!in_array(self::SCOPE, $request->getAttribute('oauth_scopes', []), true)) {
            throw OAuthServerException::accessDenied('The MCP scope is required.');
        }

        return self::authorizedUser((string)$request->getAttribute('oauth_user_id'));
    }

    public static function authorizedUser(string $id): User
    {
        $user = ctype_digit($id) ? Craft::$app->getUsers()->getUserById((int)$id) : null;
        if (!$user || !$user->active || $user->suspended || $user->locked || !$user->can('accessCp') || !$user->can('useCraftMcp')) {
            throw OAuthServerException::accessDenied('This user is not permitted to use Craft MCP.');
        }

        return $user;
    }

    /** Generate the default keys explicitly, without replacing existing keys. */
    public function generateKeys(bool $force = false): void
    {
        $private = $this->keyPath('private');
        $public = $this->keyPath('public');
        if (!$force && (file_exists($private) || file_exists($public))) {
            throw new InvalidConfigException('MCP OAuth keys already exist. Use --force to replace them and invalidate existing access tokens.');
        }

        $key = openssl_pkey_new(['private_key_bits' => 4096, 'private_key_type' => OPENSSL_KEYTYPE_RSA]);
        if (!$key || !openssl_pkey_export($key, $privatePem)) {
            throw new InvalidConfigException('Could not generate the MCP OAuth signing key.');
        }
        $publicPem = openssl_pkey_get_details($key)['key'] ?? null;
        if (!$publicPem) {
            throw new InvalidConfigException('Could not extract the MCP OAuth public key.');
        }

        FileHelper::writeToFile($private, $privatePem);
        chmod($private, 0600);
        FileHelper::writeToFile($public, $publicPem);
        chmod($public, 0600);
    }

    public function resource(): string
    {
        $general = Craft::$app->getConfig()->getGeneral();
        $base = new Uri(UrlHelper::baseCpUrl());
        $script = $general->omitScriptNameInUrls ? null : (Craft::$app->getRequest()->getIsConsoleRequest() ? 'index.php' : basename(Craft::$app->getRequest()->getScriptUrl()));
        $path = implode('/', array_filter([
            trim($base->getPath(), '/'),
            $script,
            $general->cpTrigger,
            trim(Craft::$app->getConfig()->getMcp()->endpoint, '/'),
        ], static fn(?string $part): bool => $part !== null && $part !== ''));

        return (string)$base->withPath('/' . $path)->withQuery('')->withFragment('');
    }

    public function url(string $endpoint): string
    {
        return rtrim($this->resource(), '/') . '/oauth/' . $endpoint;
    }

    public function resourceMetadataUrl(): string
    {
        $resource = new Uri($this->resource());
        return (string)$resource->withPath('/.well-known/oauth-protected-resource' . $resource->getPath());
    }

    public function protectedResourceMetadata(): array
    {
        return [
            'resource' => $this->resource(),
            'authorization_servers' => [$this->resource()],
            'scopes_supported' => [self::SCOPE],
            'bearer_methods_supported' => ['header'],
        ];
    }

    public function metadata(): array
    {
        return [
            'issuer' => $this->resource(),
            'authorization_endpoint' => $this->url('authorize'),
            'token_endpoint' => $this->url('token'),
            'registration_endpoint' => $this->url('register'),
            'token_endpoint_auth_methods_supported' => ['none'],
            'response_types_supported' => ['code'],
            'grant_types_supported' => ['authorization_code', 'refresh_token'],
            'scopes_supported' => [self::SCOPE],
            'code_challenge_methods_supported' => ['S256'],
        ];
    }

    private function key(string $type): CryptKey
    {
        $value = App::parseEnv(Craft::$app->getConfig()->getMcp()->{"{$type}Key"});
        if (!$value) {
            $value = $this->keyPath($type);
        }
        if (!str_contains($value, '-----BEGIN')) {
            $value = Craft::getAlias($value);
        }

        return new CryptKey($value);
    }

    private function keyPath(string $type): string
    {
        return Craft::$app->getPath()->getStoragePath() . "/oauth-$type.key";
    }
}
