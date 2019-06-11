<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace crafttests\unit\console;

use craft\helpers\FileHelper;
use craft\helpers\StringHelper;
use craft\test\console\ConsoleTest as BaseConsoleTest;
use yii\base\Exception;
use yii\base\InvalidConfigException;
use yii\console\ExitCode;

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
    public function testTestController()
    {
        $this->consoleCommand('tests/test')
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
     * @throws Exception
     */
    public function testSetupWithDefaultPath()
    {
        $this->consoleCommand('tests/setup', [], true)
            ->confirm('Are you sure you want to continue?', false, false)
            ->exitCode(ExitCode::UNSPECIFIED_ERROR)
            ->run();
    }

    /**
     *
     */
    public function testSetupTestsWithCustomPath()
    {
        $dst = getcwd() . DIRECTORY_SEPARATOR . StringHelper::randomString();

        $this->consoleCommand('tests/setup', [$dst], true)
            ->confirm('Continue?', false, true)
            ->exitCode(ExitCode::UNSPECIFIED_ERROR)
            ->run();

        $this->consoleCommand('tests/setup', [$dst], true)
            ->confirm('Continue?', true, true)
            ->exitCode(ExitCode::OK)
            ->run();

        if (!is_dir($dst) || FileHelper::isDirectoryEmpty($dst)) {
            $this->fail('Setting up tests failed to create directory');
        }

        FileHelper::removeDirectory($dst);
    }
}
