<?php

namespace craft\services;

use Craft;
use craft\elements\User;
use craft\events\RegisterOAuthResourcesEvent;
use craft\helpers\App;
use craft\helpers\FileHelper;
use craft\helpers\UrlHelper;
use craft\oauth\Repositories;
use craft\oauth\Resource;
use DateInterval;
use GuzzleHttp\Psr7\Uri;
use League\OAuth2\Server\AuthorizationServer;
use League\OAuth2\Server\CryptKey;
use League\OAuth2\Server\Exception\OAuthServerException;
use League\OAuth2\Server\Grant\AuthCodeGrant;
use League\OAuth2\Server\Grant\RefreshTokenGrant;
use League\OAuth2\Server\ResourceServer;
use Psr\Http\Message\ServerRequestInterface;
use yii\base\Component;
use yii\base\InvalidConfigException;

/** Shared OAuth authorization and resource authentication. @since 5.12.0 */
class OAuth extends Component
{
    /** @event RegisterOAuthResourcesEvent Registers protected API definitions. */
    public const EVENT_REGISTER_RESOURCES = 'registerResources';

    private ?array $resources = null;

    /** @return array<string,Resource> */
    public function getResources(): array
    {
        if ($this->resources === null) {
            $definitions = Craft::$app->getConfig()->getOAuth()->resources;
            $definitions['mcp'] ??= Craft::$app->get('mcp')->getOAuthResource();
            $event = new RegisterOAuthResourcesEvent(['resources' => $definitions]);
            $this->trigger(self::EVENT_REGISTER_RESOURCES, $event);
            $resources = [];
            $metadataPaths = [];
            foreach ($event->resources as $id => $definition) {
                if (!is_string($id) || !preg_match('/^[a-zA-Z0-9_-]{1,64}$/D', $id)) {
                    throw new InvalidConfigException('OAuth resource IDs must contain 1–64 letters, numbers, underscores, or hyphens.');
                }
                $resource = $definition instanceof Resource ? $definition : new Resource($definition + ['id' => $id]);
                if (!isset($resource->id) || $resource->id !== $id || !$resource->scopes) {
                    throw new InvalidConfigException('Each OAuth resource must define its allowed scopes.');
                }
                foreach ($resource->scopes as $scope => $description) {
                    if (!is_string($scope) || !preg_match('/^[\x21\x23-\x5b\x5d-\x7e]+$/D', $scope) || !is_string($description) || trim($description) === '') {
                        throw new InvalidConfigException('OAuth scopes require valid IDs and consent descriptions.');
                    }
                }
                if (!array_is_list($resource->defaultScopes) || ($resource->defaultScopes && !$resource->allowsScopes($resource->defaultScopes))) {
                    throw new InvalidConfigException('OAuth default scopes must be a subset of the resource scopes.');
                }
                $url = isset($resource->resourceUrl) ? $resource->getResourceUrl() : '';
                $uri = new Uri($url);
                if (!filter_var($url, FILTER_VALIDATE_URL) || !in_array($uri->getScheme(), ['http', 'https'], true) || $uri->getFragment() !== '' || $uri->getUserInfo() !== '') {
                    throw new InvalidConfigException("OAuth resource $id requires an absolute HTTP or HTTPS URL without credentials or a fragment.");
                }
                if (in_array($uri->getPath(), $metadataPaths, true)) {
                    throw new InvalidConfigException('OAuth resources require distinct paths for protected-resource discovery.');
                }
                $metadataPaths[] = $uri->getPath();
                $resources[$id] = $resource;
            }
            $this->resources = $resources;
        }

        return $this->resources;
    }

    public function getResource(string $id): Resource
    {
        return $this->getResources()[$id] ?? throw new \yii\web\NotFoundHttpException('Unknown OAuth resource.');
    }

    public function server(string $resource): AuthorizationServer
    {
        $config = Craft::$app->getConfig()->getOAuth();
        $repository = new Repositories($this->getResource($resource));
        $securityKey = Craft::$app->getConfig()->getGeneral()->securityKey;
        if ($securityKey === '') {
            throw new InvalidConfigException('A Craft security key is required for OAuth.');
        }

        $server = new AuthorizationServer($repository, $repository, $repository, $this->key('private'), hash('sha256', "craft-oauth\0$securityKey"));
        $server->setDefaultScope(implode(' ', $this->getResource($resource)->defaultScopes));
        $server->revokeRefreshTokens(true);

        $code = new AuthCodeGrant($repository, $repository, new DateInterval($config->authorizationCodeDuration));
        $code->setRefreshTokenTTL(new DateInterval($config->refreshTokenDuration));
        $server->enableGrantType($code, new DateInterval($config->accessTokenDuration));

        $refresh = new RefreshTokenGrant($repository);
        $refresh->setRefreshTokenTTL(new DateInterval($config->refreshTokenDuration));
        $server->enableGrantType($refresh, new DateInterval($config->accessTokenDuration));

        return $server;
    }

    public function resourceServer(string $resource): ResourceServer
    {
        return new ResourceServer(new Repositories($this->getResource($resource)), $this->key('public'));
    }

    /**
     * Authenticates a resource's bearer token and requires every supplied scope.
     * @param string[] $requiredScopes
     */
    public function authenticate(ServerRequestInterface $request, string $resource, array $requiredScopes = []): User
    {
        $request = $this->resourceServer($resource)->validateAuthenticatedRequest($request);
        $scopes = $request->getAttribute('oauth_scopes', []);
        if (!is_array($scopes) || !$this->getResource($resource)->allowsScopes($scopes) || array_diff($requiredScopes, $scopes)) {
            throw OAuthServerException::accessDenied('The token does not grant the required scopes for this resource.');
        }

        return $this->authorizedUser((string)$request->getAttribute('oauth_user_id'), $resource);
    }

    public function authorizedUser(string $id, string $resource): User
    {
        $user = ctype_digit($id) ? Craft::$app->getUsers()->getUserById((int)$id) : null;
        if (!$user || !$user->active || $user->suspended || $user->locked) {
            throw OAuthServerException::accessDenied('This user is not permitted to access this resource.');
        }

        foreach ($this->getResource($resource)->permissions as $permission) {
            if (!$user->can($permission)) {
                throw OAuthServerException::accessDenied('This user is not permitted to access this resource.');
            }
        }

        return $user;
    }

    /** Generate the default keys explicitly, without replacing existing keys. */
    public function generateKeys(bool $force = false): void
    {
        $private = $this->keyPath('private');
        $public = $this->keyPath('public');
        if (!$force && (file_exists($private) || file_exists($public))) {
            throw new InvalidConfigException('OAuth keys already exist. Use --force to replace them and invalidate existing access tokens.');
        }

        $key = openssl_pkey_new(['private_key_bits' => 4096, 'private_key_type' => OPENSSL_KEYTYPE_RSA]);
        if (!$key || !openssl_pkey_export($key, $privatePem)) {
            throw new InvalidConfigException('Could not generate the OAuth signing key.');
        }
        $publicPem = openssl_pkey_get_details($key)['key'] ?? null;
        if (!$publicPem) {
            throw new InvalidConfigException('Could not extract the OAuth public key.');
        }

        FileHelper::writeToFile($private, $privatePem);
        chmod($private, 0600);
        FileHelper::writeToFile($public, $publicPem);
        chmod($public, 0600);
    }

    public function resource(string $resource): string
    {
        return $this->getResource($resource)->getResourceUrl();
    }

    public function issuer(string $resource): string
    {
        return $this->url('', $resource);
    }

    public function url(string $endpoint, string $resource): string
    {
        $this->getResource($resource);
        return UrlHelper::removeParam(UrlHelper::cpUrl("oauth/$resource/$endpoint"), 'site');
    }

    public function resourceMetadataUrl(string $resource): string
    {
        $uri = new Uri($this->resource($resource));
        return (string)$uri->withPath('/.well-known/oauth-protected-resource' . $uri->getPath())->withQuery('')->withFragment('');
    }

    public function protectedResourceMetadata(string $resource): array
    {
        return [
            'resource' => $this->resource($resource),
            'authorization_servers' => [$this->issuer($resource)],
            'scopes_supported' => array_keys($this->getResource($resource)->scopes),
            'bearer_methods_supported' => ['header'],
        ];
    }

    public function metadata(string $resource): array
    {
        return [
            'issuer' => $this->issuer($resource),
            'authorization_endpoint' => $this->url('authorize', $resource),
            'token_endpoint' => $this->url('token', $resource),
            'registration_endpoint' => $this->url('register', $resource),
            'token_endpoint_auth_methods_supported' => ['none'],
            'response_types_supported' => ['code'],
            'grant_types_supported' => ['authorization_code', 'refresh_token'],
            'scopes_supported' => array_keys($this->getResource($resource)->scopes),
            'code_challenge_methods_supported' => ['S256'],
        ];
    }

    private function key(string $type): CryptKey
    {
        $value = App::parseEnv(Craft::$app->getConfig()->getOAuth()->{"{$type}Key"});
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
