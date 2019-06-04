<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace crafttests\unit\console;

use craft\helpers\FileHelper;
use craft\test\console\ConsoleTest as BaseConsoleTest;
use yii\base\InvalidConfigException;
use yii\console\ExitCode;
use Craft;

/**
 * Class ConsoleTest
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @author Global Network Group | Giel Tettelaar <giel@yellowflash.net>
 * @since 3.2
 */
class TestsControllerTest extends BaseConsoleTest
{
    // Public methods
    // =========================================================================

    /**
     * @throws InvalidConfigException
     */
    public function testStuff()
    {
        $this->consoleCommand('update/info')
            ->stdOut('Fetching available updates ... ')
            ->stdOut('done' . PHP_EOL)
            ->stdOut('You’re all up-to-date!' . PHP_EOL . PHP_EOL)
            ->exitCode(ExitCode::OK)
            ->run();
    }

    /**
     * @throws InvalidConfigException
     */
    public function testTestController()
    {
        $this->consoleCommand('test/test')
            ->stdOut('22')
            ->stderr('123321123')
            ->select('Select', '2', ['2', '22'])
            ->confirm('asd', true, true)
            ->prompt('A prompt', 'hi', ['2', '22'])
            ->outputCommand('An output command')
            ->exitCode(ExitCode::OK)
            ->run();
    }

    /**
     * @throws InvalidConfigException
     * @throws \yii\base\ErrorException
     * @throws \yii\base\Exception
     */
    public function testSetupTests()
    {
        $this->consoleCommand('test/setup-tests')
            ->confirm('Are you sure you want to generate the tests suite?', true, false)
            ->confirm('Do you want a custom path?', false, false)
            ->stdOut('Test suite generated. Ensure you update you update your composer dependencies.')
            ->exitCode(ExitCode::OK)
            ->run();

        $oneUpAtVendor = dirname(Craft::$app->getPath()->getVendorPath());

        $dstPath = $oneUpAtVendor.DIRECTORY_SEPARATOR.'generated-tests';

        if (!is_dir($dstPath) || FileHelper::isDirectoryEmpty($dstPath)) {
            $this->fail('Setting up tests failed to create directory');
        }

        FileHelper::removeDirectory($dstPath);
    }

    /**
     *
     */
    public function testSetupTestsWithCustomPath()
    {
        $dstPath = __DIR__.DIRECTORY_SEPARATOR.'generated-test-material';

        $this->consoleCommand('test/setup-tests')
            ->confirm('Are you sure you want to generate the tests suite?', true, false)
            ->confirm('Do you want a custom path?', true, false)
            ->prompt('Which path should the "tests/" dir be placed in?', $dstPath)
            ->stdOut('Test suite generated. Ensure you update your composer file is in accordance with the Craft documentation. "')
            ->exitCode(ExitCode::OK)
            ->run();

        if (!is_dir($dstPath) || FileHelper::isDirectoryEmpty($dstPath)) {
            $this->fail('Setting up tests failed to create directory');
        }

        FileHelper::removeDirectory($dstPath);
    }

    /**
     * @throws InvalidConfigException
     * @throws \yii\base\ErrorException
     * @throws \yii\base\Exception
     */
    public function testFailCustomTestSetupIfNoConfirm()
    {
        $this->consoleCommand('test/setup-tests')
            ->confirm('Are you sure you want to generate the tests suite?', false, false)
            ->stdOut('Aborted!')
            ->exitCode(ExitCode::OK)
            ->run();

        $oneUpAtVendor = dirname(Craft::$app->getPath()->getVendorPath());

        $dstPath = $oneUpAtVendor.DIRECTORY_SEPARATOR.'generated-tests';

        if (is_dir($dstPath)) {
            $this->fail('Setting up tests created a directory when it shouldnt.');
        }

        FileHelper::removeDirectory($dstPath);
    }
}
