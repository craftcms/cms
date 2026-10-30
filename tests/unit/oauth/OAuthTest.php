<?php

namespace crafttests\unit\oauth;

use Craft;
use craft\config\GeneralConfig;
use craft\config\OAuthConfig;
use craft\db\Query;
use craft\db\Table;
use craft\elements\User;
use craft\oauth\Resource;
use craft\oauth\User as OAuthUser;
use craft\services\Mcp;
use craft\services\OAuth;
use craft\services\Sites;
use craft\web\Request;
use GuzzleHttp\Psr7\Response;
use GuzzleHttp\Psr7\ServerRequest;
use League\OAuth2\Server\Exception\OAuthServerException;
use PHPUnit\Framework\TestCase;
use yii\base\InvalidConfigException;
use yii\console\Application;
use yii\db\Transaction;

/** Exercises the real League grants and Craft repositories against the configured test database. */
class OAuthTest extends TestCase
{
    private mixed $originalApp;
    private array $originalAliases;
    private string $originalTimeZone;
    private OAuthTestApplication $app;
    private OAuth $oauth;
    private Transaction $transaction;

    protected function setUp(): void
    {
        $this->originalApp = Craft::$app;
        $this->originalAliases = Craft::$aliases;
        $this->originalTimeZone = date_default_timezone_get();
        $config = new OAuthConfig([
            'privateKey' => null,
            'publicKey' => null,
            'accessTokenDuration' => 'PT1H',
            'refreshTokenDuration' => 'P30D',
            'authorizationCodeDuration' => 'PT10M',
            'resources' => [
                'mcp' => new Resource(['resourceUrl' => 'https://cms.example/admin/mcp'] + get_object_vars((new Mcp())->getOAuthResource())),
                'api' => ['scopes' => ['api:read' => 'Read content', 'api:write' => 'Change content'], 'defaultScopes' => ['api:read'], 'permissions' => ['useApi'], 'resourceUrl' => 'https://api.example/content', 'consentDescription' => 'Read content'],
            ],
        ]);
        $key = openssl_pkey_new(['private_key_bits' => 2048]);
        openssl_pkey_export($key, $config->privateKey);
        $config->publicKey = openssl_pkey_get_details($key)['key'];
        $generalConfig = new GeneralConfig(['securityKey' => 'oauth-integration-test-security-key', 'enableGql' => false, 'baseCpUrl' => 'https://cms.example', 'cpTrigger' => 'admin', 'omitScriptNameInUrls' => true]);
        $db = Craft::$app->getDb();
        $userId = (new Query())->select('id')->from(Table::USERS)->scalar($db);
        $this->transaction = $db->beginTransaction();
        $this->app = new OAuthTestApplication(['id' => 'oauth-test', 'basePath' => __DIR__, 'components' => ['db' => $db]]);
        $this->app->oauthConfig = $config;
        $this->app->generalConfig = $generalConfig;
        $this->app->identity = new OAuthTestUser(['id' => (int)$userId, 'active' => true]);
        foreach (['mcp' => '["mcp:read","mcp:write","mcp:admin"]', 'api' => '["api:read","api:write"]'] as $resource => $scopes) {
            $db->createCommand()->insert(Table::OAUTH_CLIENTS, ['id' => 'test-' . $resource, 'name' => 'Test client', 'redirect_uris' => '["https://client.example/callback"]', 'grant_types' => '["authorization_code","refresh_token"]', 'scopes' => $scopes, 'revoked' => false])->execute();
        }
        $this->oauth = new OAuth();
        $this->app->set('oauth', $this->oauth);
    }

    protected function tearDown(): void
    {
        $this->transaction->rollBack();
        Craft::$app = $this->originalApp;
        Craft::$aliases = $this->originalAliases;
        date_default_timezone_set($this->originalTimeZone);
    }

    public function testPkceExchangeRotationAndRevocation(): void
    {
        $verifier = str_repeat('a', 43);
        $code = $this->authorize($verifier);
        $tokens = $this->exchange(['grant_type' => 'authorization_code', 'code' => $code, 'redirect_uri' => 'https://client.example/callback', 'code_verifier' => $verifier]);
        self::assertGreaterThanOrEqual(3599, $tokens['expires_in']);
        self::assertLessThanOrEqual(3600, $tokens['expires_in']);
        self::assertSame($this->app->identity->id, $this->authenticate($tokens['access_token'])->id);
        $this->expectOAuthError(fn() => $this->exchange(['grant_type' => 'authorization_code', 'code' => $code, 'redirect_uri' => 'https://client.example/callback', 'code_verifier' => $verifier]), 'invalid_grant');

        $fresh = $this->exchange(['grant_type' => 'refresh_token', 'refresh_token' => $tokens['refresh_token']]);
        self::assertSame($this->app->identity->id, $this->authenticate($fresh['access_token'])->id);
        $this->expectOAuthError(fn() => $this->authenticate($tokens['access_token']), 'access_denied');
        $this->expectOAuthError(fn() => $this->exchange(['grant_type' => 'refresh_token', 'refresh_token' => $tokens['refresh_token']]), 'invalid_grant');

        $this->app->identity->suspended = true;
        $this->expectOAuthError(fn() => $this->authenticate($fresh['access_token']), 'access_denied');
        $this->expectOAuthError(fn() => $this->exchange(['grant_type' => 'refresh_token', 'refresh_token' => $fresh['refresh_token']]), 'access_denied');
    }

    public function testWrongVerifierCannotConsumeAuthorizationCode(): void
    {
        $verifier = str_repeat('b', 43);
        $code = $this->authorize($verifier);
        $params = ['grant_type' => 'authorization_code', 'code' => $code, 'redirect_uri' => 'https://client.example/callback'];
        $this->expectOAuthError(fn() => $this->exchange($params + ['code_verifier' => str_repeat('c', 43)]), 'invalid_grant');
        $tokens = $this->exchange($params + ['code_verifier' => $verifier]);
        self::assertSame($this->app->identity->id, $this->authenticate($tokens['access_token'])->id);
    }

    public function testCredentialsCannotCrossResourceBoundaries(): void
    {
        $verifier = str_repeat('d', 43);
        $code = $this->authorize($verifier);
        $parameters = ['grant_type' => 'authorization_code', 'code' => $code, 'redirect_uri' => 'https://client.example/callback', 'code_verifier' => $verifier];
        $this->expectOAuthError(fn() => $this->exchange($parameters, 'api'), 'invalid_grant');

        $tokens = $this->exchange($parameters);
        $this->expectOAuthError(fn() => $this->authenticate($tokens['access_token'], 'api'), 'access_denied');
        $this->expectOAuthError(fn() => $this->exchange(['grant_type' => 'refresh_token', 'refresh_token' => $tokens['refresh_token']], 'api'), 'invalid_grant');

        $fresh = $this->exchange(['grant_type' => 'refresh_token', 'refresh_token' => $tokens['refresh_token']]);
        self::assertSame($this->app->identity->id, $this->authenticate($fresh['access_token'])->id);
    }

    public function testClientsCanOnlyRequestTheirScopes(): void
    {
        // The MCP client can’t use the API resource’s endpoints at all.
        $request = (new ServerRequest('GET', $this->oauth->url('authorize', 'api')))->withQueryParams(['response_type' => 'code', 'client_id' => 'test-mcp', 'redirect_uri' => 'https://client.example/callback', 'scope' => 'api:read']);
        $this->expectOAuthError(fn() => $this->oauth->server('api')->validateAuthorizationRequest($request), 'invalid_client');

        // A client limited to some of a resource’s scopes can’t be granted the others.
        $this->app->getDb()->createCommand()->update(Table::OAUTH_CLIENTS, ['scopes' => '["api:read"]'], ['id' => 'test-api'])->execute();
        self::assertSame(['api:read'], $this->tokenScopes($this->grant('api', 'api:read')['access_token']));
        $verifier = str_repeat('h', 43);
        $code = $this->authorize($verifier, 'api', 'api:read api:write');
        $this->expectOAuthError(fn() => $this->exchange(['grant_type' => 'authorization_code', 'code' => $code, 'redirect_uri' => 'https://client.example/callback', 'code_verifier' => $verifier], 'api'), 'invalid_scope');

        // Like Passport, a client without scopes may request any of them.
        $this->app->getDb()->createCommand()->update(Table::OAUTH_CLIENTS, ['scopes' => null], ['id' => 'test-api'])->execute();
        self::assertEqualsCanonicalizing(['api:read', 'api:write'], $this->tokenScopes($this->grant('api', 'api:read api:write')['access_token']));
    }

    public function testScopesMustBeUniqueAcrossResources(): void
    {
        $this->app->oauthConfig->resources['api']['scopes']['mcp:read'] = 'Read MCP';
        $this->expectException(InvalidConfigException::class);
        $this->oauth->getResources();
    }

    public function testResourcePermissionChangesDoNotRequireControlPanelAccess(): void
    {
        $this->app->identity->permissions = ['useApi'];
        $verifier = str_repeat('e', 43);
        $code = $this->authorize($verifier, 'api');
        $apiTokens = $this->exchange(['grant_type' => 'authorization_code', 'code' => $code, 'redirect_uri' => 'https://client.example/callback', 'code_verifier' => $verifier], 'api');
        self::assertSame($this->app->identity->id, $this->authenticate($apiTokens['access_token'], 'api')->id);

        $this->app->identity->permissions = ['useApi'];
        $code = $this->authorize($verifier);
        $mcpTokens = $this->exchange(['grant_type' => 'authorization_code', 'code' => $code, 'redirect_uri' => 'https://client.example/callback', 'code_verifier' => $verifier]);
        $this->app->identity->permissions = [];

        $this->expectOAuthError(fn() => $this->authenticate($apiTokens['access_token'], 'api'), 'access_denied');
        $this->expectOAuthError(fn() => $this->exchange(['grant_type' => 'refresh_token', 'refresh_token' => $apiTokens['refresh_token']], 'api'), 'access_denied');
        self::assertSame($this->app->identity->id, $this->authenticate($mcpTokens['access_token'])->id);
    }

    public function testStoredScopesMustMatchTheProtectedResource(): void
    {
        $verifier = str_repeat('f', 43);
        $code = $this->authorize($verifier);
        $tokens = $this->exchange(['grant_type' => 'authorization_code', 'code' => $code, 'redirect_uri' => 'https://client.example/callback', 'code_verifier' => $verifier]);
        $this->app->getDb()->createCommand()->update(Table::OAUTH_ACCESS_TOKENS, ['scopes' => '["api:read"]'])->execute();

        $this->expectOAuthError(fn() => $this->authenticate($tokens['access_token']), 'access_denied');
        $this->expectOAuthError(fn() => $this->exchange(['grant_type' => 'refresh_token', 'refresh_token' => $tokens['refresh_token']]), 'invalid_grant');
    }

    public function testClientsCanOnlyUseTheirGrantTypes(): void
    {
        $tokens = $this->grant('mcp');
        $this->app->getDb()->createCommand()->update(Table::OAUTH_CLIENTS, ['grant_types' => '["authorization_code"]'], ['id' => 'test-mcp'])->execute();

        $this->expectOAuthError(fn() => $this->exchange(['grant_type' => 'refresh_token', 'refresh_token' => $tokens['refresh_token']]), 'unauthorized_client');
    }

    /**
     * Craft 6 adopts these tables with Laravel Passport, so they must keep Passport 13’s columns (plus the optional
     * `oauth_clients.scopes` column Passport supports).
     */
    public function testTablesHavePassportColumns(): void
    {
        $passport = [
            Table::OAUTH_CLIENTS => ['id', 'owner_type', 'owner_id', 'name', 'secret', 'provider', 'redirect_uris', 'grant_types', 'scopes', 'revoked', 'created_at', 'updated_at'],
            Table::OAUTH_ACCESS_TOKENS => ['id', 'user_id', 'client_id', 'name', 'scopes', 'revoked', 'created_at', 'updated_at', 'expires_at'],
            Table::OAUTH_AUTH_CODES => ['id', 'user_id', 'client_id', 'scopes', 'revoked', 'expires_at'],
            Table::OAUTH_REFRESH_TOKENS => ['id', 'access_token_id', 'revoked', 'expires_at'],
        ];

        foreach ($passport as $table => $columns) {
            $actual = $this->app->getDb()->getTableSchema($table, true)->columnNames;
            self::assertSame([], array_values(array_diff($columns, $actual)), "$table is missing Passport columns.");
            self::assertSame([], array_values(array_diff($actual, $columns)), "$table has columns Passport doesn’t.");
        }
    }

    public function testRequestedScopeSubsetControlsProtectedOperations(): void
    {
        $tokens = $this->grant('api', 'api:read');
        self::assertSame(['api:read'], $this->tokenScopes($tokens['access_token']));
        self::assertSame($this->app->identity->id, $this->authenticate($tokens['access_token'], 'api', ['api:read'])->id);
        $this->expectOAuthError(fn() => $this->authenticate($tokens['access_token'], 'api', ['api:write']), 'access_denied');
    }

    public function testRefreshPreservesScopesAndCanOnlyNarrowThem(): void
    {
        $tokens = $this->grant('api', 'api:read api:write');
        self::assertEqualsCanonicalizing(['api:read', 'api:write'], $this->tokenScopes($tokens['access_token']));
        self::assertSame($this->app->identity->id, $this->authenticate($tokens['access_token'], 'api', ['api:read', 'api:write'])->id);

        $preserved = $this->exchange(['grant_type' => 'refresh_token', 'refresh_token' => $tokens['refresh_token']], 'api');
        self::assertEqualsCanonicalizing(['api:read', 'api:write'], $this->tokenScopes($preserved['access_token']));
        $narrowed = $this->exchange(['grant_type' => 'refresh_token', 'refresh_token' => $preserved['refresh_token'], 'scope' => 'api:read'], 'api');
        self::assertSame(['api:read'], $this->tokenScopes($narrowed['access_token']));
        $this->expectOAuthError(fn() => $this->authenticate($narrowed['access_token'], 'api', ['api:write']), 'access_denied');

        $this->expectOAuthError(fn() => $this->exchange(['grant_type' => 'refresh_token', 'refresh_token' => $preserved['refresh_token'], 'scope' => 'api:read api:write'], 'api'), 'invalid_grant');
        $this->expectOAuthError(fn() => $this->exchange(['grant_type' => 'refresh_token', 'refresh_token' => $narrowed['refresh_token'], 'scope' => 'api:read api:write'], 'api'), 'invalid_scope');
        $fresh = $this->exchange(['grant_type' => 'refresh_token', 'refresh_token' => $narrowed['refresh_token']], 'api');
        self::assertSame(['api:read'], $this->tokenScopes($fresh['access_token']));
    }

    public function testUnknownAndForeignScopesAreRejected(): void
    {
        foreach (['mcp:read', 'api:unknown', 'api:read api:unknown'] as $scope) {
            $this->expectOAuthError(fn() => $this->authorize(str_repeat('g', 43), 'api', $scope), 'invalid_scope');
        }
    }

    public function testOmittedScopeUsesOnlyExplicitlyConfiguredDefaults(): void
    {
        $tokens = $this->grant('api');
        self::assertSame(['api:read'], $this->tokenScopes($tokens['access_token']));
        self::assertSame($this->app->identity->id, $this->authenticate($tokens['access_token'], 'api', ['api:read'])->id);
        $this->expectOAuthError(fn() => $this->authenticate($tokens['access_token'], 'api', ['api:write']), 'access_denied');
    }

    public function testRemovingAScopeInvalidatesAffectedCredentialsOnly(): void
    {
        $write = $this->grant('api', 'api:write');
        $read = $this->grant('api', 'api:read');
        unset($this->app->oauthConfig->resources['api']['scopes']['api:write']);
        $this->oauth = new OAuth();
        $this->app->set('oauth', $this->oauth);

        $this->expectOAuthError(fn() => $this->authenticate($write['access_token'], 'api', ['api:write']), 'access_denied');
        $this->expectOAuthError(fn() => $this->exchange(['grant_type' => 'refresh_token', 'refresh_token' => $write['refresh_token']], 'api'), 'invalid_grant');
        self::assertSame($this->app->identity->id, $this->authenticate($read['access_token'], 'api', ['api:read'])->id);
        $fresh = $this->exchange(['grant_type' => 'refresh_token', 'refresh_token' => $read['refresh_token']], 'api');
        self::assertSame(['api:read'], $this->tokenScopes($fresh['access_token']));
    }

    /**
     * @dataProvider accessLevelProvider
     */
    public function testChosenAccessLevelIsGranted(bool $admin, ?string $level, array $expected): void
    {
        $this->app->identity->admin = $admin;
        $tokens = $this->grant('mcp', null, fn($authorization) => $this->oauth->approveScopes($authorization, 'mcp', $this->app->identity, $level));

        self::assertSame($expected, $this->tokenScopes($tokens['access_token'], 'mcp'));
    }

    public static function accessLevelProvider(): array
    {
        return [
            'read by default' => [true, null, ['mcp:read']],
            'write' => [true, 'mcp:write', ['mcp:read', 'mcp:write']],
            'admin' => [true, 'mcp:admin', ['mcp:read', 'mcp:write', 'mcp:admin']],
            'admin for a non-admin' => [false, 'mcp:admin', ['mcp:read']],
            'unknown level' => [true, 'api:read', ['mcp:read']],
        ];
    }

    public function testRefreshDropsAdminAccessAfterLosingAdminStatus(): void
    {
        $this->app->identity->admin = true;
        $tokens = $this->grant('mcp', null, fn($authorization) => $this->oauth->approveScopes($authorization, 'mcp', $this->app->identity, 'mcp:admin'));

        $this->app->identity->admin = false;
        $fresh = $this->exchange(['grant_type' => 'refresh_token', 'refresh_token' => $tokens['refresh_token']]);
        self::assertSame(['mcp:read', 'mcp:write'], $this->tokenScopes($fresh['access_token'], 'mcp'));
    }

    public function testReauthorizationReusesTheEarlierAccessLevel(): void
    {
        $this->app->identity->admin = true;
        $this->grant('mcp', null, fn($authorization) => $this->oauth->approveScopes($authorization, 'mcp', $this->app->identity, 'mcp:write'));

        $tokens = $this->grant('mcp', null, fn($authorization) => self::assertTrue($this->oauth->reuseConsent($authorization, 'mcp')));
        self::assertSame(['mcp:read', 'mcp:write'], $this->tokenScopes($tokens['access_token'], 'mcp'));

        $this->grant('mcp', 'mcp:admin', fn($authorization) => self::assertFalse($this->oauth->reuseConsent($authorization, 'mcp')));
    }

    public function testDiscoveryUrlsRetainCpBasePathAndPort(): void
    {
        $this->app->generalConfig->baseCpUrl = 'https://cms.example:8443/installation/';
        self::assertSame('https://cms.example:8443/installation/admin/oauth/api/token', $this->oauth->metadata('api')['token_endpoint']);
        self::assertSame('https://api.example/content', $this->oauth->protectedResourceMetadata('api')['resource']);
        self::assertSame('https://api.example/.well-known/oauth-protected-resource/content', $this->oauth->resourceMetadataUrl('api'));

        $this->app->generalConfig->cpTrigger = null;
        self::assertSame('https://cms.example:8443/installation/oauth/api/token', $this->oauth->url('token', 'api'));

        $this->app->generalConfig->omitScriptNameInUrls = false;
        $this->app->set('request', new Request([
            'generalConfig' => $this->app->generalConfig,
            'sites' => new Sites(),
            'isCpRequest' => true,
            'isConsoleRequest' => false,
            'hostInfo' => 'https://cms.example:8443',
            'scriptUrl' => '/installation/index.php',
            'scriptFile' => __FILE__,
            'pathInfo' => '',
        ]));
        self::assertSame('https://cms.example:8443/index.php?p=oauth/api/token', $this->oauth->url('token', 'api'));
    }

    private function grant(string $resource, ?string $scope = null, ?callable $prepare = null): array
    {
        $verifier = str_repeat('h', 43);
        $code = $this->authorize($verifier, $resource, $scope, $prepare);

        return $this->exchange(['grant_type' => 'authorization_code', 'code' => $code, 'redirect_uri' => 'https://client.example/callback', 'code_verifier' => $verifier], $resource);
    }

    private function tokenScopes(string $token, string $resource = 'api'): array
    {
        $request = $this->oauth->resourceServer($resource)->validateAuthenticatedRequest(
            new ServerRequest('POST', $this->oauth->resource($resource), ['Authorization' => "Bearer $token"]),
        );

        return $request->getAttribute('oauth_scopes');
    }

    private function authorize(string $verifier, string $resource = 'mcp', ?string $scope = null, ?callable $prepare = null): string
    {
        $challenge = rtrim(strtr(base64_encode(hash('sha256', $verifier, true)), '+/', '-_'), '=');
        $parameters = [
            'response_type' => 'code', 'client_id' => 'test-' . $resource, 'redirect_uri' => 'https://client.example/callback', 'state' => 'client-state', 'code_challenge' => $challenge, 'code_challenge_method' => 'S256',
        ];
        if ($scope !== null) {
            $parameters['scope'] = $scope;
        }

        $request = (new ServerRequest('GET', $this->oauth->url('authorize', $resource)))->withQueryParams($parameters);
        $server = $this->oauth->server($resource);
        $authorization = $server->validateAuthorizationRequest($request);
        $user = new OAuthUser();
        $user->setIdentifier((string)$this->app->identity->id);
        $authorization->setUser($user);
        if ($prepare) {
            $prepare($authorization);
        }
        $authorization->setAuthorizationApproved(true);
        $response = $server->completeAuthorizationRequest($authorization, new Response());
        parse_str(parse_url($response->getHeaderLine('Location'), PHP_URL_QUERY), $query);
        self::assertSame('client-state', $query['state']);
        return $query['code'];
    }

    private function exchange(array $params, string $resource = 'mcp'): array
    {
        $request = (new ServerRequest('POST', $this->oauth->url('token', $resource)))->withParsedBody($params + ['client_id' => 'test-' . $resource]);
        $response = $this->oauth->server($resource)->respondToAccessTokenRequest($request, new Response());
        return json_decode((string)$response->getBody(), true, flags: JSON_THROW_ON_ERROR);
    }

    private function authenticate(string $token, string $resource = 'mcp', array $requiredScopes = []): User
    {
        return $this->oauth->authenticate(new ServerRequest('POST', $this->oauth->resource($resource), ['Authorization' => "Bearer $token"]), $resource, $requiredScopes);
    }

    private function expectOAuthError(callable $action, string $error): void
    {
        try {
            $action();
            self::fail("Expected OAuth error $error.");
        } catch (OAuthServerException $e) {
            self::assertSame($error, $e->getErrorType());
        }
    }
}

class OAuthTestApplication extends Application
{
    public object $oauthConfig;
    public object $generalConfig;
    public OAuthTestUser $identity;

    public function getIsInitialized(): bool
    {
        return false;
    }

    public function getIsInstalled(): bool
    {
        return false;
    }

    public function getConfig(): object
    {
        return new class($this->oauthConfig, $this->generalConfig) {
            public function __construct(private object $oauthConfig, private object $generalConfig)
            {
            }

            public function getOAuth(): object
            {
                return $this->oauthConfig;
            }

            public function getGeneral(): object
            {
                return $this->generalConfig;
            }
        };
    }

    public function getUsers(): object
    {
        return new class($this->identity) {
            public function __construct(private User $user)
            {
            }

            public function getUserById(int $id): ?User
            {
                return $id === $this->user->id ? $this->user : null;
            }
        };
    }
}

class OAuthTestUser extends User
{
    public array $permissions = ['accessCp', 'useApi'];
    public function init(): void
    {
    }

    public function can(string $permission): bool
    {
        return in_array($permission, $this->permissions, true);
    }
}
