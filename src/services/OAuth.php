<?php

namespace craft\services;

use Craft;
use craft\db\Query;
use craft\db\Table;
use craft\elements\User;
use craft\events\RegisterOAuthResourcesEvent;
use craft\helpers\App;
use craft\helpers\DateTimeHelper;
use craft\helpers\Db;
use craft\helpers\FileHelper;
use craft\helpers\StringHelper;
use craft\helpers\UrlHelper;
use craft\oauth\AccessToken;
use craft\oauth\Repositories;
use craft\oauth\Resource;
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
        if (!is_array($granted) || !$this->getResource($resource)->allowsScopes($granted) || array_diff($requiredScopes, $granted)) {
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
     * Returns the resources a user can create personal access tokens for: those that allow them, which the user is
     * permitted to access, and which have scopes the user may be granted.
     *
     * @return array<string,Resource>
     */
    public function getPersonalTokenResources(User $user): array
    {
        return array_filter($this->getResources(), function(Resource $resource) use ($user) {
            if (!$resource->personalTokens || !$this->personalTokenScopes($resource, $user)) {
                return false;
            }
            try {
                $this->authorizedUser((string)$user->id, $resource->id);
            } catch (OAuthServerException) {
                return false;
            }
            return true;
        });
    }

    /**
     * @return string[]
     */
    private function personalTokenScopes(Resource $resource, User $user): array
    {
        return $resource->scopesForUser($user, array_keys($resource->scopes));
    }

    /**
     * Creates a personal access token for a user, without an OAuth client flow.
     *
     * As with Laravel Passport, the token is issued through the resource’s personal access client (one that only
     * supports the `personal_access` grant), which is created the first time it’s needed. The token’s name is stored
     * with the token.
     *
     * @param string[] $scopes The scope IDs to grant. Access levels include the narrower levels.
     * @return string The signed access token. It isn’t stored, so it can’t be retrieved again.
     * @throws InvalidArgumentException if the resource doesn’t allow personal access tokens for the user, or the
     * scopes aren’t ones the user may be granted
     */
    public function createPersonalAccessToken(string $resource, User $user, string $name, array $scopes, DateTimeImmutable $expiresAt): string
    {
        if (!isset($this->getPersonalTokenResources($user)[$resource])) {
            throw new InvalidArgumentException("Personal access tokens aren’t available for $resource.");
        }
        $definition = $this->getResource($resource);
        if (!$scopes || array_diff($scopes, $this->personalTokenScopes($definition, $user))) {
            throw new InvalidArgumentException('Invalid scopes.');
        }
        $level = $definition->highestLevel($scopes);
        if ($level !== null) {
            $scopes = array_merge($scopes, $definition->grantedLevels($level));
        }
        $scopes = array_values(array_intersect(array_keys($definition->scopes), $scopes));

        $repository = new Repositories($definition);
        $scopes = array_map(fn(string $scope) => $repository->getScopeEntityByIdentifier($scope), $scopes);
        /** @var AccessToken $token */
        $token = $repository->getNewToken($repository->getClientEntity($this->personalAccessClientId($definition)), $scopes, (string)$user->id);
        $token->name = $name;
        $token->setIdentifier(bin2hex(random_bytes(40)));
        $token->setExpiryDateTime($expiresAt);
        $token->setPrivateKey($this->key('private'));
        $repository->persistNewAccessToken($token);

        return $token->toString();
    }

    /**
     * Returns the ID of a resource’s personal access client, creating it if it doesn’t exist yet.
     */
    private function personalAccessClientId(Resource $resource): string
    {
        $clients = (new Query())
            ->select(['id', 'grant_types'])
            ->from(Table::OAUTH_CLIENTS)
            ->where(['resource_id' => $resource->id, 'revoked' => false, 'redirect_uris' => '[]'])
            ->orderBy(['created_at' => SORT_DESC])
            ->all();
        foreach ($clients as $client) {
            if ($this->isPersonalAccessClient($client['grant_types'])) {
                return $client['id'];
            }
        }

        $id = StringHelper::UUID();
        $now = Db::prepareDateForDb(new DateTime());
        Db::insert(Table::OAUTH_CLIENTS, [
            'id' => $id,
            'resource_id' => $resource->id,
            'name' => "{$resource->getLabel()} Personal Access Client",
            'redirect_uris' => '[]',
            'grant_types' => '["personal_access"]',
            'revoked' => false,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        return $id;
    }

    private function isPersonalAccessClient(?string $grantTypes): bool
    {
        $grantTypes = json_decode($grantTypes ?? '', true);
        return is_array($grantTypes) && in_array('personal_access', $grantTypes, true);
    }

    /**
     * Returns a user’s active personal access tokens and the apps they’ve authorized for a resource.
     *
     * Personal access tokens are listed individually, with their `tokenId`. Apps are listed once per client, with a
     * `tokenId` of `null`.
     *
     * @return array{resourceId:string,clientId:string,tokenId:string|null,name:string,scopes:string[],expiresAt:DateTime,personal:bool}[]
     */
    public function getUserGrants(string $resource, User $user): array
    {
        $now = Db::prepareDateForDb(new DateTime());
        $rows = (new Query())
            ->select([
                'access.id',
                'access.client_id',
                'access.name',
                'access.scopes',
                'access.created_at',
                'access.expires_at',
                'refreshExpiresAt' => 'refresh.expires_at',
                'clientName' => 'clients.name',
                'clients.grant_types',
            ])
            ->from(['access' => Table::OAUTH_ACCESS_TOKENS])
            ->innerJoin(['clients' => Table::OAUTH_CLIENTS], '[[clients.id]] = [[access.client_id]]')
            ->leftJoin(['refresh' => Table::OAUTH_REFRESH_TOKENS], [
                'and',
                '[[refresh.access_token_id]] = [[access.id]]',
                ['refresh.revoked' => false],
                ['>', 'refresh.expires_at', $now],
            ])
            ->where([
                'access.resource_id' => $resource,
                'access.user_id' => $user->id,
                'access.revoked' => false,
                'clients.revoked' => false,
            ])
            ->andWhere([
                'or',
                ['>', 'access.expires_at', $now],
                ['not', ['refresh.id' => null]],
            ])
            ->orderBy(['access.created_at' => SORT_DESC])
            ->all();

        $grants = [];
        foreach ($rows as $row) {
            $personal = $this->isPersonalAccessClient($row['grant_types']);
            $key = $personal ? "token:{$row['id']}" : "client:{$row['client_id']}";
            $expiresAt = DateTimeHelper::toDateTime(max($row['expires_at'], $row['refreshExpiresAt'] ?? ''));
            $scopes = json_decode($row['scopes'] ?? '[]', true) ?: [];
            $grant = $grants[$key] ?? null;
            $grants[$key] = [
                'resourceId' => $resource,
                'clientId' => $row['client_id'],
                'tokenId' => $personal ? $row['id'] : null,
                'name' => $personal ? ($row['name'] ?? $row['clientName']) : $row['clientName'],
                'scopes' => array_values(array_unique(array_merge($grant['scopes'] ?? [], $scopes))),
                'expiresAt' => $grant ? max($grant['expiresAt'], $expiresAt) : $expiresAt,
                'personal' => $personal,
            ];
        }

        return array_values($grants);
    }

    /**
     * Revokes a user’s access and refresh tokens for a client, or just one of them if `$tokenId` is given (as for
     * personal access tokens).
     */
    public function revokeUserGrant(string $resource, User $user, string $clientId, ?string $tokenId = null): void
    {
        $condition = ['resource_id' => $resource, 'user_id' => $user->id, 'client_id' => $clientId];
        if ($tokenId !== null) {
            $condition['id'] = $tokenId;
        }
        $accessTokenIds = (new Query())->select('id')->from(Table::OAUTH_ACCESS_TOKENS)->where($condition);
        Db::update(Table::OAUTH_REFRESH_TOKENS, ['revoked' => true], ['access_token_id' => $accessTokenIds], updateTimestamp: false);
        Db::update(Table::OAUTH_ACCESS_TOKENS, [
            'revoked' => true,
            'updated_at' => Db::prepareDateForDb(new DateTime()),
        ], $condition, updateTimestamp: false);
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
