<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace crafttests\unit\services;

use Craft;
use craft\db\Query;
use craft\db\Table;
use craft\elements\User;
use craft\enums\CmsEdition;
use craft\events\UserEvent;
use craft\helpers\Db;
use craft\helpers\StringHelper;
use craft\mail\Message;
use craft\services\Users;
use craft\test\EventItem;
use craft\test\TestCase;
use crafttests\fixtures\UserGroupsFixture;
use DateTime;
use DateTimeZone;
use ErrorException;
use ReflectionException;
use Throwable;
use UnitTester;
use yii\base\Exception;
use yii\base\NotSupportedException;
use yii\db\Exception as YiiDbException;
use yii\web\ServerErrorHttpException;

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

    /**
     *
     */
    public function testUserCreation(): void
    {
        self::assertSame(User::STATUS_ACTIVE, $this->lockedUser->getStatus());
        self::assertTrue($this->lockedUser->locked);
        self::assertSame(User::STATUS_PENDING, $this->pendingUser->getStatus());
        self::assertSame(User::STATUS_ACTIVE, $this->activeUser->getStatus());
        self::assertSame(User::STATUS_SUSPENDED, $this->suspendedUser->getStatus());
        self::assertTrue($this->suspendedUser->suspended);
    }

    /**
     * @throws Throwable
     */
    public function testUserActivation(): void
    {
        $this->users->activateUser($this->pendingUser);

        $user = $this->getUser($this->pendingUser->id);

        self::assertSame(User::STATUS_ACTIVE, $user->getStatus());
        self::assertSame('jsmith', $user->username);
    }

    /**
     * @throws Throwable
     */
    public function testUserActivationEmailAsUsernameWithAnUnverifedEmail(): void
    {
        // Set useEmailAsUsername to true and add an unverified email.
        Craft::$app->getConfig()->getGeneral()->useEmailAsUsername = true;
        $this->tester->saveElement($this->pendingUser);

        $this->users->activateUser($this->pendingUser);

        $user = $this->getUser($this->pendingUser->id);

        self::assertSame(User::STATUS_ACTIVE, $user->getStatus());
        self::assertSame('jsmith@gmail.com', $user->username);

        Craft::$app->getConfig()->getGeneral()->useEmailAsUsername = false;
    }

    /**
     * @throws Throwable
     */
    public function testUserActivationEmailAsUsernameWithNoUnverifedEmail(): void
    {
        // Run the same test as above but without an unverified email.
        Craft::$app->getConfig()->getGeneral()->useEmailAsUsername = true;

        // Remove the unverifiedEmail property from the user record - meaning no username will be set.
        $this->pendingUser->unverifiedEmail = null;

        $this->users->activateUser($this->pendingUser);

        $user = $this->getUser($this->pendingUser->id);

        self::assertSame(User::STATUS_ACTIVE, $user->getStatus());
        self::assertSame('jsmith', $user->username);

        Craft::$app->getConfig()->getGeneral()->useEmailAsUsername = false;
    }

    /**
     * @throws Throwable
     */
    public function testUserUnlocking(): void
    {
        $this->users->unlockUser($this->lockedUser);

        self::assertFalse($this->lockedUser->locked);
        self::assertNull($this->lockedUser->lockoutDate);
        self::assertNull($this->lockedUser->invalidLoginCount);
        self::assertSame(User::STATUS_ACTIVE, $this->lockedUser->getStatus());
    }

    /**
     * @throws Throwable
     */
    public function testUserSuspending(): void
    {
        $this->users->suspendUser($this->activeUser);

        self::assertTrue($this->activeUser->suspended);
        self::assertSame(User::STATUS_SUSPENDED, $this->activeUser->getStatus());
    }

    /**
     * @throws Throwable
     */
    public function testUserUnSuspending(): void
    {
        $this->users->unsuspendUser($this->suspendedUser);

        self::assertFalse($this->suspendedUser->suspended);
        self::assertSame(User::STATUS_ACTIVE, $this->suspendedUser->getStatus());
    }

    /**
     * @todo Monitor this one doesn't break on travis
     * @throws \Exception
     */
    public function testSetVerificationCodeOnUser(): void
    {
        $verificationCode = $this->users->setVerificationCodeOnUser($this->pendingUser);
        $dateTime = new DateTime('now', new DateTimeZone('UTC'));

        $user = (new Query())
            ->select('*')
            ->from(Table::USERS)->where(['id' => $this->pendingUser->id])->one();

        self::assertSame(32, strlen($verificationCode));
        self::assertNotNull($user['verificationCode']);

        $this->tester->assertEqualDates(
            $this,
            $dateTime->format('Y-m-d H:i:s'),
            $user['verificationCodeIssuedDate']
        );
    }

    /**
     *
     */
    public function testUserGroupAssignment(): void
    {
        Craft::$app->edition = CmsEdition::Pro;

        $this->users->assignUserToGroups(
            $this->activeUser->id,
            [1000, 1001, 1002]
        );

        $groups = $this->activeUser->getGroups();
        self::assertCount(3, $groups);
    }

    /**
     * @throws ReflectionException
     */
    public function testUserGroupAssignmentInvalidation(): void
    {
        Craft::$app->edition = CmsEdition::Pro;

        $this->users->assignUserToGroups(
            $this->activeUser->id,
            [1000]
        );

        $groups = $this->activeUser->getGroups();
        self::assertCount(1, $groups);

        // Invalidate the currentGroups - otherwise memoization will ruin our tests.
        $this->setInaccessibleProperty($this->activeUser, '_groups', null);

        $this->users->assignUserToGroups(
            $this->activeUser->id,
            [1001, 1002]
        );

        // There should now be 2 - not three.
        $groups = $this->activeUser->getGroups();
        self::assertCount(2, $groups);
    }

    /**
     * @throws ErrorException
     * @throws Exception
     * @throws NotSupportedException
     * @throws ServerErrorHttpException
     */
    public function testUserAssignmentToDefaultGroup(): void
    {
        Craft::$app->edition = CmsEdition::Pro;
        Craft::$app->getProjectConfig()->set('users.defaultGroup', 'usergroup-1002-------------------uid');

        $this->users->assignUserToDefaultGroup($this->activeUser);

        $groups = $this->activeUser->getGroups();
        self::assertCount(1, $groups);
        self::assertSame('Group 3', $groups[0]->name);
    }

    /**
     * @throws \Exception
     */
    public function testHandleInvalidLogin(): void
    {
        $this->users->handleInvalidLogin($this->activeUser);
        $dateTime = new DateTime('now', new DateTimeZone('UTC'));

        $user = $this->getUserQuery($this->activeUser->id);

        self::assertSame('1', (string)$user['invalidLoginCount']);
        $this->tester->assertEqualDates($this, $user['invalidLoginWindowStart'], $dateTime->format('Y-m-d H:i:s'));
        $this->tester->assertEqualDates($this, $user['lastInvalidLoginDate'], $dateTime->format('Y-m-d H:i:s'));
    }

    /**
     *
     */
    public function testHandleInvalidLoginUserIpStore(): void
    {
        Craft::$app->getConfig()->getGeneral()->storeUserIps = true;
        $this->tester->mockCraftMethods('request', [
            'getUserIP' => '127.0.0.1',
        ]);

        $this->users->handleInvalidLogin($this->activeUser);

        $user = $this->getUserQuery($this->activeUser->id);
        self::assertSame('127.0.0.1', $user['lastLoginAttemptIp']);
    }

    /**
     *
     */
    public function testHandleInvalidLoginWithoutLimit(): void
    {
        Craft::$app->getConfig()->getGeneral()->maxInvalidLogins = false;
        Craft::$app->getConfig()->getGeneral()->storeUserIps = true;
        $this->tester->mockCraftMethods('request', [
            'getUserIP' => '127.0.0.1',
        ]);

        $this->users->handleInvalidLogin($this->activeUser);

        $user = $this->getUserQuery($this->activeUser->id);
        self::assertSame('127.0.0.1', $user['lastLoginAttemptIp']);
        self::assertNull($user['invalidLoginWindowStart']);
        self::assertNull($user['invalidLoginCount']);
        self::assertNotNull($user['lastInvalidLoginDate']);
        self::assertNull($user['lockoutDate']);
    }

    /**
     * @throws YiiDbException
     */
    public function testHandleInvalidLoginWithMaxOutsideWindow(): void
    {
        $dateTime = new DateTime('now', new DateTimeZone('UTC'));

        Craft::$app->getDb()->createCommand()
            ->update(Table::USERS, ['invalidLoginWindowStart' => null], ['id' => $this->activeUser->id])->execute();

        Craft::$app->getConfig()->getGeneral()->maxInvalidLogins = 1;
        $this->users->handleInvalidLogin($this->activeUser);

        $user = $this->getUserQuery($this->activeUser->id);

        $this->tester->assertEqualDates($this, $dateTime->format('Y-m-d H:i:s'), $user['invalidLoginWindowStart']);
        self::assertSame('1', (string)$user['invalidLoginCount']);
        self::assertFalse((bool)$user['locked']);
        self::assertNull($user['lockoutDate']);
        self::assertNull($user['lockoutDate']);
    }

    /**
     * @throws YiiDbException
     */
    public function testHandleInvalidLoginInsideWindow(): void
    {
        $dateTime = new DateTime('now', new DateTimeZone('UTC'));

        // First. Set the correct conditions
        $this->updateUser([
            // The past.
            'invalidLoginWindowStart' => Db::prepareDateForDb(new DateTime()),
            'invalidLoginCount' => '1',
        ], ['id' => $this->activeUser->id]);

        // 3 max - that's important for a little bit later. Also a 2 day invalidLoginWindowDuration
        Craft::$app->getConfig()->getGeneral()->maxInvalidLogins = 3;
        Craft::$app->getConfig()->getGeneral()->invalidLoginWindowDuration = 172800;

        // 1 st invalid login.
        $this->users->handleInvalidLogin($this->activeUser);

        // This should just increment the invalidLoginCount
        $user = $this->getUserQuery($this->activeUser->id);
        self::assertSame('2', (string)$user['invalidLoginCount']);
        self::assertFalse((bool)$user['locked']);

        // Wrap this in an event check - because the EVENT_AFTER_LOCK_USER only get's thrown under specific circumstances.
        $this->tester->expectEvent(Users::class, Users::EVENT_AFTER_LOCK_USER, function() use ($dateTime) {
            // The user should now be locked out.
            $this->users->handleInvalidLogin($this->activeUser);
            $user = $this->getUserQuery($this->activeUser->id);
            self::assertTrue((bool)$user['locked']);
            self::assertNull($user['invalidLoginCount']);
            self::assertNull($user['invalidLoginWindowStart']);
            $this->tester->assertEqualDates($this, $dateTime->format('Y-m-d H:i:s'), $user['lockoutDate'], 10);
        }, UserEvent::class, $this->tester->createEventItems([
            [
                'type' => EventItem::TYPE_CLASS,
                'eventPropName' => 'user',
                'desiredClass' => User::class,
                'desiredValue' => [
                    'id' => $this->activeUser->id,
                    'locked' => true,
                ],
            ],
        ]));
    }

    /**
     * @throws \Exception
     */
    public function testHandleValidLogin(): void
    {
        $dateTime = new DateTime('now', new DateTimeZone('UTC'));

        $this->users->handleValidLogin($this->activeUser);

        $user = $this->getUserQuery($this->activeUser->id);

        $this->tester->assertEqualDates($this, $dateTime->format('Y-m-d H:i:s'), $user['lastLoginDate']);
        self::assertNotNull($user['lastLoginAttemptIp']);
    }

    /**
     *
     */
    public function testHandleValidLoginIpCollection(): void
    {
        $this->tester->mockCraftMethods('request', [
            'getUserIP' => '127.0.0.1',
        ]);

        Craft::$app->getConfig()->getGeneral()->storeUserIps = true;

        $this->users->handleValidLogin($this->activeUser);

        $user = $this->getUserQuery($this->activeUser->id);

        self::assertSame('127.0.0.1', $user['lastLoginAttemptIp']);
    }

    /**
     * @throws YiiDbException
     */
    public function testHandleValidLoginClearsValues(): void
    {
        $this->updateUser([
            'invalidLoginWindowStart' => '2019-06-06 20:00:00',
            'invalidLoginCount' => '5',
        ], ['id' => $this->activeUser->id]);

        $this->users->handleValidLogin($this->activeUser);

        // These variables are now overriden to null.
        $user = $this->getUserQuery($this->activeUser->id);
        self::assertNull($user['invalidLoginWindowStart']);
        self::assertNull($user['invalidLoginCount']);
    }

    public function testIsVerificationCodeValidForUser(): void
    {
        // Ensure password validation is irrelevant
        $this->ensurePasswordValidationReturns(true);
        Craft::$app->getConfig()->getGeneral()->verificationCodeDuration = 172800;

        $this->updateUser([
            // The past.
            'verificationCodeIssuedDate' => '2018-06-06 20:00:00',
            'verificationCode' => 'irrelevant_code',
        ], ['id' => $this->activeUser->id]);

        self::assertFalse(
            $this->users->isVerificationCodeValidForUser($this->activeUser, 'irrelevant_code')
        );

        // Now the code should be present - within 2 day window
        $this->updateUser([
            // The present.
            'verificationCodeIssuedDate' => Db::prepareDateForDb(new DateTime('now')),
            'verificationCode' => 'irrelevant_code',
        ], ['id' => $this->activeUser->id]);

        self::assertTrue(
            $this->users->isVerificationCodeValidForUser($this->activeUser, 'irrelevant_code')
        );
    }

    public function testSendActivationEmail(): void
    {
        // Ensure we know what the unhashed code is - so we can compare against it later.
        $this->tester->mockCraftMethods('security', [
            'generateRandomString' => $string = StringHelper::randomString(32),
        ]);

        // Test send activation email with password null
        $this->pendingUser->password = null;
        $this->users->sendActivationEmail($this->pendingUser);
        $this->testUsersEmailFunctions(
            'account_activation',
            'setpassword?code=' . $string
        );

        $this->pendingUser->password = 'some_password';
        $this->users->sendActivationEmail($this->pendingUser);
        $this->testUsersEmailFunctions(
            'account_activation',
            'verifyemail?code=' . $string
        );
        $this->pendingUser->password = null;

        // Test send Email Verify
        $this->users->sendNewEmailVerifyEmail($this->pendingUser);
        $this->testUsersEmailFunctions(
            'verify_new_email',
            'verifyemail?code=' . $string
        );

        // Test password reset email
        $this->users->sendPasswordResetEmail($this->pendingUser);
        $this->testUsersEmailFunctions(
            'forgot_password',
            'setpassword?code=' . $string
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
     * @return array|null
     */
    protected function getUserQuery(?int $userId): ?array
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
