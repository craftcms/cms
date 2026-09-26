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

        $headings = [];
        foreach ($keys as $key) {
            $sample = Arr::sampleValueAtDotifiedKey($array, $key);
            $hint = is_scalar($sample) && $sample !== '' ? (string) $sample : null;
            $headings[] = array_filter([
                'label' => $key,
                'value' => $key,
                'data' => $hint !== null ? ['hint' => $hint] : null,
            ], fn ($value) => $value !== null);
        }

        usort($headings, fn ($a, $b) => $a['label'] <=> $b['label']);

        return $headings;
    }

    /**
     * Thin wrapper around the app's JsonHelper::decode.
     */
    private static function getData(string $data): array
    {
        return JsonHelper::decode($data);
    }

    /**
     * Recursively walks a nested array collecting keys by reference.
     */
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
