<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace tests\functional;

use craft\elements\User;
use FunctionalTester;
use Craft;

/**
 * Class FunctionalCest
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @author Global Network Group | Giel Tettelaar <giel@yellowflash.net>
 * @since 3.1
 */
class FunctionalCest
{
    // Public Methods
    // =========================================================================

    // Tests
    // =========================================================================

    /**
     * @param FunctionalTester $I
     */
    protected function _before(FunctionalTester $I)
    {
        $userEl = User::find()
            ->admin()
            ->one();

        Craft::$app->getUser()->setIdentity($userEl);

        $I->amLoggedInAs($userEl);
    }

    /**
     * @param FunctionalTester $I
     */
    public function seeSections(FunctionalTester $I)
    {
        $I->amOnPage('?p=/adminustriggerus/settings/sections');
        $I->see('Craft CMS Test section');
    }

    /**
     * @param FunctionalTester $I
     */
    public function seeTemplateHomepageTest(FunctionalTester $I)
    {
         $I->amOnPage('?p=/adminustriggerus/entries');
         $I->see('Craft CMS');
    }

    /**
     * @param FunctionalTester $I
     */
    public function seeSettingsPage(FunctionalTester $I)
    {
        $I->amOnPage('?p=/adminustriggerus/settings');
        $I->see('Settings');
    }
}
