<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace crafttests\functional\users;

use Craft;
use craft\elements\User;
use craft\helpers\ArrayHelper;
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
    /**
     * @var string
     */
    public $cpTrigger;

    /**
     * @var
     */
    public $currentUser;

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

        Craft::$app->setEdition(Craft::Pro);
    }

    /**
     * @param FunctionalTester $I
     */
    public function testMyAccountPage(FunctionalTester $I)
    {
        $I->amOnPage('/' . $this->cpTrigger . '/myaccount');

        $I->see('My Account');

        $I->submitForm('#userform', [
            'firstName' => 'IM A CHANGED FIRSTNAME',
        ]);

        $I->see('User saved');
        $I->seeInTitle('Users');

        // Check that the Db was updated.
        $I->assertSame(
            'IM A CHANGED FIRSTNAME',
            User::find()
                ->id($this->currentUser->id)
                ->one()->firstName
        );
    }

}
