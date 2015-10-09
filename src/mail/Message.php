<?php
/**
 * @link      http://buildwithcraft.com/
 * @copyright Copyright (c) 2015 Pixel & Tonic, Inc.
 * @license   http://buildwithcraft.com/license
 */

namespace craft\app\mail;

use Craft;
use craft\app\elements\User;

/**
 * Represents an email message.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since  3.0
 */
class Message extends \yii\swiftmailer\Message
{
    // Properties
    // =========================================================================

    /**
     * @var string The key of the message that should be loaded
     */
    public $key;

    /**
     * @var array Any variables that should be applied to the template when it is rendered
     */
    public $variables;

    /**
     * @var string The language that the email should be sent in, based on the first [[User]] model passed into [[setTo()]] with a preferred locale
     */
    public $language;

    // Public Methods
    // =========================================================================

    /**
     * @inheritdoc
     *
     * @param string|array|User|User[] $from The sender’s email address, or their user model(s).
     * You may pass an array of addresses if this message is from multiple people.
     * You may also specify sender name in addition to email address using format:
     * `[email => name]`.
     * @return static self reference.
     */
    public function setFrom($from)
    {
        $from = $this->_normalizeEmails($from);
        return parent::setFrom($from);
    }

    /**
     * @inheritdoc
     *
     * @param string|array|User|User[] $to The receiver’s email address, or their user model(s).
     * You may pass an array of addresses if multiple recipients should receive this message.
     * You may also specify receiver name in addition to email address using format:
     * `[email => name]`.
     * @return static self reference.
     */
    public function setTo($to)
    {
        $to = $this->_normalizeEmails($to);
        return parent::setTo($to, true);
    }

    /**
     * @inheritdoc
     *
     * @param string|array|User|User[] $cc The copied receiver’s email address, or their user model(s).
     * You may pass an array of addresses if multiple recipients should receive this message.
     * You may also specify receiver name in addition to email address using format:
     * `[email => name]`.
     * @return static self reference.
     */
    public function setCc($cc)
    {
        $cc = $this->_normalizeEmails($cc);
        return parent::setCc($cc);
    }

    /**
     * @inheritdoc
     *
     * @param string|array|User|User[] $bcc The hidden copied receiver’ email address, or their user model(s).
     * You may pass an array of addresses if multiple recipients should receive this message.
     * You may also specify receiver name in addition to email address using format:
     * `[email => name]`.
     * @return static self reference.
     */
    public function setBcc($bcc)
    {
        $bcc = $this->_normalizeEmails($bcc);
        return parent::setBcc($bcc);
    }

    // Private Methods
    // =========================================================================

    /**
     * @param string|array|User|User[] $emails
     * @param boolean                  $setLanguage
     *
     * @return string|array
     */
    private function _normalizeEmails($emails, $setLanguage = false)
    {
        if (is_array($emails)) {
            foreach ($emails as $key => $email) {
                if (is_numeric($key)) {
                    $emails[$key] = $this->_normalizeEmail($email, $setLanguage);
                }
            }
        } else {
            $emails = $this->_normalizeEmail($emails, $setLanguage);
        }

        return $emails;
    }

    /**
     * @param string|User $email
     * @param boolean     $setLanguage
     *
     * @return string|array
     */
    private function _normalizeEmail($email, $setLanguage)
    {
        if ($email instanceof User) {
            if ($setLanguage && $this->language === null) {
                $this->language = $email->getPreferredLocale();
            }

            return [$email->email => $email->getName()];
        } else {
            return $email;
        }
    }
}
