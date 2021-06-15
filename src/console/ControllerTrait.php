<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\console;

use Composer\Util\Platform;
use Composer\Util\Silencer;
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
     * Returns whether a command should be executed depending on whether it's being run as a root user,
     * and whether they're OK with that.
     *
     * @return bool
     * @since 3.7.0
     */
    protected function checkRootUser(): bool
    {
        // Check if we're running as root. Borrowed heavily from
        // https://github.com/composer/composer/blob/master/src/Composer/Console/Application.php
        if (!Platform::isWindows() && function_exists('exec')) {
            if (function_exists('posix_getuid') && posix_getuid() === 0) {
                $this->stdout('Craft commands should not be run as the root user.' . PHP_EOL, Console::FG_RED);
                $this->stdout('See https://craftcms.com/knowledge-base/craft-console-root for details on why that’s a bad idea.' . PHP_EOL, Console::FG_GREY);

                if ($this->interactive && !$this->confirm('Proceed anyway?')) {
                    return false;
                }

                if ($uid = (int)getenv('SUDO_UID')) {
                    // Silently clobber any sudo credentials on the invoking user to avoid privilege escalations later on
                    // ref. https://github.com/composer/composer/issues/5119
                    /** @noinspection CommandExecutionAsSuperUserInspection */
                    Silencer::call('exec', "sudo -u \\#$uid sudo -K > /dev/null 2>&1");
                }
            }

            // Silently clobber any remaining sudo leases on the current user as well to avoid privilege escalations
            /** @noinspection CommandExecutionAsSuperUserInspection */
            Silencer::call('exec', 'sudo -K > /dev/null 2>&1');

            return true;
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
