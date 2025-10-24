<?php

declare(strict_types=1);

namespace CraftCms\Cms\Support;

use CraftCms\Cms\Support\Facades\Deprecator;
use DateTimeInterface;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Support\Collection;

class Arr extends \Illuminate\Support\Arr
{
    /**
     * Converts an object or an array of objects into an array.
     *
     * @param  mixed  $object  the object to be converted into an array
     * @param  array  $properties  a mapping from object class names to the properties that need to put into the resulting arrays.
     *                             The properties specified for each class are an array of the following format:
     *
     * ```php
     * [
     *     'app\models\Post' => [
     *         'id',
     *         'title',
     *         // the key name in array result => property name
     *         'createTime' => 'created_at',
     *         // the key name in array result => anonymous function
     *         'length' => function ($post) {
     *             return strlen($post->content);
     *         },
     *     ],
     * ]
     * ```
     *
     * The result of `Arr::toArray($post, $properties)` could be like the following:
     *
     * ```php
     * [
     *     'id' => 123,
     *     'title' => 'test',
     *     'createTime' => '2013-01-01 12:00AM',
     *     'length' => 301,
     * ]
     * ```
     * @param  bool  $recursive  whether to recursively convert properties which are objects into arrays.
     * @return array the array representation of the object
     */
    public static function toArray(mixed $object, array $properties = [], bool $recursive = true): array
    {
        if ($object === null) {
            return [];
        }

        if (is_string($object) && str_contains($object, ',')) {
            Deprecator::log('Arr::toArray(string)', 'Passing a string to `Arr::toArray()` has been deprecated. Use `StringHelper::split()` instead.');

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
            return array_values(static::whereNotEmpty($object));
        }

        if (is_array($object)) {
            if (! $recursive) {
                return $object;
            }

            foreach ($object as $key => $value) {
                if (is_array($value) || is_object($value)) {
                    $object[$key] = static::toArray($value, $properties, true);
                }
            }

            return $object;
        }

        if ($object instanceof DateTimeInterface) {
            return (array) $object;
        }

        if (is_object($object)) {
            if (! empty($properties)) {
                $className = get_class($object);
                if (! empty($properties[$className])) {
                    $result = [];
                    foreach ($properties[$className] as $key => $name) {
                        if (is_int($key)) {
                            $result[$name] = $object->$name;
                        } else {
                            $result[$key] = static::get($object, $name);
                        }
                    }

                    return $recursive ? static::toArray($result, $properties) : $result;
                }
            }

            if ($object instanceof \yii\base\Arrayable) {
                $result = $object->toArray([], [], $recursive);
            } elseif ($object instanceof Arrayable || method_exists($object, 'toArray')) {
                $result = $object->toArray();
            } else {
                $result = [];

                foreach ($object as $key => $value) {
                    $result[$key] = $value;
                }
            }

            return $recursive ? static::toArray($result, $properties) : $result;
        }

        return [$object];
    }

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
     * @param  array  ...$arrays  The arrays to merge. The first array will be merged to.
     * @return array the merged array (the original arrays are not changed.)
     */
    public static function merge(array ...$arrays): array
    {
        $result = array_shift($arrays);

        while (! empty($arrays)) {
            foreach (array_shift($arrays) as $key => $value) {
                if (is_int($key) && array_key_exists($key, $result)) {
                    $result[] = $value;

                    continue;
                }

                if (! is_int($key) && is_array($value) && isset($result[$key]) && is_array($result[$key])) {
                    $result[$key] = static::merge($result[$key], $value);

                    continue;
                }

                $result[$key] = $value;
            }
        }

        return $result;
    }

    /**
     * {@inheritdoc}
     *
     * If the key is specified in square bracket notation (e.g. `x[y][z]`), it will automatically be converted
     * to dot notation (`x.y.z`).
     */
    #[\Override]
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
     */
    public static function whereNotEmpty(array $array): array
    {
        return static::where($array, fn ($value) => $value !== '');
    }

    /**
     * Checks whether a numerically-indexed array's keys are in ascending order.
     *
     * @since 6.x
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
     * @since 6.x
     */
    public static function isNumeric(array $array): bool
    {
        return Collection::make($array)->every(fn ($v) => is_numeric($v));
    }

    /**
     * Returns whether all the elements in the array are integers.
     *
     * @since 6.x
     */
    public static function isIndexed(array $array): bool
    {
        return Collection::make($array)->every(fn ($v) => is_int($v));
    }
}
