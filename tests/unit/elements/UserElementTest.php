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
use craft\elements\Address;
use craft\elements\Asset;
use craft\elements\db\EagerLoadPlan;
use craft\elements\User;
use craft\errors\InvalidElementException;
use craft\helpers\Session;
use craft\helpers\StringHelper;
use craft\services\Users;
use craft\test\TestCase;
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
     * @throws Exception
     */
    public function testGetAuthKey(): void
    {
        Session::reset();

        $this->tester->mockCraftMethods('session', [
            'getHasSessionId' => fn() => true,
            'get' => function($tokenParam) {
                self::assertSame(Craft::$app->getUser()->tokenParam, $tokenParam);

                return 'TOKEN';
            },
        ]);

        $this->tester->mockCraftMethods('request', [
            'getUserAgent' => 'Mozilla/5.0 (iPad; U; CPU OS 3_2_1 like Mac OS X; en-us)',
        ]);

        self::assertSame(
            '["TOKEN",null,"' . md5('Mozilla/5.0 (iPad; U; CPU OS 3_2_1 like Mac OS X; en-us)') . '"]',
            $this->activeUser->getAuthKey()
        );

        Session::reset();
    }

    /**
     *
     */
    public function testGetAuthKeyException(): void
    {
        $this->tester->mockCraftMethods('session', [
            'get' => null,
        ]);

        $this->tester->expectThrowable(Exception::class, function() {
            $this->activeUser->getAuthKey();
        });
    }

    /**
     * @throws \yii\db\Exception
     */
    public function testValidateAuthKey(): void
    {
        $validUserAgent = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/74.0.3729.169 Safari/537.36';
        Craft::$app->getDb()->createCommand()
            ->insert(Table::SESSIONS, [
                'userId' => $this->activeUser->id,
                'token' => 'EXAMPLE_TOKEN',
            ])->execute();

        self::assertFalse($this->activeUser->validateAuthKey('NOT_JSON'));
        self::assertFalse($this->activeUser->validateAuthKey('["JSON_ONE_ITEM"]'));
        self::assertFalse(
            $this->activeUser->validateAuthKey(
                '["EXAMPLE_TOKEN",null,"NOT_A_USER_AGENT"]'
            )
        );
        self::assertFalse(
            $this->activeUser->validateAuthKey(
                '["NOT_A_VALID_TOKEN",null,"' . $validUserAgent . '"]'
            )
        );

        Craft::$app->getConfig()->getGeneral()->requireMatchingUserAgentForSession = true;

        // Valid token, user agent, and json string
        $this->tester->mockCraftMethods('request', [
            'getUserAgent' => $validUserAgent,
        ]);
        self::assertTrue(
            $this->activeUser->validateAuthKey(
                '["EXAMPLE_TOKEN",null,"' . md5($validUserAgent) . '"]'
            )
        );
    }

    /**
     * @throws \yii\db\Exception
     */
    public function testValidateAuthKeyWithConfigDisabled(): void
    {
        $validUserAgent = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/74.0.3729.169 Safari/537.36';

        Craft::$app->getConfig()->getGeneral()->requireMatchingUserAgentForSession = false;
        $this->tester->mockCraftMethods('request', [
            'getUserAgent' => $validUserAgent,
        ]);

        Craft::$app->getDb()->createCommand()
            ->insert(Table::SESSIONS, [
                'userId' => $this->activeUser->id,
                'token' => 'EXAMPLE_TOKEN',
            ])->execute();

        self::assertTrue(
            $this->activeUser->validateAuthKey(
                '["EXAMPLE_TOKEN",null,"INVALID_USER_AGENT"]'
            )
        );
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


        Craft::$app->getConfig()->getGeneral()->cooldownDuration = 172800;
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
        Craft::$app->getConfig()->getGeneral()->cooldownDuration = (60 * 60 * 24 * 2) + 10; // 2 days and 10 seconds

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
                'userId',
                'token',
            ], [
                [
                    $this->activeUser->id,
                    StringHelper::randomString(32),
                ], [
                    $this->activeUser->id,
                    StringHelper::randomString(32),
                ],
            ]);

        $this->activeUser->newPassword = 'random_password';
        $this->tester->saveElement($this->activeUser);

        $exists = (new Query())->from(Table::SESSIONS)->where(['userId' => $this->activeUser->id])->exists();
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
    public function testAuthenticate(): void
    {
        self::assertTrue($this->activeUser->authenticate('password'));
        self::assertFalse($this->inactiveUser->authenticate('password'));
        self::assertEquals($this->inactiveUser->authError, User::AUTH_INVALID_CREDENTIALS);
        $this->inactiveUser->authError = null;
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
     * `photo` is a magic property that resolves to `getPhoto()`. Since eager-loading the `photo`
     * handle bypasses the generic `Element::$_eagerLoadedElements` array (see
     * `User::setEagerLoadedElements()`), property access should always match `getPhoto()`,
     * whether or not the photo has been eager-loaded.
     */
    public function testPhotoPropertyMatchesGetter(): void
    {
        // No photo set at all
        $user = new User(['id' => 600]);
        self::assertNull($user->getPhoto());
        self::assertSame($user->getPhoto(), $user->photo);

        // Photo set directly, not via eager-loading
        $photo = new Asset(['id' => 601]);
        $user->setPhoto($photo);
        self::assertSame($photo, $user->getPhoto());
        self::assertSame($user->getPhoto(), $user->photo);

        // Photo eager-loaded
        $user2 = new User(['id' => 602]);
        $plan = new EagerLoadPlan(['handle' => 'photo']);
        $user2->setEagerLoadedElements('photo', [$photo], $plan);
        self::assertSame($photo, $user2->getPhoto());
        self::assertSame($user2->getPhoto(), $user2->photo);

        // No photo eager-loaded (empty result)
        $user3 = new User(['id' => 603]);
        $user3->setEagerLoadedElements('photo', [], $plan);
        self::assertNull($user3->getPhoto());
        self::assertSame($user3->getPhoto(), $user3->photo);
    }

    /**
     * `addresses` is a magic property that resolves to `getAddresses()`. Since eager-loading the
     * `addresses` handle bypasses the generic `Element::$_eagerLoadedElements` array (see
     * `User::setEagerLoadedElements()`), property access should always match `getAddresses()`,
     * whether or not the addresses have been eager-loaded.
     */
    public function testAddressesPropertyMatchesGetter(): void
    {
        // Not eager-loaded (no addresses saved for this user)
        $user = new User(['id' => 999999]);
        self::assertCount(0, $user->getAddresses());
        self::assertSame($user->getAddresses(), $user->addresses);

        // Addresses eager-loaded
        $address = new Address(['id' => 9001, 'countryCode' => 'US']);
        $user2 = new User(['id' => 500]);
        $plan = new EagerLoadPlan(['handle' => 'addresses']);
        $user2->setEagerLoadedElements('addresses', [$address], $plan);
        self::assertSame([$address], $user2->getAddresses()->all());
        self::assertSame($user2->getAddresses(), $user2->addresses);

        // No addresses eager-loaded (empty result)
        $user3 = new User(['id' => 501]);
        $user3->setEagerLoadedElements('addresses', [], $plan);
        self::assertCount(0, $user3->getAddresses());
        self::assertSame($user3->getAddresses(), $user3->addresses);
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
