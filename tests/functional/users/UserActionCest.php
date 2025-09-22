<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace crafttests\functional\users;

use Craft;
use craft\elements\User;
use CraftCms\Cms\Config\GeneralConfig;
use CraftCms\Cms\Edition;
use CraftCms\Cms\Edition\Exceptions\WrongEditionException;
use FunctionalTester;
use Throwable;
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
    public ?User $activeUser = null;

    /**
     * @var User|null
     */
    public ?User $currentUser = null;

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
        $this->cpTrigger = app(GeneralConfig::class)->cpTrigger;
        $user = new User([
            'active' => true,
            'username' => 'craftcmsfunctionaltest',
            'email' => 'craft@cms.com',
        ]);

        Edition::set(Edition::Pro);
        $I->saveElement($user);
        Craft::$app->getUsers()->activateUser($user);
        Craft::$app->getUserPermissions()->saveUserPermissions($user->id, ['accessCp']);

        /** @var User|null $user */
        $user = User::find()
            ->id($user->id)
            ->one();
        $this->activeUser = $user;
    }
}
