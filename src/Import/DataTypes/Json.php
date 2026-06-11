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

        $keys = static::collectUniqueKeys($array);
        array_walk($keys, function (&$value, $key) {
            $value = ['label' => $key, 'value' => $key, 'children' => $value];
        });

        return $keys;
    }

    private static function getData(string $data): array
    {
        return JsonHelper::decode($data);
    }

    private static function collectUniqueKeys(array $items): array
    {
        $keys = [];

        foreach ($items as $item) {
            if (! is_array($item)) {
                continue;
            }

            static::collectKeysFromArray($item, $keys);
        }

        return $keys;
    }

    private static function collectKeysFromArray(array $array, array &$keys): void
    {
        foreach ($array as $key => $value) {
            $keys[$key] ??= [];

            if (is_array($value)) {
                static::collectKeysFromArray($value, $keys[$key]);
            }
        }
    }
}
