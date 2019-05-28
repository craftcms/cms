<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\mail;

use craft\elements\User;

/**
 * Represents an email message.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0
 */
class Message extends \yii\swiftmailer\Message
{
    // Properties
    // =========================================================================

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

    // Public Methods
    // =========================================================================

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
        $from = $this->_normalizeEmails($from);
        parent::setFrom($from);

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

        $to = $this->_normalizeEmails($to);
        parent::setTo($to);

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
        $cc = $this->_normalizeEmails($cc);
        parent::setCc($cc);

        return $this;
    }

    /**
     * Sets the BCC (hidden copy receiver) addresses of this message.
     *
     * @param string|array|User|User[] $bcc The hidden copied receiver’ email address, or their user model(s).
     * You may pass an array of addresses if multiple recipients should receive this message.
     * You may also specify receiver name in addition to email address using format:
     * `[email => name]`.
     * @return static self reference
     */
    public function setBcc($bcc)
    {
        $bcc = $this->_normalizeEmails($bcc);
        parent::setBcc($bcc);

        return $this;
    }

    // Private Methods
    // =========================================================================

    /**
     * @param string|array|User|User[]|null $emails
     * @return string|array
     */
    private function _normalizeEmails($emails)
    {
        if (empty($emails)) {
            return null;
        }

        if (!is_array($emails)) {
            $emails = [$emails];
        }

        $normalized = [];

        foreach ($emails as $key => $value) {
            if ($value instanceof User) {
                if (($name = $value->getFullName()) !== null) {
                    $normalized[$value->email] = $name;
                } else {
                    $normalized[] = $value->email;
                }
            } else if (is_numeric($key)) {
                $normalized[] = $value;
            } else {
                $normalized[$key] = $value;
            }
        }

        return $normalized;
    }
}
