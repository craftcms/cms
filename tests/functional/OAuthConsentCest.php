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
        $cpTrigger = Craft::$app->getConfig()->getGeneral()->cpTrigger;
        $I->amOnPage("/$cpTrigger/oauth/mcp/authorize?" . http_build_query($this->authorizeParams()));

        $I->seeResponseCodeIs(200);
        $I->see('Authorize “Test MCP client”', 'h1');
        $I->see('You will be redirected to client.example.');
        $I->see('client.example', 'strong');
        $I->dontSee('https://client.example/callback');
        $I->assertSame('DENY', Craft::$app->getResponse()->getHeaders()->get('X-Frame-Options'));
        $I->seeElement("form[action*=\"/$cpTrigger/oauth/mcp/approve\"]");
    }

    public function testFrontEndConsentPagePostsToFrontEnd(FunctionalTester $I): void
    {
        $actionTrigger = Craft::$app->getConfig()->getGeneral()->actionTrigger;
        $I->amOnPage("/$actionTrigger/oauth/authorize?" . http_build_query(['resource' => 'mcp'] + $this->authorizeParams()));

        $I->seeResponseCodeIs(200);
        $I->seeElement("form[action*=\"$actionTrigger/oauth/approve\"][action*=\"resource=mcp\"]");
        $I->seeElement("button[formaction*=\"$actionTrigger/oauth/deny\"][formaction*=\"resource=mcp\"]");
    }

    public function testRegistrationNormalizesClientNames(FunctionalTester $I): void
    {
        $response = $this->register($I, "  My\u{200B}  Cool\u{202E}\tApp  ");
        $I->assertSame(201, Craft::$app->getResponse()->getStatusCode());
        $I->assertSame('My Cool App', $response['client_name']);
    }

    public function testRegistrationLimitsClientNameLength(FunctionalTester $I): void
    {
        $this->register($I, str_repeat('a', 64));
        $I->assertSame(201, Craft::$app->getResponse()->getStatusCode());

        $response = $this->register($I, str_repeat('a', 65));
        $I->assertSame(400, Craft::$app->getResponse()->getStatusCode());
        $I->assertSame('invalid_client_metadata', $response['error']);

        $response = $this->register($I, "\u{200B} \u{3164}");
        $I->assertSame(400, Craft::$app->getResponse()->getStatusCode());
        $I->assertSame('invalid_client_metadata', $response['error']);
    }

    public function testRegistrationRejectsReservedClientNames(FunctionalTester $I): void
    {
        $names = [
            'Craft',
            'craft cms',
            'Ｃｒａｆｔ ＣＭＳ',
            "Craft\u{200B} \u{00A0}CMS",
            Craft::$app->getSystemName(),
            ...array_map(fn($site) => mb_strtoupper($site->getName()), Craft::$app->getSites()->getAllSites(true)),
        ];

        foreach ($names as $name) {
            $response = $this->register($I, $name);
            $I->assertSame(400, Craft::$app->getResponse()->getStatusCode(), "“{$name}” should be reserved.");
            $I->assertSame('The client name is reserved.', $response['error_description']);
        }

        $this->register($I, 'Craft CMS Companion');
        $I->assertSame(201, Craft::$app->getResponse()->getStatusCode());
    }

    private function register(FunctionalTester $I, string $name): array
    {
        $cpTrigger = Craft::$app->getConfig()->getGeneral()->cpTrigger;
        $I->sendAjaxPostRequest("/$cpTrigger/oauth/mcp/register", [
            'client_name' => $name,
            'redirect_uris' => ['https://client.example/callback'],
        ]);

        return json_decode($I->grabPageSource(), true);
    }

    private function authorizeParams(): array
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

        return [
            'response_type' => 'code',
            'client_id' => $clientId,
            'redirect_uri' => 'https://client.example/callback',
            'code_challenge' => rtrim(strtr(base64_encode(hash('sha256', 'verifier', true)), '+/', '-_'), '='),
            'code_challenge_method' => 'S256',
            'state' => 'state',
        ];
    }
}
