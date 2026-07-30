<?php

declare(strict_types=1);

namespace CraftCms\Cms\Component;

use ArrayAccess;
use CraftCms\Cms\Component\Contracts\ComponentInterface;
use CraftCms\Cms\Component\Exceptions\InvalidCallException;
use CraftCms\Cms\Component\Exceptions\UnknownPropertyException;
use CraftCms\Cms\Shared\Concerns\LegacyEventConstants;
use CraftCms\Cms\Support\Arr;
use CraftCms\Cms\Support\Concerns\MacroableMagicMethods;
use CraftCms\Cms\Support\DateTimeHelper;
use CraftCms\Cms\Support\Typecast;
use CraftCms\Cms\Validation\Concerns\Validates;
use CraftCms\Cms\Validation\Contracts\Validatable;
use CraftCms\Cms\Validation\ValidatableRules;
use CraftCms\RulesetValidation\Attributes\Ruleset;
use DateTimeInterface;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Support\Traits\Conditionable;
use Illuminate\Support\Traits\Macroable;
use Yiisoft\Arrays\ArrayableInterface;
use Yiisoft\Arrays\ArrayableTrait;

/**
 * @implements Arrayable<string, mixed>
 * @implements ArrayAccess<string, mixed>
 */
#[Ruleset(ValidatableRules::class)]
abstract class Component implements Arrayable, ArrayableInterface, ArrayAccess, ComponentInterface, Validatable
{
    use ArrayableTrait {
        fields as private traitFields;
    }
    use Conditionable;
    use LegacyEventConstants;
    use Macroable;
    use MacroableMagicMethods;
    use Validates;

    /** @param array<string, mixed>|object $config */
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
     * @param  array<string, mixed>  $properties  the property initial values given in terms of name-value pairs.
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

    /** @return array<string, string|callable> */
    public function fields(): array
    {
        $fields = Arr::except($this->traitFields(), ['ruleset']);

        return $this->formatDateFields($fields);
    }

    /**
     * @param  array<string, string|callable>  $fields
     * @return array<string, string|callable>
     */
    protected function formatDateFields(array $fields): array
    {
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

    public function __get(string $name): mixed
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

    public function __set(string $name, mixed $value): void
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

    public function canSetProperty(string $name): bool
    {
        if (property_exists($this, $name)) {
            return true;
        }

        return $this->resolveMagicMethod('set', $name) !== null;
    }

    public function canGetProperty(string $name): bool
    {
        if (property_exists($this, $name)) {
            return true;
        }

        return $this->resolveMagicMethod('get', $name) !== null;
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

    public function offsetExists(mixed $offset): bool
    {
        return isset($this->$offset);
    }

    public function offsetGet(mixed $offset): mixed
    {
        return $this->$offset;
    }

    public function offsetSet(mixed $offset, mixed $value): void
    {
        $this->$offset = $value;
    }

    public function offsetUnset(mixed $offset): void
    {
        $this->$offset = null;
    }
}
