<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\errors;

use mikehaertl\shellcommand\Command;
use yii\base\Exception;

/**
 * ShellCommandException represents an exception caused by setting an invalid license key on a plugin.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0
 */
class ShellCommandException extends Exception
{
    /**
     * @var string|null The command that was executed
     */
    public $command;

    /**
     * @var int|null The command’s exit code
     */
    public $exitCode;

    /**
     * @var string|null The command’s error output
     */
    public $error;

    /**
     * Creates a ShellCommandException from a [[Command]] object
     *
     * @param Command $command The failed Command object
     * @return static|false
     */
    public static function createFromCommand(Command $command)
    {
        $execCommand = $command->getExecCommand();

        if ($execCommand !== false) {
            return new static($execCommand, $command->getExitCode(), $command->getStdErr());
        }

        return false;
    }

    /**
     * Constructor.
     *
     * @param string $command The command that was executed
     * @param int $exitCode The command’s exit code
     * @param string|null $error The command’s error output
     * @param string|null $message The error message
     * @param int $code The error code
     */
    public function __construct(string $command, int $exitCode, string $error = null, string $message = null, int $code = 0)
    {
        $this->command = $command;
        $this->exitCode = $exitCode;
        $this->error = $error;

        if ($message === null) {
            // Quote the command
            if ($command !== false) {
                $command = "\"{$command}\"";
            } else {
                $command = '`false`';
            }
            $message = "The shell command {$command} failed with exit code {$exitCode}".($error ? ": {$error}" : '.');
        }

        parent::__construct($message, $code);
    }

    /**
     * @return string the user-friendly name of this exception
     */
    public function getName(): string
    {
        return 'Shell Command Failure';
    }
}
