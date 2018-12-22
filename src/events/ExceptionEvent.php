<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\events;

use yii\base\Event;

/**
 * Exception event class.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0
 */
class ExceptionEvent extends Event
{
    // Properties
    // =========================================================================

    /**
     * @var \Exception The uncaught exception that was thrown
     */
    public $exception;
}
