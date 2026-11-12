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
use craft\elements\Entry;
use craft\elements\User;
use craft\enums\CmsEdition;
use craft\fieldlayoutelements\entries\EntryTitleField;
use craft\helpers\ArrayHelper;
use craft\helpers\ElementHelper;
use craft\helpers\Json;
use craft\helpers\StringHelper;
use craft\models\EntryType;
use craft\models\FieldLayout;
use craft\models\GqlSchema;
use craft\models\GqlToken;
use craft\models\Section;
use craft\models\Section_SiteSettings;
use craft\oauth\ScopeGroup;
use craft\oauth\User as OAuthUser;
use craft\services\Gql;
use craft\services\Mcp;
use craft\services\PersonalAccessTokens;
use DateInterval;
use DateTime;
use FunctionalTester;
use GuzzleHttp\Psr7\Response;
use GuzzleHttp\Psr7\ServerRequest;
use League\OAuth2\Server\Exception\OAuthServerException;
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

    public function _after(FunctionalTester $I): void
    {
        Craft::$app->get('mcp')->resetSettings();
        ElementHelper::setProvisionalDraftUser(null);
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
        $tokens = Craft::$app->getPersonalAccessTokens();
        $user = $this->user(['viewUsers']);
        $token = $tokens->create($user, 'Build script', [Gql::SCOPE_READ], new DateInterval('P7D'));
        $other = $tokens->create($user, 'Preview', [Gql::SCOPE_READ], new DateInterval('P7D'));
        $this->token($user);

        $data = $this->graphql($I, '{ users(email: "nobody@example.com") { id } }', $token);
        $I->assertSame(['users' => []], $data['data']);

        $grants = ArrayHelper::index($tokens->grants($user), 'name');
        $I->assertEqualsCanonicalizing(['Build script', 'Preview', 'Test GraphQL client'], array_keys($grants));
        $I->assertTrue($grants['Build script']['personal']);
        $I->assertSame(['graphql' => [Gql::SCOPE_READ]], $grants['Build script']['scopes']);
        $I->assertFalse($grants['Test GraphQL client']['personal']);
        $I->assertEqualsWithDelta((new DateTime('+7 days'))->getTimestamp(), $grants['Build script']['expiresAt']->getTimestamp(), 60);

        // Like Passport, personal tokens share one personal access client that only supports the personal_access grant,
        // and can be used for any resource. The token's scopes decide which resource it belongs to.
        $I->assertSame($grants['Build script']['clientId'], $grants['Preview']['clientId']);
        $client = (new Query())->from(Table::OAUTH_CLIENTS)->where(['id' => $grants['Build script']['clientId']])->one();
        $I->assertSame(['personal_access'], Json::decode($client['grant_types']));
        $I->assertNull($client['scopes']);
        $I->assertSame('craft', $client['provider']);
        $I->assertSame('Build script', (new Query())->select('name')->from(Table::OAUTH_ACCESS_TOKENS)->where(['id' => $grants['Build script']['tokenId']])->scalar());

        // Personal tokens can be renamed, but not other users' tokens or apps' tokens
        $tokens->rename($user, $grants['Preview']['tokenId'], 'Preview site');
        $I->assertContains('Preview site', array_column($tokens->grants($user), 'name'));
        $I->expectThrowable(InvalidArgumentException::class, fn() => $tokens->rename($this->user([]), $grants['Preview']['tokenId'], 'Mine'));
        $I->expectThrowable(InvalidArgumentException::class, fn() => $tokens->rename($user, 'nope', 'Mine'));

        // Revoking one personal token leaves the others alone
        $tokens->revoke($user, $grants['Build script']['clientId'], $grants['Build script']['tokenId']);
        $I->assertEqualsCanonicalizing(['Preview site', 'Test GraphQL client'], array_column($tokens->grants($user), 'name'));
        $I->haveHttpHeader('Authorization', "Bearer $token");
        $I->amOnPage('/actions/graphql/api?' . http_build_query(['query' => '{ ping }']));
        $I->seeResponseCodeIs(401);
        $I->assertSame(['ping' => 'pong'], $this->graphql($I, '{ ping }', $other)['data']);
    }

    public function testPersonalAccessTokensCanMixApis(FunctionalTester $I): void
    {
        $user = $this->user(['viewUsers', 'accessCp', 'useCraftMcp']);
        Craft::$app->get('mcp')->getSettings()->enabled = true;
        $tokens = new PersonalAccessTokens();
        // Users can't grant MCP scopes from the Tokens screen, but tokens can still be issued for several APIs
        $token = Craft::$app->getOAuth()->createPersonalAccessToken($user, 'Agent', [Gql::SCOPE_READ, 'mcp:read'], new DateInterval('P7D'));

        // Each API accepts the token, and only sees its own scopes
        $I->assertSame(['users' => []], $this->graphql($I, '{ users(email: "nobody@example.com") { id } }', $token)['data']);
        $request = new ServerRequest('POST', 'https://example.test/mcp', ['Authorization' => "Bearer $token"]);
        $I->assertSame($user->id, Craft::$app->getOAuth()->authenticate($request, 'mcp', scopes: $scopes)->id);
        $I->assertSame(['mcp:read'], $scopes);
        $I->expectThrowable(OAuthServerException::class, fn() => Craft::$app->getOAuth()->authenticate($request, 'mcp', ['mcp:write']));

        // It's listed once, with its scopes grouped by API, and revoking it revokes it for every API
        $grants = $tokens->grants($user);
        $I->assertCount(1, $grants);
        $I->assertSame(['graphql' => [Gql::SCOPE_READ], 'mcp' => ['mcp:read']], $grants[0]['scopes']);
        $tokens->revoke($user, $grants[0]['clientId'], $grants[0]['tokenId']);
        $I->expectThrowable(OAuthServerException::class, fn() => Craft::$app->getOAuth()->authenticate($request, 'mcp'));
        $I->haveHttpHeader('Authorization', "Bearer $token");
        $I->amOnPage('/actions/graphql/api?' . http_build_query(['query' => '{ ping }']));
        $I->seeResponseCodeIs(401);

        // Tokens for one API still only work with that API
        $graphqlToken = $tokens->create($user, 'GraphQL only', [Gql::SCOPE_READ], new DateInterval('P7D'));
        $request = new ServerRequest('POST', 'https://example.test/mcp', ['Authorization' => "Bearer $graphqlToken"]);
        $I->expectThrowable(OAuthServerException::class, fn() => Craft::$app->getOAuth()->authenticate($request, 'mcp'));
    }

    public function testPersonalAccessTokenScopes(FunctionalTester $I): void
    {
        $tokens = Craft::$app->getPersonalAccessTokens();
        $user = $this->user([]);
        $I->assertSame(['graphql'], array_keys($tokens->scopeGroupsFor($user)));

        // Tokens get the scopes that were chosen, as in Craft 6. A write token can still run queries.
        $token = $tokens->create($user, 'Write', [Gql::SCOPE_WRITE], new DateInterval('P7D'));
        $I->assertSame(['graphql' => [Gql::SCOPE_WRITE]], $tokens->grants($user)[0]['scopes']);
        $I->assertSame(['ping' => 'pong'], $this->graphql($I, '{ ping }', $token)['data']);

        // MCP tokens come from MCP clients, not the Tokens screen
        $I->expectThrowable(InvalidArgumentException::class, fn() => $tokens->create($user, 'MCP', ['mcp:read'], new DateInterval('P7D')));
        $I->expectThrowable(InvalidArgumentException::class, fn() => $tokens->create($user, 'None', [], new DateInterval('P7D')));

        // Plugins can offer their own scopes, as long as an OAuth resource defines them
        $tokens = new PersonalAccessTokens();
        $tokens->registerScopeGroup('custom', fn() => new ScopeGroup('custom', 'Custom', ['custom:read' => 'Read']));
        $I->expectThrowable(InvalidArgumentException::class, fn() => $tokens->create($user, 'Custom', ['custom:read'], new DateInterval('P7D')));

        // Without GraphQL, nothing offers personal access tokens
        Craft::$app->getConfig()->getGeneral()->enableGql = false;
        try {
            $I->assertSame([], (new PersonalAccessTokens())->scopeGroupsFor($user));
        } finally {
            Craft::$app->getConfig()->getGeneral()->enableGql = true;
        }
    }

    public function testMcpTokens(FunctionalTester $I): void
    {
        Craft::$app->getDb()->createCommand()->insert(Table::OAUTH_CLIENTS, [
            'id' => 'test-mcp',
            'name' => 'Test MCP client',
            'redirect_uris' => '["https://client.example/callback"]',
            'grant_types' => '["authorization_code","refresh_token"]',
            'scopes' => '["mcp:read","mcp:write","mcp:admin"]',
            'revoked' => false,
        ])->execute();
        Craft::$app->get('mcp')->getSettings()->enabled = true;
        $tokens = new PersonalAccessTokens();
        $user = $this->user([]);

        // MCP tokens come from MCP clients, not the Tokens screen
        $I->assertArrayNotHasKey('mcp', $tokens->scopeGroupsFor(User::find()->admin()->one()));
        $I->expectThrowable(InvalidArgumentException::class, fn() => $tokens->create($user, 'MCP', [Mcp::SCOPE_READ], new DateInterval('P7D')));

        // But MCP clients the user has authorized are listed, and can be revoked
        $clientToken = $this->token($user, Mcp::SCOPE_WRITE, 'mcp', 'test-mcp');
        $grants = $tokens->grants($user);
        $I->assertCount(1, $grants);
        $I->assertSame('Test MCP client', $grants[0]['name']);
        $I->assertFalse($grants[0]['personal']);
        $I->assertSame(['mcp' => [Mcp::SCOPE_WRITE]], $grants[0]['scopes']);

        $tokens->revoke($user, 'test-mcp');
        $I->assertSame([], $tokens->grants($user));
        $request = new ServerRequest('POST', 'https://example.test/mcp', ['Authorization' => "Bearer $clientToken"]);
        $I->expectThrowable(OAuthServerException::class, fn() => Craft::$app->getOAuth()->authenticate($request, 'mcp'));
    }

    public function testGqlTokenHeaderPairsWithAccessTokens(FunctionalTester $I): void
    {
        $entries = Craft::$app->getEntries();
        $entryType = new EntryType(['name' => 'Page', 'handle' => 'page', 'hasTitleField' => true]);
        $entryType->setFieldLayout(FieldLayout::createFromConfig([
            'type' => Entry::class,
            'tabs' => [['name' => 'Content', 'elements' => [['type' => EntryTitleField::class]]]],
        ]));
        $entries->saveEntryType($entryType);
        $section = new Section(['name' => 'Pages', 'handle' => 'pages', 'type' => Section::TYPE_CHANNEL]);
        $section->setEntryTypes([$entryType]);
        $section->setSiteSettings([
            new Section_SiteSettings(['siteId' => Craft::$app->getSites()->getPrimarySite()->id, 'hasUrls' => false]),
        ]);
        $entries->saveSection($section);
        $entry = new Entry(['sectionId' => $section->id, 'typeId' => $entryType->id, 'title' => 'Published']);
        Craft::$app->getElements()->saveElement($entry);

        $gql = Craft::$app->getGql();
        $schema = new GqlSchema([
            'name' => 'Front end',
            'scope' => [
                'sites.' . Craft::$app->getSites()->getPrimarySite()->uid . ':read',
                'elements.drafts:read',
                "sections.$section->uid:read",
            ],
        ]);
        $gql->saveSchema($schema);
        $gqlToken = new GqlToken([
            'name' => 'Front end',
            'accessToken' => StringHelper::randomString(32),
            'enabled' => true,
            'schemaId' => $schema->id,
        ]);
        $gql->saveToken($gqlToken);

        // The user has no permissions, so the schema can only come from the GraphQL token
        $user = $this->user([]);
        $draft = Craft::$app->getDrafts()->createDraft($entry, $user->id, provisional: true);
        $draft->title = 'Unsaved changes';
        Craft::$app->getElements()->saveElement($draft);
        $userToken = $this->token($user);
        $query = sprintf('{ entry(id: %s, withProvisionalDrafts: true) { title } }', $entry->id);

        // Results are cached, so the user's provisional drafts must not leak into the next request. Save the pending
        // project config first, so the cache keys don't change between requests.
        Craft::$app->getProjectConfig()->saveModifiedConfigData();
        $gql->flushCaches();
        $data = $this->request($I, $query, ['X-Craft-Gql-Token' => $gqlToken->accessToken, 'Authorization' => "Bearer $userToken"]);
        $I->assertSame('Unsaved changes', $data['data']['entry']['title']);
        $data = $this->request($I, $query, ['X-Craft-Gql-Token' => $gqlToken->accessToken]);
        $I->assertSame('Published', $data['data']['entry']['title']);

        // The same goes for users authenticated by their session
        $gql->flushCaches();
        $I->amLoggedInAs($user);
        $data = $this->request($I, $query, ['X-Craft-Gql-Token' => $gqlToken->accessToken]);
        $I->assertSame('Unsaved changes', $data['data']['entry']['title']);
        $data = $this->request($I, $query, ['X-Craft-Gql-Token' => $gqlToken->accessToken]);
        $I->assertSame('Published', $data['data']['entry']['title']);

        // Without the GraphQL token, the schema is the user's own, which can't query entries
        $data = $this->request($I, $query, ['Authorization' => "Bearer $userToken"]);
        $I->assertNull($data['data']['entry'] ?? null);

        $this->request($I, $query, ['X-Craft-Gql-Token' => 'nope', 'Authorization' => "Bearer $userToken"], 400);
        $this->request($I, $query, ['X-Craft-Gql-Token' => $gqlToken->accessToken, 'Authorization' => 'Bearer aaa.bbb.ccc'], 401);
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

    private function request(FunctionalTester $I, string $query, array $headers, int $status = 200): array
    {
        $I->deleteHeader('Authorization');
        $I->deleteHeader('X-Craft-Gql-Token');
        foreach ($headers as $name => $value) {
            $I->haveHttpHeader($name, $value);
        }
        $I->amOnPage('/actions/graphql/api?' . http_build_query(['query' => $query]));
        $I->seeResponseCodeIs($status);

        // The application persists between requests here, so forget the request's user, as a new PHP process would
        ElementHelper::setProvisionalDraftUser(null);
        Craft::$app->getUser()->setIdentity(null);

        return Json::decode($I->grabPageSource()) ?? [];
    }

    private function token(User $user, string $scope = Gql::SCOPE_READ, string $resource = 'graphql', string $clientId = 'test-graphql'): string
    {
        $oauth = Craft::$app->getOAuth();
        $server = $oauth->server($resource);
        $verifier = str_repeat('v', 43);

        $request = (new ServerRequest('GET', $oauth->url('authorize', $resource)))->withQueryParams([
            'response_type' => 'code',
            'client_id' => $clientId,
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

        $request = (new ServerRequest('POST', $oauth->url('token', $resource)))->withParsedBody([
            'grant_type' => 'authorization_code',
            'client_id' => $clientId,
            'redirect_uri' => 'https://client.example/callback',
            'code' => $query['code'],
            'code_verifier' => $verifier,
        ]);
        $response = $server->respondToAccessTokenRequest($request, new Response());

        return Json::decode((string)$response->getBody())['access_token'];
    }
}
