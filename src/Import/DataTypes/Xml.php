<?php

declare(strict_types=1);

namespace CraftCms\Cms\Import\DataTypes;

use CraftCms\Cms\Support\Arr;
use CraftCms\Cms\Support\Json as JsonHelper;
use CraftCms\Cms\Support\Str;
use Exception;
use InvalidArgumentException;
use Override;

use function CraftCms\Cms\t;

/**
 * @since 6.0.0
 */
class Xml implements DataTypeInterface
{
    #[Override]
    public static function displayName(): string
    {
        return t('XML');
    }

    #[Override]
    public static function extensions(): array
    {
        return ['xml'];
    }

    #[Override]
    public static function matches(string $sample): bool
    {
        return str_starts_with(ltrim(Str::chopStart($sample, "\xEF\xBB\xBF")), '<');
    }

    #[Override]
    public static function settingsUi(array $settings, ?string $extension): array
    {
        return [];
    }

    #[Override]
    public static function getSettingsRules(): array
    {
        return [];
    }

    #[Override]
    public static function splitList(string $value, array $settings): ?array
    {
        return null;
    }

    #[Override]
    public static function format(string $data, array $settings = [], ?string $extension = null): array
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
    public static function getHeadings(string $data, array $settings = [], ?string $extension = null): array
    {
        try {
            $array = self::getData($data);
        } catch (Exception $e) {
            $error = "Invalid XML: {$e->getMessage()}";

            return ['success' => false, 'error' => $error];
        }

        // iterate through the array and get all unique properties;
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
     * Converts an XML string to array via SimpleXML + JSON round-trip, unwrapping the first key if it's itself an array.
     *
     * @return array<mixed>
     */
    private static function getData(string $data): array
    {
        $previous = libxml_use_internal_errors(true);

        try {
            $xmlObj = simplexml_load_string($data);
        } finally {
            libxml_clear_errors();
            libxml_use_internal_errors($previous);
        }

        if ($xmlObj === false) {
            throw new InvalidArgumentException('The data must be an XML document.');
        }

        $array = JsonHelper::decode(JsonHelper::encode($xmlObj));

        // an empty root element has no rows to unwrap
        if (! is_array($array) || $array === []) {
            return [];
        }

        $firstKey = array_key_first($array);
        if (is_array($array[$firstKey])) {
            return $array[$firstKey];
        }

        return $array;
    }
}
