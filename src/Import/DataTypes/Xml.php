<?php

declare(strict_types=1);

namespace CraftCms\Cms\Import\DataTypes;

use CraftCms\Cms\Support\Json as JsonHelper;
use Exception;
use Override;

class Xml implements DataTypeInterface
{
    #[Override]
    public static function format(string $data): array
    {
        try {
            $array = self::getData($data);
        } catch (Exception $e) {
            $error = "Invalid XML: {$e->getMessage()}";

            return ['success' => false, 'error' => $error];
        }

        return ['success' => true, 'data' => $array];
    }

    #[Override]
    public static function getHeadings(string $data): array
    {
        try {
            $array = self::getData($data);
        } catch (Exception $e) {
            $error = "Invalid XML: {$e->getMessage()}";

            return ['success' => false, 'error' => $error];
        }

        // iterate through the array and get all unique properties;
        // TODO: what about nested arrays?
        return $array
                |> (fn ($array) => array_map(array_keys(...), $array))
                |> (fn ($keys) => array_merge(...$keys))
                |> array_unique(...)
                |> array_values(...);
    }

    private static function getData(string $data): array
    {
        $xmlObj = simplexml_load_string($data);
        $array = JsonHelper::decode(json_encode($xmlObj));

        $firstKey = array_key_first($array);
        if (is_array($array[$firstKey])) {
            return $array[$firstKey];
        }

        return $array;
    }
}
