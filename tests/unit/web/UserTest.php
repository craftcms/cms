<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace crafttests\unit\web;

use Craft;
use craft\elements\User as UserElement;
use craft\helpers\DateTimeHelper;
use craft\helpers\Session;
use craft\services\Config;
use craft\test\TestCase;
use craft\web\User as WebUser;
use UnitTester;

/**
 * Unit tests for UserTest
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @author Global Network Group | Giel Tettelaar <giel@yellowflash.net>
 * @since 3.2
 */
class UserTest extends TestCase
{
    /**
     * @var UnitTester
     */
    public UnitTester $tester;

    /**
     * @var UserElement
     */
    public UserElement $userElement;

    /**
     * @var Config
     */
    public Config $config;

    /**
     * @var WebUser
     */
    public WebUser $user;

    /**
     *
     */
    public function testSendUsernameCookie(): void
    {
        DateTimeHelper::pause();

        // Send the cookie with a hardcoded time value
        $this->config->getGeneral()->rememberUsernameDuration = 20;
        $this->user->sendUsernameCookie($this->userElement);

        // Assert that the cookie is correct
        $cookie = Craft::$app->getResponse()->getCookies()->get($this->_getUsernameCookieName());

        self::assertSame($this->userElement->username, $cookie->value);
        self::assertSame(DateTimeHelper::currentTimeStamp() + 20, $cookie->expire);

        DateTimeHelper::resume();
    }

    /**
     *
     */
    public function testSendUsernameCookieDeletes(): void
    {
        // Ensure something is set
        $this->user->sendUsernameCookie($this->userElement);

        // Setting this to (int)0 will trigger sendUsernameCookie to set the values to null in the existing cookie.
        $this->config->getGeneral()->rememberUsernameDuration = 0;
        $this->user->sendUsernameCookie($this->userElement);

        $cookie = Craft::$app->getResponse()->getCookies()->get($this->_getUsernameCookieName());

        self::assertSame('', $cookie->value);
        self::assertSame(1, $cookie->expire);
    }

    /**
     *
     */
    public function testGetRemainingSessionTime(): void
    {
        // No identity. Remaining should be null.
        $this->user->setIdentity(null);
        self::assertSame(0, $this->user->getRemainingSessionTime());

        // With a user and authTimeout null it should return -1
        $this->user->setIdentity($this->userElement);
        $this->user->authTimeout = null;
        self::assertSame(-1, $this->user->getRemainingSessionTime());
    }

    /**
     * Test that the current timestamp is subtracted from the session expiration value.
     * We use a stub to ensure Craft::$app->getSession()->get() always returns 50 PHP sessions are difficult(ish) in testing.
     */
    public function testGetRemainingSessionTimeMath(): void
    {
        DateTimeHelper::pause();
        $this->user->setIdentity($this->userElement);

        // ensure Craft::$app->getSession()->get() always returns the current timestamp + 50.
        $this->_sessionGetStub(DateTimeHelper::currentTimeStamp() + 50);

        // Give a few seconds depending on how fast tests run.
        self::assertEquals(Craft::$app->getUser()->getRemainingSessionTime(), 50);

        DateTimeHelper::resume();
        Session::reset();
    }

    /**
     * Test if not logged in getElevated returns 0 or false depending on conditions
     * Important to test this because of PHP's typing system
     */
    public function testGetHasElevatedSession(): void
    {
        $this->user->setIdentity(null);
        self::assertSame(0, $this->user->getElevatedSessionTimeout());

        $this->config->getGeneral()->elevatedSessionDuration = 0;

        self::assertFalse($this->user->getElevatedSessionTimeout());
    }

    /**
     * Test that if a user is logged in and no expires session has been set null is returned.
     */
    public function testGetHasElevatedSessionVoid(): void
    {
        $this->user->setIdentity($this->userElement);
        // Session must return null
        $this->_sessionGetStub(null);

        $generalConfig = Craft::$app->getConfig()->getGeneral();
        $oldValue = $generalConfig->elevatedSessionDuration;
        $generalConfig->elevatedSessionDuration = 0;
        self::assertSame(false, $this->user->getElevatedSessionTimeout());
        $generalConfig->elevatedSessionDuration = $oldValue;

        Session::reset();
    }

    /**
     * Test that if a user is logged in and no expires session has been set null is returned.
     */
    public function testGetHasElevatedSessionMath(): void
    {
        DateTimeHelper::pause();
        $this->user->setIdentity($this->userElement);

        $this->_sessionGetStub(DateTimeHelper::currentTimeStamp() + 50);
        self::assertEquals(50, $this->user->getElevatedSessionTimeout());

        // If the session->get() return value is smaller than time 0 is returned
        $this->_sessionGetStub(DateTimeHelper::currentTimeStamp() - 50);
        self::assertEquals(0, $this->user->getElevatedSessionTimeout());

        DateTimeHelper::resume();
        Session::reset();
    }

    /**
     * @inheritdoc
     */
    protected function _before(): void
    {
        parent::_before();
        $this->userElement = $this->_getUser();
        $this->config = Craft::$app->getConfig();
        $this->user = Craft::$app->getUser();
    }

    /**
     * Sets the Craft::$app->getSession(); to a stub where the get() method returns what you want.
     *
     * @param int|null $returnValue
     */
    private function _sessionGetStub(?int $returnValue)
    {
        Session::reset();

        $this->tester->mockCraftMethods('session', [
            'getHasSessionId' => function() {
                return true;
            },
            'get' => function($tokenParam) use ($returnValue) {
                return $returnValue;
            },
        ]);
    }

    /**
     * @return UserElement|null
     */
    private function _getUser(): ?UserElement
    {
        return Craft::$app->getUsers()->getUserById(1);
    }

    /**
     * @return mixed
     */
    private function _getUsernameCookieName(): mixed
    {
        return Craft::$app->getUser()->usernameCookie['name'];
    }
}
