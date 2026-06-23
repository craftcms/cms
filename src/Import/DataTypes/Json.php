<?php

declare(strict_types=1);

namespace CraftCms\Cms\Import\DataTypes;

use CraftCms\Cms\Support\Arr;
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

        //        $keys = static::collectUniqueKeys($array);
        $keys = Arr::uniqueDotifiedKeys($array);
        sort($keys);

        array_walk($keys, function (&$value, $key) {
            $value = ['label' => $value, 'value' => $value];
        });

        return $keys;
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
