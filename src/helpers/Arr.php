<?php

namespace craft\helpers;

use Illuminate\Support\Collection;

/**
 * Class Arr
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 5.x
 */
class Arr extends \Illuminate\Support\Arr
{
    /**
     * Merges two or more arrays into one recursively.
     *
     * If each array has an element with the same string key value, the latter
     * will overwrite the former (different from array_merge_recursive).
     *
     * Recursive merging will be conducted if both arrays have an element of array
     * type and are having the same key.
     *
     * For integer-keyed elements, the elements from the latter array will
     * be appended to the former array.
     *
     * @param array ...$arrays The arrays to merge. The first array will be merged to.
     * @return array the merged array (the original arrays are not changed.)
     */
    public static function merge(array ...$arrays): array
    {
        $result = array_shift($arrays);

        while (!empty($arrays)) {
            foreach (array_shift($arrays) as $key => $value) {
                if (is_int($key) && array_key_exists($key, $result)) {
                    $result[] = $value;
                    continue;
                }

                if (!is_int($key) && is_array($value) && isset($result[$key]) && is_array($result[$key])) {
                    $result[$key] = static::merge($result[$key], $value);
                    continue;
                }

                $result[$key] = $value;
            }
        }

        return $result;
    }

    /**
     * @inheritdoc
     *
     * If the key is specified in square bracket notation (e.g. `x[y][z]`), it will automatically be converted
     * to dot notation (`x.y.z`).
     */
    public static function get($array, $key, $default = null)
    {
        // Normalize the key into dot notation
        if (is_string($key) && preg_match('/^[\w\-]+(?:\[[^\[\]]+\])+$/', $key)) {
            $key = rtrim(preg_replace('/[\[\]]+/', '.', $key), '.');
        }

        return parent::get($array, $key, $default);
    }

    /**
     * Filter items where the value is not empty.
     *
     * @param  array  $array
     * @return array
     */
    public static function whereNotEmpty(array $array): array
    {
        return static::where($array, fn($value) => $value !== '');
    }

    /**
     * Checks whether a numerically-indexed array's keys are in ascending order.
     *
     * @param array $array
     * @return bool
     * @since 5.x
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
     * @since 5.x
     */
    public static function isNumeric(array $array): bool
    {
        return Collection::make($array)->every(fn($v) => is_numeric($v));
    }

    /**
     * Returns whether all the elements in the array are integers.
     *
     * @param array $array
     * @return bool
     * @since 5.x
     */
    public static function isIndexed(array $array): bool
    {
        return Collection::make($array)->every(fn($v) => is_int($v));
    }
}
