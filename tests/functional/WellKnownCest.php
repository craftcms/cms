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

            // URLs with the script name are built on the request's host, but the endpoints stay on the primary site's,
            // even for metadata requested through the control panel on its own host
            $general->baseCpUrl = 'https://cp.example.com';
            $I->haveServerParameter('HTTP_HOST', 'cp.example.com');
            $I->haveServerParameter('HTTPS', 'on');
            $I->amOnPage("/$general->cpTrigger/$general->actionTrigger/oauth/metadata?resource=graphql");
            $I->seeResponseCodeIs(200);
            $I->assertSame($endpoint, Json::decode($I->grabPageSource())['registration_endpoint']);
            $general->baseCpUrl = null;
            $I->haveServerParameter('HTTP_HOST', parse_url($endpoint, PHP_URL_HOST));

            $I->sendAjaxPostRequest(UrlHelper::rootRelativeUrl($endpoint), [
                'client_name' => 'Test client',
                'redirect_uris' => ['https://client.example/callback'],
            ]);
            $I->seeResponseCodeIs(201);
        } finally {
            $general->omitScriptNameInUrls = true;
            $general->baseCpUrl = null;
        }
    }
}
