<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\console;

use Composer\Util\Platform;
use craft\helpers\Console;

/**
 * ConsoleControllerTrait implements the common methods and properties for console controllers.
 *
 * @mixin \yii\console\Controller
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @author Global Network Group | Giel Tettelaar <giel@yellowflash.net>
 * @since 3.2.0
 */
trait ControllerTrait
{
    /**
     * Sets [[\yii\console\Controller::$interactive]] to `false` if this isn’t a TTY shell.
     *
     * @return void
     * @since 3.6.1
     */
    protected function checkTty(): void
    {
        // Don't treat this as interactive if it doesn't appear to be a TTY shell
        if ($this->interactive && !Platform::isTty()) {
            $this->interactive = false;
        }
    }

    /**
     * @param string $command
     * @param bool $withScriptName
     */
    protected function outputCommand(string $command, bool $withScriptName = true)
    {
        Console::outputCommand($command, $withScriptName);
    }
}
