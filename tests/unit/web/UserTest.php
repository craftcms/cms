<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */


namespace craftunit\web;


use Craft;
use craft\elements\User;
use craft\errors\UserLockedException;
use craft\services\Config;
use craft\test\TestCase;
use UnitTester;

/**
 * Unit tests for UserTest
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @author Global Network Group | Giel Tettelaar <giel@yellowflash.net>
 * @since 3.0
 */
class UserTest extends TestCase
{
    /**
     * @var UnitTester $tester
     */
    public $tester;

    /**
     * @var User $userElement
     */
    public $userElement;

    /**
     * @var Config
     */
    public $config;

    /**
     * @var \craft\web\User $user
     */
    public $user;

    public function _before()
    {
        parent::_before();
        $this->userElement = $this->getUser();
        $this->config = Craft::$app->getConfig();
        $this->user = Craft::$app->getUser();
    }

    public function testSendUsernameCookie()
    {
        // Send the cookie with a hardcoded time value
        $this->config->getGeneral()->rememberUsernameDuration = 20;
        $this->user->sendUsernameCookie($this->userElement);

        // Assert that the cookie is correct
        $cookie = Craft::$app->getResponse()->getCookies()->get($this->getUsernameCookieName());

        $this->assertSame($this->userElement->username, $cookie->value);
        $this->assertSame(time() + 20, $cookie->expire);
    }

    public function testSendUsernameCookieDeletes()
    {
        // Ensure something is set
        $this->user->sendUsernameCookie($this->userElement);

        // Setting this to (int)0 will trigger sendUsernameCookie to set the values to null in the existing cookie.
        $this->config->getGeneral()->rememberUsernameDuration = 0;
        $this->user->sendUsernameCookie($this->userElement);

        $cookie = Craft::$app->getResponse()->getCookies()->get($this->getUsernameCookieName());

        $this->assertSame('', $cookie->value);
        $this->assertSame(1, $cookie->expire);
    }

    public function testGetRemainingSessionTime()
    {
        // No identity. Remaining should be null.
        $this->user->setIdentity(null);
        $this->assertSame(0, $this->user->getRemainingSessionTime());

        // With a user and authTimeout null it should return -1
        $this->user->setIdentity($this->userElement);
        $this->user->authTimeout = null;
        $this->assertSame(-1, $this->user->getRemainingSessionTime());
    }

    /**
     * Test that the current time() is substracted from the session expiration value.
     * We use a stub to ensure Craft::$app->getSession()->get() always returns 50 PHP sessions are difficult(ish) in testing.
     *
     * TODO: >.<: Currently this test can fail because the by the time getRemainingSessionTime gets to line 204 a (half) second may have passed
     * meaning that it will return 49 seconds remaining instead of 50 (because between setting the session stub and processesing the remaining session time
     * a second will have passed). Solve this.
     *
     */
    public function testGetRemainingSessionTimeMath()
    {
        $this->user->setIdentity($this->userElement);

        // ensure Craft::$app->getSession()->get() always returns time() + 50.
        $this->sessionGetStub(time() + 50);

        // Session expiry (set above) minus time() should return 50.
        $this->assertSame(50, Craft::$app->getUser()->getRemainingSessionTime());
    }

    /**
     * Test if not logged in getElevated returns 0 or false depending on conditions
     * Important to test this because of PHP's typing system
     */
    public function testGetHasElevatedSession()
    {
        $this->user->setIdentity(null);
        $this->assertSame(0, $this->user->getElevatedSessionTimeout($this->userElement));

        $this->config->getGeneral()->elevatedSessionDuration = 0;

        $this->assertFalse($this->user->getElevatedSessionTimeout());
    }

    /**
     * Test that if a user is logged in and no expires session has been set null is returned.
     */
    public function testGetHasElevatedSessionVoid()
    {
        $this->user->setIdentity($this->userElement);
        // Session must return null
        $this->sessionGetStub(null);
        $this->assertSame(0, $this->user->getElevatedSessionTimeout());
    }

    /**
     * Test that if a user is logged in and no expires session has been set null is returned.
     */
    public function testGetHasElevatedSessionMath()
    {
        $this->user->setIdentity($this->userElement);

        $this->sessionGetStub(time() + 50);
        $this->assertSame(50, $this->user->getElevatedSessionTimeout());

        // If the session->get() return value is smaller than time 0 is returned
        $this->sessionGetStub(time() - 50);
        $this->assertSame(0, $this->user->getElevatedSessionTimeout());
    }

    public function testGetElevatedSession()
    {
        // Setup a password and a mismatching hash to work with.
        $passwordHash = Craft::$app->getSecurity()->hashPassword('this is not the correct password');
        $this->userElement->password = Craft::$app->getSecurity()->hashPassword('this is a password');

        // Ensure no user is logged in.
        $this->user->setIdentity(null);

        // If no user it should return false
        $this->assertFalse($this->user->startElevatedSession('Doesnt matter'));

        // With a user it should still return false.
        $this->user->setIdentity($this->userElement);
        $this->assertFalse($this->user->startElevatedSession($passwordHash));

        // Ensure password validation returns true
        $this->passwordValidationStub(true);

        // If we set this to 0. It should return true
        $this->config->getGeneral()->elevatedSessionDuration = 0;
        $this->assertTrue($this->user->startElevatedSession($passwordHash));
    }

    /**
     * @throws UserLockedException
     */
    public function testStartElevatedSessionSetting()
    {
        $passwordHash = Craft::$app->getSecurity()->hashPassword('this is not the correct password');
        $this->user->setIdentity($this->userElement);

        // Ensure password validation always works.
        $this->passwordValidationStub(true);

        // Ensure a specific value is set to when setting a session
        $this->ensureSetSessionIsOfValue(time() + $this->config->getGeneral()->elevatedSessionDuration);

        // With a user and Craft::$app->getSecurity()->validatePassword() returning true it should return null because the current user doesnt exist or doesnt have a password
        $this->assertFalse($this->user->startElevatedSession($passwordHash));


        $this->userElement->password = 'doesntmatter';
        $this->setInaccessibleProperty(Craft::$app->getUser(), '_identity', $this->userElement);

        // With all the above and a current user with a password. Starting should work.
        $this->assertTrue($this->user->startElevatedSession($passwordHash));
    }


    /**
     * Sets the Craft::$app->getSession(); to a stub where the get() method returns what you want.
     *
     * @param int $returnValue
     */
    private function passwordValidationStub($returnValue)
    {
        $this->tester->mockCraftMethods('security', ['validatePassword' => $returnValue]);
    }

    /**
     * Ensure that the param $value is equal to the value that is trying to be set to the session.
     * @param $value
     */
    private function ensureSetSessionIsOfValue($value)
    {
        $this->tester->mockCraftMethods('session', ['set' => function($name, $val) use ($value) {
            $this->assertSame($value, $val);
        }]);
    }

    /**
     * Sets the Craft::$app->getSession(); to a stub where the get() method returns what you want.
     * @param int $returnValue
     */
    private function sessionGetStub($returnValue)
    {
        $this->tester->mockCraftMethods('session', ['get' => $returnValue]);
    }

    private function getUser()
    {
        return Craft::$app->getUsers()->getUserById('1');
    }

    private function getUsernameCookieName()
    {
        return Craft::$app->getUser()->usernameCookie['name'];
    }
}
