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
use FunctionalTester;

/**
 * Tests `.well-known/*` discoverable URLs.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 5.11.0
 */
class WellKnownCest
{
    /**
     * @param FunctionalTester $I
     */
    public function testPasskeyEndpoints(FunctionalTester $I)
    {
        $I->amOnPage('/.well-known/passkey-endpoints');
        $I->seeResponseCodeIs(200);
        $I->assertSame('{}', $I->grabPageSource());
    }

    /**
     * @param FunctionalTester $I
     */
    public function testOAuthDiscoveryDoesNotRevealCpTrigger(FunctionalTester $I)
    {
        $cpTrigger = Craft::$app->getConfig()->getGeneral()->cpTrigger;

        $I->amOnPage('/.well-known/oauth-protected-resource/actions/graphql/api');
        $I->seeResponseCodeIs(200);
        $I->assertStringNotContainsString($cpTrigger, $I->grabPageSource());
        $issuer = Json::decode($I->grabPageSource())['authorization_servers'][0];

        $I->amOnPage('/.well-known/oauth-authorization-server' . parse_url($issuer, PHP_URL_PATH));
        $I->seeResponseCodeIs(200);
        $I->assertStringNotContainsString($cpTrigger, $I->grabPageSource());
        $metadata = Json::decode($I->grabPageSource());
        $I->assertSame($issuer, $metadata['issuer']);

        // The advertised endpoints work on the front end
        $I->sendAjaxPostRequest(UrlHelper::rootRelativeUrl($metadata['registration_endpoint']), [
            'client_name' => 'Test client',
            'redirect_uris' => ['https://client.example/callback'],
        ]);
        $I->seeResponseCodeIs(201);
    }

    /**
     * @param FunctionalTester $I
     */
    public function testOAuthEndpointsRespectScriptNameRouting(FunctionalTester $I)
    {
        $general = Craft::$app->getConfig()->getGeneral();
        $general->omitScriptNameInUrls = false;
        try {
            $I->amOnPage('/.well-known/oauth-authorization-server/actions/oauth/graphql');
            $I->seeResponseCodeIs(200);
            $endpoint = Json::decode($I->grabPageSource())['registration_endpoint'];
            $I->assertStringContainsString('/index.php?p=actions/oauth/register', $endpoint);

            $I->sendAjaxPostRequest(UrlHelper::rootRelativeUrl($endpoint), [
                'client_name' => 'Test client',
                'redirect_uris' => ['https://client.example/callback'],
            ]);
            $I->seeResponseCodeIs(201);
        } finally {
            $general->omitScriptNameInUrls = true;
        }
    }

    /**
     * @param FunctionalTester $I
     */
    public function testOAuthDiscoveryOnlyServesRoutedResources(FunctionalTester $I)
    {
        $general = Craft::$app->getConfig()->getGeneral();
        // The authenticated MCP server defaults to the control panel, so its discovery metadata has the cpTrigger
        $I->assertStringContainsString("/$general->cpTrigger/", Craft::$app->getOAuth()->resource('mcp'));

        $I->amOnPage('/.well-known/oauth-protected-resource/actions/graphql/api?oauthResource=mcp');
        $I->seeResponseCodeIs(200);
        $I->assertStringNotContainsString($general->cpTrigger, $I->grabPageSource());

        // Metadata is only served from its routes, which can't be pointed at another resource
        foreach (['', "/$general->cpTrigger"] as $prefix) {
            foreach (['resource', 'metadata'] as $action) {
                foreach (['resource', 'oauthResource'] as $param) {
                    $I->amOnPage("$prefix/$general->actionTrigger/oauth/$action?$param=mcp");
                    $I->seeResponseCodeIs(404);
                }
            }
        }
    }
}
