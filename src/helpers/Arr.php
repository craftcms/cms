<?php

namespace craft\helpers;

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
     *
     * @param array $array array to be merged to
     * @param array ...$arrays one or more arrays to be merged from.
     *
     * @return array the merged array (the original arrays are not changed.)
     */
    public static function merge(array $array, array ...$arrays): array
    {
        foreach ($arrays as $k => $v) {
            if (is_int($k)) {
                if (array_key_exists($k, $array)) {
                    $array[] = $v;
                } else {
                    $array[$k] = $v;
                }
            } elseif (is_array($v) && isset($array[$k]) && is_array($array[$k])) {
                $array[$k] = static::merge($array[$k], $v);
            } else {
                $array[$k] = $v;
            }
        }

        return $array;
    }
}
