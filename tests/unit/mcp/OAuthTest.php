<?php

namespace crafttests\unit\mcp;

use Craft;
use craft\elements\User;
use craft\mcp\oauth\OAuth;
use craft\mcp\oauth\Repositories;
use craft\mcp\oauth\User as OAuthUser;
use GuzzleHttp\Psr7\Response;
use GuzzleHttp\Psr7\ServerRequest;
use League\OAuth2\Server\Exception\OAuthServerException;
use PHPUnit\Framework\TestCase;
use yii\console\Application;
use yii\db\Connection;

/** Exercises the real League grants and Craft repositories against an isolated SQL store. */
class OAuthTest extends TestCase
{
    private mixed $originalApp;
    private array $originalAliases;
    private string $originalTimeZone;
    private OAuthTestApplication $app;
    private OAuth $oauth;

    protected function setUp(): void
    {
        $this->originalApp = Craft::$app;
        $this->originalAliases = Craft::$aliases;
        $this->originalTimeZone = date_default_timezone_get();
        $config = (object)[
            'privateKey' => null,
            'publicKey' => null,
            'accessTokenDuration' => 'PT1H',
            'refreshTokenDuration' => 'P30D',
            'authorizationCodeDuration' => 'PT10M',
            'endpoint' => 'mcp',
        ];
        $key = openssl_pkey_new(['private_key_bits' => 2048]);
        openssl_pkey_export($key, $config->privateKey);
        $config->publicKey = openssl_pkey_get_details($key)['key'];
        $this->app = new OAuthTestApplication(['id' => 'oauth-test', 'basePath' => __DIR__, 'components' => ['db' => ['class' => Connection::class, 'dsn' => 'sqlite::memory:']]]);
        $this->app->oauthConfig = $config;
        $this->app->generalConfig = (object)['securityKey' => 'oauth-integration-test-security-key', 'baseCpUrl' => 'https://cms.example', 'cpTrigger' => 'admin', 'omitScriptNameInUrls' => true];
        $this->app->identity = new OAuthTestUser(['id' => 7, 'active' => true]);
        $db = $this->app->getDb();
        $db->createCommand('CREATE TABLE mcp_oauth_clients (id TEXT PRIMARY KEY, name TEXT, redirectUris TEXT, revoked INTEGER)')->execute();
        $db->createCommand('CREATE TABLE mcp_oauth_access_tokens (id TEXT PRIMARY KEY, clientId TEXT, userId INTEGER, scopes TEXT, revoked INTEGER, expiresAt TEXT)')->execute();
        $db->createCommand('CREATE TABLE mcp_oauth_auth_codes (id TEXT PRIMARY KEY, clientId TEXT, userId INTEGER, revoked INTEGER, expiresAt TEXT)')->execute();
        $db->createCommand('CREATE TABLE mcp_oauth_refresh_tokens (id TEXT PRIMARY KEY, accessTokenId TEXT, revoked INTEGER, expiresAt TEXT)')->execute();
        $db->createCommand()->insert(Repositories::CLIENTS, ['id' => 'test-client', 'name' => 'Test client', 'redirectUris' => '["https://client.example/callback"]', 'revoked' => false])->execute();
        $this->oauth = new OAuth();
    }

    protected function tearDown(): void
    {
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
        self::assertSame(7, $this->authenticate($tokens['access_token'])->id);
        $this->expectOAuthError(fn() => $this->exchange(['grant_type' => 'authorization_code', 'code' => $code, 'redirect_uri' => 'https://client.example/callback', 'code_verifier' => $verifier]), 'invalid_grant');

        $fresh = $this->exchange(['grant_type' => 'refresh_token', 'refresh_token' => $tokens['refresh_token']]);
        self::assertSame(7, $this->authenticate($fresh['access_token'])->id);
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
        self::assertSame(7, $this->authenticate($tokens['access_token'])->id);
    }

    public function testDiscoveryUrlsRetainCpBasePathAndPort(): void
    {
        $this->app->generalConfig->baseCpUrl = 'https://cms.example:8443/installation/';
        self::assertSame('https://cms.example:8443/installation/admin/mcp', $this->oauth->metadata()['issuer']);
        self::assertSame('https://cms.example:8443/.well-known/oauth-protected-resource/installation/admin/mcp', $this->oauth->resourceMetadataUrl());
        self::assertSame('https://cms.example:8443/installation/admin/mcp/oauth/token', $this->oauth->metadata()['token_endpoint']);

        $this->app->generalConfig->cpTrigger = null;
        self::assertSame('https://cms.example:8443/installation/mcp', $this->oauth->resource());

        $this->app->generalConfig->omitScriptNameInUrls = false;
        self::assertSame('https://cms.example:8443/installation/index.php/mcp', $this->oauth->resource());
    }

    private function authorize(string $verifier): string
    {
        $challenge = rtrim(strtr(base64_encode(hash('sha256', $verifier, true)), '+/', '-_'), '=');
        $request = (new ServerRequest('GET', 'https://craft.example/admin/mcp/oauth/authorize'))->withQueryParams([
            'response_type' => 'code', 'client_id' => 'test-client', 'redirect_uri' => 'https://client.example/callback', 'scope' => 'mcp:use', 'state' => 'client-state', 'code_challenge' => $challenge, 'code_challenge_method' => 'S256',
        ]);
        $server = $this->oauth->server();
        $authorization = $server->validateAuthorizationRequest($request);
        $user = new OAuthUser();
        $user->setIdentifier('7');
        $authorization->setUser($user);
        $authorization->setAuthorizationApproved(true);
        $response = $server->completeAuthorizationRequest($authorization, new Response());
        parse_str(parse_url($response->getHeaderLine('Location'), PHP_URL_QUERY), $query);
        self::assertSame('client-state', $query['state']);
        return $query['code'];
    }

    private function exchange(array $params): array
    {
        $request = (new ServerRequest('POST', 'https://craft.example/admin/mcp/oauth/token'))->withParsedBody($params + ['client_id' => 'test-client']);
        $response = $this->oauth->server()->respondToAccessTokenRequest($request, new Response());
        return json_decode((string)$response->getBody(), true, flags: JSON_THROW_ON_ERROR);
    }

    private function authenticate(string $token): User
    {
        return $this->oauth->authenticate(new ServerRequest('POST', 'https://craft.example/admin/mcp', ['Authorization' => "Bearer $token"]));
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

    public function getConfig(): object
    {
        return new class($this->oauthConfig, $this->generalConfig) {
            public function __construct(private object $oauthConfig, private object $generalConfig)
            {
            }

            public function getMcp(): object
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
    public function init(): void
    {
    }

    public function can(string $permission): bool
    {
        return in_array($permission, ['accessCp', 'useCraftMcp'], true);
    }
}
