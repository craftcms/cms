<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */


namespace craft\helpers;

use BackedEnum;
use DateTime;
use DateTimeInterface;
use ReflectionClass;
use ReflectionNamedType;
use ReflectionProperty;
use ReflectionUnionType;
use yii\base\InvalidArgumentException;

/**
 * Typecast Helper
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 4.0.0
 */
final class Typecast
{
    private const TYPE_BOOL = 'bool';
    private const TYPE_FLOAT = 'float';
    private const TYPE_INT = 'int';
    private const TYPE_INT_FLOAT = 'int|float';
    private const TYPE_INT_STRING = 'int|string';
    private const TYPE_STRING = 'string';
    private const TYPE_ARRAY = 'array';
    private const TYPE_NULL = 'null';
    private const TYPE_DATETIME = DateTime::class;
    private const TYPE_DATETIMEINTERFACE = DateTimeInterface::class;

    private static array $types = [];

    /**
     * Typecasts the given property values based on their type declarations.
     *
     * @param class-string $class The class name
     * @param array $properties The property values
     */
    public static function properties(string $class, array &$properties): void
    {
        foreach ($properties as $name => &$value) {
            self::property($class, $name, $value);
        }
    }

    /**
     * Typecasts the given property value based on its type declaration.
     *
     * @param class-string $class The class name
     * @param string $property The property name
     * @param mixed $value The property value
     */
    private static function property(string $class, string $property, mixed &$value): void
    {
        $type = self::propertyType($class, $property);
        if (!$type) {
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
                    $value = App::normalizeBooleanValue($value);
                    if ($value === null && !$allowsNull) {
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
                        self::TYPE_FLOAT => (float)$value,
                        self::TYPE_INT => (int)$value,
                        self::TYPE_INT_FLOAT => Number::toIntOrFloat($value ?? 0),
                        self::TYPE_INT_STRING => is_int($value) || ($value === (string)(int)$value) ? (int)$value : $value,
                        self::TYPE_STRING => (string)$value,
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
                        $value = StringHelper::split($value);
                    }
                    return;
                }
                if (is_iterable($value)) {
                    $value = iterator_to_array($value);
                }
                return;
            case self::TYPE_DATETIME:
            case self::TYPE_DATETIMEINTERFACE:
                /** @phpstan-ignore-next-line */
                $expected = match ($typeName) {
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
                    /** @var BackedEnum $typeName */
                    $value = $typeName::from($value);
                }
        }
    }

    private static function propertyType(string $class, string $property): array|false
    {
        if (!isset(self::$types[$class])) {
            self::resolveClassTypes($class);
        }

        return self::$types[$class][$property] ?? false;
    }

    private static function resolveClassTypes(string $class): void
    {
        self::$types[$class] = [];

        $properties = (new ReflectionClass($class))->getProperties(ReflectionProperty::IS_PUBLIC);

        foreach ($properties as $ref) {
            if ($ref->isStatic()) {
                continue;
            }

            $type = $ref->getType();

            if ($type instanceof ReflectionNamedType) {
                self::$types[$class][$ref->getName()] = [$type->getName(), $type->allowsNull()];
            } elseif ($type instanceof ReflectionUnionType) {
                $resolved = self::resolveUnionType($type);

                if ($resolved !== false) {
                    self::$types[$class][$ref->getName()] = $resolved;
                }
            }
        }
    }

    private static function resolveUnionType(ReflectionUnionType $type): array|false
    {
        $names = array_map(fn(ReflectionNamedType $t) => $t->getName(), $type->getTypes());

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
