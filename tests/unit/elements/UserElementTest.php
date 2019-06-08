<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace crafttests\unit\elements;

use Craft;
use craft\base\Element;
use craft\elements\User;
use craft\errors\SiteNotFoundException;
use craft\helpers\ArrayHelper;
use craft\mail\Message;
use craft\models\SystemMessage;
use craft\services\Users;
use craft\test\TestCase;
use craft\test\TestMailer;
use ReflectionException;
use UnitTester;
use yii\base\ErrorException;
use yii\base\Exception;
use yii\base\InvalidArgumentException;
use yii\base\InvalidConfigException;
use yii\base\NotSupportedException;
use yii\validators\InlineValidator;
use yii\web\ServerErrorHttpException;

/**
 * Unit tests for the User Element
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @author Global Network Group | Giel Tettelaar <giel@yellowflash.net>
 * @since 3.2
 */
class UserElementTest extends TestCase
{
    // Public Properties
    // =========================================================================

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

    // Public Methods
    // =========================================================================

    // Tests Methods
    // =========================================================================

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

        $this->saveElement($user);

        $this->activeUser->validateUnverifiedEmail('unverifiedEmail', [], $validator);
        $this->assertSame(
            ['unverifiedEmail' => ['Unverified Email "unverifemail@email.com" has already been taken.']],
            $this->activeUser->getErrors()
        );
    }

    public function testGetAuthKey()
    {
        $this->tester->mockCraftMethods('session', [
            'get' => function ($tokenParam) {
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

    public function testGetAuthKeyException()
    {
        $this->tester->mockCraftMethods('session', [
            'get' => null
        ]);

        $this->tester->expectThrowable(Exception::class, function() {
            $this->activeUser->getAuthKey();
        });
    }

    // Protected Methods
    // =========================================================================

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
