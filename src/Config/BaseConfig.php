<?php

declare(strict_types=1);

namespace CraftCms\Cms\Config;

use ArrayAccess;
use BadMethodCallException;
use CraftCms\Cms\Support\Facades\Deprecator;
use CraftCms\Cms\Support\Typecast;
use Illuminate\Contracts\Support\Arrayable;
use Override;
use ReflectionProperty;

/**
 * @implements Arrayable<string, mixed>
 * @implements ArrayAccess<string, mixed>
 *
 * @since 6.0.0
 */
abstract class BaseConfig implements Arrayable, ArrayAccess
{
    /**
     * @var array<string, string> Old setting names, mapped to their new names. A new name with dots, such as
     *                            `gql.enabled`, names a setting of a config object that a setting holds.
     */
    protected static array $renamedSettings = [];

    public static function create(): static
    {
        /** @phpstan-ignore new.static */
        return new static;
    }

    /**
     * Returns the old setting names, mapped to their new names.
     *
     * @return array<string, string>
     */
    public static function renamedSettings(): array
    {
        return static::$renamedSettings;
    }

    public function __get(string $name): mixed
    {
        if (! isset(static::$renamedSettings[$name])) {
            return null;
        }

        [$config, $setting] = $this->renamedSettingTarget($name);

        return $config->$setting;
    }

    public function __set(string $name, mixed $value): void
    {
        if (! isset(static::$renamedSettings[$name])) {
            return;
        }

        $this->deprecateRenamedSetting($name);
        [$config, $setting] = $this->renamedSettingTarget($name);

        $values = [$setting => $value];
        Typecast::properties($config::class, $values);

        $config->$setting = $values[$setting];
    }

    public function __isset(string $name): bool
    {
        if (! isset(static::$renamedSettings[$name])) {
            return false;
        }

        [$config, $setting] = $this->renamedSettingTarget($name);

        return isset($config->$setting);
    }

    /**
     * Forwards the fluent methods of renamed settings to the new ones.
     *
     * If GeneralConfig throws an exception, Laravel won't be able to
     * show the exception as the Framework isn't booted when it
     * happens. This delays the exception until that time.
     *
     * @param  list<mixed>  $arguments
     */
    public function __call(string $name, array $arguments): static
    {
        if (isset(static::$renamedSettings[$name])) {
            [$config, $setting] = $this->renamedSettingTarget($name);

            if (method_exists($config, $setting)) {
                $this->deprecateRenamedSetting($name);
                $config->$setting(...$arguments);

                return $this;
            }
        }

        app()->booted(function () use ($name) {
            throw new BadMethodCallException("Method `$name` does not exist on ".static::class);
        });

        return $this;
    }

    #[Override]
    public function offsetGet(mixed $offset): mixed
    {
        return $this->$offset;
    }

    #[Override]
    public function offsetExists(mixed $offset): bool
    {
        return isset($this->$offset);
    }

    #[Override]
    public function offsetSet(mixed $offset, mixed $value): void
    {
        $this->$offset = $value;
    }

    #[Override]
    public function offsetUnset(mixed $offset): void
    {
        unset($this->$offset);
    }

    #[Override]
    public function toArray(): array
    {
        return get_object_vars($this);
    }

    /**
     * Returns the config object and setting name that a renamed setting now maps to.
     *
     * @return array{BaseConfig, string}
     */
    private function renamedSettingTarget(string $name): array
    {
        $path = explode('.', static::$renamedSettings[$name]);
        $setting = array_pop($path);
        $config = $this;

        foreach ($path as $segment) {
            $config = $config->$segment;
        }

        return [$config, $setting];
    }

    private function deprecateRenamedSetting(string $name): void
    {
        $newName = static::$renamedSettings[$name];

        app()->booted(fn () => Deprecator::log(
            sprintf('%s::%s', static::class, $name),
            "The `$name` config setting has been renamed to `$newName`.",
        ));
    }

    /**
     * Restores the state of an object from an array. This
     * is used when the config is cached by Laravel.
     */
    /** @param array<string, mixed> $stateData */
    public static function __set_state(array $stateData): static
    {
        $object = static::create();

        foreach ($stateData as $prop => $state) {
            if (! property_exists($object, $prop)) {
                continue;
            }

            /**
             * We use reflection because some properties are private.
             */
            $reflection = new ReflectionProperty($object, $prop);
            $reflection->setValue($object, $state);
        }

        return $object;
    }
}
