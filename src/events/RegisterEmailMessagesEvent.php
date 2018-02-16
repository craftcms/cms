<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\events;

use yii\base\Event;

/**
 * RegisterEmailMessagesEvent class.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0
 */
class RegisterEmailMessagesEvent extends Event
{
    // Properties
    // =========================================================================

    /**
     * @var array List of registered email messages. Each message should contain 'key', 'category', and 'sourceLanguage' keys.
     */
    public $messages = [];
}
