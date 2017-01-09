<?php
/**
 * @link      https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license   https://craftcms.com/license
 */

namespace craft\helpers;

/**
 * Class ArrayHelper
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since  3.0
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

        if (is_string($object)) {
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
            $object = array_merge(array_filter($object));

            return $object;
        }

        return parent::toArray($object, $properties, $recursive);
    }

    /**
     * Prepends or appends a value to an array.
     *
     * @param array &$arr
     * @param mixed $value
     *
     * @param bool  $prepend
     */
    public static function prependOrAppend(array &$arr, $value, bool $prepend)
    {
        if ($prepend) {
            array_unshift($arr, $value);
        } else {
            $arr[] = $value;
        }
    }

    /**
     * Filters empty strings from an array.
     *
     * @param array $arr
     *
     * @return array
     */
    public static function filterEmptyStringsFromArray(array $arr): array
    {
        return array_filter($arr, [ArrayHelper::class, '_isNotAnEmptyString']);
    }

    /**
     * Returns the first key in a given array.
     *
     * @param array $arr
     *
     * @return string|int|null The first key, whether that is a number (if the array is numerically indexed) or a string, or null if $arr isn’t an array, or is empty.
     */
    public static function firstKey(array $arr)
    {
        if (is_array($arr)) {
            /** @noinspection LoopWhichDoesNotLoopInspection */
            foreach ($arr as $key => $value) {
                return $key;
            }
        }

        return null;
    }

    /**
     * Returns the first value in a given array.
     *
     * @param array $arr
     *
     * @return mixed|null
     */
    public static function firstValue(array $arr)
    {
        if (is_array($arr)) {
            /** @noinspection LoopWhichDoesNotLoopInspection */
            foreach ($arr as $value) {
                return $value;
            }
        }

        return null;
    }

    /**
     * Renames an item in an array. If the new key already exists in the array and the old key doesn’t,
     * the array will be left unchanged.
     *
     * @param array  $array   the array to extract value from
     * @param string $oldKey  old key name of the array element
     * @param string $newKey  new key name of the array element
     * @param mixed  $default the default value to be set if the specified old key does not exist
     *
     * @return void
     */
    public static function rename(array &$array, string $oldKey, string $newKey, $default = null)
    {
        if (is_array($array) && (!array_key_exists($newKey, $array) || array_key_exists($oldKey, $array))) {
            $array[$newKey] = static::remove($array, $oldKey, $default);
        }
    }

    // Private Methods
    // =========================================================================

    /**
     * The array_filter() callback function for filterEmptyStringsFromArray().
     *
     * @param string $val
     *
     * @return bool
     */
    private static function _isNotAnEmptyString(string $val): bool
    {
        return (mb_strlen($val) !== 0);
    }
}
