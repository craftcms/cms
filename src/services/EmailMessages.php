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
use yii\base\Exception;

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
     * @param integer|null $siteId
     *
     * @return array
     * @throws Exception if $siteId is invalid
     */
    public function getAllMessages($siteId = null)
    {
        if ($siteId) {
            $site = Craft::$app->getSites()->getSiteById($siteId);

            if (!$site) {
                throw new Exception('Invalid site ID: '.$siteId);
            }
        } else {
            $site = Craft::$app->getSites()->currentSite;
        }

        // Find any custom messages
        $records = EmailMessageRecord::findAll([
            'siteId' => $site->id
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
            $message->siteId = $siteId;

            // Is there a custom message?
            if (isset($recordsByKey[$key])) {
                $message->subject = $recordsByKey[$key]->subject;
                $message->body = $recordsByKey[$key]->body;
            } else {
                // Default to whatever's in the translation file
                $message->subject = $this->_translateMessageString($key, 'subject', $site->language);
                $message->body = $this->_translateMessageString($key, 'body', $site->language);
            }

            // Not possible to customize the heading
            $message->heading = $this->_translateMessageString($key, 'heading', $site->language);

            $messages[] = $message;
        }

        return $messages;
    }

    /**
     * Returns a system email message by its key.
     *
     * @param string       $key
     * @param integer|null $siteId
     *
     * @return RebrandEmail
     */
    public function getMessage($key, $siteId = null)
    {
        if (!$siteId) {
            $siteId = Craft::$app->getSites()->currentSite->id;
        }

        $message = new RebrandEmail();
        $message->key = $key;
        $message->siteId = $siteId;

        $record = $this->_getMessageRecord($key, $siteId);

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
        $record = $this->_getMessageRecord($message->key, $message->siteId);

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
     * @param string       $key
     * @param integer|null $siteId
     *
     * @return EmailMessageRecord
     * @throws Exception if $siteId is invalid
     */
    private function _getMessageRecord($key, $siteId = null)
    {
        if ($siteId) {
            $site = Craft::$app->getSites()->getSiteById($siteId);

            if (!$site) {
                throw new Exception('Invalid site ID: '.$siteId);
            }
        } else {
            $site = Craft::$app->getSites()->currentSite;
        }

        $record = EmailMessageRecord::findOne([
            'key' => $key,
            'siteId' => $site->id,
        ]);

        if (!$record) {
            $record = new EmailMessageRecord();
            $record->key = $key;
            $record->siteId = $site->id;
            $record->subject = $this->_translateMessageString($key, 'subject', $site->language);
            $record->body = $this->_translateMessageString($key, 'body', $site->language);
        }

        return $record;
    }
}
