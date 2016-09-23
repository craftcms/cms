<?php
/**
 * @link      https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license   https://craftcms.com/license
 */

namespace craft\app\services;

use Craft;
use craft\app\base\Plugin;
use craft\app\models\RebrandEmail;
use craft\app\records\EmailMessage as EmailMessageRecord;
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
     * @param string|null $localeId
     *
     * @return array
     */
    public function getAllMessages($localeId = null)
    {
        // Find any custom messages
        if (!$localeId) {
            $localeId = Craft::$app->language;
        }

        $records = EmailMessageRecord::findAll([
            'locale' => $localeId
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
            $message->locale = $localeId;

            // Is there a custom message?
            if (isset($recordsByKey[$key])) {
                $message->subject = $recordsByKey[$key]->subject;
                $message->body = $recordsByKey[$key]->body;
            } else {
                // Default to whatever's in the translation file
                $message->subject = $this->_translateMessageString($key, 'subject', $localeId);
                $message->body = $this->_translateMessageString($key, 'body', $localeId);
            }

            // Not possible to customize the heading
            $message->heading = $this->_translateMessageString($key, 'heading', $localeId);

            $messages[] = $message;
        }

        return $messages;
    }

    /**
     * Returns a system email message by its key.
     *
     * @param string      $key
     * @param string|null $localeId
     *
     * @return RebrandEmail
     */
    public function getMessage($key, $localeId = null)
    {
        if (!$localeId) {
            $localeId = Craft::$app->language;
        }

        $message = new RebrandEmail();
        $message->key = $key;
        $message->locale = $localeId;

        $record = $this->_getMessageRecord($key, $localeId);

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
        $record = $this->_getMessageRecord($message->key, $message->locale);

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
     * Returns the source locale for a message by its key.
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
     * Sets all of the email message keys and source locales.
     *
     * @return void
     */
    private function _setAllMessageInfo()
    {
        if (!isset($this->_messagesInfo)) {
            $craftMessageInfo = [
                'category' => 'app',
                'sourceLanguage' => Craft::$app->sourceLanguage
            ];

            $this->_messagesInfo = [
                'account_activation' => $craftMessageInfo,
                'verify_new_email' => $craftMessageInfo,
                'forgot_password' => $craftMessageInfo,
                'test_email' => $craftMessageInfo,
            ];

            // Give plugins a chance to add additional messages
            foreach (Craft::$app->getPlugins()->call('registerEmailMessages') as $pluginHandle => $pluginKeys) {
                /** @var Plugin $plugin */
                $plugin = Craft::$app->getPlugins()->getPlugin($pluginHandle);

                foreach ($pluginKeys as $key) {
                    $this->_messagesInfo[$key] = [
                        'category' => $pluginHandle,
                        'sourceLanguage' => $plugin->sourceLanguage
                    ];
                }
            }
        }
    }

    /**
     * Translates an email message string.
     *
     * @param string $key
     * @param string $part
     * @param string $localeId
     *
     * @return null|string
     */
    private function _translateMessageString($key, $part, $localeId)
    {
        $messageInfo = $this->_getMessageInfoByKey($key);

        if (!$messageInfo) {
            return null;
        }

        $combinedKey = $key.'_'.$part;
        $t = Craft::t($messageInfo['category'], $combinedKey, null, $localeId);

        // If a translation couldn't be found, default to the message's source locale
        if ($t == $combinedKey) {
            $t = Craft::t($messageInfo['category'], $combinedKey, null, $messageInfo['sourceLanguage']);
        }

        return $t;
    }

    /**
     * Gets a message record by its key.
     *
     * @param string      $key
     * @param string|null $localeId
     *
     * @return EmailMessageRecord
     */
    private function _getMessageRecord($key, $localeId = null)
    {
        if (!$localeId) {
            $localeId = Craft::$app->language;
        }

        $record = EmailMessageRecord::findOne([
            'key' => $key,
            'locale' => $localeId,
        ]);

        if (!$record) {
            $record = new EmailMessageRecord();
            $record->key = $key;
            $record->locale = $localeId;
            $record->subject = $this->_translateMessageString($key, 'subject', $localeId);
            $record->body = $this->_translateMessageString($key, 'body', $localeId);
        }

        return $record;
    }
}
