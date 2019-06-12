<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace crafttests\functional\users;

use Craft;
use craft\elements\User;
use FunctionalTester;

/**
 * Test editing of a user.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @author Global Network Group | Giel Tettelaar <giel@yellowflash.net>
 * @since 3.2
 */
class EditUserCest
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
    public function testMyAccountPage(FunctionalTester $I)
    {
        $I->amOnPage('/'.$this->cpTrigger.'/myaccount');

        $I->see('My account');

        $I->submitForm('#userform', [
            'firstName' => 'IM A CHANGED FIRSTNAME'
        ]);

        $I->see('Users');

        // Check that the Db was updated.
        $I->assertSame(
            'IM A CHANGED FIRSTNAME',
            User::find()
                ->id($this->currentUser->id)
                ->one()->firstName
        );
    }
}
