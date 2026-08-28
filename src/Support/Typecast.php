<?php

declare(strict_types=1);

namespace CraftCms\Cms\Support;

use BackedEnum;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use CraftCms\Cms\Component\Component;
use DateTime;
use DateTimeInterface;
use InvalidArgumentException;
use PropertyHookType;
use ReflectionClass;
use ReflectionNamedType;
use ReflectionParameter;
use ReflectionProperty;
use ReflectionUnionType;
use RuntimeException;
use Throwable;

class Typecast
{
    private const string TYPE_BOOL = 'bool';

    private const string TYPE_FLOAT = 'float';

    private const string TYPE_INT = 'int';

    private const string TYPE_INT_FLOAT = 'int|float';

    private const string TYPE_INT_STRING = 'int|string';

    private const string TYPE_STRING = 'string';

    private const string TYPE_ARRAY = 'array';

    private const string TYPE_NULL = 'null';

    private const string TYPE_CARBON = Carbon::class;

    private const string TYPE_CARBONINTERFACE = CarbonInterface::class;

    private const string TYPE_DATETIME = DateTime::class;

    private const string TYPE_DATETIMEINTERFACE = DateTimeInterface::class;

    /** @var array<class-string, array<string, array{string, bool}>> */
    private static array $types = [];

    /** @var array<class-string, array<string, array{string, bool}|false|null>> */
    private static array $setterTypes = [];

    /** @var array<class-string, array<string, string>> */
    private static array $setters = [];

    /** @var array<class-string, array<string, bool>> */
    private static array $assignableProperties = [];

    /**
     * Configures a component with the initial property values.
     *
     * @template T of object
     *
     * @param  T  $object  the object to be configured
     * @param  array<string, mixed>  $properties  the property initial values given in terms of name-value pairs.
     * @return T the object itself
     */
    final public static function configure(object $object, array $properties = []): object
    {
        $class = $object::class;

        self::properties($class, $properties);

        foreach ($properties as $name => $value) {
            if (! self::isAssignableProperty($class, $name)) {
                $setter = self::setter($class, $name);
                if ($setter !== null) {
                    $object->$setter($value);
                }

                continue;
            }

            if ($object instanceof Component && ! $object->canSetProperty($name)) {
                continue;
            }

            try {
                $object->$name = $value;
            } catch (Throwable $error) {
                match (true) {
                    str_contains($error->getMessage(), 'Cannot modify private(set)') => true,
                    str_contains($error->getMessage(), 'Cannot modify protected(set)') => true,
                    str_contains($error->getMessage(), 'is read-only') => true,
                    default => throw $error,
                };
            }
        }

        return $object;
    }

    private static function isAssignableProperty(string $class, string $property): bool
    {
        if (! isset(self::$assignableProperties[$class])) {
            self::resolveClassTypes($class);
        }

        return self::$assignableProperties[$class][$property] ?? true;
    }

    private static function setter(string $class, string $property): ?string
    {
        if (! isset(self::$setters[$class])) {
            self::resolveClassTypes($class);
        }

        return self::$setters[$class][$property] ?? null;
    }

    /**
     * Typecasts the given property values based on their type declarations.
     *
     * @param  class-string  $class  The class name
     * @param  array<string, mixed>  $properties  The property values
     */
    public static function properties(string $class, array &$properties): void
    {
        foreach ($properties as $name => &$value) {
            self::property($class, $name, $value);
        }
    }

    public static function isDateTimeProperty(string $class, string $property): bool
    {
        $type = self::propertyType($class, $property);

        if ($type === false) {
            return false;
        }

        [$typeName] = $type;

        return is_a($typeName, DateTimeInterface::class, true);
    }

    public static function isInt(float|int|string $value): bool
    {
        if (! is_numeric($value)) {
            throw new RuntimeException('Only numeric values can be typecast to an integer or float.');
        }

        return (float) (int) $value === (float) $value;
    }

    public static function isIntOrFloat(mixed $value): bool
    {
        return
            is_int($value) ||
            is_float($value) ||
            (is_string($value) && preg_match('/^([1-9]\d*|0|([1-9]\d*|0)?\.\d+)$/', $value));
    }

    public static function toIntOrFloat(float|int|string $value): float|int
    {
        return self::isInt($value) ? (int) $value : (float) $value;
    }

    /**
     * Typecasts the given property value based on its type declaration.
     *
     * @param  class-string  $class  The class name
     * @param  string  $property  The property name
     * @param  mixed  $value  The property value
     */
    private static function property(string $class, string $property, mixed &$value): void
    {
        $type = self::propertyType($class, $property);

        if (! $type) {
            return;
        }

        [$typeName, $allowsNull] = $type;

        if ($allowsNull && ($value === null || $value === '')) {
            $value = null;

            return;
        }

        switch ($typeName) {
            case self::TYPE_BOOL:
                if ($value === null || is_scalar($value)) {
                    $value = Env::normalizeBooleanValue($value);

                    if ($value === null && ! $allowsNull) {
                        $value = false;
                    }
                }

                return;
            case self::TYPE_FLOAT:
            case self::TYPE_INT:
            case self::TYPE_INT_FLOAT:
            case self::TYPE_INT_STRING:
            case self::TYPE_STRING:
                if ($value === null || is_scalar($value)) {
                    /** @phpstan-var self::TYPE_FLOAT|self::TYPE_INT|self::TYPE_INT_FLOAT|self::TYPE_INT_STRING|self::TYPE_STRING $typeName */
                    $value = match ($typeName) {
                        self::TYPE_FLOAT => (float) $value,
                        self::TYPE_INT => (int) $value,
                        self::TYPE_INT_FLOAT => self::toIntOrFloat($value ?? 0),
                        self::TYPE_INT_STRING => is_int($value) || ($value === (string) (int) $value) ? (int) $value : $value,
                        self::TYPE_STRING => (string) $value,
                    };
                }

                return;
            case self::TYPE_ARRAY:
                if ($value === null) {
                    $value = [];
                }
                if (is_array($value)) {
                    return;
                }
                if (is_string($value)) {
                    try {
                        $decoded = Json::decode($value) ?? [];
                        if (is_array($decoded)) {
                            $value = $decoded;
                        }
                    } catch (InvalidArgumentException) {
                        $value = str($value)->explode(',')->all();
                    }

                    return;
                }
                if (is_iterable($value)) {
                    $value = iterator_to_array($value);
                }

                return;
            case self::TYPE_CARBON:
            case self::TYPE_CARBONINTERFACE:
            case self::TYPE_DATETIME:
            case self::TYPE_DATETIMEINTERFACE:
                $expected = match ($typeName) {
                    self::TYPE_CARBON => Carbon::class,
                    self::TYPE_CARBONINTERFACE => CarbonInterface::class,
                    self::TYPE_DATETIME => DateTime::class,
                    self::TYPE_DATETIMEINTERFACE => DateTimeInterface::class,
                };

                if ($value instanceof $expected) {
                    return;
                }

                $date = DateTimeHelper::toDateTime($value);

                if ($date || $allowsNull) {
                    $value = $date ?: null;
                }

                return;
            default:
                if (
                    is_scalar($value) &&
                    is_subclass_of($typeName, BackedEnum::class)
                ) {
                    $value = $typeName::from($value);
                }
        }
    }

    /** @return array{string, bool}|false */
    private static function propertyType(string $class, string $property): array|false
    {
        if (! isset(self::$types[$class])) {
            self::resolveClassTypes($class);
        }

        if (
            array_key_exists($property, self::$types[$class]) &&
            (self::$assignableProperties[$class][$property] ?? false)
        ) {
            return self::$types[$class][$property];
        }

        if (array_key_exists($property, self::$setterTypes[$class])) {
            $setterType = self::$setterTypes[$class][$property];

            if ($setterType !== null) {
                return $setterType;
            }
        }

        if (array_key_exists($property, self::$types[$class])) {
            return self::$types[$class][$property];
        }

        return self::$types[$class]['_'.lcfirst($property)] // Underscore prefixed private
            ?? false;
    }

    private static function resolveClassTypes(string $class): void
    {
        self::$types[$class] = [];
        self::$setterTypes[$class] = [];
        self::$setters[$class] = [];
        self::$assignableProperties[$class] = [];

        $className = $class;
        $class = new ReflectionClass($className);

        foreach ($class->getMethods() as $ref) {
            if ($ref->isStatic()) {
                continue;
            }
            if (! $ref->isPublic()) {
                continue;
            }
            if (! str_starts_with($ref->getName(), 'set')) {
                continue;
            }
            $parameters = $ref->getParameters();
            if (! isset($parameters[0])) {
                continue;
            }
            if (count(array_filter($parameters, fn (ReflectionParameter $parameter) => ! $parameter->isOptional())) > 1) {
                continue;
            }

            $property = lcfirst(substr($ref->getName(), 3));

            if ($property === '') {
                continue;
            }

            self::$setterTypes[$className][$property] = self::resolveParameterType($parameters[0]);
            self::$setters[$className][$property] = $ref->getName();
        }

        foreach ($class->getProperties() as $ref) {
            if ($ref->isStatic()) {
                continue;
            }

            self::$assignableProperties[$className][$ref->getName()] = self::reflectionPropertyIsAssignable($ref);

            $type = $ref->getType();

            if ($type instanceof ReflectionNamedType) {
                self::$types[$className][$ref->getName()] = [$type->getName(), $type->allowsNull()];
            } elseif ($type instanceof ReflectionUnionType) {
                $resolved = self::resolveUnionType($type);

                if ($resolved !== false) {
                    self::$types[$className][$ref->getName()] = $resolved;
                }
            }
        }
    }

    private static function reflectionPropertyIsAssignable(ReflectionProperty $property): bool
    {
        if (! $property->isPublic()) {
            return false;
        }

        if ($property->isPrivateSet() || $property->isProtectedSet() || $property->isReadOnly()) {
            return false;
        }

        if ($property->isVirtual()) {
            return $property->hasHook(PropertyHookType::Set);
        }

        return true;
    }

    /** @return array{string, bool}|false|null */
    private static function resolveParameterType(ReflectionParameter $parameter): array|false|null
    {
        $type = $parameter->getType();

        if ($type instanceof ReflectionNamedType) {
            if ($type->getName() === 'mixed') {
                return false;
            }

            return [$type->getName(), $type->allowsNull()];
        }

        if ($type instanceof ReflectionUnionType) {
            return self::resolveUnionType($type);
        }

        return null;
    }

    /** @return array{string, bool}|false */
    private static function resolveUnionType(ReflectionUnionType $type): array|false
    {
        $names = array_map(fn (ReflectionNamedType $t) => $t->getName(), $type->getTypes());

        sort($names);

        $allowsNull = in_array(self::TYPE_NULL, $names);

        // Special case for int|float
        if ($names === [self::TYPE_FLOAT, self::TYPE_INT] || $names === [self::TYPE_FLOAT, self::TYPE_INT, self::TYPE_NULL]) {
            return [self::TYPE_INT_FLOAT, $allowsNull];
        }

        // Special case for int|string
        if ($names === [self::TYPE_INT, self::TYPE_STRING] || $names === [self::TYPE_INT, self::TYPE_NULL, self::TYPE_STRING]) {
            return [self::TYPE_INT_STRING, $allowsNull];
        }

        return false;
    }
}
