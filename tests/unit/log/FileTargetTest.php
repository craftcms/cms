<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace crafttests\unit\log;

use Codeception\Stub;
use Craft;
use craft\elements\User;
use craft\log\FileTarget;
use craft\test\TestCase;
use craft\web\Application;
use craft\web\Request;
use craft\web\Session;
use Exception;
use Throwable;
use UnitTester;
use yii\base\InvalidConfigException;

/**
 * Unit tests for FileTarget
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @author Global Network Group | Giel Tettelaar <giel@yellowflash.net>
 * @since 3.2
 */
class FileTargetTest extends TestCase
{
    // Public Properties
    // =========================================================================

    /**
     * @var FileTarget
     */
    public $fileTarget;

    /**
     * @var UnitTester
     */
    public $tester;

    // Public Methods
    // =========================================================================

    // Tests
    // =========================================================================

    /**
     * @throws InvalidConfigException
     * @throws Throwable
     */
    public function testgetMessagePrefixWithUserFunc()
    {
        $wasCalled = false;
        $this->fileTarget->prefix = function($mess) use (&$wasCalled) {
            $wasCalled = true;
            $this->assertSame('message', $mess);
        };

        $this->fileTarget->getMessagePrefix('message');
        $this->assertTrue($wasCalled);
    }

    /**
     * @throws InvalidConfigException
     * @throws Throwable
     */
    public function testGetMessagePrefixWithNullCraft()
    {
        $craftApp = Craft::$app;
        Craft::$app = null;
        $this->assertSame('', $this->fileTarget->getMessagePrefix('message'));
        Craft::$app = $craftApp;
    }

    /**
     * @throws InvalidConfigException
     * @throws Throwable
     */
    public function testFullMessagePrefix()
    {
        $craftApp = clone Craft::$app;
        $this->_mockCraftForFullMessagePrefix();

        $this->fileTarget->includeUserIp = true;

        $this->assertSame('[192.168.10.10][666][999]', $this->fileTarget->getMessagePrefix('message'));

        Craft::$app = $craftApp;
    }

    // Protected Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    protected function _before()
    {
        $this->fileTarget = new FileTarget();
    }

    // Private Methods
    // =========================================================================

    /**
     * Mocks the Craft::$app object so that it overrides the components we need for self::testFullMessagePrefix
     *
     * @throws Exception
     */
    private function _mockCraftForFullMessagePrefix()
    {
        $identityStub = Stub::make(User::class, ['getId' => '666']);

        $stubArray = Craft::$app->getComponents();

        $stubArray['request'] = Stub::construct(Request::class, [], ['getUserIp' => '192.168.10.10']);
        $stubArray['user'] = Stub::construct(\craft\web\User::class, [['identityClass' => User::class]], ['getIdentity' => $identityStub]);
        $stubArray['session'] = Stub::construct(Session::class, [], ['getIsActive' => true, 'getId' => '999']);

        Craft::$app = Stub::make(Application::class, [
                'has' => true,
                'getRequest' => $stubArray['request'],
                'get' => function($type) use ($stubArray) {
                    return $stubArray[$type] ?? null;
                }
            ]
        );
    }
}
