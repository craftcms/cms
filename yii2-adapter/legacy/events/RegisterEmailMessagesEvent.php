<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\events;

use craft\base\Event;

/**
 * RegisterEmailMessagesEvent class.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0.0
 * @deprecated 6.0.0 use {@see \CraftCms\Cms\SystemMessage\Events\RegisterSystemMessages} instead.
 */
class RegisterEmailMessagesEvent extends Event
{
    /**
     * @var array List of registered email messages. Each message should contain 'key' and optionally 'heading', 'subject', and 'body' keys.
     */
    public array $messages = [];
}
