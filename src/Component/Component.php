<?php

declare(strict_types=1);

namespace CraftCms\Cms\Component;

use CraftCms\Cms\Component\Contracts\ComponentInterface;
use CraftCms\Cms\Component\Exceptions\InvalidCallException;
use CraftCms\Cms\Component\Exceptions\UnknownPropertyException;
use CraftCms\Cms\Support\Typecast;
use CraftCms\Cms\Validation\Concerns\Validates;
use CraftCms\Cms\Validation\Contracts\Validatable;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Support\Traits\Macroable;
use Yiisoft\Arrays\ArrayableInterface;
use Yiisoft\Arrays\ArrayableTrait;

abstract class Component implements Arrayable, ArrayableInterface, ComponentInterface, Validatable
{
    use ArrayableTrait;
    use Validates;

    public function __construct(array|object $config = [])
    {
        if (is_object($config)) {
            $config = (array) $config;
        }

        self::configure($this, $config);
    }

    /**
     * Configures a component with the initial property values.
     *
     * @param  self  $component  the component to be configured
     * @param  array  $properties  the property initial values given in terms of name-value pairs.
     * @return self the component itself
     */
    final public static function configure(self $component, array $properties = []): self
    {
        Typecast::properties(static::class, $properties);

        foreach ($properties as $name => $value) {
            $component->$name = $value;
        }

        return $component;
    }

    /**
     * Returns the display name of this class.
     *
     * @return string The display name of this class.
     */
    public static function displayName(): string
    {
        $classNameParts = explode('\\', static::class);

        return array_pop($classNameParts);
    }

    public static function isSelectable(): bool
    {
        return true;
    }

    public function __get(string $name)
    {
        $getter = $this->resolveMagicMethod('get', $name);

        if ($getter !== null) {
            return $this->$getter();
        }

        if ($this->resolveMagicMethod('set', $name) !== null) {
            throw new InvalidCallException('Getting write-only property: '.static::class.'::'.$name);
        }

        throw new UnknownPropertyException('Getting unknown property: '.static::class.'::'.$name);
    }

    public function __set(string $name, $value): void
    {
        $setter = $this->resolveMagicMethod('set', $name);

        if ($setter !== null) {
            // set property
            $this->$setter($value);

            return;
        }

        if ($this->resolveMagicMethod('get', $name) !== null) {
            throw new InvalidCallException('Setting read-only property: '.static::class.'::'.$name);
        }

        throw new UnknownPropertyException('Setting unknown property: '.static::class.'::'.$name);
    }

    public function __isset(string $name): bool
    {
        $getter = $this->resolveMagicMethod('get', $name);

        if ($getter !== null) {
            return $this->$getter() !== null;
        }

        return false;
    }

    public function __unset(string $name): void
    {
        $setter = $this->resolveMagicMethod('set', $name);

        if ($setter !== null) {
            $this->$setter(null);

            return;
        }

        throw new InvalidCallException('Unsetting an unknown or read-only property: '.static::class.'::'.$name);
    }

    private function resolveMagicMethod(string $prefix, string $name): ?string
    {
        $method = $prefix.$name;

        if (method_exists($this, $method)) {
            return $method;
        }

        if (! self::supportsMacroableMagicMethods()) {
            return null;
        }

        if (self::macroMethodExists($method)) {
            return $method;
        }

        $normalizedMethod = $prefix.ucfirst($name);

        if ($normalizedMethod !== $method && self::macroMethodExists($normalizedMethod)) {
            return $normalizedMethod;
        }

        return null;
    }

    private static function supportsMacroableMagicMethods(): bool
    {
        static $supports = [];

        $class = static::class;

        if (array_key_exists($class, $supports)) {
            return $supports[$class];
        }

        $usesRecursive = class_uses_recursive($class);

        $supports[$class] = in_array(Macroable::class, $usesRecursive, true) && is_callable([$class, 'hasMacro']);

        return $supports[$class];
    }

    private static function macroMethodExists(string $method): bool
    {
        return (bool) call_user_func([static::class, 'hasMacro'], $method);
    }
}
