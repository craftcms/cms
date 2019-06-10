<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace crafttests\unit\web;

use Codeception\Stub;
use Craft;
use craft\test\TestCase;
use craft\web\ErrorHandler;
use Exception;
use ReflectionException;
use Throwable;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;
use UnitTester;
use yii\base\ErrorException;
use yii\web\HttpException;

/**
 * Unit tests for ErrorHandler
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @author Global Network Group | Giel Tettelaar <giel@yellowflash.net>
 * @since 3.2
 */
class ErrorHandlerTest extends TestCase
{
    // Public Properties
    // =========================================================================

    /**
     * @var UnitTester
     */
    protected $tester;

    /**
     * @var ErrorHandler
     */
    protected $errorHandler;

    // Public Methods
    // =========================================================================

    // Tests
    // =========================================================================

    /**
     * Test that Twig runtime errors use the previous error (if it exists).
     *
     * @throws Exception
     */
    public function testHandleTwigException()
    {
        // Disable clear output as this throws: Test code or tested code did not (only) close its own output buffers
        $this->errorHandler = Stub::construct(ErrorHandler::class, [], [
            'logException' => $this->assertObjectIsInstanceOfClassCallback(Exception::class),
            'clearOutput' => null,
            'renderException' => $this->assertObjectIsInstanceOfClassCallback(Exception::class)
        ]);

        $exception = new RuntimeError('A Twig error occurred');
        $this->setInaccessibleProperty($exception, 'previous', new Exception('Im not a twig error'));
        $this->errorHandler->handleException($exception);
    }

    /**
     * @throws Exception
     */
    public function testHandle404Exception()
    {
        // Disable clear output as this throws: Test code or tested code did not (only) close its own output buffers
        $this->errorHandler = Stub::construct(ErrorHandler::class, [], [
            'logException' => $this->assertObjectIsInstanceOfClassCallback(HttpException::class),
            'clearOutput' => null,
            'renderException' => $this->assertObjectIsInstanceOfClassCallback(HttpException::class)
        ]);

        // Oops. Page not found
        $exception = new HttpException('I am an error.');
        $exception->statusCode = 404;

        // Test 404's are treated with a different file
        $this->errorHandler->handleException($exception);
        $this->assertSame(Craft::getAlias('@crafttestsfolder/storage/logs/web-404s.log'), Craft::$app->getLog()->targets[0]->logFile);
    }

    /**
     * @dataProvider exceptionTypeAndNameDataProvider
     *
     * @param Throwable $exception
     * @param $message
     */
    public function testGetExceptionName(Throwable $exception, $message)
    {
        $this->assertSame($message, $this->errorHandler->getExceptionName($exception));
    }

    /**
     * @dataProvider getTypeUrlDataProvider
     *
     * @param $result
     * @param $class
     * @param $method
     * @throws ReflectionException
     */
    public function testGetTypeUrl($result, $class, $method)
    {
        $this->assertSame($result, $this->invokeMethod($this->errorHandler, 'getTypeUrl', [$class, $method]));
    }

    /**
     * @throws ErrorException
     */
    public function testHandleError()
    {
        if (PHP_VERSION_ID >= 70100) {
            $this->assertNull($this->errorHandler->handleError(null, 'Narrowing occurred during type inference. Please file a bug report', null, null));
        } else {
            $this->markTestSkipped('Running on PHP 70100. parent::handleError() should be called by default in the craft ErrorHandler.');
        }
    }

    /**
     * @dataProvider isCoreFileDataProvider
     *
     * @param $result
     * @param $input
     */
    public function testIsCoreFile($result, $input)
    {
        $isCore = $this->errorHandler->isCoreFile(Craft::getAlias($input));
        $this->assertSame($result, $isCore);
    }

    // Data Providers
    // =========================================================================

    /**
     * @return array
     */
    public function exceptionTypeAndNameDataProvider(): array
    {
        return [
            [new SyntaxError('Twig go boom'), 'Twig Syntax Error'],
            [new LoaderError('Twig go boom'), 'Twig Template Loading Error'],
            [new RuntimeError('Twig go boom'), 'Twig Runtime Error'],
        ];
    }

    /**
     * @return array
     */
    public function getTypeUrlDataProvider(): array
    {
        return [
            ['http://twig.sensiolabs.org/api/2.x/Twig\Template.html#method_render', '__TwigTemplate_', 'render'],
            ['http://twig.sensiolabs.org/api/2.x/Twig\.html#method_render', 'Twig\\', 'render'],
            ['http://twig.sensiolabs.org/api/2.x/Twig\.html', 'Twig\\', null],
            [null, 'Twig_', 'render'],
        ];
    }

    /**
     * @return array
     */
    public function isCoreFileDataProvider(): array
    {
        $path = Craft::getAlias('@crafttestsfolder/storage/runtime/compiled_templates');
        $vendorPath = Craft::getAlias('@vendor');
        $craftPath = Craft::getAlias('@craft');

        return [
            [true, $path . '/created_path'],
            [true, $vendorPath . '/twig/twig/LICENSE'],
            [true, $vendorPath . '/twig/twig/composer.json'],
            [true, $craftPath . '/web/twig/Template.php'],

            [false, $craftPath . '/web/twig'],
            [false, __DIR__]
        ];
    }

    // Protected Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    protected function _before()
    {
        parent::_before();

        // Create a dir in compiled templates. See self::144
        $path = Craft::getAlias('@crafttestsfolder/storage/runtime/compiled_templates');
        mkdir($path . '/created_path');

        $this->errorHandler = Craft::createObject(ErrorHandler::class);
    }

    /**
     * @inheritdoc
     */
    protected function _after()
    {
        // Remove the dir created in _before
        $path = Craft::getAlias('@crafttestsfolder/storage/runtime/compiled_templates');
        rmdir($path . '/created_path');

        parent::_after();
    }
}
