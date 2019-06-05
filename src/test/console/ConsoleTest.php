<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\test\console;

use craft\test\TestCase;
use yii\base\InvalidConfigException;

/**
 * Class ConsoleTest
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @author Global Network Group | Giel Tettelaar <giel@yellowflash.net>
 * @since 3.2
 */
class ConsoleTest extends TestCase
{
    // Public Methods
    // =========================================================================

    /**
     * @param string $command
     * @param array $parameters
     * @param bool $ignoreStdOut
     * @return CommandTest
     * @throws InvalidConfigException
     */
    public function consoleCommand(string $command, array $parameters = [], bool $ignoreStdOut = false): CommandTest
    {
        return new CommandTest($this, $command, $parameters, $ignoreStdOut);
    }
}
