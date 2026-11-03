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
use craft\models\Site;
use FunctionalTester;
use yii\i18n\MessageSource;
use yii\i18n\MissingTranslationEvent;

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

        // Invisible-only names
        foreach (["\u{200B} \u{3164}", "\u{206A}", "\u{E0061}", "\u{FE0F}", "\u{206A} \u{E0061}\u{FE0F}"] as $name) {
            $response = $this->register($I, $name);
            $I->assertSame(400, Craft::$app->getResponse()->getStatusCode());
            $I->assertSame('invalid_client_metadata', $response['error']);
        }

        // Variation selectors are kept in otherwise visible names
        $response = $this->register($I, "My App \u{2764}\u{FE0F}");
        $I->assertSame(201, Craft::$app->getResponse()->getStatusCode());
        $I->assertSame("My App \u{2764}\u{FE0F}", $response['client_name']);
    }

    public function testRegistrationRejectsReservedClientNames(FunctionalTester $I): void
    {
        $names = [
            'Craft',
            'craft cms',
            'Ｃｒａｆｔ ＣＭＳ',
            "Craft\u{200B} \u{00A0}CMS",
            "\u{0421}raft \u{0421}MS", // Cyrillic Es
            Craft::$app->getSystemName(),
            $this->cyrillicLookalike(Craft::$app->getSystemName()),
        ];
        foreach (Craft::$app->getSites()->getAllSites(true) as $site) {
            $names[] = mb_strtoupper($site->getName());
            $names[] = $this->cyrillicLookalike($site->getName());
        }

        foreach ($names as $name) {
            $response = $this->register($I, $name);
            $I->assertSame(400, Craft::$app->getResponse()->getStatusCode(), "“{$name}” should be reserved.");
            $I->assertSame('The client name is reserved.', $response['error_description']);
        }

        $this->register($I, 'Craft CMS Companion');
        $I->assertSame(201, Craft::$app->getResponse()->getStatusCode());
    }

    public function testRegistrationRejectsTranslatedBrandNames(FunctionalTester $I): void
    {
        // Translate the system name into a language other than the registration request’s
        $systemName = Craft::$app->getSystemName();
        $source = Craft::$app->getI18n()->getMessageSource('site');
        $handler = function(MissingTranslationEvent $event) use ($systemName) {
            if ($event->language === 'fr' && $event->message === $systemName) {
                $event->translatedMessage = 'Boutique';
            }
        };
        $source->on(MessageSource::EVENT_MISSING_TRANSLATION, $handler);

        try {
            foreach (['Boutique', "\u{0412}outique"] as $name) { // Cyrillic Ve
                $response = $this->register($I, $name);
                $I->assertSame(400, Craft::$app->getResponse()->getStatusCode(), "“{$name}” should be reserved.");
                $I->assertSame('The client name is reserved.', $response['error_description']);
            }
        } finally {
            $source->off(MessageSource::EVENT_MISSING_TRANSLATION, $handler);
        }
    }

    public function testRegistrationRejectsLookalikesOfNonLatinSiteNames(FunctionalTester $I): void
    {
        $sitesService = Craft::$app->getSites();
        $siteNames = [
            'cyrillic' => ["\u{0410}\u{0440}\u{0435}\u{0445}", 'Apex'], // Cyrillic “Арех”, Latin look-alike
            'greek' => ["\u{039A}\u{0391}\u{03A4}\u{039F}", 'KATO'], // Greek “ΚΑΤΟ”, Latin look-alike
        ];

        foreach ($siteNames as $handle => [$siteName, $lookalike]) {
            $site = new Site([
                'groupId' => $sitesService->getPrimarySite()->groupId,
                'name' => $siteName,
                'handle' => $handle,
                'language' => 'en-US',
            ]);
            $I->assertTrue($sitesService->saveSite($site), json_encode($site->getErrors()));

            foreach ([$siteName, $lookalike, mb_strtolower($lookalike)] as $name) {
                $response = $this->register($I, $name);
                $I->assertSame(400, Craft::$app->getResponse()->getStatusCode(), "“{$name}” should be reserved.");
                $I->assertSame('The client name is reserved.', $response['error_description']);
            }

            $this->register($I, "$lookalike Companion");
            $I->assertSame(201, Craft::$app->getResponse()->getStatusCode());
        }
    }

    /**
     * Swaps Latin letters for look-alike Cyrillic ones.
     */
    private function cyrillicLookalike(string $str): string
    {
        return strtr($str, [
            'a' => "\u{0430}",
            'c' => "\u{0441}",
            'e' => "\u{0435}",
            'o' => "\u{043E}",
            'p' => "\u{0440}",
            'A' => "\u{0410}",
            'C' => "\u{0421}",
            'E' => "\u{0415}",
            'O' => "\u{041E}",
            'P' => "\u{0420}",
        ]);
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
