<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\mail;

use craft\elements\User;
use craft\helpers\MailerHelper;

/**
 * Represents an email message.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0.0
 */
class Message extends \yii\swiftmailer\Message
{
    /**
     * @var string|null The key of the message that should be loaded
     */
    public $key;

    /**
     * @var array|null Any variables that should be applied to the template when it is rendered
     */
    public $variables;

    /**
     * @var string|null The language that the email should be sent in, based on the first [[User]] model passed into [[setTo()]] with a preferred language
     */
    public $language;

    /**
     * Sets the message sender.
     *
     * @param string|array|User|User[] $from The sender’s email address, or their
     * user model(s). You may pass an array of addresses if this message is from
     * multiple people. You may also specify sender name in addition to email
     * address using format: `[email => name]`.
     * @return static self reference
     */
    public function setFrom($from)
    {
        parent::setFrom(MailerHelper::normalizeEmails($from));
        return $this;
    }

    /**
     * Sets the Reply-To email.
     *
     * @param string|array|User|User[] $replyTo The Reply-To email address, or their
     * user model(s). You may pass an array of addresses if this message is from
     * multiple people. You may also specify Reply-To name in addition to email
     * address using format: `[email => name]`.
     * @return static self reference
     * @since 3.4.0
     */
    public function setReplyTo($replyTo)
    {
        parent::setReplyTo(MailerHelper::normalizeEmails($replyTo));
        return $this;
    }

    /**
     * Sets the message recipient(s).
     *
     * @param string|array|User|User[] $to The receiver’s email address, or their
     * user model(s). You may pass an array of addresses if multiple recipients
     * should receive this message. You may also specify receiver name in addition
     * to email address using format: `[email => name]`.
     * @return static self reference
     */
    public function setTo($to)
    {
        if ($to instanceof User) {
            if ($this->language === null) {
                $this->language = $to->getPreferredLanguage();
            }

            $this->variables['user'] = $to;
        }

        parent::setTo(MailerHelper::normalizeEmails($to));
        return $this;
    }

    /**
     * Sets the CC (additional copy receiver) addresses of this message.
     *
     * @param string|array|User|User[] $cc The copied receiver’s email address, or their user model(s).
     * You may pass an array of addresses if multiple recipients should receive this message.
     * You may also specify receiver name in addition to email address using format:
     * `[email => name]`.
     * @return static self reference
     */
    public function setCc($cc)
    {
        parent::setCc(MailerHelper::normalizeEmails($cc));
        return $this;
    }

    /**
     * Sets the BCC (hidden copy receiver) addresses of this message.
     *
     * @param string|array|User|User[] $bcc The hidden copied receiver’s email address, or their user model(s).
     * You may pass an array of addresses if multiple recipients should receive this message.
     * You may also specify receiver name in addition to email address using format:
     * `[email => name]`.
     * @return static self reference
     */
    public function setBcc($bcc)
    {
        parent::setBcc(MailerHelper::normalizeEmails($bcc));
        return $this;
    }
}
