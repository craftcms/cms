<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace crafttests\unit\elements;

use Craft;
use craft\db\Query;
use craft\db\Table;
use craft\services\Users;
use craft\test\TestCase;
use CraftCms\Cms\Cms;
use CraftCms\Cms\Element\Exceptions\InvalidElementException;
use CraftCms\Cms\Support\Str;
use CraftCms\Cms\User\Elements\User;
use DateInterval;
use DateTime;
use DateTimeZone;
use UnitTester;
use yii\base\Exception;
use yii\validators\InlineValidator;

/**
 * Unit tests for the User Element
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @author Global Network Group | Giel Tettelaar <giel@yellowflash.net>
 * @since 3.2
 */
class UserElementTest extends TestCase
{
    /**
     * @var Users
     */
    public Users $users;

    /**
     * @var UnitTester
     */
    protected UnitTester $tester;

    /**
     * @var User
     */
    protected User $activeUser;

    /**
     * @var User
     */
    protected User $inactiveUser;

    /**
     *
     */
    public function testValidateUnverifiedEmail(): void
    {
        $validator = new InlineValidator();

        $this->activeUser->unverifiedEmail = 'unverifemail@email.com';

        $this->activeUser->validateUnverifiedEmail('unverifiedEmail', [], $validator);
        self::assertSame([], $this->activeUser->getErrors());

        $user = new User([
            'active' => true,
            'email' => 'unverifemail@email.com',
            'username' => 'unverifusername',
            'unverifiedEmail' => 'unverifemail@email.com',
        ]);

        $this->tester->saveElement($user);

        $this->activeUser->validateUnverifiedEmail('unverifiedEmail', [], $validator);
        self::assertSame(
            ['unverifiedEmail' => ['Email "unverifemail@email.com" has already been taken.']],
            $this->activeUser->getErrors()
        );

        $this->tester->deleteElement($user);
    }

    public function testActivationValidation(): void
    {
        $user = new User([
            'active' => false,
            'email' => 'unverifemail@email.com',
            'username' => 'unverifusername',
        ]);

        $this->tester->saveElement($user);

        $user->username = $this->activeUser->username;
        $user->email = $this->activeUser->email;

        // Set invalid value, as it should get cleared when activating user.
        $user->fullName = 'invalid://';

        $e = null;
        try {
            Craft::$app->getUsers()->activateUser($user);
        } catch (InvalidElementException $e) {
        }

        self::assertNotNull($e);
        self::assertFalse($user->hasErrors('fullName'));
        self::assertTrue($user->hasErrors('username'));
        self::assertTrue($user->hasErrors('email'));

        $e = null;
        try {
            Craft::$app->getUsers()->getActivationUrl($user);
        } catch (InvalidElementException $e) {
            // catching so we can clean up after
        }

        self::assertInstanceOf(InvalidElementException::class, $e ?? null);

        $this->tester->deleteElement($user);
    }

    public function testUserStatusChange(): void
    {
        $this->activeUser->active = false;
        $this->expectException(Exception::class);
        $this->tester->saveElement($this->activeUser);
    }

    /**
     * @throws \Exception
     */
    public function testGetCooldownEndTime(): void
    {
        $this->activeUser->locked = false;
        self::assertNull($this->activeUser->getCooldownEndTime());

        $this->activeUser->locked = true;
        $this->activeUser->lockoutDate = null;
        self::assertNull($this->activeUser->getCooldownEndTime());


        Cms::config()->cooldownDuration = 172800;
        $this->activeUser->locked = true;
        $this->activeUser->lockoutDate = new DateTime('now', new DateTimeZone('UTC'));
        $cooldown = $this->activeUser->getCooldownEndTime();

        // Check valid.
        $dateTime = new DateTime('now', new DateTimeZone('UTC'));
        $dateTime->add(new DateInterval('P2D'));
        $this->tester->assertEqualDates(
            $this,
            $cooldown->format('Y-m-d H:i:s'),
            $dateTime->format('Y-m-d H:i:s'),
            5
        );
    }

    /**
     * @throws \Exception
     */
    public function testGetRemainingCooldownTime(): void
    {
        self::assertNull($this->activeUser->getRemainingCooldownTime());

        $this->activeUser->locked = true;
        $this->activeUser->lockoutDate = new DateTime('now', new DateTimeZone('UTC'));
        Cms::config()->cooldownDuration = (60 * 60 * 24 * 2) + 10; // 2 days and 10 seconds

        self::assertInstanceOf(DateInterval::class, $interval = $this->activeUser->getRemainingCooldownTime());
        self::assertSame('2', (string)$interval->d);

        $this->activeUser->lockoutDate->sub(new DateInterval('P10D'));
        self::assertNull($this->activeUser->getRemainingCooldownTime());
    }

    /**
     *
     */
    public function testChangePasswordNukesSessions(): void
    {
        Craft::$app->getDb()->createCommand()
            ->batchInsert(Table::SESSIONS, [
                'user_id',
                'id',
            ], [
                [
                    $this->activeUser->id,
                    Str::random(32),
                ], [
                    $this->activeUser->id,
                    Str::random(32),
                ],
            ]);

        $this->activeUser->newPassword = 'random_password';
        $this->tester->saveElement($this->activeUser);

        $exists = (new Query())->from(Table::SESSIONS)->where(['user_id' => $this->activeUser->id])->exists();
        self::assertFalse($exists);
    }

    /**
     *
     */
    public function testNotAllowedToSwitchStatusValues(): void
    {
        // Change locked
        $this->activeUser->locked = true;
        $this->tester->expectThrowable(Exception::class, function() {
            $this->activeUser->afterSave(false);
        });
        $this->activeUser->locked = false;

        // Change suspended
        $this->activeUser->suspended = true;
        $this->tester->expectThrowable(Exception::class, function() {
            $this->activeUser->afterSave(false);
        });
        $this->activeUser->suspended = false;

        // Change pending
        $this->activeUser->pending = true;
        $this->tester->expectThrowable(Exception::class, function() {
            $this->activeUser->afterSave(false);
        });
        $this->activeUser->pending = false;
    }

    /**
     *
     */
    public function testIsCredentialed(): void
    {
        self::assertTrue($this->activeUser->getIsCredentialed());
        self::assertFalse($this->inactiveUser->getIsCredentialed());
    }

    /**
     * @inheritdoc
     */
    protected function _before(): void
    {
        parent::_before();

        $this->activeUser = new User(
            [
                'active' => true,
                'firstName' => 'active',
                'lastName' => 'user',
                'username' => 'activeUser',
                'email' => 'active@user.com',
                'password' => '$2a$13$5j8bSRoKQZipjtIg6FXWR.kGRR3UfCL.QeMIt2yTRH1.hCNHLQKtq',
            ]
        );

        $this->inactiveUser = new User(
            [
                'firstName' => 'inactive',
                'lastName' => 'user',
                'username' => 'inactiveUser',
                'email' => 'inactive@user.com',
                'password' => '$2a$13$5j8bSRoKQZipjtIg6FXWR.kGRR3UfCL.QeMIt2yTRH1.hCNHLQKtq',
            ]
        );

        $this->users = Craft::$app->getUsers();

        $this->tester->saveElement($this->activeUser);
        $this->tester->saveElement($this->inactiveUser);
    }

    /**
     * @inheritdoc
     */
    protected function _after(): void
    {
        parent::_after();

        $this->tester->deleteElement($this->activeUser);
        $this->tester->deleteElement($this->inactiveUser);
    }
}
