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

/**
 * Unit tests for ErrorHandler
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @author Global Network Group | Giel Tettelaar <giel@yellowflash.net>
 * @since 3.2
 */
class ErrorHandlerTest extends TestCase
{
    /**
     * @var UnitTester
     */
    protected $tester;

    /**
     * @var ErrorHandler
     */
    protected $errorHandler;

    /**
     * Test that Twig runtime errors use the previous error (if it exists).
     *
     * @throws Exception
     */
    public function testHandleTwigException()
    {
        // Disable clear output as this throws: Test code or tested code did not (only) close its own output buffers
        $this->errorHandler = Stub::construct(ErrorHandler::class, [], [
            'logException' => self::assertObjectIsInstanceOfClassCallback(Exception::class),
            'clearOutput' => null,
            'renderException' => self::assertObjectIsInstanceOfClassCallback(Exception::class)
        ]);

        $exception = new RuntimeError('A Twig error occurred');
        $this->setInaccessibleProperty($exception, 'previous', new Exception('Im not a twig error'));
        $this->errorHandler->handleException($exception);
    }

    /**
     * @dataProvider exceptionTypeAndNameDataProvider
     *
     * @param Throwable $exception
     * @param $message
     */
    public function testGetExceptionName(Throwable $exception, $message)
    {
        self::assertSame($message, $this->errorHandler->getExceptionName($exception));
    }

    /**
     * @dataProvider getTypeUrlDataProvider
     *
     * @param string|null $expected
     * @param string $class
     * @param string|null $method
     * @throws ReflectionException
     */
    public function testGetTypeUrl(?string $expected, string $class, ?string $method)
    {
        self::assertSame($expected, $this->invokeMethod($this->errorHandler, 'getTypeUrl', [$class, $method]));
    }

    /**
     * @throws ErrorException
     */
    public function testHandleError()
    {
        if (PHP_VERSION_ID >= 70100) {
            self::assertNull($this->errorHandler->handleError(null, 'Narrowing occurred during type inference. Please file a bug report', null, null));
        } else {
            $this->markTestSkipped('Running on PHP 70100. parent::handleError() should be called by default in the craft ErrorHandler.');
        }
    }

    /**
     * @dataProvider isCoreFileDataProvider
     *
     * @param bool $expected
     * @param string $file
     */
    public function testIsCoreFile(bool $expected, string $file)
    {
        self::assertSame($expected, $this->errorHandler->isCoreFile(Craft::getAlias($file)));
    }

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
