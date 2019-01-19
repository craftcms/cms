<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */


namespace craftunit\web;


use craft\test\TestCase;
use craft\web\ErrorHandler;

/**
 * Unit tests for ErrorHandlerTest
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @author Global Network Group | Giel Tettelaar <giel@yellowflash.net>
 * @since 3.0
 */
class ErrorHandlerTest extends TestCase
{
    /**
     * @var \UnitTester
     */
    protected $tester;

    /**
     * @var ErrorHandler $errorHandler
     */
    protected $errorHandler;

    public function _before()
    {
        parent::_before();

        $this->errorHandler = \Craft::createObject(ErrorHandler::class);
    }

    /**
     * @param \Throwable $exception
     * @param $message
     * @dataProvider exceptionTypeAndNameData
     */
    public function testGetExceptionName(\Throwable $exception, $message)
    {
        $this->assertSame($message, $this->errorHandler->getExceptionName($exception));
    }
    public function exceptionTypeAndNameData()
    {
        return [
            [new \Twig_Error_Syntax('Twig go boom'), 'Twig Syntax Error'],
            [new \Twig_Error_Loader('Twig go boom'), 'Twig Template Loading Error'],
            [new \Twig_Error_Runtime('Twig go boom'), 'Twig Runtime Error'],
        ];
    }

    /**
     * @param $result
     * @param $class
     * @param $method
     * @dataProvider getTypeUrlData
     */
    public function testGetTypeUrl($result, $class, $method)
    {
        $this->assertSame($result, $this->invokeMethod($this->errorHandler, 'getTypeUrl', [$class, $method]));
    }
    public function getTypeUrlData() : array
    {
        return [
            ['http://twig.sensiolabs.org/api/2.x/Twig_Template.html#method_render', '__TwigTemplate_', 'render'],
            ['http://twig.sensiolabs.org/api/2.x/Twig_.html#method_render', 'Twig_', 'render'],
            ['http://twig.sensiolabs.org/api/2.x/Twig_.html', 'Twig_', null],
        ];
    }

    /**
     * @throws \yii\base\ErrorException
     */
    public function testHandleError()
    {
        if (PHP_VERSION_ID >= 70100) {
            $this->assertNull($this->errorHandler->handleError(null, 'Narrowing occurred during type inference. Please file a bug report', null, null));
        } else {
            $this->markTestSkipped('Running on PHP 70100. parent::handleError() should be called in the craft ErrorHandler.');
        }
    }
}
