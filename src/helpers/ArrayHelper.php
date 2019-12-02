<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\helpers;

use Craft;

/**
 * Class ArrayHelper
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0.0
 */
class ArrayHelper extends \yii\helpers\ArrayHelper
{
    // Public Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    public static function toArray($object, $properties = [], $recursive = true): array
    {
        if ($object === null) {
            return [];
        }

        if (is_string($object) && strpos($object, ',') !== false) {
            Craft::$app->getDeprecator()->log('ArrayHelper::toArray(string)', 'Passing a string to ArrayHelper::toArray() has been deprecated. Use StringHelper::split() instead.');

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
     * Prepends or appends a value to an array.
     *
     * @param array &$array the array to be prepended/appended to
     * @param mixed $value the value to prepend/append to the array
     * @param bool $prepend `true` will prepend the value; `false` will append it
     */
    public static function prependOrAppend(array &$array, $value, bool $prepend)
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
     * Array keys are preserved.
     *
     * @param array|\Traversable $array the array that needs to be indexed or grouped
     * @param string|\Closure $key the column name or anonymous function which result will be used to index the array
     * @param mixed $value the value that $key should be compared with
     * @param bool $strict whether a strict type comparison should be used when checking array element values against $value
     * @return array the filtered array
     * @deprecated in 3.2.0. Use [[where()]] instead.
     */
    public static function filterByValue($array, $key, $value = true, bool $strict = false): array
    {
        return static::where($array, $key, $value, $strict);
    }

    /**
     * Filters an array to only the values where a given key (the name of a
     * sub-array key or sub-object property) is set to a given value.
     * Array keys are preserved.
     *
     * @param array|\Traversable $array the array that needs to be indexed or grouped
     * @param string|\Closure $key the column name or anonymous function which result will be used to index the array
     * @param mixed $value the value that $key should be compared with
     * @param bool $strict whether a strict type comparison should be used when checking array element values against $value
     * @return array the filtered array
     */
    public static function where($array, $key, $value = true, bool $strict = false): array
    {
        $result = [];

        foreach ($array as $i => $element) {
            $elementValue = static::getValue($element, $key);
            /** @noinspection TypeUnsafeComparisonInspection */
            if (($strict && $elementValue === $value) || (!$strict && $elementValue == $value)) {
                $result[$i] = $element;
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
     * @param array|\Traversable $array the array that needs to be indexed or grouped
     * @param array $conditions An array of key/value pairs of allowed values. Values can be arrays to allow multiple values.
     * @param bool $strict whether a strict type comparison should be used when checking array element values against $value
     * @return array the filtered array
     * @since 3.3.0
     */
    public static function whereMultiple($array, array $conditions, bool $strict = false): array
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
     * @param array|\Traversable $array the array that the value will be searched for in
     * @param string|\Closure $key the column name or anonymous function which must be set to $value
     * @param mixed $value the value that $key should be compared with
     * @param bool $strict whether a strict type comparison should be used when checking array element values against $value
     * @return mixed the value, or null if it can't be found
     * @since 3.1.0
     */
    public static function firstWhere($array, $key, $value = true, bool $strict = false)
    {
        foreach ($array as $i => $element) {
            $elementValue = static::getValue($element, $key);
            /** @noinspection TypeUnsafeComparisonInspection */
            if (($strict && $elementValue === $value) || (!$strict && $elementValue == $value)) {
                return $element;
            }
        }

        return null;
    }

    /**
     * Filters empty strings from an array.
     *
     * @param array $arr
     * @return array
     */
    public static function filterEmptyStringsFromArray(array $arr): array
    {
        return array_filter($arr, function($value): bool {
            return $value !== '';
        });
    }

    /**
     * Returns the first key in a given array.
     *
     * @param array $arr
     * @return string|int|null The first key, whether that is a number (if the array is numerically indexed) or a string, or null if $arr isn’t an array, or is empty.
     */
    public static function firstKey(array $arr)
    {
        /** @noinspection LoopWhichDoesNotLoopInspection */
        foreach ($arr as $key => $value) {
            return $key;
        }

        return null;
    }

    /**
     * Returns the first value in a given array.
     *
     * @param array $arr
     * @return mixed The first value, or null if $arr isn’t an array, or is empty.
     */
    public static function firstValue(array $arr)
    {
        return !empty($arr) ? reset($arr) : null;
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
    public static function rename(array &$array, string $oldKey, string $newKey, $default = null)
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
     * Returns a copy of an array without items with matching the given value.
     *
     * @param array $array
     * @param mixed $value
     * @return array
     */
    public static function withoutValue(array $array, $value): array
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
    public static function ensureNonAssociative(array &$array)
    {
        if (static::isAssociative($array, false)) {
            $array = array_values($array);
        }
    }
}
