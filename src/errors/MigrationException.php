<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\errors;

use Throwable;
use yii\base\UserException;
use yii\db\Migration;

/**
 * MigrationException represents an exception thrown while executing a migration.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0.0
 */
class MigrationException extends UserException
{
    /**
     * Constructor.
     *
     * @param Migration $migration The migration being executed
     * @param string|null $output The migration output
     * @param string|null $message The error message
     * @param int $code The error code
     * @param Throwable|null $previous The previous exception
     */
    public function __construct(public Migration $migration, public ?string $output = null, ?string $message = null, int $code = 0, ?Throwable $previous = null)
    {
        if ($message === null) {
            $message = 'An error occurred while executing the "' . $this->migration::class . ' migration' . ($previous ? ': ' . $previous->getMessage() : '.');
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
