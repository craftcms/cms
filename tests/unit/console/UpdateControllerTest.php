<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace crafttests\unit\console;

use craft\test\console\ConsoleTest as BaseConsoleTest;
use yii\base\InvalidConfigException;
use yii\console\ExitCode;

/**
 * Class UpdateControllerTest
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @author Global Network Group | Giel Tettelaar <giel@yellowflash.net>
 * @since 3.2
 */
class UpdateControllerTest extends BaseConsoleTest
{
    // Public methods
    // =========================================================================

    /**
     * @throws InvalidConfigException
     */
    public function testUpdateInfo()
    {
        $this->consoleCommand('update/info')
            ->stdOut('Fetching available updates ... ')
            ->stdOut('done' . PHP_EOL)
            ->stdOut('You’re all up-to-date!' . PHP_EOL . PHP_EOL)
            ->exitCode(ExitCode::OK)
            ->run();
    }
}
