<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace crafttests\functional;

use Codeception\Example;
use Craft;
use craft\elements\User;
use FunctionalTester;

/**
 * Test editing of sites
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @author Global Network Group | Giel Tettelaar <giel@yellowflash.net>
 * @since 3.2
 */
class SitesEditCest
{

    // Public Properties
    // =========================================================================

    /**
     * @var string
     */
    public $cpTrigger;

    /**
     * @var
     */
    public $currentUser;

    // Public Methods
    // =========================================================================

    /**
     * @param FunctionalTester $I
     */
    public function _before(FunctionalTester $I)
    {
        $this->currentUser = User::find()
            ->admin()
            ->one();

        $I->amLoggedInAs($this->currentUser);
        $this->cpTrigger = Craft::$app->getConfig()->getGeneral()->cpTrigger;
    }

    // Tests
    // =========================================================================

    /**
     * @param FunctionalTester $I
     */
    public function testEditSite(FunctionalTester $I)
    {
        $I->amOnPage('/'.$this->cpTrigger.'/settings/sites');
        $I->click('Craft CMS testing');
        $I->see('Craft CMS testing');

        $I->submitForm('#main-form', ['name' => 'EDITED SITE']);

        $site = Craft::$app->getSites()->getSiteByHandle('default');
        $I->assertSame('EDITED SITE', $site->name);
    }
}
