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
 * Class InvalidSubpathException
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0.0
 */
class InvalidSubpathException extends Exception
{
    /**
     * Constructor.
     *
     * @param string $subpath The invalid subpath
     * @param string|null $message The error message
     * @param int $code The error code
     * @param Throwable|null $previous The previous exception
     */
    public function __construct(public string $subpath, ?string $message = null, int $code = 0, ?Throwable $previous = null)
    {
        if ($message === null) {
            $message = "Could not resolve the subpath “{$this->subpath}”.";
        }

        parent::__construct($message, $code, $previous);
    }

    /**
     * @return string the user-friendly name of this exception
     */
    public function getName(): string
    {
        return 'Invalid subpath';
    }
}
