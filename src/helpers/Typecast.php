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
use ReflectionException;
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
            case self::TYPE_FLOAT:
            case self::TYPE_INT:
            case self::TYPE_INT_FLOAT:
            case self::TYPE_INT_STRING:
            case self::TYPE_STRING:
                if ($value === null || is_scalar($value)) {
                    /** @phpstan-var self::TYPE_BOOL|self::TYPE_FLOAT|self::TYPE_INT|self::TYPE_INT_FLOAT|self::TYPE_INT_STRING|self::TYPE_STRING $typeName */
                    $value = match ($typeName) {
                        self::TYPE_BOOL => (bool)$value,
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
        if (!isset(self::$types[$class][$property])) {
            self::$types[$class][$property] = self::_propertyType($class, $property);
        }

        return self::$types[$class][$property];
    }

    private static function _propertyType(string $class, string $property): array|false
    {
        try {
            $ref = new ReflectionProperty($class, $property);
        } catch (ReflectionException) {
            // The property doesn’t exist
            return false;
        }

        if (!$ref->isPublic() || $ref->isStatic()) {
            return false;
        }

        $type = $ref->getType();

        if ($type instanceof ReflectionNamedType) {
            return [$type->getName(), $type->allowsNull()];
        }

        if ($type instanceof ReflectionUnionType) {
            $names = array_map(fn(ReflectionNamedType $type) => $type->getName(), $type->getTypes());
            sort($names);
            // Special case for int|float
            if ($names === [self::TYPE_FLOAT, self::TYPE_INT] || $names === [self::TYPE_FLOAT, self::TYPE_INT, self::TYPE_NULL]) {
                return [self::TYPE_INT_FLOAT, in_array(self::TYPE_NULL, $names)];
            }
            // Special case for int|string
            if ($names === [self::TYPE_INT, self::TYPE_STRING] || $names === [self::TYPE_INT, self::TYPE_NULL, self::TYPE_STRING]) {
                return [self::TYPE_INT_STRING, in_array(self::TYPE_NULL, $names)];
            }
        }

        return false;
    }
}
