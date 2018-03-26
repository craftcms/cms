<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\events;

use craft\mail\Message;

/**
 * MailMessage event class.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0
 */
class MailMessageEvent extends CancelableEvent
{
    // Properties
    // =========================================================================

    /**
     * @var Message|null The message model associated with the event.
     */
    public $message;
}
