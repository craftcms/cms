<?php

namespace CraftCms\Cms\Component\Concerns;

use BackedEnum;
use craft\helpers\DateTimeHelper;
use craft\helpers\Html;
use CraftCms\Cms\Component\Events\DefineSettingsAttributes;
use CraftCms\Cms\Support\Utils;
use DateTimeInterface;
use Illuminate\Support\Facades\Event;
use ReflectionProperty;

/**
 * @since 6.0.0
 */
trait ConfigurableComponent
{
    use HasComponentEvents;

    /**
     * @event {@see DefineSettingsAttributes} The event triggered when defining the component’s settings attributes, as returned by {@see settingsAttributes()}.
     */
    public const string EVENT_DEFINE_SETTINGS_ATTRIBUTES = 'defineSettingsAttributes';

    public function settingsAttributes(): array
    {
        $attributes = Utils::getPublicProperties($this, fn (ReflectionProperty $property) => $property->class === static::class);

        if (Event::hasListeners(self::componentEventName(self::EVENT_DEFINE_SETTINGS_ATTRIBUTES))) {
            Event::dispatch(self::componentEventName(self::EVENT_DEFINE_SETTINGS_ATTRIBUTES), $event = new DefineSettingsAttributes(
                component: $this,
                attributes: $attributes,
            ));

            return $event->attributes;
        }

        return $attributes;
    }

    public static function onDefineSettingsAttributes(callable $callback): void
    {
        static::registerModelEvent(self::EVENT_DEFINE_SETTINGS_ATTRIBUTES, $callback);
    }

    public function getSettings(): array
    {
        $settings = [];

        foreach ($this->settingsAttributes() as $attribute => $value) {
            $value = match (true) {
                $value instanceof DateTimeInterface => DateTimeHelper::toIso8601($value) ?: null,
                $value instanceof BackedEnum => $value->value,
                default => $value,
            };

            $settings[$attribute] = $value;
        }

        return $settings;
    }

    public function getSettingsHtml(): ?string
    {
        return null;
    }

    public function getReadOnlySettingsHtml(): ?string
    {
        // Just return the settings HTML with disabled inputs by default
        return Html::disableInputs(fn () => $this->getSettingsHtml());
    }
}
