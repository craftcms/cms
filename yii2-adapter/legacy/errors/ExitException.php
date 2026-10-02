<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\errors;

use Throwable;
use yii\base\ExitException as YiiExitException;

/**
 * ExitException
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 4.5.8
 */
class ExitException extends YiiExitException
{
    public function __construct(
        int $status = 0,
        ?string $message = null,
        int $code = 0,
        ?Throwable $previous = null,
        public ?string $output = null,
    ) {
        parent::__construct($status, $message, $code, $previous);
    }
}
