<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */


namespace craftunit\web;


use Codeception\Test\Unit;
use craft\helpers\ArrayHelper;
use craft\test\Craft;
use craft\test\mockclasses\controllers\TestController;
use craft\web\Application;
use yii\base\Action;
use yii\base\ExitException;
use yiiunit\TestCase;

/**
 * Unit tests for ControllerTest
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @author Global Network Group | Giel Tettelaar <giel@yellowflash.net>
 * @since 3.0
 */
class ControllerTest extends Unit
{
    /**
     * @var \UnitTester
     */
    protected $tester;

    /**
     * @var TestController
     */
    private $controller;
    public function _before()
    {
        parent::_before();
        $_SERVER['REQUEST_URI'] = 'https://craftcms.com/admin/dashboard';
        $this->controller = new TestController('test', \Craft::$app);
    }
    public function testStuff()
    {
        $this->tester->expectThrowable(ExitException::class, function () {
            // AllowAnonymous should redirect and Craft::$app->exit(); I.E. An exit exception
            $this->controller->beforeAction(new Action('not-allow-anonymous', $this->controller));
        });

        $this->assertTrue($this->controller->beforeAction(new Action('allow-anonymous', $this->controller)));
    }


}