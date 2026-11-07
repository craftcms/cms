<?php

declare(strict_types=1);

namespace CraftCms\Cms\Component;

use ArrayAccess;
use BadMethodCallException;
use Closure;
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
use RuntimeException;
use Throwable;
use Yiisoft\Arrays\ArrayableInterface;
use Yiisoft\Arrays\ArrayableTrait;

/**
 * @implements Arrayable<string, mixed>
 * @implements ArrayAccess<string, mixed>
 *
 * @since 6.0.0
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

    /**
     * The registered macros, by the class they were registered on, so a macro registered on a component class
     * applies to it and its subclasses, and one registered on this class applies to every component.
     *
     * Laravel’s `Macroable` keeps one array for a class and all of its subclasses. Its `mixin()` still works,
     * since it registers through `macro()`. The macro methods below have no return types, like `Macroable`’s,
     * so subclasses that use `Macroable` again stay compatible.
     *
     * @var array<class-string, array<string, object|callable>>
     */
    protected static array $macrosByClass = [];

    /**
     * Every registered macro by name, in the format of Laravel’s `Macroable`, for tools that read it, such as
     * Larastan. Macros are looked up in [[$macrosByClass]].
     *
     * @var array<string, object|callable>
     */
    protected static $macros = [];

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

    /**
     * Register a macro for this class and its subclasses.
     *
     * @param  string  $name
     * @param  object|callable  $macro
     * @return void
     */
    public static function macro($name, $macro)
    {
        static::$macrosByClass[static::class][$name] = $macro;
        static::$macros[$name] = $macro;
    }

    /**
     * Checks if a macro is registered on this class or a parent class.
     *
     * @param  string  $name
     * @return bool
     */
    public static function hasMacro($name)
    {
        return static::findMacro($name) !== null;
    }

    /**
     * Flush the macros registered on this class and its subclasses.
     *
     * @return void
     */
    public static function flushMacros()
    {
        static::$macrosByClass = array_filter(
            static::$macrosByClass,
            fn (string $class) => ! is_a($class, static::class, true),
            ARRAY_FILTER_USE_KEY,
        );

        static::$macros = array_merge(...array_values(static::$macrosByClass));
    }

    /**
     * Dynamically handle static calls to macros.
     *
     * @param  string  $method
     * @param  array<int, mixed>  $parameters
     * @return mixed
     *
     * @throws BadMethodCallException
     */
    public static function __callStatic($method, $parameters)
    {
        $macro = static::findMacro($method) ?? throw new BadMethodCallException(sprintf(
            'Method %s::%s does not exist.', static::class, $method,
        ));

        if ($macro instanceof Closure) {
            $macro = $macro->bindTo(null, static::class);
        }

        return $macro(...$parameters);
    }

    /**
     * Dynamically handle calls to macros.
     *
     * @param  string  $method
     * @param  array<int, mixed>  $parameters
     * @return mixed
     *
     * @throws BadMethodCallException
     */
    public function __call($method, $parameters)
    {
        $macro = static::findMacro($method) ?? throw new BadMethodCallException(sprintf(
            'Method %s::%s does not exist.', static::class, $method,
        ));

        if ($macro instanceof Closure) {
            try {
                $macro = $macro->bindTo($this, static::class) ?? throw new RuntimeException;
            } catch (Throwable) {
                $macro = $macro->bindTo(null, static::class);
            }
        }

        return $macro(...$parameters);
    }

    /**
     * Returns the macro registered under a name on this class or the closest parent class.
     */
    protected static function findMacro(string $name): object|callable|null
    {
        for ($class = static::class; $class !== false; $class = get_parent_class($class)) {
            if (isset(static::$macrosByClass[$class][$name])) {
                return static::$macrosByClass[$class][$name];
            }
        }

        return null;
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
