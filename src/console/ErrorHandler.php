<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\console;

use craft\events\ExceptionEvent;

/**
 * Class ErrorHandler
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.7.18
 */
class ErrorHandler extends \yii\console\ErrorHandler
{
    /**
     * @event ExceptionEvent The event that is triggered before handling an exception.
     */
    public const EVENT_BEFORE_HANDLE_EXCEPTION = 'beforeHandleException';

    /**
     * @inheritdoc
     */
    public function handleException($exception): void
    {
        // Fire a 'beforeHandleException' event
        if ($this->hasEventHandlers(self::EVENT_BEFORE_HANDLE_EXCEPTION)) {
            $this->trigger(self::EVENT_BEFORE_HANDLE_EXCEPTION, new ExceptionEvent([
                'exception' => $exception,
            ]));
        }

        parent::handleException($exception);
    }
}
