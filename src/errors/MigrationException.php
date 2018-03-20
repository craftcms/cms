<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\errors;

use yii\base\Exception;
use yii\db\Migration;

/**
 * MigrationException represents an exception thrown while executing a migration.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0
 */
class MigrationException extends Exception
{
    /**
     * @var Migration The migration being executed
     */
    public $migration;

    /**
     * @var string|null The migration output
     */
    public $output;

    /**
     * Constructor.
     *
     * @param Migration $migration The migration being executed
     * @param string|null $output The migration output
     * @param string|null $message The error message
     * @param int $code The error code
     * @param \Throwable|null $previous The previous exception
     */
    public function __construct(Migration $migration, string $output = null, string $message = null, int $code = 0, \Throwable $previous = null)
    {
        $this->migration = $migration;
        $this->output = $output;

        if ($message === null) {
            $message = 'An error occurred while executing the "'.get_class($migration).' migration'.($previous ? ': '.$previous->getMessage() : '.');
        }

        parent::__construct($message, $code, $previous);
    }

    /**
     * @return string the user-friendly name of this exception
     */
    public function getName(): string
    {
        return 'Migration Error';
    }
}
