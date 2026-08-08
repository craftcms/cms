<?php

declare(strict_types=1);

namespace CraftCms\Cms\Component\Concerns;

use BackedEnum;
use CraftCms\Cms\Component\Contracts\ConfigurableComponentInterface;
use CraftCms\Cms\Component\Events\DefineSettingsAttributes;
use CraftCms\Cms\Form\Form;
use CraftCms\Cms\Form\FormContext;
use CraftCms\Cms\Support\DateTimeHelper;
use CraftCms\Cms\Support\Str;
use CraftCms\Cms\Support\Utils;
use DateTimeInterface;
use Illuminate\Support\Facades\Event;
use ReflectionProperty;
use RuntimeException;

/** @phpstan-require-implements ConfigurableComponentInterface */
trait ConfigurableComponent
{
    /**
     * @event {@see DefineSettingsAttributes} The event triggered when defining the component’s settings attributes, as returned by {@see settingsAttributes()}.
     */
    public const string EVENT_DEFINE_SETTINGS_ATTRIBUTES = 'defineSettingsAttributes';

    public function settingsForm(FormContext $context = new FormContext): ?Form
    {
        return null;
    }

    public function settingsAttributes(): array
    {
        $attributes = array_keys(Utils::getPublicProperties($this, fn (ReflectionProperty $property) => $property->class === static::class));

        event($event = new DefineSettingsAttributes(
            component: $this instanceof ConfigurableComponentInterface ? $this : throw new RuntimeException(sprintf('%s must implement %s.', static::class, ConfigurableComponentInterface::class)),
            attributes: $attributes,
        ));

        return $event->attributes;
    }

    public static function onDefineSettingsAttributes(callable $callback): void
    {
        $class = static::class;

        Event::listen(function (DefineSettingsAttributes $event) use ($callback, $class): void {
            if ($event->component instanceof $class) {
                $callback($event);
            }
        });
    }

    public function getSettings(): array
    {
        $settings = [];

        foreach ($this->settingsAttributes() as $attribute) {
            try {
                $value = match (true) {
                    property_exists($this, $attribute) => $this->$attribute ?? null,
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
}
