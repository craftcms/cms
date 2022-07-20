<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\mail;

use craft\elements\User;
use craft\helpers\MailerHelper;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;

/**
 * Represents an email message.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0.0
 */
class Message extends \yii\symfonymailer\Message
{
    /**
     * @var string|null The key of the message that should be loaded
     */
    public ?string $key = null;

    /**
     * @var array|null Any variables that should be applied to the template when it is rendered
     */
    public ?array $variables = null;

    /**
     * @var string|null The language that the email should be sent in, based on the first [[User]] model passed into [[setTo()]] with a preferred language
     */
    public ?string $language = null;

    /**
     * @var TransportExceptionInterface|null The caught error object, if the message failed to send
     */
    public ?TransportExceptionInterface $error = null;

    /**
     * Sets the message sender.
     *
     * @param string|array<User|string>|User $from The sender’s email address, or their
     * user model(s). You may pass an array of addresses if this message is from
     * multiple people. You may also specify sender name in addition to email
     * address using format: `[email => name]`.
     * @return self self reference
     */
    public function setFrom($from): self
    {
        parent::setFrom(MailerHelper::normalizeEmails($from));
        return $this;
    }

    /**
     * Sets the Reply-To email.
     *
     * @param string|array<User|string>|User $replyTo The Reply-To email address, or their
     * user model(s). You may pass an array of addresses if this message is from
     * multiple people. You may also specify Reply-To name in addition to email
     * address using format: `[email => name]`.
     * @return self self reference
     * @since 3.4.0
     */
    public function setReplyTo($replyTo): self
    {
        parent::setReplyTo(MailerHelper::normalizeEmails($replyTo));
        return $this;
    }

    /**
     * Sets the message recipient(s).
     *
     * @param string|array<User|string>|User $to The receiver’s email address, or their
     * user model(s). You may pass an array of addresses if multiple recipients
     * should receive this message. You may also specify receiver name in addition
     * to email address using format: `[email => name]`.
     * @return self self reference
     */
    public function setTo($to): self
    {
        if ($to instanceof User) {
            if (!isset($this->language)) {
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
     * @param string|array<User|string>|User $cc The copied receiver’s email address, or their user model(s).
     * You may pass an array of addresses if multiple recipients should receive this message.
     * You may also specify receiver name in addition to email address using format:
     * `[email => name]`.
     * @return self self reference
     */
    public function setCc($cc): self
    {
        parent::setCc(MailerHelper::normalizeEmails($cc));
        return $this;
    }

    /**
     * Sets the BCC (hidden copy receiver) addresses of this message.
     *
     * @param string|array<User|string>|User|null $bcc The hidden copied receiver’s email address, or their user model(s).
     * You may pass an array of addresses if multiple recipients should receive this message.
     * You may also specify receiver name in addition to email address using format:
     * `[email => name]`.
     * @return self self reference
     */
    public function setBcc($bcc): self
    {
        parent::setBcc(MailerHelper::normalizeEmails($bcc));
        return $this;
    }
}
