<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace crafttests\functional\users;

use Craft;
use craft\elements\User;
use craft\errors\WrongEditionException;
use craft\helpers\UrlHelper;
use FunctionalTester;
use Throwable;
use yii\base\InvalidConfigException;
use yii\db\Exception;

/**
 * Test various actions you can perform on a user.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @author Global Network Group | Giel Tettelaar <giel@yellowflash.net>
 * @since 3.2
 */
class UserActionCest
{
    /**
     * @var string
     */
    public string $cpTrigger;

    /**
     * @var User|null
     */
    public ?User $activeUser;

    /**
     * @var User|null
     */
    public ?User $currentUser;

    /**
     * @param FunctionalTester $I
     * @throws Throwable
     * @throws WrongEditionException
     * @throws Exception
     */
    public function _before(FunctionalTester $I)
    {
        $this->currentUser = User::find()
            ->admin()
            ->one();

        $I->amLoggedInAs($this->currentUser);
        $this->cpTrigger = Craft::$app->getConfig()->getGeneral()->cpTrigger;
        $user = new User([
            'active' => true,
            'username' => 'craftcmsfunctionaltest',
            'email' => 'craft@cms.com',
        ]);

        Craft::$app->setEdition(Craft::Pro);
        $I->saveElement($user);
        Craft::$app->getUsers()->activateUser($user);
        Craft::$app->getUserPermissions()->saveUserPermissions($user->id, ['accessCp']);

        /** @var User|null $user */
        $user = User::find()
            ->id($user->id)
            ->one();
        $this->activeUser = $user;
    }

    /**
     * @param FunctionalTester $I
     * @throws InvalidConfigException
     * @throws \yii\base\Exception
     */
    public function seeUserImpersonation(FunctionalTester $I)
    {
        $I->amOnPage('/' . $this->cpTrigger . '/users/' . $this->activeUser->id . '');

        $I->see('Sign in as');

        Craft::$app->getConfig()->getGeneral()->requireUserAgentAndIpForSession = false;
        $I->submitForm('#userform', [
            'action' => 'users/impersonate',
            'redirect' => Craft::$app->getSecurity()->hashData(UrlHelper::cpUrl('dashboard')),
        ]);

        $I->see('Dashboard');

        $I->assertSame(
            (string)$this->activeUser->id,
            (string)Craft::$app->getUser()->getId()
        );
    }
}
