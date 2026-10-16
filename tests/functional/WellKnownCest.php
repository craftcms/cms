<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace crafttests\functional;

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
}
