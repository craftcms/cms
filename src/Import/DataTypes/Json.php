<?php

declare(strict_types=1);

namespace CraftCms\Cms\Import\DataTypes;

use CraftCms\Cms\Support\Json as JsonHelper;
use InvalidArgumentException;
use Override;

class Json implements DataTypeInterface
{
    #[Override]
    public static function format(string $data): array
    {
        // Parse the JSON string
        try {
            $array = self::getData($data);
        } catch (InvalidArgumentException $e) {
            $error = 'Invalid JSON: '.$e->getMessage();

            return ['success' => false, 'error' => $error];
        }

        return ['success' => true, 'data' => $array];
    }

    #[Override]
    public static function getHeadings(string $data): array
    {
        try {
            $array = self::getData($data);
        } catch (InvalidArgumentException $e) {
            $error = 'Invalid JSON: '.$e->getMessage();

            return ['success' => false, 'error' => $error];
        }

        // iterate through the array and get all unique properties;
        // TODO: what about nested arrays?
        //        return $array
        //            |> (fn ($array) => array_map(array_keys(...), $array))
        //            |> (fn ($keys) => array_merge(...$keys))
        //            |> array_unique(...)
        //            |> array_values(...);

        //        $keys = static::collectUniqueKeys($array);
        $keys = self::flattenKeys($array);
        sort($keys);

        array_walk($keys, function (&$value, $key) {
            $value = ['label' => $value, 'value' => $value];
        });

        return $keys;
    }

    private static function flattenKeys(array $data, string $prefix = ''): array
    {
        $keys = [];

        foreach ($data as $key => $value) {
            $isListItem = is_int($key);
            $path = $isListItem
                ? $prefix
                : ($prefix === '' ? (string) $key : $prefix.'.'.$key);

            if (! $isListItem && $path !== '') {
                $keys[] = $path;
            }

            if (is_array($value)) {
                $keys = array_merge($keys, self::flattenKeys($value, $path));
            }
        }

        return array_values(array_unique($keys));
    }

    private static function getData(string $data): array
    {
        return JsonHelper::decode($data);
    }

    private static function collectKeysFromArray(array $array, array &$keys): void
    {
        foreach ($array as $key => $value) {
            $keys[$key] ??= [];

            if (is_array($value)) {
                self::collectKeysFromArray($value, $keys[$key]);
            }
        }
    }
}
