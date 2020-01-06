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
    public function testSaveUserFunctions(FunctionalTester $I)
    {
        $I->amOnPage('/'.$this->cpTrigger.'/users/new');
        $I->see('New User');

        $variables = [
            'username' => 'newusernameforcreateuserfunctest',
            'firstName' => 'NewUserFirstName',
            'lastName' => 'NewUserLastName',
            'email' => 'NewUser@email.com',
            'fields' => ['exampleTextField1' => 'Test data']
        ];

        $I->submitForm('#userform', $variables);

        unset($variables['fields']);

        $I->see('User saved');
        $I->see('Users');

        $user = $I->assertElementsExist(User::class, $variables, 1, true);
        $user = ArrayHelper::firstValue($user);

        $I->amOnPage('/'.$this->cpTrigger.'/users/'.$user->id.'');
        $I->submitForm('#userform', []);
    }

    /**
     * @param FunctionalTester $I
     */
    public function testNewUserValidationError(FunctionalTester $I)
    {
        $I->amOnPage('/'.$this->cpTrigger.'/users/new');
        $I->see('New User');

        $I->submitForm('#userform', []);
        $I->see('Couldn’t save user.');
        $I->see('Username cannot be blank.');
        $I->see('Email cannot be blank.');

        $I->submitForm('#userform', [
            'username' => 'testusernametestusername32798132789312789',
            'email' => 'test',
            'fields' => ['exampleTextField1' => 'Test data']
        ]);

        $I->see('Email is not a valid email address.');
    }

    /**
     * @param FunctionalTester $I
     */
    public function testMyAccountPage(FunctionalTester $I)
    {
        $I->amOnPage('/'.$this->cpTrigger.'/myaccount');

        $I->see('My account');

        $I->submitForm('#userform', [
            'firstName' => 'IM A CHANGED FIRSTNAME',
            'fields' => ['exampleTextField1' => 'Test data']
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

    /**
     * @param FunctionalTester $I
     */
    public function testCustomFieldValidation(FunctionalTester $I)
    {
        $I->amOnPage('/'.$this->cpTrigger.'/myaccount');

        $I->see('My account');

        $I->submitForm('#userform', [
            'firstName' => 'IM A CHANGED FIRSTNAME',
        ]);

        $I->canSeeInCurrentUrl('myaccount');
        $I->see('Example text field 1 cannot be blank.');
    }
}
