<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\errors;

use Throwable;
use yii\base\Exception;

/**
 * MigrateException represents an error that occurred while migrating Craft or a plugin.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0.0
 */
class MigrateException extends Exception
{
    /**
     * Constructor.
     *
     * @param string $ownerName The name of the thing being updated
     * @param string $ownerHandle The handle of the thing being updated
     * @param string|null $message The error message
     * @param int $code The error code
     * @param Throwable|null $previous The previous exception
     */
    public function __construct(public string $ownerName, public string $ownerHandle, ?string $message = null, int $code = 0, Throwable $previous = null)
    {
        if ($message === null) {
            $message = 'An error occurred while migrating ' . $this->ownerName . '.';
        }

        parent::__construct($message, $code, $previous);
    }

    /**
     * @return string the user-friendly name of this exception
     */
    public function getName(): string
    {
        return 'Migrate Error';
    }
}
