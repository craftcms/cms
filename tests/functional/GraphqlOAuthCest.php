<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace crafttests\functional;

use Craft;
use craft\db\Table;
use craft\elements\User;
use craft\enums\CmsEdition;
use craft\helpers\Json;
use craft\helpers\StringHelper;
use craft\oauth\User as OAuthUser;
use craft\services\Gql;
use FunctionalTester;
use GuzzleHttp\Psr7\Response;
use GuzzleHttp\Psr7\ServerRequest;

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
            'resourceId' => 'graphql',
            'name' => 'Test GraphQL client',
            'redirectUris' => '["https://client.example/callback"]',
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
