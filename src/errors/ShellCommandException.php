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
 * @since 3.0.0
 */
final class ShellCommandException extends Exception
{
    /**
     * Creates a ShellCommandException from a [[Command]] object
     *
     * @param Command $command The failed Command object
     * @return static|false
     */
    public static function createFromCommand(Command $command): self|false
    {
        $execCommand = $command->getExecCommand();

        if ($execCommand === false) {
            return false;
        }

        return new self($execCommand, $command->getExitCode(), $command->getStdErr());
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
    public function __construct(public string $command, public int $exitCode, public ?string $error = null, ?string $message = null, int $code = 0)
    {
        if ($message === null) {
            $message = "The shell command \"{$this->command}\" failed with exit code {$this->exitCode}" . ($this->error ? ": {$this->error}" : '.');
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
