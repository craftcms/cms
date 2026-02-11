<?php

declare(strict_types=1);

namespace CraftCms\Cms\Component;

use CraftCms\Cms\Component\Exceptions\InvalidCallException;
use CraftCms\Cms\Component\Exceptions\UnknownPropertyException;
use CraftCms\Cms\Support\Typecast;
use CraftCms\Cms\Validation\Concerns\Validates;
use CraftCms\Cms\Validation\Contracts\Validatable;
use Illuminate\Contracts\Support\Arrayable;
use Yiisoft\Arrays\ArrayableInterface;
use Yiisoft\Arrays\ArrayableTrait;

abstract class Component implements Arrayable, ArrayableInterface, Validatable
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

    public function __get(string $name)
    {
        $getter = 'get'.$name;

        if (method_exists($this, $getter)) {
            return $this->$getter();
        }

        if (method_exists($this, 'set'.$name)) {
            throw new InvalidCallException('Getting write-only property: '.static::class.'::'.$name);
        }

        throw new UnknownPropertyException('Getting unknown property: '.static::class.'::'.$name);
    }

    public function __set(string $name, $value): void
    {
        $setter = 'set'.$name;

        if (method_exists($this, $setter)) {
            // set property
            $this->$setter($value);

            return;
        }

        if (method_exists($this, 'get'.$name)) {
            throw new InvalidCallException('Setting read-only property: '.static::class.'::'.$name);
        }

        throw new UnknownPropertyException('Setting unknown property: '.static::class.'::'.$name);
    }

    public function __isset(string $name): bool
    {
        $getter = 'get'.$name;

        if (method_exists($this, $getter)) {
            return $this->$getter() !== null;
        }

        return false;
    }

    public function __unset(string $name): void
    {
        $setter = 'set'.$name;

        if (method_exists($this, $setter)) {
            $this->$setter(null);

            return;
        }

        throw new InvalidCallException('Unsetting an unknown or read-only property: '.static::class.'::'.$name);
    }
}
