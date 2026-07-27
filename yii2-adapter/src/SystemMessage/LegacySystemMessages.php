<?php

declare(strict_types=1);

namespace CraftCms\Yii2Adapter\SystemMessage;

use Craft;
use craft\events\RegisterEmailMessagesEvent;
use craft\services\SystemMessages as LegacySystemMessagesService;
use CraftCms\Cms\SystemMessage\Models\SystemMessage;
use CraftCms\Cms\SystemMessage\SystemMessages;
use Illuminate\Support\Collection;
use Override;

/** @internal */
class LegacySystemMessages extends SystemMessages
{
    #[Override]
    public function messages(): Collection
    {
        $messages = parent::messages();
        $service = Craft::$app->getSystemMessages();

        if (!$service->hasEventHandlers(LegacySystemMessagesService::EVENT_REGISTER_MESSAGES)) {
            return $messages;
        }

        $event = new RegisterEmailMessagesEvent([
            'messages' => $messages->map(fn(SystemMessage $message) => $message->toArray())->all(),
        ]);
        $service->trigger(LegacySystemMessagesService::EVENT_REGISTER_MESSAGES, $event);

        return collect($event->messages)
            ->map(fn(SystemMessage|array $message) => is_array($message) ? new SystemMessage($message) : $message)
            ->keyBy('key')
            ->sortKeys();
    }
}
