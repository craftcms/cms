<?php

declare(strict_types=1);

namespace CraftCms\Yii2Adapter\Cp;

use craft\events\RegisterCpSettingsEvent;
use craft\web\twig\variables\Cp;
use CraftCms\Cms\Cp\Settings;
use Override;
use yii\base\Event;

/** @internal */
class LegacySettings extends Settings
{
    #[Override]
    public function apply(array $settings, bool $readOnly): array
    {
        $settings = parent::apply($settings, $readOnly);
        $eventName = $readOnly
            ? Cp::EVENT_REGISTER_READ_ONLY_CP_SETTINGS
            : Cp::EVENT_REGISTER_CP_SETTINGS;

        if (!Event::hasHandlers(Cp::class, $eventName)) {
            return $settings;
        }

        $event = new RegisterCpSettingsEvent(['settings' => $settings]);
        Event::trigger(Cp::class, $eventName, $event);

        return $event->settings;
    }
}
