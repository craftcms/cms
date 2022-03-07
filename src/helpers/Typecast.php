<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

declare(strict_types=1);

namespace craft\helpers;

use DateTime;
use ReflectionException;
use ReflectionNamedType;
use ReflectionProperty;
use yii\base\InvalidArgumentException;
use yii\base\InvalidValueException;

/**
 * Typecast Helper
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 4.0.0
 */
final class Typecast
{
    private static array $types = [];

    /**
     * Typecasts the given property values based on their type declarations.
     *
     * @param string $class The class name
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
     * @param string $class The class name
     * @param string $property The property name
     * @param mixed $value The property value
     */
    private static function property(string $class, string $property, mixed &$value): void
    {
        $type = self::propertyType($class, $property);
        if (!$type) {
            return;
        }

        if ($value === '') {
            $value = null;
        }

        if ($value === null && $type->allowsNull()) {
            return;
        }

        switch ($type->getName()) {
            case 'int':
                if ($value !== null && !is_scalar($value)) {
                    throw new InvalidValueException("Unable to typecast $property value to an int.");
                }
                $value = (int)$value;
                return;
            case 'float':
                if ($value !== null && !is_scalar($value)) {
                    throw new InvalidValueException("Unable to typecast $property value to a float.");
                }
                $value = (float)$value;
                return;
            case 'string':
                if ($value !== null && !is_scalar($value)) {
                    // We could technically call __toString() here, but that seems hacky.
                    // If an object is getting set to a string property, that's probably a bug that shouldn't go unnoticed.
                    throw new InvalidValueException("Unable to typecast $property value to a string.");
                }
                $value = (string)$value;
                return;
            case 'bool':
                if ($value !== null && !is_scalar($value)) {
                    throw new InvalidValueException("Unable to typecast $property value to a bool.");
                }
                $value = (bool)$value;
                return;
            case 'array':
                if ($value === null) {
                    $value = [];
                }
                if (is_array($value)) {
                    return;
                }
                if (is_string($value)) {
                    try {
                        $decoded = Json::decode($value) ?? [];
                        if (!is_array($decoded)) {
                            throw new InvalidValueException();
                        }
                        $value = $decoded;
                    } catch (InvalidArgumentException | InvalidValueException) {
                        $value = StringHelper::split($value);
                    }
                    return;
                }
                if (is_iterable($value)) {
                    $value = iterator_to_array($value);
                    return;
                }
                throw new InvalidValueException("Unable to typecast $property value to an array.");
            case DateTime::class:
                if ($value instanceof DateTime) {
                    return;
                }
                $date = DateTimeHelper::toDateTime($value);
                if (!$date && !$type->allowsNull()) {
                    throw new InvalidValueException("Unable to typecast $property value to a DateTime object.");
                }
                $value = $date ?: null;
                return;
        }
    }

    private static function propertyType(string $class, string $property): ?ReflectionNamedType
    {
        if (!isset(self::$types[$class][$property])) {
            try {
                $ref = new ReflectionProperty($class, $property);
                if (!$ref->isPublic() || $ref->isStatic()) {
                    self::$types[$class][$property] = false;
                } else {
                    $type = $ref->getType();
                    if ($type instanceof ReflectionNamedType) {
                        self::$types[$class][$property] = $type;
                    } else {
                        // We don't support typecasting properties with union types
                        self::$types[$class][$property] = false;
                    }
                }
            } catch (ReflectionException) {
                // The property doesn’t exist
                self::$types[$class][$property] = false;
            }
        }

        return self::$types[$class][$property] ?: null;
    }
}
