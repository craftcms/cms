<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace crafttests\unit\services;

use Craft;
use craft\db\Table;
use craft\helpers\Db;
use craft\mail\Message;
use craft\services\Users;
use craft\test\TestCase;
use CraftCms\Cms\User\Elements\User;
use crafttests\fixtures\UserGroupsFixture;
use DateTime;
use UnitTester;
use yii\db\Exception as YiiDbException;

/**
 * Unit tests for the Users service
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @author Global Network Group | Giel Tettelaar <giel@yellowflash.net>
 * @since 3.2
 */
class UsersTest extends TestCase
{
    /**
     * @var UnitTester
     */
    protected UnitTester $tester;

    /**
     * @var Users
     */
    protected Users $users;

    /**
     * @var User
     */
    protected User $pendingUser;

    /**
     * @var User
     */
    protected User $lockedUser;

    /**
     * @var User
     */
    protected User $activeUser;

    /**
     * @var User
     */
    protected User $suspendedUser;

    public function _fixtures(): array
    {
        return [
            'user-groups' => [
                'class' => UserGroupsFixture::class,
            ],
        ];
    }

    public function testSendActivationEmail(): void
    {
        // Test send activation email with password null
        $this->pendingUser->password = null;
        $this->users->sendActivationEmail($this->pendingUser);
        $this->testUsersEmailFunctions(
            'account_activation',
            'setpassword?code='
        );

        $this->pendingUser->password = 'some_password';
        $this->users->sendActivationEmail($this->pendingUser);
        $this->testUsersEmailFunctions(
            'account_activation',
            'verifyemail?code='
        );
        $this->pendingUser->password = null;

        // Test send Email Verify
        $this->users->sendNewEmailVerifyEmail($this->pendingUser);
        $this->testUsersEmailFunctions(
            'verify_new_email',
            'verifyemail?code='
        );
    }


    /**
     * @param string $desiredKey
     * @param string $desiredLinkResult
     */
    protected function testUsersEmailFunctions(string $desiredKey, string $desiredLinkResult)
    {
        /* @var Message $lastEmail */
        $lastEmail = $this->tester->grabLastSentEmail();
        self::assertSame($desiredKey, $lastEmail->key);
        self::assertStringContainsString(
            $desiredLinkResult,
            urldecode($lastEmail->variables['link'])
        );
    }

    protected function ensurePasswordValidationReturns(bool $result)
    {
        $this->tester->mockCraftMethods('security', [
            'validatePassword' => $result,
        ]);
    }

    /**
     * @param array $collumns
     * @param array $conditions
     * @return int
     * @throws YiiDbException
     */
    protected function updateUser(array $collumns, array $conditions): int
    {
        // First. Set the correct conditions
        return Craft::$app->getDb()->createCommand()
            ->update(Table::USERS, $collumns, $conditions)
            ->execute();
    }

    /**
     * @param int|null $userId
     * @return User|null
     */
    protected function getUser(?int $userId): ?User
    {
        return Craft::$app->getUsers()->getUserById($userId);
    }

    /**
     * @inheritdoc
     */
    protected function _before(): void
    {
        parent::_before();

        $this->users = Craft::$app->getUsers();

        $this->pendingUser = new User(
            [
                'firstName' => 'John',
                'lastName' => 'Smith',
                'username' => 'jsmith',
                'unverifiedEmail' => 'jsmith@gmail.com',
                'email' => 'jsmith@gmail.com',
                'pending' => true,
            ]
        );

        $this->lockedUser = new User(
            [
                'active' => true,
                'firstName' => 'locked',
                'lastName' => 'user',
                'username' => 'lockedUser',
                'email' => 'locked@user.com',
                'locked' => true,
                'invalidLoginCount' => 2,
                'lockoutDate' => Db::prepareDateForDb(new DateTime('now')),
            ]
        );

        $this->activeUser = new User(
            [
                'active' => true,
                'firstName' => 'active',
                'lastName' => 'user',
                'username' => 'activeUser',
                'email' => 'active@user.com',
            ]
        );

        $this->suspendedUser = new User(
            [
                'active' => true,
                'firstName' => 'suspended',
                'lastName' => 'user',
                'username' => 'suspendedUser',
                'email' => 'suspended@user.com',
                'suspended' => true,
            ]
        );

        $this->tester->saveElement($this->pendingUser);
        $this->tester->saveElement($this->suspendedUser);
        $this->tester->saveElement($this->lockedUser);
        $this->tester->saveElement($this->activeUser);
    }

    /**
     * @inheritdoc
     */
    protected function _after()
    {
        parent::_after();

        $this->tester->deleteElement($this->pendingUser);
        $this->tester->deleteElement($this->suspendedUser);
        $this->tester->deleteElement($this->lockedUser);
        $this->tester->deleteElement($this->activeUser);
    }
}
