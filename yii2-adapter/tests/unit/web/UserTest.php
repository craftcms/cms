<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace crafttests\unit\web;

use Craft;
use craft\services\Config;
use craft\test\TestCase;
use craft\web\User as WebUser;
use CraftCms\Cms\User\Elements\User as UserElement;
use CraftCms\Yii2Adapter\IdentityWrapper;
use Illuminate\Support\Facades\Auth;
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
    public function testGetRemainingSessionTime(): void
    {
        // No identity. Remaining should be null.
        $this->user->setIdentity(null);
        self::assertSame(0, $this->user->getRemainingSessionTime());

        // With a user and authTimeout null it should return -1
        Auth::login($this->userElement);
        $this->user->setIdentity(new IdentityWrapper($this->userElement));
        $this->user->authTimeout = null;
        self::assertSame(-1, $this->user->getRemainingSessionTime());
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
        \Illuminate\Support\Facades\Session::invalidate();

        $this->tester->mockCraftMethods('session', [
            'getHasSessionId' => fn() => true,
            'get' => fn($tokenParam) => $returnValue,
        ]);
    }

    /**
     * @return UserElement|null
     */
    private function _getUser(): ?UserElement
    {
        return Craft::$app->getUsers()->getUserById(1);
    }
}
