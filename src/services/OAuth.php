<?php

namespace craft\services;

use Craft;
use craft\db\Query;
use craft\db\Table;
use craft\elements\User;
use craft\events\RegisterOAuthResourcesEvent;
use craft\helpers\App;
use craft\helpers\ArrayHelper;
use craft\helpers\Db;
use craft\helpers\FileHelper;
use craft\helpers\StringHelper;
use craft\helpers\UrlHelper;
use craft\oauth\AccessToken;
use craft\oauth\Client;
use craft\oauth\Repositories;
use craft\oauth\Resource;
use craft\oauth\Scope;
use DateInterval;
use DateTime;
use DateTimeImmutable;
use GuzzleHttp\Psr7\Uri;
use League\OAuth2\Server\AuthorizationServer;
use League\OAuth2\Server\CryptKey;
use League\OAuth2\Server\Entities\ScopeEntityInterface;
use League\OAuth2\Server\Exception\OAuthServerException;
use League\OAuth2\Server\Grant\AuthCodeGrant;
use League\OAuth2\Server\Grant\RefreshTokenGrant;
use League\OAuth2\Server\RequestTypes\AuthorizationRequestInterface;
use League\OAuth2\Server\ResourceServer;
use Psr\Http\Message\ServerRequestInterface;
use yii\base\Component;
use yii\base\InvalidArgumentException;
use yii\base\InvalidConfigException;

/** Shared OAuth authorization and resource authentication. @since 5.12.0 */
class OAuth extends Component
{
    /** @event RegisterOAuthResourcesEvent Registers protected API definitions. */
    public const EVENT_REGISTER_RESOURCES = 'registerResources';

    /** The user provider Craft 6’s Passport guard uses, which Passport matches personal access clients against. */
    private const PERSONAL_ACCESS_PROVIDER = 'craft';

    private ?array $resources = null;

    /** @return array<string,Resource> */
    public function getResources(): array
    {
        if ($this->resources === null) {
            $definitions = Craft::$app->getConfig()->getOAuth()->resources;
            $definitions['mcp'] ??= Craft::$app->get('mcp')->getOAuthResource();
            if (Craft::$app->getConfig()->getGeneral()->enableGql) {
                $definitions['graphql'] ??= Craft::$app->getGql()->getOAuthResource();
            }
            $event = new RegisterOAuthResourcesEvent(['resources' => $definitions]);
            $this->trigger(self::EVENT_REGISTER_RESOURCES, $event);
            $resources = [];
            $metadataPaths = [];
            $scopeResources = [];
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
                    // Tokens are bound to a resource by their scopes, so a scope can only belong to one resource.
                    if (isset($scopeResources[$scope])) {
                        throw new InvalidConfigException("OAuth resources $scopeResources[$scope] and $id can’t both define the $scope scope.");
                    }
                    $scopeResources[$scope] = $id;
                }
                if (!array_is_list($resource->defaultScopes) || ($resource->defaultScopes && !$resource->allowsScopes($resource->defaultScopes))) {
                    throw new InvalidConfigException('OAuth default scopes must be a subset of the resource scopes.');
                }
                if ($resource->scopeLevels && (!$resource->allowsScopes($resource->scopeLevels) || count(array_unique($resource->scopeLevels)) !== count($resource->scopeLevels))) {
                    throw new InvalidConfigException('OAuth scope levels must be a list of distinct resource scopes.');
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
     * @param array|null $scopes Set to the scope IDs the token grants.
     */
    public function authenticate(ServerRequestInterface $request, string $resource, array $requiredScopes = [], ?array &$scopes = null): User
    {
        $request = $this->resourceServer($resource)->validateAuthenticatedRequest($request);
        $granted = $request->getAttribute('oauth_scopes', []);
        // Personal access tokens can grant scopes for several resources, so only this resource’s scopes count here.
        $granted = is_array($granted) ? array_values(array_intersect($granted, array_keys($this->getResource($resource)->scopes))) : [];
        if (!$granted || array_diff($requiredScopes, $granted)) {
            throw OAuthServerException::accessDenied('The token does not grant the required scopes for this resource.');
        }

        $user = $this->authorizedUser((string)$request->getAttribute('oauth_user_id'), $resource);
        $scopes = $granted;
        return $user;
    }

    /**
     * Narrows an authorization request to the access level the user chose, falling back to the narrowest level they
     * may choose.
     */
    public function approveScopes(AuthorizationRequestInterface $request, string $resource, User $user, ?string $level): void
    {
        $definition = $this->getResource($resource);
        $choices = $definition->levelChoices($this->scopeIds($request->getScopes()), $user);
        $this->narrowRequest($request, $definition, in_array($level, $choices, true) ? $level : ($choices[0] ?? null));
    }

    /**
     * Whether the user already approved the request's scopes for its client. If so, narrows the request to the access
     * level they chose before.
     */
    public function reuseConsent(AuthorizationRequestInterface $request, string $resource): bool
    {
        $definition = $this->getResource($resource);
        $rows = (new Query())->select(['scopes'])->from(Table::OAUTH_ACCESS_TOKENS)->where([
            'client_id' => $request->getClient()->getIdentifier(),
            'user_id' => $request->getUser()->getIdentifier(),
            'revoked' => false,
        ])->andWhere(['>', 'expires_at', Db::prepareDateForDb(new DateTime())])->column();
        $granted = [];
        foreach ($rows as $scopes) {
            $scopes = json_decode($scopes, true, flags: JSON_THROW_ON_ERROR);
            if ($definition->allowsScopes($scopes)) {
                $granted = array_merge($granted, $scopes);
            }
        }

        if (!$definition->grantsCover($granted, $this->scopeIds($request->getScopes()))) {
            return false;
        }

        $level = $definition->highestLevel($granted);
        if ($level !== null) {
            $this->narrowRequest($request, $definition, $level);
        }
        return true;
    }

    private function narrowRequest(AuthorizationRequestInterface $request, Resource $definition, ?string $level): void
    {
        $scopeIds = $definition->narrowScopes($this->scopeIds($request->getScopes()), $level);
        $request->setScopes(array_values(array_filter(
            $request->getScopes(),
            fn(ScopeEntityInterface $scope) => in_array($scope->getIdentifier(), $scopeIds, true),
        )));
    }

    /**
     * @param ScopeEntityInterface[] $scopes
     * @return string[]
     */
    private function scopeIds(array $scopes): array
    {
        return array_map(fn(ScopeEntityInterface $scope) => $scope->getIdentifier(), $scopes);
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

    /**
     * Creates a personal access token for a user, like Laravel Passport’s `HasApiTokens::createToken()`.
     *
     * The token is issued through the personal access client (one that only supports the `personal_access` grant),
     * which is shared by all resources and created the first time it’s needed. The token’s name is stored with the
     * token. Its scopes can belong to several resources, and each resource only accepts the token for its own scopes.
     *
     * Use [[PersonalAccessTokens::create()]] to create tokens with the scopes a user may grant.
     *
     * @param string[] $scopes The scope IDs to grant
     * @return string The signed access token. It isn’t stored, so it can’t be retrieved again.
     * @throws InvalidArgumentException if a scope doesn’t belong to a resource, or the user may not access its resource
     * @internal
     */
    public function createPersonalAccessToken(User $user, string $name, array $scopes, DateInterval $expiresIn): string
    {
        $scopes = array_values(array_unique($scopes));
        if (!$scopes) {
            throw new InvalidArgumentException('Invalid scopes.');
        }
        $resources = [];
        foreach ($scopes as $scope) {
            $resource = ArrayHelper::firstWhere($this->getResources(), fn(Resource $resource) => isset($resource->scopes[$scope]));
            if (!$resource) {
                throw new InvalidArgumentException("Invalid scope: $scope");
            }
            $resources[$resource->id] = $resource;
        }
        try {
            foreach ($resources as $resource) {
                $this->authorizedUser((string)$user->id, $resource->id);
            }
        } catch (OAuthServerException $e) {
            throw new InvalidArgumentException($e->getMessage(), previous: $e);
        }

        // The personal access client has no `scopes`, so any resource’s repository can issue the token.
        $repository = new Repositories(reset($resources));
        $token = $repository->getNewToken($repository->getClientEntity($this->personalAccessClientId()), array_map(function(string $id) {
            $scope = new Scope();
            $scope->setIdentifier($id);
            return $scope;
        }, $scopes), (string)$user->id);
        /** @var AccessToken $token */
        $token->name = $name;
        $token->setIdentifier(bin2hex(random_bytes(40)));
        $token->setExpiryDateTime((new DateTimeImmutable())->add($expiresIn));
        $token->setPrivateKey($this->key('private'));
        $repository->persistNewAccessToken($token);

        return $token->toString();
    }

    /**
     * Returns the ID of the personal access client, creating it if it doesn’t exist yet.
     *
     * This finds the same client that Passport’s `ClientRepository::personalAccessClient()` does in Craft 6: the latest
     * unrevoked client for the `craft` user provider whose `grant_types` include `personal_access`. Its `scopes` are
     * `null`, so it can issue tokens for any resource.
     */
    private function personalAccessClientId(): string
    {
        $clients = (new Query())
            ->from(Table::OAUTH_CLIENTS)
            ->where(['revoked' => false, 'provider' => self::PERSONAL_ACCESS_PROVIDER])
            ->orderBy(['created_at' => SORT_DESC])
            ->all();
        foreach ($clients as $client) {
            if ((new Client($client))->supportsGrantType('personal_access')) {
                return $client['id'];
            }
        }

        $id = StringHelper::UUID();
        $now = Db::prepareDateForDb(new DateTime());
        Db::insert(Table::OAUTH_CLIENTS, [
            'id' => $id,
            'name' => 'Personal Access Client',
            'provider' => self::PERSONAL_ACCESS_PROVIDER,
            'redirect_uris' => '[]',
            'grant_types' => '["personal_access"]',
            'revoked' => false,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        return $id;
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
