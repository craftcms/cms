<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace crafttests\functional;

use Craft;
use craft\db\Query;
use craft\db\Table;
use craft\elements\User;
use craft\enums\CmsEdition;
use craft\helpers\ArrayHelper;
use craft\helpers\Json;
use craft\helpers\StringHelper;
use craft\oauth\User as OAuthUser;
use craft\services\Gql;
use craft\services\OAuth;
use DateTimeImmutable;
use FunctionalTester;
use GuzzleHttp\Psr7\Response;
use GuzzleHttp\Psr7\ServerRequest;
use yii\base\InvalidArgumentException;

/**
 * Tests GraphQL API requests authenticated with OAuth access tokens.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 5.12.0
 */
class GraphqlOAuthCest
{
    public function _before(FunctionalTester $I): void
    {
        Craft::$app->edition = CmsEdition::Pro;

        $key = openssl_pkey_new(['private_key_bits' => 2048]);
        $config = Craft::$app->getConfig()->getOAuth();
        openssl_pkey_export($key, $config->privateKey);
        $config->publicKey = openssl_pkey_get_details($key)['key'];

        Craft::$app->getDb()->createCommand()->insert(Table::OAUTH_CLIENTS, [
            'id' => 'test-graphql',
            'name' => 'Test GraphQL client',
            'redirect_uris' => '["https://client.example/callback"]',
            'grant_types' => '["authorization_code","refresh_token"]',
            'scopes' => '["graphql:read","graphql:write"]',
            'revoked' => false,
        ])->execute();
    }

    public function testSchemaIsBasedOnTheTokensUser(FunctionalTester $I): void
    {
        $query = '{ ping users(email: "nobody@example.com") { id } }';

        $data = $this->graphql($I, $query, $this->token($this->user(['viewUsers'])));
        $I->assertSame(['ping' => 'pong', 'users' => []], $data['data']);

        $data = $this->graphql($I, $query, $this->token($this->user([])));
        $I->assertSame('Cannot query field "users" on type "Query".', $data['errors'][0]['message']);
    }

    public function testPersonalAccessTokens(FunctionalTester $I): void
    {
        $oauth = Craft::$app->getOAuth();
        $user = $this->user(['viewUsers']);
        $token = $oauth->createPersonalAccessToken('graphql', $user, 'Build script', [Gql::SCOPE_READ], new DateTimeImmutable('+7 days'));
        $other = $oauth->createPersonalAccessToken('graphql', $user, 'Preview', [Gql::SCOPE_READ], new DateTimeImmutable('+7 days'));
        $this->token($user);

        $data = $this->graphql($I, '{ users(email: "nobody@example.com") { id } }', $token);
        $I->assertSame(['users' => []], $data['data']);

        $grants = ArrayHelper::index($oauth->getUserGrants('graphql', $user), 'name');
        $I->assertEqualsCanonicalizing(['Build script', 'Preview', 'Test GraphQL client'], array_keys($grants));
        $I->assertTrue($grants['Build script']['personal']);
        $I->assertSame([Gql::SCOPE_READ], $grants['Build script']['scopes']);
        $I->assertFalse($grants['Test GraphQL client']['personal']);

        // Like Passport, personal tokens share one personal access client that only supports the personal_access grant,
        // and can be used for any resource. The token's scopes decide which resource it belongs to.
        $I->assertSame($grants['Build script']['clientId'], $grants['Preview']['clientId']);
        $client = (new Query())->from(Table::OAUTH_CLIENTS)->where(['id' => $grants['Build script']['clientId']])->one();
        $I->assertSame(['personal_access'], Json::decode($client['grant_types']));
        $I->assertNull($client['scopes']);
        $I->assertSame('craft', $client['provider']);
        $I->assertSame([], $oauth->getUserGrants('mcp', $user));
        $oauth->revokeUserGrant('mcp', $user, $grants['Build script']['clientId'], $grants['Build script']['tokenId']);
        $I->assertSame(['users' => []], $this->graphql($I, '{ users(email: "nobody@example.com") { id } }', $token)['data']);
        $I->assertSame('Build script', (new Query())->select('name')->from(Table::OAUTH_ACCESS_TOKENS)->where(['id' => $grants['Build script']['tokenId']])->scalar());

        // Revoking one personal token leaves the others alone
        $oauth->revokeUserGrant('graphql', $user, $grants['Build script']['clientId'], $grants['Build script']['tokenId']);
        $I->assertEqualsCanonicalizing(['Preview', 'Test GraphQL client'], array_column($oauth->getUserGrants('graphql', $user), 'name'));
        $I->haveHttpHeader('Authorization', "Bearer $token");
        $I->amOnPage('/actions/graphql/api?' . http_build_query(['query' => '{ ping }']));
        $I->seeResponseCodeIs(401);
        $I->assertSame(['ping' => 'pong'], $this->graphql($I, '{ ping }', $other)['data']);
    }

    public function testPersonalAccessTokenScopes(FunctionalTester $I): void
    {
        $oauth = Craft::$app->getOAuth();
        $user = $this->user([]);
        $I->assertSame(['graphql'], array_keys($oauth->getPersonalTokenResources($user)));

        // Choosing an access level grants the narrower levels too
        $oauth->createPersonalAccessToken('graphql', $user, 'Write', [Gql::SCOPE_WRITE], new DateTimeImmutable('+7 days'));
        $I->assertSame([Gql::SCOPE_READ, Gql::SCOPE_WRITE], $oauth->getUserGrants('graphql', $user)[0]['scopes']);

        // MCP tokens come from MCP clients, not the Tokens screen
        $I->expectThrowable(InvalidArgumentException::class, fn() => $oauth->createPersonalAccessToken('mcp', $user, 'MCP', ['mcp:read'], new DateTimeImmutable('+7 days')));
        $I->expectThrowable(InvalidArgumentException::class, fn() => $oauth->createPersonalAccessToken('graphql', $user, 'Bad', ['mcp:read'], new DateTimeImmutable('+7 days')));

        // Without GraphQL, nothing offers personal access tokens
        Craft::$app->getConfig()->getGeneral()->enableGql = false;
        try {
            $I->assertSame([], (new OAuth())->getPersonalTokenResources($user));
        } finally {
            Craft::$app->getConfig()->getGeneral()->enableGql = true;
        }
    }

    public function testInvalidTokensAreRejected(FunctionalTester $I): void
    {
        $I->haveHttpHeader('Authorization', 'Bearer aaa.bbb.ccc');
        $I->amOnPage('/actions/graphql/api?' . http_build_query(['query' => '{ ping }']));

        $I->seeResponseCodeIs(401);
        $I->assertStringContainsString(
            'resource_metadata="' . Craft::$app->getOAuth()->resourceMetadataUrl('graphql') . '"',
            Craft::$app->getResponse()->getHeaders()->get('WWW-Authenticate'),
        );
    }

    private function user(array $permissions): User
    {
        $user = new User([
            'username' => 'gql-' . StringHelper::randomString(6),
            'email' => 'gql-' . StringHelper::randomString(6) . '@example.com',
            'active' => true,
        ]);
        Craft::$app->getElements()->saveElement($user, false);
        Craft::$app->getUserPermissions()->saveUserPermissions($user->id, $permissions);

        return $user;
    }

    private function graphql(FunctionalTester $I, string $query, string $token): array
    {
        Craft::$app->getGql()->flushCaches();
        $I->haveHttpHeader('Authorization', "Bearer $token");
        $I->amOnPage('/actions/graphql/api?' . http_build_query(['query' => $query]));
        $I->seeResponseCodeIs(200);

        return Json::decode($I->grabPageSource());
    }

    private function token(User $user, string $scope = Gql::SCOPE_READ): string
    {
        $oauth = Craft::$app->getOAuth();
        $server = $oauth->server('graphql');
        $verifier = str_repeat('v', 43);

        $request = (new ServerRequest('GET', $oauth->url('authorize', 'graphql')))->withQueryParams([
            'response_type' => 'code',
            'client_id' => 'test-graphql',
            'redirect_uri' => 'https://client.example/callback',
            'scope' => $scope,
            'state' => 'state',
            'code_challenge' => rtrim(strtr(base64_encode(hash('sha256', $verifier, true)), '+/', '-_'), '='),
            'code_challenge_method' => 'S256',
        ]);
        $authorization = $server->validateAuthorizationRequest($request);
        $oauthUser = new OAuthUser();
        $oauthUser->setIdentifier((string)$user->id);
        $authorization->setUser($oauthUser);
        $authorization->setAuthorizationApproved(true);
        $response = $server->completeAuthorizationRequest($authorization, new Response());
        parse_str(parse_url($response->getHeaderLine('Location'), PHP_URL_QUERY), $query);

        $request = (new ServerRequest('POST', $oauth->url('token', 'graphql')))->withParsedBody([
            'grant_type' => 'authorization_code',
            'client_id' => 'test-graphql',
            'redirect_uri' => 'https://client.example/callback',
            'code' => $query['code'],
            'code_verifier' => $verifier,
        ]);
        $response = $server->respondToAccessTokenRequest($request, new Response());

        return Json::decode((string)$response->getBody())['access_token'];
    }
}
