<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\helpers;

use Craft;
use Illuminate\Support\Collection;

/**
 * Class ArrayHelper
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0.0
 */
class ArrayHelper extends \yii\helpers\ArrayHelper
{
    /**
     * @inheritdoc
     * @param object|array|string|null $object
     * @param array $properties
     * @param bool $recursive
     * @return array
     */
    public static function toArray($object, $properties = [], $recursive = true): array
    {
        if ($object === null) {
            return [];
        }

        if (is_string($object) && str_contains($object, ',')) {
            Craft::$app->getDeprecator()->log('ArrayHelper::toArray(string)', 'Passing a string to `ArrayHelper::toArray()` has been deprecated. Use `StringHelper::split()` instead.');

            // Split it on the non-escaped commas
            $object = preg_split('/(?<!\\\),/', $object);

            // Remove any of the backslashes used to escape the commas
            foreach ($object as $key => $val) {
                // Remove leading/trailing whitespace
                $val = trim($val);

                // Remove any backslashes used to escape commas
                $val = str_replace('\,', ',', $val);

                $object[$key] = $val;
            }

            // Remove any empty elements and reset the keys
            return array_values(static::filterEmptyStringsFromArray($object));
        }

        return parent::toArray($object, $properties, $recursive);
    }

    /**
     * Prepends values to an array.
     *
     * ---
     * ```php
     * ArrayHelper::prepend($array, ...$values);
     * ```
     *
     * @param array $array the array to be prepended to
     * @param mixed ...$values the values to prepend.
     * @since 3.4.0
     * @deprecated in 4.0.0. `array_unshift()` should be used instead.
     */
    public static function prepend(array &$array, ...$values): void
    {
        array_unshift($array, ...$values);
    }

    /**
     * Appends values to an array.
     *
     * ---
     * ```php
     * ArrayHelper::append($array, ...$values);
     * ```
     *
     * @param array $array the array to be appended to
     * @param mixed ...$values the values to append.
     * @since 3.4.0
     * @deprecated in 4.0.0. `array_push()` should be used instead.
     */
    public static function append(array &$array, ...$values): void
    {
        array_push($array, ...$values);
    }

    /**
     * Prepends or appends a value to an array.
     *
     * @param array $array the array to be prepended/appended to
     * @param mixed $value the value to prepend/append to the array
     * @param bool $prepend `true` will prepend the value; `false` will append it
     */
    public static function prependOrAppend(array &$array, mixed $value, bool $prepend): void
    {
        if ($prepend) {
            array_unshift($array, $value);
        } else {
            $array[] = $value;
        }
    }

    /**
     * Filters an array to only the values where a given key (the name of a
     * sub-array key or sub-object property) is set to a given value.
     *
     * Array keys are preserved by default.
     *
     * @param iterable $array the array that needs to be indexed or grouped
     * @param callable|string $key the column name or anonymous function which result will be used to index the array
     * @param mixed $value the value that $key should be compared with
     * @param bool $strict whether a strict type comparison should be used when checking array element values against $value
     * @param bool $keepKeys whether to maintain the array keys. If false, the resulting array
     * will be re-indexed with integers.
     * @return array the filtered array
     */
    public static function where(iterable $array, callable|string $key, mixed $value = true, bool $strict = false, bool $keepKeys = true): array
    {
        $result = [];

        foreach ($array as $i => $element) {
            $elementValue = static::getValue($element, $key);
            /** @noinspection TypeUnsafeComparisonInspection */
            if (($strict && $elementValue === $value) || (!$strict && $elementValue == $value)) {
                if ($keepKeys) {
                    $result[$i] = $element;
                } else {
                    $result[] = $element;
                }
            }
        }

        return $result;
    }

    /**
     * Filters an array to only the values where a given key (the name of a
     * sub-array key or sub-object property) is set to one of a given range of values.
     *
     * Array keys are preserved by default.
     *
     * @param iterable $array the array that needs to be indexed or grouped
     * @param callable|string $key the column name or anonymous function which result will be used to index the array
     * @param array $values the range of values that `$key` should be compared with
     * @param bool $strict whether a strict type comparison should be used when checking array element values against `$values`
     * @param bool $keepKeys whether to maintain the array keys. If false, the resulting array
     * will be re-indexed with integers.
     * @return array the filtered array
     * @since 3.5.8
     */
    public static function whereIn(iterable $array, callable|string $key, array $values, bool $strict = false, bool $keepKeys = true): array
    {
        $result = [];

        foreach ($array as $i => $element) {
            $elementValue = static::getValue($element, $key);
            if (in_array($elementValue, $values, $strict)) {
                if ($keepKeys) {
                    $result[$i] = $element;
                } else {
                    $result[] = $element;
                }
            }
        }

        return $result;
    }

    /**
     * Filters an array to only the values where a list of keys is set to given values.
     * Array keys are preserved.
     *
     * This method is most useful when, given an array of elements, it is needed to filter
     * them by multiple conditions.
     *
     * Below are some usage examples,
     *
     * ```php
     * // Entries with certain entry types
     * $filtered = \craft\helpers\ArrayHelper::whereMultiple($entries, ['typeId' => [2, 4]]);
     *
     * // Entries with multiple conditions
     * $filtered = \craft\helpers\ArrayHelper::whereMultiple($entries, ['typeId' => 2, 'authorId' => [1, 2]);
     *
     * // Testing for an array value
     * $filtered = \craft\helpers\ArrayHelper::whereMultiple($asset, ['focalPoint' => [['x' => 0.5, 'y' => 0.5]]]);
     *
     * ```
     *
     * @param iterable $array the array that needs to be indexed or grouped
     * @param array $conditions An array of key/value pairs of allowed values. Values can be arrays to allow multiple values.
     * @param bool $strict whether a strict type comparison should be used when checking array element values against $value
     * @return array the filtered array
     * @since 3.3.0
     */
    public static function whereMultiple(iterable $array, array $conditions, bool $strict = false): array
    {
        $result = [];

        foreach ($array as $i => $element) {
            foreach ($conditions as $key => $value) {
                if (is_array($value) && !count($value)) {
                    continue;
                }

                $elementValue = static::getValue($element, $key);

                // Skip this element if there are multiple options and none of them match
                if (is_array($value) && !in_array($elementValue, $value, $strict)) {
                    continue 2;
                }

                if (!is_array($value) && (($strict && $elementValue !== $value) || (!$strict && $elementValue != $value))) {
                    continue 2;
                }
            }

            // If we haven't continue'd over this part, this is a good element.
            $result[$i] = $element;
        }

        return $result;
    }

    /**
     * Returns the first value in a given array where a given key (the name of a
     * sub-array key or sub-object property) is set to a given value.
     *
     * @param iterable $array the array that the value will be searched for in
     * @param callable|string $key the column name or anonymous function which must be set to $value
     * @param mixed $value the value that $key should be compared with
     * @param bool $strict whether a strict type comparison should be used when checking array element values against $value
     * @return mixed the value, or null if it can't be found
     * @since 3.1.0
     */
    public static function firstWhere(iterable $array, callable|string $key, mixed $value = true, bool $strict = false): mixed
    {
        foreach ($array as $element) {
            $elementValue = static::getValue($element, $key);
            /** @noinspection TypeUnsafeComparisonInspection */
            if (($strict && $elementValue === $value) || (!$strict && $elementValue == $value)) {
                return $element;
            }
        }

        return null;
    }

    /**
     * Returns whether the given array contains any values where a given key (the name of a
     * sub-array key or sub-object property) is set to a given value.
     *
     * @param iterable $array the array that the value will be searched for in
     * @param callable|string $key the column name or anonymous function which must be set to $value
     * @param mixed $value the value that $key should be compared with
     * @param bool $strict whether a strict type comparison should be used when checking array element values against $value
     * @return bool whether the value exists in the array
     * @since 3.4.0
     */
    public static function contains(iterable $array, callable|string $key, mixed $value = true, bool $strict = false): bool
    {
        foreach ($array as $element) {
            $elementValue = static::getValue($element, $key);
            /** @noinspection TypeUnsafeComparisonInspection */
            if (($strict && $elementValue === $value) || (!$strict && $elementValue == $value)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Returns whether the given array contains *only* values where a given key (the name of a
     * -ub-array key or sub-object property) is sett o given value.
     *
     * @param iterable $array the array that the value will be searched for in
     * @param callable|string $key the column name or anonymous function which must be set to $value
     * @param mixed $value the value that $key should be compared with
     * @param bool $strict whether a strict type comparison should be used when checking array element values against $value
     * @return bool whether the value exists in the array
     * @since 3.7.38
     */
    public static function onlyContains(iterable $array, callable|string $key, mixed $value = true, bool $strict = false): bool
    {
        foreach ($array as $element) {
            $elementValue = static::getValue($element, $key);
            /** @noinspection TypeUnsafeComparisonInspection */
            if (($strict && $elementValue !== $value) || (!$strict && $elementValue != $value)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Filters empty strings from an array.
     *
     * @param array $array
     * @return array
     */
    public static function filterEmptyStringsFromArray(array $array): array
    {
        return array_filter($array, function($value): bool {
            return $value !== '';
        });
    }

    /**
     * Returns the first key in a given array.
     *
     * @param array $array
     * @return string|int|null The first key, whether that is a number (if the array is numerically indexed) or a string, or null if $array isn’t an array, or is empty.
     */
    public static function firstKey(array $array): int|string|null
    {
        /** @noinspection LoopWhichDoesNotLoopInspection */
        foreach ($array as $key => $value) {
            return $key;
        }

        return null;
    }

    /**
     * Returns the first value in a given array.
     *
     * @param array $array
     * @return mixed The first value, or null if $array isn’t an array, or is empty.
     */
    public static function firstValue(array $array): mixed
    {
        return !empty($array) ? reset($array) : null;
    }

    /**
     * Renames an item in an array. If the new key already exists in the array and the old key doesn’t,
     * the array will be left unchanged.
     *
     * @param array $array the array to extract value from
     * @param string $oldKey old key name of the array element
     * @param string $newKey new key name of the array element
     * @param mixed $default the default value to be set if the specified old key does not exist
     */
    public static function rename(array &$array, string $oldKey, string $newKey, mixed $default = null): void
    {
        if (!array_key_exists($newKey, $array) || array_key_exists($oldKey, $array)) {
            $array[$newKey] = static::remove($array, $oldKey, $default);
        }
    }

    /**
     * Returns a copy of an array without a given key.
     *
     * @param array $array
     * @param string $key
     * @return array
     * @since 3.0.9
     */
    public static function without(array $array, string $key): array
    {
        static::remove($array, $key);
        return $array;
    }

    /**
     * Returns a copy of an array without items matching the given value.
     *
     * @param array $array
     * @param mixed $value
     * @return array
     */
    public static function withoutValue(array $array, mixed $value): array
    {
        static::removeValue($array, $value);
        return $array;
    }

    /**
     * Ensures an array is non-associative.
     *
     * @param array $array
     * @since 3.1.17.1
     */
    public static function ensureNonAssociative(array &$array): void
    {
        if (static::isAssociative($array, false)) {
            $array = array_values($array);
        }
    }

    /**
     * Checks whether a numerically-indexed array's keys are in ascending order.
     *
     * @param array $array
     * @return bool
     * @since 3.4.0
     */
    public static function isOrdered(array $array): bool
    {
        $lastKey = null;
        foreach (array_keys($array) as $key) {
            if (is_string($key)) {
                // Associative arrays don't have an order
                return false;
            }

            if ($lastKey !== null) {
                if ($key < $lastKey) {
                    return false;
                }
            }

            $lastKey = $key;
        }

        return true;
    }

    /**
     * Returns whether all the elements in the array are numeric.
     *
     * @param array $array
     * @return bool
     * @since 3.5.0
     */
    public static function isNumeric(array $array): bool
    {
        return Collection::make($array)->every(fn($v) => is_numeric($v));
    }

    /**
     * @inheritdoc
     *
     * If the key is specified in square bracket notation (e.g. `x[y][z]`), it will automatically be converted
     * to dot notation (`x.y.z`).
     */
    public static function getValue($array, $key, $default = null)
    {
        // Normalize the key into dot notation
        if (is_string($key) && preg_match('/^[\w\-]+(?:\[[^\[\]]+\])+$/', $key)) {
            $key = rtrim(preg_replace('/[\[\]]+/', '.', $key), '.');
        }

        return parent::getValue($array, $key, $default);
    }

    /**
     * @inheritdoc
     * @param array $array the array where to look the value from
     * @param mixed $value the value to remove from the array
     * @param bool $strict whether a strict type comparison should be used when checking array element values against $value
     * @return array the items that were removed from the array
     * @since 4.2.0
     */
    public static function removeValue(&$array, $value, bool $strict = false)
    {
        if (is_object($value)) {
            $strict = true;
        }

        $result = [];

        if (is_array($array)) {
            foreach ($array as $key => $val) {
                if (
                    (($strict || is_object($val)) && $val === $value) ||
                    (!$strict && !is_object($val) && $val == $value)
                ) {
                    $result[$key] = $val;
                    unset($array[$key]);
                }
            }
        }

        return $result;
    }
}
