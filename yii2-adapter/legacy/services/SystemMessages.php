<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\services;

use craft\events\RegisterEmailMessagesEvent;
use craft\models\SystemMessage;
use CraftCms\Cms\SystemMessage\Events\RegisterSystemMessages;
use Illuminate\Support\Facades\Event;
use yii\base\Component;

/**
 * System Messages service.
 *
 * An instance of the service is available via [[\craft\base\ApplicationTrait::getSystemMessages()|`Craft::$app->getSystemMessages()`]].
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0.0
 * @deprecated 6.0.0 use {@see \CraftCms\Cms\SystemMessage\SystemMessages} instead.
 */
class SystemMessages extends Component
{
    /**
     * @event RegisterEmailMessagesEvent The event that is triggered when registering system messages.
     *
     * ```php
     * use craft\base\Event;
     * use craft\events\RegisterEmailMessagesEvent;
     * use craft\services\SystemMessages;
     *
     * Event::on(
     *     SystemMessages::class,
     *     SystemMessages::EVENT_REGISTER_MESSAGES,
     *     function(RegisterEmailMessagesEvent $event) {
     *         $event->messages[] = [
     *             'key' => 'account_approved',
     *             'heading' => 'When a member’s account is approved',
     *             'subject' => 'Your account is approved!',
     *             'body' => "Hey {{user.friendlyName|e}},\n\nYour account with {{systemName}} has been approved by {{approver}}!",
     *         ];
     *     },
     * );
     * ```
     *
     * Once a system message is registered, it will be editable from the System Messages utility.
     *
     * System messages can be sent via [[\craft\mail\Mailer::composeFromKey()]]:
     *
     * ```php
     * Craft::$app->getMailer()
     *    ->composeFromKey('account_approved', [
     *        'approver' => $approver->friendlyName,
     *    ])
     *    ->setTo($user)
     *    ->send();
     * ```
     */
    public const EVENT_REGISTER_MESSAGES = 'registerMessages';

    /**
     * Returns all of the default system email messages, without subject/body overrides.
     *
     * @return SystemMessage[]
     */
    public function getAllDefaultMessages(): array
    {
        return app(\CraftCms\Cms\SystemMessage\SystemMessages::class)->getAllDefaultMessages()->map(function(\CraftCms\Cms\SystemMessage\Models\SystemMessage $message) {
            return new SystemMessage($message->toArray());
        })->all();
    }

    /**
     * Returns a default system email messages by its key, without subject/body overrides.
     *
     * @param string $key
     *
     * @return SystemMessage|null
     */
    public function getDefaultMessage(string $key): ?SystemMessage
    {
        $message = app(\CraftCms\Cms\SystemMessage\SystemMessages::class)->getDefaultMessage($key);

        if (!$message) {
            return null;
        }

        return new SystemMessage($message->toArray());
    }

    /**
     * Returns all of the system email messages in a given language, with subject/body overrides.
     *
     * @param string|null $language
     *
     * @return SystemMessage[]
     */
    public function getAllMessages(?string $language = null): array
    {
        return app(\CraftCms\Cms\SystemMessage\SystemMessages::class)->getAllMessages($language)->map(function(\CraftCms\Cms\SystemMessage\Models\SystemMessage $message) {
            return new SystemMessage($message->toArray());
        })->all();
    }

    /**
     * Returns a system email messages in a given language by its key, with subject/body overrides.
     *
     * @param string $key
     * @param string|null $language
     *
     * @return SystemMessage|null
     */
    public function getMessage(string $key, ?string $language = null): ?SystemMessage
    {
        $message = app(\CraftCms\Cms\SystemMessage\SystemMessages::class)->getMessage($key, $language);

        if (!$message) {
            return null;
        }

        return new SystemMessage($message->toArray());
    }

    /**
     * Saves the subject/body overrides for a system email message.
     *
     * @param SystemMessage $message
     * @param string|null $language
     *
     * @return bool
     */
    public function saveMessage(SystemMessage $message, ?string $language = null): bool
    {
        $message = new \CraftCms\Cms\SystemMessage\Models\SystemMessage($message->toArray());

        app(\CraftCms\Cms\SystemMessage\SystemMessages::class)->saveMessage($message, $language);

        return true;
    }

    public static function registerEvents(): void
    {
        Event::listen(RegisterSystemMessages::class, function(RegisterSystemMessages $event) {
            $messages = $event->messages->map(function(\CraftCms\Cms\SystemMessage\Models\SystemMessage $message) {
                return $message->toArray();
            })->all();

            $yiiEvent = new RegisterEmailMessagesEvent(['messages' => $messages]);

            app('Craft')->getSystemMessages()->trigger(self::EVENT_REGISTER_MESSAGES, $yiiEvent);

            $event->messages = collect($yiiEvent->messages)->map(function($message) {
                return match (true) {
                    is_array($message) => new \CraftCms\Cms\SystemMessage\Models\SystemMessage($message),
                    $message instanceof SystemMessage => new \CraftCms\Cms\SystemMessage\Models\SystemMessage($message->toArray()),
                    default => new \CraftCms\Cms\SystemMessage\Models\SystemMessage((array) $message),
                };
            });
        });
    }
}
