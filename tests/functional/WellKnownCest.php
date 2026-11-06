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
}
