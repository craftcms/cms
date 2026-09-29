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
     *
     * @return array<mixed>
     */
    private static function getData(string $data): array
    {
        $array = JsonHelper::decode($data);

        if (! is_array($array)) {
            throw new InvalidArgumentException('The data must be a JSON array or object.');
        }

        return $array;
    }
}
