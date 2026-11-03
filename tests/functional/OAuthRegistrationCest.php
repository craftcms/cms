<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace crafttests\functional;

use Craft;
use craft\config\OAuthConfig;
use FunctionalTester;

/**
 * Tests OAuth client registration.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 5.12.0
 */
class OAuthRegistrationCest
{
    private OAuthConfig $config;
    private int|false $registrationRateLimit;
    private int $registrationRateLimitDuration;

    public function _before(FunctionalTester $I): void
    {
        $this->config = Craft::$app->getConfig()->getOAuth();
        $this->registrationRateLimit = $this->config->registrationRateLimit;
        $this->registrationRateLimitDuration = $this->config->registrationRateLimitDuration;

        // Start each test with fresh rate limit allowances
        Craft::$app->getCache()->flush();
    }

    public function _after(FunctionalTester $I): void
    {
        $this->config->registrationRateLimit = $this->registrationRateLimit;
        $this->config->registrationRateLimitDuration = $this->registrationRateLimitDuration;
    }

    public function testRegistrationIsRateLimited(FunctionalTester $I): void
    {
        $this->config->registrationRateLimit = 2;
        $this->config->registrationRateLimitDuration = 3600;

        $this->register($I);
        $I->seeResponseCodeIs(201);
        $this->register($I);
        $I->seeResponseCodeIs(201);
        $this->register($I);
        $I->seeResponseCodeIs(429);

        // The limit only applies to registration
        $cpTrigger = Craft::$app->getConfig()->getGeneral()->cpTrigger;
        $I->amOnPage("/$cpTrigger/oauth/mcp/authorize");
        $I->dontSeeResponseCodeIs(429);

        // Disabling it lifts it
        $this->config->registrationRateLimit = false;
        $this->register($I);
        $I->seeResponseCodeIs(201);
    }

    private function register(FunctionalTester $I): void
    {
        $cpTrigger = Craft::$app->getConfig()->getGeneral()->cpTrigger;
        $I->sendAjaxPostRequest("/$cpTrigger/oauth/mcp/register", [
            'client_name' => 'Test client',
            'redirect_uris' => ['https://client.example/callback'],
        ]);
    }
}
