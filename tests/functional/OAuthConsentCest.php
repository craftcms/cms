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
use FunctionalTester;

/**
 * Tests the OAuth consent page.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 5.12.0
 */
class OAuthConsentCest
{
    public function _before(FunctionalTester $I): void
    {
        $key = openssl_pkey_new(['private_key_bits' => 2048]);
        $config = Craft::$app->getConfig()->getOAuth();
        openssl_pkey_export($key, $config->privateKey);
        $config->publicKey = openssl_pkey_get_details($key)['key'];

        $I->amLoggedInAs(User::find()->admin()->one());
    }

    public function testConsentPageCannotBeFramed(FunctionalTester $I): void
    {
        $clientId = StringHelper::UUID();
        Craft::$app->getDb()->createCommand()->insert(Table::OAUTH_CLIENTS, [
            'id' => $clientId,
            'name' => 'Test MCP client',
            'redirect_uris' => '["https://client.example/callback"]',
            'grant_types' => '["authorization_code","refresh_token"]',
            'scopes' => '["mcp:read","mcp:write","mcp:admin"]',
            'revoked' => false,
        ])->execute();

        $cpTrigger = Craft::$app->getConfig()->getGeneral()->cpTrigger;
        $I->amOnPage("/$cpTrigger/oauth/mcp/authorize?" . http_build_query([
            'response_type' => 'code',
            'client_id' => $clientId,
            'redirect_uri' => 'https://client.example/callback',
            'code_challenge' => rtrim(strtr(base64_encode(hash('sha256', 'verifier', true)), '+/', '-_'), '='),
            'code_challenge_method' => 'S256',
            'state' => 'state',
        ]));

        $I->seeResponseCodeIs(200);
        $I->see('You will be redirected to https://client.example/callback');
        $I->assertSame('DENY', Craft::$app->getResponse()->getHeaders()->get('X-Frame-Options'));
    }
}
