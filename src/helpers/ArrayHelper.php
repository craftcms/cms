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
 * @since 3.0
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
            $object = array_merge(array_filter($object, function($value) {
                return $value !== '';
            }));

            return $object;
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
     */
    public static function filterByValue($array, $key, $value = true, bool $strict = false): array
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
     * Returns the first value in a given array where a given key (the name of a
     * sub-array key or sub-object property) is set to a given value.
     *
     * @param array|\Traversable $array the array that the value will be searched for in
     * @param string|\Closure $key the column name or anonymous function which must be set to $value
     * @param mixed $value the value that $key should be compared with
     * @param bool $strict whether a strict type comparison should be used when checking array element values against $value
     * @return mixed the value, or null if it can't be found
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
     */
    public static function ensureNonAssociative(array &$array)
    {
        if (static::isAssociative($array, false)) {
            $array = array_values($array);
        }
    }
}
