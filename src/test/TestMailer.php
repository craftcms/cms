<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\test;

use craft\mail\Mailer;
use craft\mail\Message;

/**
 * Exactly the same as Codeception\Lib\Connector\Yii2\TestMailer except that we overide based on Crafts own mailer class.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @author Global Network Group | Giel Tettelaar <giel@yellowflash.net>
 * @since 3.1
 */
class TestMailer extends Mailer
{
    // Public properties
    // =========================================================================

    /**
     * @var string
     */
    public $messageClass = Message::class;

    /**
     * @var \Closure
     */
    public $callback;

    // Protected functions
    // =========================================================================

    /**
     * @param $message
     * @return bool
     */
    protected function sendMessage($message) : bool
    {
        call_user_func($this->callback, $message);
        return true;
    }

    /**
     * @param \yii\mail\MessageInterface $message
     * @return bool
     */
    protected function saveMessage($message) : bool
    {
        call_user_func($this->callback, $message);
        return true;
    }
}
