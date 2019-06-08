<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace crafttests\unit\services;

use Craft;
use craft\base\Element;
use craft\db\Query;
use craft\db\Table;
use craft\elements\User;
use craft\helpers\Db;
use craft\services\Users;
use craft\test\TestCase;
use crafttests\fixtures\UserGroupsFixture;
use UnitTester;
use yii\base\InvalidArgumentException;
use DateTime;
use DateTimeZone;

/**
 * Unit tests for the Users service
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @author Global Network Group | Giel Tettelaar <giel@yellowflash.net>
 * @since 3.2
 */
class UsersTest extends TestCase
{
    // Public Properties
    // =========================================================================

    /**
     * @var UnitTester
     */
    protected $tester;

    /**
     * @var Users
     */
    protected $users;

    /**
     * @var User
     */
    protected $pendingUser;

    /**
     * @var User
     */
    protected $lockedUser;

    /**
     * @var User
     */
    protected $activeUser;

    /**
     * @var User
     */
    protected $suspendedUser;

    // Public Methods
    // =========================================================================

    public function _fixtures() : array
    {
        return [
            'user-groups' => [
                'class' => UserGroupsFixture::class
            ]
        ];
    }

    // Tests
    // =========================================================================

    public function testUserCreation()
    {
        $this->assertSame(User::STATUS_ACTIVE, $this->lockedUser->getStatus());
        $this->assertTrue($this->lockedUser->locked);
        $this->assertSame(User::STATUS_PENDING, $this->pendingUser->getStatus());
        $this->assertSame(User::STATUS_ACTIVE, $this->activeUser->getStatus());
        $this->assertSame(User::STATUS_SUSPENDED, $this->suspendedUser->getStatus());
        $this->assertTrue($this->suspendedUser->suspended);
    }

    public function testUserActivation()
    {
        $this->users->activateUser($this->pendingUser);

        $user = $this->getUser($this->pendingUser->id);

        $this->assertSame(User::STATUS_ACTIVE, $user->getStatus());
        $this->assertSame('jsmith', $user->username);
    }

    public function testUserActivationEmailAsUsernameWithAnUnverifedEmail()
    {
        // Set useEmailAsUsername to true and add an unverified email.
        Craft::$app->getConfig()->getGeneral()->useEmailAsUsername = true;
        $this->saveElement($this->pendingUser);

        $this->users->activateUser($this->pendingUser);

        $user = $this->getUser($this->pendingUser->id);

        $this->assertSame(User::STATUS_ACTIVE, $user->getStatus());
        $this->assertSame('jsmith@gmail.com', $user->username);
    }

    public function testUserActivationEmailAsUsernameWithNoUnverifedEmail()
    {
        // Run the same test as above but without an unverified email.
        Craft::$app->getConfig()->getGeneral()->useEmailAsUsername = true;

        // Remove the unverifiedEmail property from the user record - meaning no username will be set.
        $this->pendingUser->unverifiedEmail = null;

        $this->users->activateUser($this->pendingUser);

        $user = $this->getUser($this->pendingUser->id);

        $this->assertSame(User::STATUS_ACTIVE, $user->getStatus());
        $this->assertSame('jsmith', $user->username);
    }

    public function testUserUnlocking()
    {
        $this->users->unlockUser($this->lockedUser);

        $this->assertFalse($this->lockedUser->locked);
        $this->assertNull($this->lockedUser->lockoutDate);
        $this->assertNull($this->lockedUser->invalidLoginCount);
        $this->assertSame(User::STATUS_ACTIVE, $this->lockedUser->getStatus());
    }

    public function testUserSuspending()
    {
        $this->users->suspendUser($this->activeUser);

        $this->assertTrue($this->activeUser->suspended);
        $this->assertSame(User::STATUS_SUSPENDED, $this->activeUser->getStatus());
    }

    public function testUserUnSuspending()
    {
        $this->users->unsuspendUser($this->suspendedUser);

        $this->assertFalse($this->suspendedUser->suspended);
        $this->assertSame(User::STATUS_ACTIVE, $this->suspendedUser->getStatus());
    }

    /**
     * @todo Monitor this one doesn't break on travis
     * @throws \Exception
     */
    public function testSetVerificaitonCodeOnUser()
    {
        $verificationCode = $this->users->setVerificationCodeOnUser($this->pendingUser);
        $dateTime = new DateTime(null, new DateTimeZone('UTC'));

        $user = (new Query())
            ->select('*')
            ->from(Table::USERS)->where(['id' => $this->pendingUser->id])->one();

        $this->assertSame(32, strlen($verificationCode));
        $this->assertNotNull($user['verificationCode']);

        // Check the date with a delta of 1.5 seconds.
        $this->assertEqualsWithDelta(
            $dateTime->format('Y-m-d H:i:s'),
            $user['verificationCodeIssuedDate'],
            1.5
        );
    }

    public function testUserGroupAssignment()
    {
        // Need fancy Craft for this.
        Craft::$app->setEdition(Craft::Pro);

        $this->users->assignUserToGroups(
            $this->activeUser->id,
            ['1000', '1001', '1002']
        );

        $groups = $this->activeUser->getGroups();
        $this->assertCount(3, $groups);
    }

    public function testUserGroupAssignmentInvalidation()
    {
        // Need fancy Craft for this.
        Craft::$app->setEdition(Craft::Pro);

        $this->users->assignUserToGroups(
            $this->activeUser->id,
            ['1000']
        );

        $groups = $this->activeUser->getGroups();
        $this->assertCount(1, $groups);

        // Invalidate the currentGroups - otherwise memoization will ruin our tests.
        $this->setInaccessibleProperty($this->activeUser, '_groups', null);

        $this->users->assignUserToGroups(
            $this->activeUser->id,
            ['1001', '1002']
        );

        // There should now be 2 - not three.
        $groups = $this->activeUser->getGroups();
        $this->assertCount(2, $groups);
    }

    public function testUserAssignmentToDefaultGroup()
    {
        Craft::$app->setEdition(Craft::Pro);
        Craft::$app->getProjectConfig()->set('users.defaultGroup', '9bea2a4a-52c9-42xe-b30b-dd844c4343f4');

        $this->users->assignUserToDefaultGroup($this->activeUser);

        $groups = $this->activeUser->getGroups();
        $this->assertCount(1, $groups);
        $this->assertSame('Group 3', $groups[0]->name);
    }

    public function testHandleInvalidLogin()
    {
        $this->users->handleInvalidLogin($this->activeUser);
        $dateTime = new DateTime('now', new DateTimeZone('UTC'));

        $user = $this->getUserQuery($this->activeUser->id);

        $this->assertSame('1', (string)$user['invalidLoginCount']);
        $this->assertEqualsWithDelta($user['invalidLoginWindowStart'], $dateTime->format('Y-m-d H:i:s'), 1.5);
        $this->assertSame($user['lastInvalidLoginDate'], $dateTime->format('Y-m-d H:i:s'), 1.5);
    }

    public function testHandleInvalidLoginUserIpStore()
    {
        Craft::$app->getConfig()->getGeneral()->storeUserIps = true;
        $this->tester->mockCraftMethods('request', [
            'getUserIP' => '127.0.0.1'
        ]);

        $this->users->handleInvalidLogin($this->activeUser);

        $user = $this->getUserQuery($this->activeUser->id);
        $this->assertSame('127.0.0.1', $user['lastLoginAttemptIp']);
    }

    public function testHandleInvalidLoginWithoutLimit()
    {
        Craft::$app->getConfig()->getGeneral()->maxInvalidLogins = false;
        Craft::$app->getConfig()->getGeneral()->storeUserIps = true;
        $this->tester->mockCraftMethods('request', [
            'getUserIP' => '127.0.0.1'
        ]);

        $this->users->handleInvalidLogin($this->activeUser);

        $user = $this->getUserQuery($this->activeUser->id);
        $this->assertSame('127.0.0.1', $user['lastLoginAttemptIp']);
        $this->assertNull($user['invalidLoginWindowStart']);
        $this->assertNull($user['invalidLoginCount']);
        $this->assertNotNull($user['lastInvalidLoginDate']);
        $this->assertNull($user['lockoutDate']);
    }

    public function testHandleInvalidLoginWithMaxOutsideWindow()
    {
        $dateTime = new DateTime(null, new DateTimeZone('UTC'));

        Craft::$app->getDb()->createCommand()
            ->update(Table::USERS, ['invalidLoginWindowStart' => null], ['id' => $this->activeUser->id])->execute();

        Craft::$app->getConfig()->getGeneral()->maxInvalidLogins = 5;
        $this->users->handleInvalidLogin($this->activeUser);

        $user = $this->getUserQuery($this->activeUser->id);

        $this->tester->assertEqualDates($this, $dateTime->format('Y-m-d H:i:s'), (string)$user['invalidLoginCount']);
        $this->assertSame('1', (string)$user['invalidLoginCount']);
        $this->assertSame($dateTime->format('Y-m-d H:i:s'), (string)$user['invalidLoginCount']);
    }


    // Protected Methods
    // =========================================================================

    /**
     * @param int $userId
     * @return array|bool
     */
    protected function getUserQuery(int $userId)
    {
        return (new Query())
            ->select('*')
            ->from(Table::USERS)
            ->where(['id' => $userId])
            ->one();
    }

    /**
     * @param int|null $userId
     * @return User|null
     */
    protected function getUser(int $userId)
    {
        return Craft::$app->getUsers()->getUserById($userId);
    }

    /**
     * @todo These tests are 'Dependancy Injected` -ish so i'll swap them out with fixtures later.
     * @inheritdoc
     */
    protected function _before()
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
                'pending' => true
            ]
        );

        $this->lockedUser = new User(
            [
                'firstName' => 'locked',
                'lastName' => 'user',
                'username' => 'lockedUser',
                'email' => 'locked@user.com',
                'locked' => true,
                'invalidLoginCount' => 2,
                'lockoutDate' => Db::prepareDateForDb(new DateTime('now'))
            ]
        );

        $this->activeUser = new User(
            [
                'firstName' => 'active',
                'lastName' => 'user',
                'username' => 'activeUser',
                'email' => 'active@user.com',
            ]
        );

        $this->suspendedUser = new User(
            [
                'firstName' => 'suspended',
                'lastName' => 'user',
                'username' => 'suspendedUser',
                'email' => 'suspended@user.com',
                'suspended' => true
            ]
        );

        $this->saveElement($this->pendingUser);
        $this->saveElement($this->suspendedUser);
        $this->saveElement($this->lockedUser);
        $this->saveElement($this->activeUser);
    }

    /**
     * @param Element $element
     * @return bool
     * @throws \Throwable
     * @throws \craft\errors\ElementNotFoundException
     * @throws \yii\base\Exception
     */
    protected function saveElement(Element $element)
    {
        if (!Craft::$app->getElements()->saveElement($element)) {
            throw new InvalidArgumentException(
                implode(', ', $element->getErrorSummary(true))
            );
        }

        return true;
    }
}
