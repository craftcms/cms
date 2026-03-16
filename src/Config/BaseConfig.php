<?php

declare(strict_types=1);

namespace CraftCms\Cms\Config;

use ArrayAccess;
use BadMethodCallException;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Support\Facades\Log;
use Override;
use ReflectionProperty;

abstract class BaseConfig implements Arrayable, ArrayAccess
{
    protected static array $renamedSettings = [];

    public static function create(): static
    {
        /** @phpstan-ignore new.static */
        return new static;
    }

    public function __get(string $name)
    {
        if (isset(static::$renamedSettings[$name])) {
            return $this->{static::$renamedSettings[$name]};
        }

        return null;
    }

    public function __set($name, $value)
    {
        if (! isset(static::$renamedSettings[$name])) {
            return;
        }
        $newName = static::$renamedSettings[$name];

        Log::debug("`$name` has been renamed to `$newName`.", [sprintf('%s::%s', static::class, $name)]);

        $this->$newName = $value;
    }

    public function __isset($name)
    {
        if (isset(static::$renamedSettings[$name])) {
            return isset($this->{static::$renamedSettings[$name]});
        }

        return false;
    }

    /**
     * If GeneralConfig throws an exception, Laravel won't be able to
     * show the exception as the Framework isn't booted when it
     * happens. This delays the exception until that time.
     */
    public function __call(string $name, array $arguments)
    {
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
     * Restores the state of an object from an array. This
     * is used when the config is cached by Laravel.
     */
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
