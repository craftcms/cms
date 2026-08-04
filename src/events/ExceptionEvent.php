<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\events;

use craft\base\Event;
use Throwable;

/**
 * Exception event class.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0.0
 */
class ExceptionEvent extends Event
{
    /**
     * @var Throwable The uncaught exception that was thrown
     */
    public Throwable $exception;
}
