<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace crafttests\functional;

use Craft;
use craft\elements\User;
use FunctionalTester;

/**
 * Test editing of user groups
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @author Global Network Group | Giel Tettelaar <giel@yellowflash.net>
 * @since 3.2
 */
class UserGroupEditCest
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
        $I->amOnPage('/'.$this->cpTrigger.'/settings/users');
        $I->click('Test group1');
        $I->see('Test group1');

        $I->submitForm('#main-form', ['name' => 'EDITED USERGROUP']);

        $group = Craft::$app->getUserGroups()->getGroupByHandle('testGroup1');
        $I->assertSame('EDITED USERGROUP', $group->name);
    }
}
