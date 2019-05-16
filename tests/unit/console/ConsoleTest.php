<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craftunit\console;

use \craft\test\console\ConsoleTest as BaseConsoleTest;
use yii\console\ExitCode;

/**
 * Class ConsoleTest
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @author Global Network Group | Giel Tettelaar <giel@yellowflash.net>
 * @since 3.1
 */
class ConsoleTest extends BaseConsoleTest
{
    // Public methods
    // =========================================================================

    /**
     * @throws \yii\base\InvalidConfigException
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

    public function testTestController()
    {
        $this->consoleCommand('test/test')
            ->stdOut('22')
            ->stderr('123321123')
            ->select('Select', '2', ['2', '22'])
            ->confirm('asd', true, true)
            ->prompt('A prompt', 'hi', ['2', '22'])
            ->exitCode(ExitCode::OK)
            ->run();
    }
}
