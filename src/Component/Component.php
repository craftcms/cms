<?php

declare(strict_types=1);

namespace CraftCms\Cms\Component;

use CraftCms\Cms\Component\Contracts\ComponentInterface;
use CraftCms\Cms\Component\Exceptions\InvalidCallException;
use CraftCms\Cms\Component\Exceptions\UnknownPropertyException;
use CraftCms\Cms\Support\Concerns\MacroableMagicMethods;
use CraftCms\Cms\Support\DateTimeHelper;
use CraftCms\Cms\Support\Typecast;
use CraftCms\Cms\Validation\Concerns\Validates;
use CraftCms\Cms\Validation\Contracts\Validatable;
use DateTimeInterface;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Support\Traits\Macroable;
use Yiisoft\Arrays\ArrayableInterface;
use Yiisoft\Arrays\ArrayableTrait;

abstract class Component implements Arrayable, ArrayableInterface, ComponentInterface, Validatable
{
    use ArrayableTrait {
        fields as private traitFields;
    }
    use Macroable;
    use MacroableMagicMethods;
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
        return Typecast::configure($component, $properties);
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

    public function fields(): array
    {
        $fields = $this->traitFields();

        foreach ($fields as $field => $definition) {
            if (! is_string($definition)) {
                continue;
            }

            // Only rewrite default field-to-property mappings, not aliases or custom definitions.
            if ($definition !== $field) {
                continue;
            }

            if (! Typecast::isDateTimeProperty(static::class, $field)) {
                continue;
            }

            $fields[$field] = static function (self $component, string $field): mixed {
                $value = $component->$field;

                if (! $value instanceof DateTimeInterface) {
                    return $value;
                }

                return DateTimeHelper::toIso8601($value, true) ?: null;
            };
        }

        return $fields;
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
}
