<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\test;

use craft\elements\User;
use craft\mail\Mailer;
use craft\mail\Message;

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
     * @var callable
     */
    public $callback;

    public User|string|array|null $from = 'test@test.craft';

    /**
     * @inheritdoc
     */
    protected function sendMessage($message): bool
    {
        call_user_func($this->callback, $message);
        return true;
    }

    /**
     * @inheritdoc
     */
    protected function saveMessage($message): bool
    {
        call_user_func($this->callback, $message);
        return true;
    }
}
