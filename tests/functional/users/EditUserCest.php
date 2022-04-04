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
    /**
     * @var string
     */
    public string $cpTrigger;

    /**
     * @var User|null
     */
    public ?User $currentUser;

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
    public function testMyAccountPage(FunctionalTester $I): void
    {
        $I->amOnPage('/' . $this->cpTrigger . '/myaccount');

        $I->see('My Account');

        $I->submitForm('#userform', [
            'fullName' => 'IM A CHANGED FULLNAME',
        ]);

        $I->see('User saved');
        $I->seeInTitle('Users');

        /** @var User $user */
        $user = User::find()
            ->id($this->currentUser->id)
            ->one();

        // Check that the Db was updated.
        $I->assertSame(
            'IM A CHANGED FULLNAME',
            $user->fullName
        );
    }
}
