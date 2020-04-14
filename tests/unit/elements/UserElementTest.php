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
use craft\elements\User;
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
    public $users;

    /**
     * @var UnitTester
     */
    public $tester;

    /**
     * @var User
     */
    protected $activeUser;

    /**
     *
     */
    public function testValidateUnverifiedEmail()
    {
        $validator = new InlineValidator();

        $this->activeUser->unverifiedEmail = 'unverifemail@email.com';

        $this->activeUser->validateUnverifiedEmail('unverifiedEmail', [], $validator);
        $this->assertSame([], $this->activeUser->getErrors());

        $user = new User([
            'email' => 'unverifemail@email.com',
            'username' => 'unverifusername',
            'unverifiedEmail' => 'unverifemail@email.com',
        ]);

        $this->tester->saveElement($user);

        $this->activeUser->validateUnverifiedEmail('unverifiedEmail', [], $validator);
        $this->assertSame(
            ['unverifiedEmail' => ['Email "unverifemail@email.com" has already been taken.']],
            $this->activeUser->getErrors()
        );
    }

    /**
     * @throws Exception
     */
    public function testGetAuthKey()
    {
        $this->tester->mockCraftMethods('session', [
            'get' => function($tokenParam) {
                $this->assertSame(Craft::$app->getUser()->tokenParam, $tokenParam);

                return 'TOKEN';
            }
        ]);

        $this->tester->mockCraftMethods('request', [
            'getUserAgent' => 'Mozilla/5.0 (iPad; U; CPU OS 3_2_1 like Mac OS X; en-us)'
        ]);

        $this->assertSame(
            '["TOKEN",null,"Mozilla/5.0 (iPad; U; CPU OS 3_2_1 like Mac OS X; en-us)"]',
            $this->activeUser->getAuthKey()
        );
    }

    /**
     *
     */
    public function testGetAuthKeyException()
    {
        $this->tester->mockCraftMethods('session', [
            'get' => null
        ]);

        $this->tester->expectThrowable(Exception::class, function() {
            $this->activeUser->getAuthKey();
        });
    }

    /**
     * @throws \yii\db\Exception
     */
    public function testValidateAuthKey()
    {
        $validUserAgent = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/74.0.3729.169 Safari/537.36';
        Craft::$app->getDb()->createCommand()
            ->insert(Table::SESSIONS, [
                'userId' => $this->activeUser->id,
                'token' => 'EXAMPLE_TOKEN'
            ])->execute();

        $this->assertFalse($this->activeUser->validateAuthKey('NOT_JSON'));
        $this->assertFalse($this->activeUser->validateAuthKey('["JSON_ONE_ITEM"]'));
        $this->assertFalse(
            $this->activeUser->validateAuthKey(
                '["EXAMPLE_TOKEN",null,"NOT_A_USER_AGENT"]'
            )
        );
        $this->assertFalse(
            $this->activeUser->validateAuthKey(
                '["NOT_A_VALID_TOKEN",null,"' . $validUserAgent . '"]'
            )
        );

        Craft::$app->getConfig()->getGeneral()->requireMatchingUserAgentForSession = true;

        // Valid token, user agent, and json string
        $this->tester->mockCraftMethods('request', [
            'getUserAgent' => $validUserAgent
        ]);
        $this->assertTrue(
            $this->activeUser->validateAuthKey(
                '["EXAMPLE_TOKEN",null,"' . $validUserAgent . '"]'
            )
        );
    }

    /**
     * @throws \yii\db\Exception
     */
    public function testValidateAuthKeyWithConfigDisabled()
    {
        $validUserAgent = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/74.0.3729.169 Safari/537.36';

        Craft::$app->getConfig()->getGeneral()->requireMatchingUserAgentForSession = false;
        $this->tester->mockCraftMethods('request', [
            'getUserAgent' => $validUserAgent
        ]);

        Craft::$app->getDb()->createCommand()
            ->insert(Table::SESSIONS, [
                'userId' => $this->activeUser->id,
                'token' => 'EXAMPLE_TOKEN'
            ])->execute();

        $this->assertTrue(
            $this->activeUser->validateAuthKey(
                '["EXAMPLE_TOKEN",null,"INVALID_USER_AGENT"]'
            )
        );
    }

    /**
     * @throws \Exception
     */
    public function testGetCooldownEndTime()
    {
        $this->activeUser->locked = false;
        $this->assertNull($this->activeUser->getCooldownEndTime());

        $this->activeUser->locked = true;
        $this->activeUser->lockoutDate = null;
        $this->assertNull($this->activeUser->getCooldownEndTime());


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
    public function testGetRemainingCooldownTime()
    {
        $this->assertNull($this->activeUser->getRemainingCooldownTime());

        $this->activeUser->locked = true;
        $this->activeUser->lockoutDate = new DateTime('now', new DateTimeZone('UTC'));
        Craft::$app->getConfig()->getGeneral()->cooldownDuration = (60 * 60 * 24 * 2) + 10; // 2 days and 10 seconds

        $this->assertInstanceOf(DateInterval::class, $interval = $this->activeUser->getRemainingCooldownTime());
        $this->assertSame('2', (string)$interval->d);

        $this->activeUser->lockoutDate->sub(new DateInterval('P10D'));
        $this->assertNull($this->activeUser->getRemainingCooldownTime());
    }

    /**
     *
     */
    public function testChangePasswordNukesSessions()
    {
        Craft::$app->getDb()->createCommand()
            ->batchInsert(Table::SESSIONS, [
                'userId',
                'token'
            ], [
                [
                    $this->activeUser->id,
                    StringHelper::randomString(32)
                ], [
                    $this->activeUser->id,
                    StringHelper::randomString(32)
                ]
            ]);

        $this->activeUser->newPassword = 'random_password';
        $this->tester->saveElement($this->activeUser);

        $exists = (new Query())->from(Table::SESSIONS)->where(['userId' => $this->activeUser->id])->exists();
        $this->assertFalse($exists);
    }

    /**
     *
     */
    public function testNotAllowedToSwitchStatusValues()
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
     * @inheritdoc
     */
    protected function _before()
    {
        parent::_before();

        $this->activeUser = new User(
            [
                'firstName' => 'active',
                'lastName' => 'user',
                'username' => 'activeUser',
                'email' => 'active@user.com',
            ]
        );

        $this->users = Craft::$app->getUsers();

        $this->tester->saveElement($this->activeUser);
    }
}
