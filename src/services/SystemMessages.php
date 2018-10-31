<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\services;

use Craft;
use craft\db\Query;
use craft\events\ConfigEvent;
use craft\events\RegisterEmailMessagesEvent;
use craft\helpers\ArrayHelper;
use craft\models\SystemMessage;
use craft\records\SystemMessage as EmailMessageRecord;
use yii\base\Component;

/**
 * System Messages service.
 * An instance of the System Messages service is globally accessible in Craft via [[\craft\base\ApplicationTrait::getSystemMessages()|`Craft::$app->systemMessages`]].
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0
 */
class SystemMessages extends Component
{
    // Constants
    // =========================================================================

    /**
     * @event RegisterEmailMessagesEvent The event that is triggered when registering email messages.
     */
    const EVENT_REGISTER_MESSAGES = 'registerMessages';

    const CONFIG_MESSAGE_KEY = 'messages';

    // Properties
    // =========================================================================

    /**
     * @var SystemMessage[]|null
     */
    private $_defaultMessages;

    // Public Methods
    // =========================================================================

    /**
     * Returns all of the default system email messages, without subject/body overrides.
     *
     * @return SystemMessage[]
     */
    public function getAllDefaultMessages(): array
    {
        if ($this->_defaultMessages !== null) {
            return $this->_defaultMessages;
        }

        $messages = [
            [
                'key' => 'account_activation',
                'heading' => Craft::t('app', 'account_activation_heading'),
                'subject' => Craft::t('app', 'account_activation_subject'),
                'body' => Craft::t('app', 'account_activation_body'),
            ],
            [
                'key' => 'verify_new_email',
                'heading' => Craft::t('app', 'verify_new_email_heading'),
                'subject' => Craft::t('app', 'verify_new_email_subject'),
                'body' => Craft::t('app', 'verify_new_email_body'),
            ],
            [
                'key' => 'forgot_password',
                'heading' => Craft::t('app', 'forgot_password_heading'),
                'subject' => Craft::t('app', 'forgot_password_subject'),
                'body' => Craft::t('app', 'forgot_password_body'),
            ],
            [
                'key' => 'test_email',
                'heading' => Craft::t('app', 'test_email_heading'),
                'subject' => Craft::t('app', 'test_email_subject'),
                'body' => Craft::t('app', 'test_email_body'),
            ],
        ];

        // Give plugins a chance to add additional messages
        $event = new RegisterEmailMessagesEvent([
            'messages' => $messages
        ]);
        $this->trigger(self::EVENT_REGISTER_MESSAGES, $event);

        // Sort them all by key
        $messages = ArrayHelper::index($event->messages, 'key');

        // Make sure they're SystemMessage objects
        foreach ($messages as $key => $message) {
            if (is_array($message)) {
                $messages[$key] = new SystemMessage($message);
            }
        }

        return $this->_defaultMessages = $messages;
    }

    /**
     * Returns a default system email messages by its key, without subject/body overrides.
     *
     * @param string $key
     * @return SystemMessage|null
     */
    public function getDefaultMessage(string $key)
    {
        return $this->getAllDefaultMessages()[$key] ?? null;
    }

    /**
     * Returns all of the system email messages in a given language, with subject/body overrides.
     *
     * @param string|null $language
     * @return SystemMessage[]
     */
    public function getAllMessages(string $language = null): array
    {
        if ($language === null) {
            $language = Craft::$app->getSites()->getPrimarySite()->language;
        }

        // Start with the defaults
        $defaults = $this->getAllDefaultMessages();

        $allMessages = Craft::$app->getProjectConfig()->get(self::CONFIG_MESSAGE_KEY) ?? [];

        $overrides = [];

        foreach ($allMessages as $key => $translations) {
            if (isset($translations[$language])) {
                $overrides[$key] = $translations[$language];
            }
        }

        // Combine them to create the final messages array
        $messages = [];

        foreach ($defaults as $key => $default) {
            $message = clone $default;

            // Has it been overridden?
            if (isset($overrides[$key])) {
                $message->subject = $overrides[$key]['subject'];
                $message->body = $overrides[$key]['body'];
            }

            $messages[] = $message;
        }

        return $messages;
    }

    /**
     * Returns a system email messages in a given language by its key, with subject/body overrides.
     *
     * @param string $key
     * @param string|null $language
     * @return SystemMessage|null
     */
    public function getMessage(string $key, string $language = null)
    {
        // Get the default message (and ensure $key is valid)
        if (($default = $this->getDefaultMessage($key)) === null) {
            return null;
        }

        if ($language === null) {
            $language = Craft::$app->getSites()->getPrimarySite()->language;
        }

        // Fetch the customization (if there is one)
        $override = Craft::$app->getProjectConfig()->get(self::CONFIG_MESSAGE_KEY . '.' . $key . '.' . $language);

        // Combine them to create the final message
        $message = clone $default;

        if ($override) {
            $message->subject = $override['subject'];
            $message->body = $override['body'];
        }

        return $message;
    }

    /**
     * Saves the subject/body overrides for a system email message.
     *
     * @param SystemMessage $message
     * @param string|null $language
     * @return bool
     */
    public function saveMessage(SystemMessage $message, string $language = null): bool
    {
        $configData = [
            'subject' => $message->subject,
            'body' => $message->body
        ];

        Craft::$app->getProjectConfig()->set(self::CONFIG_MESSAGE_KEY . '.' . $message->key . '.' . $language, $configData);

        return true;
    }
}
