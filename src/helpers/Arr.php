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
     * If each array has an element with the same string key value, the latter
     * will overwrite the former (different from array_merge_recursive).
     * Recursive merging will be conducted if both arrays have an element of array
     * type and are having the same key.
     * For integer-keyed elements, the elements from the latter array will
     * be appended to the former array.
     * You can use [[UnsetArrayValue]] object to unset value from previous array or
     * [[ReplaceArrayValue]] to force replace former value instead of recursive merging.
     * @param array $a array to be merged to
     * @param array $b array to be merged from. You can specify additional
     * arrays via third argument, fourth argument etc.
     * @return array the merged array (the original arrays are not changed.)
     */
    public static function merge($a, $b)
    {
        $args = func_get_args();
        $res = array_shift($args);
        while (!empty($args)) {
            foreach (array_shift($args) as $k => $v) {
                if (is_int($k)) {
                    if (array_key_exists($k, $res)) {
                        $res[] = $v;
                    } else {
                        $res[$k] = $v;
                    }
                } elseif (is_array($v) && isset($res[$k]) && is_array($res[$k])) {
                    $res[$k] = static::merge($res[$k], $v);
                } else {
                    $res[$k] = $v;
                }
            }
        }

        return $res;
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
}
