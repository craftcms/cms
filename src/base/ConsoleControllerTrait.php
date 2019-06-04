<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\base;

use craft\helpers\Console;

/**
 * ConsoleControllerTrait implements the common methods and properties for console controllers.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @author Global Network Group | Giel Tettelaar <giel@yellowflash.net>
 * @since 3.1
 */
trait ConsoleControllerTrait
{
    // Protected methods
    // =========================================================================

    /**
     * @param string $command
     * @param bool $withScriptName
     * @return mixed
     */
    protected function outputCommand(string $command, bool $withScriptName = true)
    {
        return Console::outputCommand($command, $withScriptName);
    }
}
