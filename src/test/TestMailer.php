<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\test;

use Closure;
use craft\mail\Mailer;
use craft\mail\Message;
use yii\mail\MessageInterface;

/**
 * Exactly the same as Codeception\Lib\Connector\Yii2\TestMailer except that we override based on Crafts own mailer class.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @author Global Network Group | Giel Tettelaar <giel@yellowflash.net>
 * @since 3.2.0
 */
class TestMailer extends Mailer
{
    /**
     * @var string
     */
    public $messageClass = Message::class;

    /**
     * @var Closure
     */
    public $callback;

    /**
     * @param $message
     * @return bool
     */
    protected function sendMessage($message): bool
    {
        call_user_func($this->callback, $message);
        return true;
    }

    /**
     * @param MessageInterface $message
     * @return bool
     */
    protected function saveMessage($message): bool
    {
        call_user_func($this->callback, $message);
        return true;
    }
}
