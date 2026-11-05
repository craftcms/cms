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
use craft\helpers\StringHelper;
use craft\helpers\UrlHelper;
use craft\mcp\Settings;
use craft\oauth\User as OAuthUser;
use craft\services\Mcp;
use FunctionalTester;
use GuzzleHttp\Psr7\Response;
use GuzzleHttp\Psr7\ServerRequest;
use ReflectionProperty;

/**
 * Tests how requests reach the authenticated and public MCP servers.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 5.12.0
 */
class McpServersCest
{
    public function _before(FunctionalTester $I): void
    {
        $key = openssl_pkey_new(['private_key_bits' => 2048]);
        $config = Craft::$app->getConfig()->getOAuth();
        openssl_pkey_export($key, $config->privateKey);
        $config->publicKey = openssl_pkey_get_details($key)['key'];

        Craft::$app->getDb()->createCommand()->insert(Table::OAUTH_CLIENTS, [
            'id' => 'test-mcp',
            'name' => 'Test MCP client',
            'redirect_uris' => '["https://client.example/callback"]',
            'grant_types' => '["authorization_code","refresh_token"]',
            'scopes' => '["mcp:read"]',
            'revoked' => false,
        ])->execute();
    }

    public function _after(FunctionalTester $I): void
    {
        Craft::$app->get('mcp')->resetSettings();
    }

    public function testSharedEndpoint(FunctionalTester $I): void
    {
        $this->settings(['enabled' => true, 'route' => '/mcp', 'authorizationRoute' => '/mcp/auth', 'publicEnabled' => true, 'publicRoute' => '/mcp']);

        // Requests without credentials go to the public server, and bad credentials are rejected
        $I->sendAjaxPostRequest('/mcp');
        $I->dontSeeResponseCodeIs(401);
        $I->haveHttpHeader('Authorization', 'Bearer nope');
        $I->sendAjaxPostRequest('/mcp');
        $I->seeResponseCodeIs(401);

        // The authorization endpoint always requires a token, and points clients at its own discovery metadata
        $I->deleteHeader('Authorization');
        $I->sendAjaxPostRequest('/mcp/auth');
        $I->seeResponseCodeIs(401);
        $I->assertStringContainsString('/.well-known/oauth-protected-resource/mcp/auth"', Craft::$app->getResponse()->getHeaders()->get('WWW-Authenticate'));

        // Browsers get the authenticated server's setup page, which links to the authorization endpoint
        $I->haveHttpHeader('Accept', 'text/html');
        $I->amOnPage('/mcp');
        $I->seeResponseCodeIs(200);
        $I->see('Manage this site from your AI assistant');
        $I->seeInSource('mcp/auth');
    }

    public function testDisabledServers(FunctionalTester $I): void
    {
        $this->settings(['enabled' => false, 'publicEnabled' => false]);
        $I->sendAjaxPostRequest('/mcp');
        $I->seeResponseCodeIs(404);
        $I->sendAjaxPostRequest('/actions/mcp/authenticated');
        $I->seeResponseCodeIs(404);
    }

    public function testControlPanelEndpointsRequireControlPanelAccess(FunctionalTester $I): void
    {
        $cpTrigger = Craft::$app->getConfig()->getGeneral()->cpTrigger;
        $this->settings(['enabled' => true, 'route' => "/$cpTrigger/mcp", 'authorizationRoute' => "/$cpTrigger/mcp"]);

        $I->haveHttpHeader('Authorization', 'Bearer ' . $this->token($this->user([])));
        $I->sendAjaxPostRequest("/$cpTrigger/mcp");
        $I->seeResponseCodeIs(403);

        $I->haveHttpHeader('Authorization', 'Bearer ' . $this->token($this->user(['accessCp'])));
        $I->sendAjaxPostRequest("/$cpTrigger/mcp");
        $I->dontSeeResponseCodeIs(401);
        $I->dontSeeResponseCodeIs(403);
    }

    public function testSiteEndpointsDoNotRequireControlPanelAccess(FunctionalTester $I): void
    {
        $this->settings(['enabled' => true, 'route' => '/mcp']);
        $I->haveHttpHeader('Authorization', 'Bearer ' . $this->token($this->user([])));
        $I->sendAjaxPostRequest('/mcp');
        $I->dontSeeResponseCodeIs(401);
        $I->dontSeeResponseCodeIs(403);
    }

    public function testControlPanelDomainWithoutTrigger(FunctionalTester $I): void
    {
        $general = Craft::$app->getConfig()->getGeneral();
        $original = ['baseCpUrl' => $general->baseCpUrl, 'cpTrigger' => $general->cpTrigger];
        $general->baseCpUrl = 'https://cp.example.com';
        $general->cpTrigger = null;
        $this->settings(['enabled' => true, 'route' => '/mcp', 'authorizationRoute' => '/mcp/auth', 'publicEnabled' => true, 'publicRoute' => '/ai']);

        try {
            // The site's domain advertises the authorization endpoint on the site
            $I->amOnPage('/.well-known/oauth-protected-resource/mcp/auth');
            $I->assertSame(UrlHelper::siteUrl('mcp/auth'), json_decode($I->grabPageSource(), true)['resource']);
            $I->amOnPage('/actions/mcp/catalog');
            $I->seeResponseCodeIs(200);

            // The control panel's domain advertises it on that domain
            $I->haveServerParameter('HTTP_HOST', 'cp.example.com');
            $I->haveServerParameter('HTTPS', 'on');
            $I->sendAjaxPostRequest('/mcp/auth');
            $I->seeResponseCodeIs(401);
            $I->assertStringContainsString('"https://cp.example.com/.well-known/oauth-protected-resource/mcp/auth"', Craft::$app->getResponse()->getHeaders()->get('WWW-Authenticate'));
            $I->amOnPage('/.well-known/oauth-protected-resource/mcp/auth');
            $I->assertSame('https://cp.example.com/mcp/auth', json_decode($I->grabPageSource(), true)['resource']);

            // The public server and discovery stay on the front end
            $I->amOnPage('/actions/mcp/catalog');
            $I->seeResponseCodeIs(404);
            $I->sendAjaxPostRequest('/actions/mcp/public');
            $I->seeResponseCodeIs(404);
        } finally {
            Craft::configure($general, $original);
        }
    }

    public function testControlPanelBasePathWithoutTrigger(FunctionalTester $I): void
    {
        // The control panel lives at /backoffice on the site's own host
        $general = Craft::$app->getConfig()->getGeneral();
        $original = ['baseCpUrl' => $general->baseCpUrl, 'cpTrigger' => $general->cpTrigger, 'omitScriptNameInUrls' => $general->omitScriptNameInUrls];
        $general->baseCpUrl = UrlHelper::siteUrl('backoffice');
        $general->cpTrigger = null;
        $this->settings(['enabled' => true, 'route' => '/backoffice/mcp', 'authorizationRoute' => '/backoffice/mcp/auth']);

        try {
            $I->haveServerParameter('HTTP_HOST', parse_url($general->baseCpUrl, PHP_URL_HOST));
            $I->sendAjaxPostRequest('/backoffice/mcp/auth');
            $I->seeResponseCodeIs(401);
            $I->assertStringContainsString('"' . UrlHelper::siteUrl('.well-known/oauth-protected-resource/backoffice/mcp/auth') . '"', Craft::$app->getResponse()->getHeaders()->get('WWW-Authenticate'));

            // URLs with the script name keep the base URL's path too
            $general->omitScriptNameInUrls = false;
            $I->assertSame(UrlHelper::siteUrl('backoffice/mcp/auth'), Craft::$app->getOAuth()->resource('mcp'));
        } finally {
            Craft::configure($general, $original);
        }
    }

    public function testControlPanelDomainBasePathWithoutTrigger(FunctionalTester $I): void
    {
        $general = Craft::$app->getConfig()->getGeneral();
        $original = ['baseCpUrl' => $general->baseCpUrl, 'cpTrigger' => $general->cpTrigger, 'omitScriptNameInUrls' => $general->omitScriptNameInUrls, 'usePathInfo' => $general->usePathInfo];
        $general->baseCpUrl = 'https://cp.example.com/backoffice';
        $general->cpTrigger = null;
        $this->settings(['enabled' => true, 'route' => '/backoffice/mcp', 'authorizationRoute' => '/backoffice/mcp/auth']);

        try {
            $I->haveServerParameter('HTTP_HOST', 'cp.example.com');
            $I->haveServerParameter('HTTPS', 'on');
            $I->sendAjaxPostRequest('/backoffice/mcp/auth');
            $I->seeResponseCodeIs(401);
            $I->assertStringContainsString('"https://cp.example.com/.well-known/oauth-protected-resource/backoffice/mcp/auth"', Craft::$app->getResponse()->getHeaders()->get('WWW-Authenticate'));

            // Discovery requests outside the base URL's path are site requests, but stay on the control panel's host
            $I->amOnPage('/.well-known/oauth-protected-resource/backoffice/mcp/auth');
            $I->assertSame('https://cp.example.com/backoffice/mcp/auth', json_decode($I->grabPageSource(), true)['resource']);

            // URLs with the script name start at the web root
            $general->omitScriptNameInUrls = false;
            $general->usePathInfo = true;
            $I->assertSame('https://cp.example.com/index.php/backoffice/mcp/auth', Craft::$app->getOAuth()->resource('mcp'));
            $general->usePathInfo = false;
            $I->assertSame('https://cp.example.com/index.php?p=backoffice/mcp/auth', Craft::$app->getOAuth()->resource('mcp'));
        } finally {
            Craft::configure($general, $original);
        }
    }

    private function settings(array $config): void
    {
        $mcp = Craft::$app->get('mcp');
        (new ReflectionProperty($mcp, 'settings'))->setValue($mcp, new Settings($config));
    }

    private function user(array $permissions): User
    {
        $user = new User([
            'username' => 'mcp-' . StringHelper::randomString(6),
            'email' => 'mcp-' . StringHelper::randomString(6) . '@example.com',
            'active' => true,
        ]);
        Craft::$app->getElements()->saveElement($user, false);
        Craft::$app->getUserPermissions()->saveUserPermissions($user->id, $permissions);

        return $user;
    }

    private function token(User $user): string
    {
        $oauth = Craft::$app->getOAuth();
        $server = $oauth->server('mcp');
        $verifier = str_repeat('v', 43);

        $request = (new ServerRequest('GET', $oauth->url('authorize', 'mcp')))->withQueryParams([
            'response_type' => 'code',
            'client_id' => 'test-mcp',
            'redirect_uri' => 'https://client.example/callback',
            'scope' => Mcp::SCOPE_READ,
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

        $request = (new ServerRequest('POST', $oauth->url('token', 'mcp')))->withParsedBody([
            'grant_type' => 'authorization_code',
            'client_id' => 'test-mcp',
            'redirect_uri' => 'https://client.example/callback',
            'code' => $query['code'],
            'code_verifier' => $verifier,
        ]);

        return json_decode((string)$server->respondToAccessTokenRequest($request, new Response())->getBody(), true)['access_token'];
    }
}
