<?php
/**
 * @link      https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license   https://craftcms.com/license
 */

namespace craft\services;

use Craft;
use craft\events\RegisterEmailMessagesEvent;
use craft\helpers\ArrayHelper;
use craft\models\RebrandEmail;
use craft\records\EmailMessage as EmailMessageRecord;
use yii\base\Component;

Craft::$app->requireEdition(Craft::Client);

/**
 * Class EmailMessages service.
 *
 * An instance of the EmailMessages service is globally accessible in Craft via [[Application::emailMessages `Craft::$app->getEmailMessages()`]].
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since  3.0
 */
class EmailMessages extends Component
{
    // Constants
    // =========================================================================

    /**
     * @event RegisterEmailMessagesEvent The event that is triggered when registering email messages.
     */
    const EVENT_REGISTER_MESSAGES = 'registerMessages';

    // Properties
    // =========================================================================

    /**
     * @var
     */
    private $_messagesInfo;

    // Public Methods
    // =========================================================================

    /**
     * Returns all of the system email messages.
     *
     * @param string|null $language
     *
     * @return RebrandEmail[]
     */
    public function getAllMessages($language = null)
    {
        if ($language === null) {
            $language = Craft::$app->language;
        }

        // Find any custom messages
        $records = EmailMessageRecord::findAll([
            'language' => $language
        ]);

        // Index the records by their keys
        $recordsByKey = [];
        foreach ($records as $record) {
            $recordsByKey[$record->key] = $record;
        }

        // Now assemble the whole list of messages
        $messages = [];

        foreach ($this->_getAllMessageKeys() as $key) {
            $message = new RebrandEmail();
            $message->key = $key;
            $message->language = $language;

            // Is there a custom message?
            if (isset($recordsByKey[$key])) {
                $message->subject = $recordsByKey[$key]->subject;
                $message->body = $recordsByKey[$key]->body;
            } else {
                // Default to whatever's in the translation file
                $message->subject = $this->_translateMessageString($key, 'subject', $language);
                $message->body = $this->_translateMessageString($key, 'body', $language);
            }

            // Not possible to customize the heading
            $message->heading = $this->_translateMessageString($key, 'heading', $language);

            $messages[] = $message;
        }

        return $messages;
    }

    /**
     * Returns a system email message by its key.
     *
     * @param string $key
     * @param string $language
     *
     * @return RebrandEmail
     */
    public function getMessage($key, $language = null)
    {
        if ($language === null) {
            $language = Craft::$app->language;
        }

        $message = new RebrandEmail();
        $message->key = $key;
        $message->language = $language;

        $record = $this->_getMessageRecord($key, $language);

        $message->subject = $record->subject;
        $message->body = $record->body;

        return $message;
    }

    /**
     * Saves the localized content for a system email message.
     *
     * @param RebrandEmail $message
     *
     * @return boolean
     */
    public function saveMessage(RebrandEmail $message)
    {
        $record = $this->_getMessageRecord($message->key, $message->language);

        $record->subject = $message->subject;
        $record->body = $message->body;

        if ($record->save()) {
            return true;
        }

        $message->addErrors($record->getErrors());

        return false;
    }

    // Private Methods
    // =========================================================================

    /**
     * Returns all email message keys.
     *
     * @return array
     */
    private function _getAllMessageKeys()
    {
        $this->_setAllMessageInfo();

        return array_keys($this->_messagesInfo);
    }

    /**
     * Returns info about a message by its key.
     *
     * @param string $key
     *
     * @return array|null
     */
    private function _getMessageInfoByKey($key)
    {
        $this->_setAllMessageInfo();

        if (isset($this->_messagesInfo[$key])) {
            return $this->_messagesInfo[$key];
        }

        return null;
    }

    /**
     * Sets all of the email message keys and source languages.
     *
     * @return void
     */
    private function _setAllMessageInfo()
    {
        if (!isset($this->_messagesInfo)) {
            $messages = [
                [
                    'key' => 'account_activation',
                    'category' => 'app',
                    'sourceLanguage' => Craft::$app->sourceLanguage
                ],
                [
                    'key' => 'verify_new_email',
                    'category' => 'app',
                    'sourceLanguage' => Craft::$app->sourceLanguage
                ],
                [
                    'key' => 'forgot_password',
                    'category' => 'app',
                    'sourceLanguage' => Craft::$app->sourceLanguage
                ],
                [
                    'key' => 'test_email',
                    'category' => 'app',
                    'sourceLanguage' => Craft::$app->sourceLanguage
                ],
            ];

            // Give plugins a chance to add additional messages
            $event = new RegisterEmailMessagesEvent([
                'messages' => $messages
            ]);
            $this->trigger(self::EVENT_REGISTER_MESSAGES, $event);

            $this->_messagesInfo = ArrayHelper::index($event->messages, 'key');
        }
    }

    /**
     * Translates an email message string.
     *
     * @param string $key
     * @param string $part
     * @param string $language
     *
     * @return null|string
     */
    private function _translateMessageString($key, $part, $language)
    {
        $messageInfo = $this->_getMessageInfoByKey($key);

        if (!$messageInfo) {
            return null;
        }

        $combinedKey = $key.'_'.$part;
        $t = Craft::t($messageInfo['category'], $combinedKey, null, $language);

        // If a translation couldn't be found, default to the message's source language
        if ($t == $combinedKey) {
            $t = Craft::t($messageInfo['category'], $combinedKey, null, $messageInfo['sourceLanguage']);
        }

        return $t;
    }

    /**
     * Gets a message record by its key.
     *
     * @param string $key
     * @param string $language
     *
     * @return EmailMessageRecord
     */
    private function _getMessageRecord($key, $language = null)
    {
        if ($language === null) {
            $language = Craft::$app->language;
        }

        $record = EmailMessageRecord::findOne([
            'key' => $key,
            'language' => $language,
        ]);

        if (!$record) {
            $record = new EmailMessageRecord();
            $record->key = $key;
            $record->language = $language;
            $record->subject = $this->_translateMessageString($key, 'subject', $language);
            $record->body = $this->_translateMessageString($key, 'body', $language);
        }

        return $record;
    }
}
