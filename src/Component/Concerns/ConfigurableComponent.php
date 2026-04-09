<?php

declare(strict_types=1);

namespace CraftCms\Cms\Component\Concerns;

use BackedEnum;
use CraftCms\Cms\Component\Contracts\ConfigurableComponentInterface;
use CraftCms\Cms\Component\Events\DefineSettingsAttributes;
use CraftCms\Cms\Support\DateTimeHelper;
use CraftCms\Cms\Support\Html;
use CraftCms\Cms\Support\Str;
use CraftCms\Cms\Support\Utils;
use DateTimeInterface;
use Illuminate\Support\Facades\Event;
use ReflectionProperty;
use RuntimeException;

/** @phpstan-require-implements ConfigurableComponentInterface */
trait ConfigurableComponent
{
    use HasComponentEvents;

    /**
     * @event {@see DefineSettingsAttributes} The event triggered when defining the component’s settings attributes, as returned by {@see settingsAttributes()}.
     */
    public const string EVENT_DEFINE_SETTINGS_ATTRIBUTES = 'defineSettingsAttributes';

    public function settingsAttributes(): array
    {
        $attributes = array_keys(Utils::getPublicProperties($this, fn (ReflectionProperty $property) => $property->class === static::class));

        $this->dispatchComponentEvent(self::EVENT_DEFINE_SETTINGS_ATTRIBUTES, $event = new DefineSettingsAttributes(
            component: $this instanceof ConfigurableComponentInterface ? $this : throw new RuntimeException(sprintf('%s must implement %s.', static::class, ConfigurableComponentInterface::class)),
            attributes: $attributes,
        ));

        return $event->attributes;
    }

    public static function onDefineSettingsAttributes(callable $callback): void
    {
        static::listen(self::EVENT_DEFINE_SETTINGS_ATTRIBUTES, $callback);
    }

    public function getSettings(): array
    {
        $settings = [];

        foreach ($this->settingsAttributes() as $attribute) {
            try {
                $value = match (true) {
                    property_exists($this, $attribute) => $this->$attribute,
                    /** @phpstan-ignore-next-line https://github.com/phpstan/phpstan/issues/13981 */
                    method_exists($this, $method = 'get'.Str::studly($attribute)) => $this->$method(),
                    default => throw new RuntimeException,
                };
            } catch (RuntimeException) {
                continue;
            }

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
