<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace crafttests\functional;

use Craft;
use craft\helpers\Json;
use craft\helpers\UrlHelper;
use craft\mcp\Settings;
use craft\services\ProjectConfig;
use FunctionalTester;
use ReflectionProperty;

/**
 * Tests how MCP servers are advertised via `/.well-known/ai-catalog.json` and their Server Cards (SEP-2127).
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 5.12.0
 */
class McpDiscoveryCest
{
    private const REGISTRATION = ProjectConfig::PATH_USERS . '.allowPublicRegistration';

    private array $generalConfig;
    private mixed $allowPublicRegistration;

    public function _before(FunctionalTester $I): void
    {
        $general = Craft::$app->getConfig()->getGeneral();
        $this->generalConfig = [
            'baseCpUrl' => $general->baseCpUrl,
            'cpTrigger' => $general->cpTrigger,
            'defaultRateLimit' => $general->defaultRateLimit,
        ];
        $this->allowPublicRegistration = Craft::$app->getProjectConfig()->get(self::REGISTRATION);
        $this->registration(false);
        Craft::$app->getCache()->flush();
    }

    public function _after(FunctionalTester $I): void
    {
        Craft::configure(Craft::$app->getConfig()->getGeneral(), $this->generalConfig);
        Craft::$app->getProjectConfig()->set(self::REGISTRATION, $this->allowPublicRegistration);
        Craft::$app->get('mcp')->resetSettings();
        Craft::$app->getCache()->flush();
    }

    public function testPublicServerIsAdvertised(FunctionalTester $I): void
    {
        $this->settings(['enabled' => true, 'route' => '/mcp', 'authorizationRoute' => '/mcp/auth', 'publicEnabled' => true, 'publicRoute' => '/mcp']);

        $I->amOnPage('/.well-known/ai-catalog.json');
        $I->seeResponseCodeIs(200);
        $this->seeDiscoveryHeaders($I, 'application/ai-catalog+json');
        $host = parse_url(UrlHelper::siteUrl(), PHP_URL_HOST);
        $I->assertSame([
            'specVersion' => '1.0',
            'entries' => [
                [
                    'identifier' => "urn:air:$host:mcp:craft-test-site",
                    'type' => 'application/mcp-server-card+json',
                    'url' => UrlHelper::siteUrl('mcp/server-card'),
                ],
            ],
        ], Json::decode($I->grabPageSource()));

        $I->amOnPage('/mcp/server-card');
        $I->seeResponseCodeIs(200);
        $this->seeDiscoveryHeaders($I, 'application/mcp-server-card+json');
        $I->assertSame([
            '$schema' => 'https://static.modelcontextprotocol.io/schemas/v1/server-card.schema.json',
            'name' => implode('.', array_reverse(explode('.', $host))) . '/craft-test-site',
            'version' => explode('.', Craft::$app->getVersion())[0],
            'description' => 'Craft CMS MCP server',
            'title' => 'Craft test site',
            'remotes' => [['type' => 'streamable-http', 'url' => UrlHelper::siteUrl('mcp')]],
        ], Json::decode($I->grabPageSource()));

        // Without front-end registration, the authenticated server doesn't get a card
        $I->amOnPage('/mcp/auth/server-card');
        $I->seeResponseCodeIs(404);
    }

    public function testServerCardCaching(FunctionalTester $I): void
    {
        $this->settings(['publicEnabled' => true, 'publicRoute' => '/mcp']);

        $I->amOnPage('/mcp/server-card');
        $etag = Craft::$app->getResponse()->getHeaders()->get('ETag');
        $I->assertMatchesRegularExpression('/^"[0-9a-f]{40}"$/', $etag);

        $I->haveHttpHeader('If-None-Match', "\"stale\", $etag");
        $I->amOnPage('/mcp/server-card');
        $I->seeResponseCodeIs(304);
        $I->assertSame('', $I->grabPageSource());

        $I->haveHttpHeader('If-None-Match', '"stale"');
        $I->amOnPage('/mcp/server-card');
        $I->seeResponseCodeIs(200);
    }

    public function testServerCardPreflight(FunctionalTester $I): void
    {
        $this->settings(['publicEnabled' => true, 'publicRoute' => '/mcp']);

        $I->sendAjaxRequest('OPTIONS', '/mcp/server-card');
        $I->seeResponseCodeIs(204);
        $headers = Craft::$app->getResponse()->getHeaders();
        $I->assertSame('*', $headers->get('Access-Control-Allow-Origin'));
        $I->assertSame('Content-Type, If-None-Match', $headers->get('Access-Control-Allow-Headers'));
        $I->assertSame('', $I->grabPageSource());
    }

    public function testAuthenticatedServerRequiresPublicRegistration(FunctionalTester $I): void
    {
        $this->settings(['enabled' => true, 'route' => '/mcp', 'authorizationRoute' => '/mcp/auth']);

        $I->amOnPage('/.well-known/ai-catalog.json');
        $I->seeResponseCodeIs(404);

        $this->registration(true);
        $I->amOnPage('/.well-known/ai-catalog.json');
        $I->seeResponseCodeIs(200);
        $entries = Json::decode($I->grabPageSource())['entries'];
        $I->assertSame([UrlHelper::siteUrl('mcp/auth/server-card')], array_column($entries, 'url'));
        $I->assertStringEndsWith(':mcp:craft-test-site-admin', $entries[0]['identifier']);

        // The card points OAuth clients at the authorization endpoint
        $I->amOnPage('/mcp/auth/server-card');
        $I->seeResponseCodeIs(200);
        $card = Json::decode($I->grabPageSource());
        $I->assertStringEndsWith('/craft-test-site-admin', $card['name']);
        $I->assertSame([['type' => 'streamable-http', 'url' => UrlHelper::siteUrl('mcp/auth')]], $card['remotes']);
        $I->amOnPage('/mcp/server-card');
        $I->seeResponseCodeIs(404);
    }

    public function testBothServersAreAdvertised(FunctionalTester $I): void
    {
        $this->registration(true);
        $this->settings(['enabled' => true, 'route' => '/mcp', 'authorizationRoute' => '/mcp/auth', 'publicEnabled' => true, 'publicRoute' => '/mcp']);

        $I->amOnPage('/.well-known/ai-catalog.json');
        $I->assertSame(
            [UrlHelper::siteUrl('mcp/server-card'), UrlHelper::siteUrl('mcp/auth/server-card')],
            array_column(Json::decode($I->grabPageSource())['entries'], 'url'),
        );
    }

    public function testSharedEndpointIsAdvertisedOnce(FunctionalTester $I): void
    {
        // Requests without credentials reach the public server at a shared endpoint, so it's advertised as public
        $this->registration(true);
        $this->settings(['enabled' => true, 'route' => '/mcp', 'authorizationRoute' => '/mcp', 'publicEnabled' => true, 'publicRoute' => '/mcp']);

        $I->amOnPage('/.well-known/ai-catalog.json');
        $entries = Json::decode($I->grabPageSource())['entries'];
        $I->assertCount(1, $entries);
        $I->assertStringEndsWith(':mcp:craft-test-site', $entries[0]['identifier']);
    }

    public function testDisabledServersAreNotAdvertised(FunctionalTester $I): void
    {
        $this->registration(true);
        $this->settings(['enabled' => false, 'publicEnabled' => false]);

        $I->amOnPage('/.well-known/ai-catalog.json');
        $I->seeResponseCodeIs(404);
        $I->amOnPage('/mcp/server-card');
        $I->seeResponseCodeIs(404);
        $I->amOnPage('/mcp/auth/server-card');
        $I->seeResponseCodeIs(404);
    }

    public function testActionsRequireDiscoverableServers(FunctionalTester $I): void
    {
        $this->settings(['enabled' => false, 'publicEnabled' => false]);
        $I->amOnPage('/actions/mcp/catalog');
        $I->seeResponseCodeIs(404);

        // Server Cards are only served at their server's path
        $this->settings(['publicEnabled' => true, 'publicRoute' => '/mcp']);
        $I->amOnPage('/actions/mcp/server-card');
        $I->seeResponseCodeIs(404);
    }

    public function testControlPanelTriggerUrlsAreNotAdvertised(FunctionalTester $I): void
    {
        $cpTrigger = Craft::$app->getConfig()->getGeneral()->cpTrigger;
        $this->registration(true);
        $this->settings(['enabled' => true, 'route' => "/$cpTrigger/mcp", 'authorizationRoute' => "/$cpTrigger/mcp/auth"]);

        $I->amOnPage('/.well-known/ai-catalog.json');
        $I->seeResponseCodeIs(404);
        $I->amOnPage("/$cpTrigger/mcp/auth/server-card");
        $I->dontSeeResponseCodeIs(200);
    }

    public function testControlPanelBaseUrlsAreNotAdvertised(FunctionalTester $I): void
    {
        // The control panel lives at /mcp on the site's own host, without a trigger
        $general = Craft::$app->getConfig()->getGeneral();
        $general->baseCpUrl = UrlHelper::siteUrl('mcp');
        $general->cpTrigger = null;
        $this->registration(true);

        $this->settings(['enabled' => true, 'route' => '/mcp', 'authorizationRoute' => '/mcp/auth', 'publicEnabled' => true, 'publicRoute' => '/mcp']);
        $I->assertSame([], Craft::$app->get('mcp')->getSettings()->discoverableServers());

        // Paths outside the control panel's base URL are still advertised
        $this->settings(['enabled' => true, 'route' => '/ai', 'authorizationRoute' => '/ai/auth', 'publicEnabled' => true, 'publicRoute' => '/mcpx']);
        $I->assertSame(['mcpx' => true, 'ai/auth' => false], Craft::$app->get('mcp')->getSettings()->discoverableServers());

        // Requests go to the site when its base URL matches at least as closely as the control panel's
        $general->baseCpUrl = rtrim(Craft::$app->getSites()->getCurrentSite()->getBaseUrl(), '/');
        $this->settings(['enabled' => true, 'route' => '/mcp', 'authorizationRoute' => '/mcp/auth', 'publicEnabled' => true, 'publicRoute' => '/mcp']);
        $I->assertSame(['mcp' => true, 'mcp/auth' => false], Craft::$app->get('mcp')->getSettings()->discoverableServers());

        // So is everything, when the control panel is on another host
        $general->baseCpUrl = 'https://cp.example.com';
        $this->settings(['enabled' => true, 'route' => '/mcp', 'authorizationRoute' => '/mcp/auth', 'publicEnabled' => true, 'publicRoute' => '/mcp']);
        $I->assertSame(['mcp' => true, 'mcp/auth' => false], Craft::$app->get('mcp')->getSettings()->discoverableServers());
    }

    public function testDiscoveryIsRateLimited(FunctionalTester $I): void
    {
        $general = Craft::$app->getConfig()->getGeneral();
        $general->defaultRateLimit = 1;
        $this->settings(['publicEnabled' => true, 'publicRoute' => '/mcp']);

        $I->amOnPage('/mcp/server-card');
        $I->seeResponseCodeIs(200);
        $I->amOnPage('/mcp/server-card');
        $I->seeResponseCodeIs(429);

        // Each action is tracked separately
        $I->amOnPage('/.well-known/ai-catalog.json');
        $I->seeResponseCodeIs(200);
        $I->amOnPage('/.well-known/ai-catalog.json');
        $I->seeResponseCodeIs(429);

        // Disabling the default rate limit lifts it
        $general->defaultRateLimit = false;
        $I->amOnPage('/mcp/server-card');
        $I->seeResponseCodeIs(200);
    }

    private function seeDiscoveryHeaders(FunctionalTester $I, string $contentType): void
    {
        $headers = Craft::$app->getResponse()->getHeaders();
        $I->assertSame($contentType, $headers->get('Content-Type'));
        $I->assertSame('*', $headers->get('Access-Control-Allow-Origin'));
        $I->assertSame('GET', $headers->get('Access-Control-Allow-Methods'));
        $I->assertSame('ETag', $headers->get('Access-Control-Expose-Headers'));
        $I->assertSame('public, max-age=3600', $headers->get('Cache-Control'));
    }

    private function settings(array $config): void
    {
        $mcp = Craft::$app->get('mcp');
        (new ReflectionProperty($mcp, 'settings'))->setValue($mcp, new Settings($config));
    }

    private function registration(bool $allow): void
    {
        Craft::$app->getProjectConfig()->set(self::REGISTRATION, $allow);
    }
}
