<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\errors;

use yii\base\Exception;

/**
 * InvalidFieldException represents an exception caused by accessing a field handle that doesn’t exist.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.5.12
 */
class InvalidFieldException extends Exception
{
    /**
     * @var string The invalid field handle.
     */
    public $handle;

    /**
     * Constructor.
     *
     * @param string $handle The field handle
     * @param string|null $message The error message
     * @param int $code The error code
     * @param \Throwable|null $previous The previous exception
     */
    public function __construct(string $handle, string $message = null, int $code = 0, \Throwable $previous = null)
    {
        if ($message === null) {
            $message = "Invalid field handle: $handle";
        }

        parent::__construct($message, $code, $previous);
    }

    /**
     * @return string the user-friendly name of this exception
     */
    public function getName(): string
    {
        return 'Invalid field';
    }
}
