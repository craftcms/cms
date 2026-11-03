<?php

declare(strict_types=1);

namespace CraftCms\Cms\Import\DataTypes;

use CraftCms\Cms\Support\Arr;
use CraftCms\Cms\Support\Json as JsonHelper;
use InvalidArgumentException;
use Override;

/**
 * @since 6.0.0
 */
class Json implements DataTypeInterface
{
    #[Override]
    public static function extension(): string
    {
        return 'json';
    }

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
        $lists = self::listKeys($array);

        $headings = [];
        foreach ($keys as $key) {
            $sample = Arr::sampleValueAtDotifiedKey($array, $key);
            // as the previews show them, rather than `false` reading as nothing
            $sample = is_bool($sample) ? ($sample ? 'true' : 'false') : $sample;
            $hint = is_scalar($sample) && $sample !== '' ? (string) $sample : null;
            $headings[] = array_filter([
                'label' => self::label($key, $lists),
                'value' => $key,
                'data' => $hint !== null ? ['hint' => $hint] : null,
            ], fn ($value) => $value !== null);
        }

        usort($headings, fn ($a, $b) => $a['label'] <=> $b['label']);

        return $headings;
    }

    /**
     * Marks each part of a dot-notated key that holds a list with `[]`, so a heading shows
     * which of its values are iterated over: `content[].steps[].title`.
     *
     * @param  array<string, true>  $lists
     */
    private static function label(string $key, array $lists): string
    {
        $path = '';
        $label = '';

        foreach (explode('.', $key) as $segment) {
            $path = $path === '' ? $segment : "$path.$segment";
            $label .= ($label === '' ? '' : '.').$segment.(isset($lists[$path]) ? '[]' : '');
        }

        return $label;
    }

    /**
     * The dot-notated keys, as `uniqueDotifiedKeys()` writes them, whose value is a list in
     * any item — not just the first, which may leave a list empty or out.
     *
     * @param  array<array-key, mixed>  $array
     * @return array<string, true>
     */
    private static function listKeys(array $array, string $prepend = ''): array
    {
        $lists = [];

        foreach ($array as $key => $value) {
            if (! is_array($value)) {
                continue;
            }

            $path = is_int($key) ? $prepend : ($prepend === '' ? $key : "$prepend.$key");

            if (! is_int($key) && array_is_list($value)) {
                $lists[$path] = true;
            }

            $lists += self::listKeys($value, $path);
        }

        return $lists;
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
