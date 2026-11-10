<?php

declare(strict_types=1);

namespace CraftCms\Cms\Import\DataTypes;

use CraftCms\Cms\Support\Json as JsonHelper;
use InvalidArgumentException;
use Override;

use function CraftCms\Cms\t;

/**
 * @since 6.0.0
 */
class Json implements DataTypeInterface
{
    #[Override]
    public static function displayName(): string
    {
        return t('JSON');
    }

    #[Override]
    public static function extensions(): array
    {
        return ['json'];
    }

    #[Override]
    public static function matches(string $sample): bool
    {
        $start = DataTypes::sampleStart($sample);

        return str_starts_with($start, '[') || str_starts_with($start, '{');
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
    public static function getHeadings(string $data, array $settings = [], ?string $extension = null): array
    {
        try {
            $array = self::getData($data);
        } catch (InvalidArgumentException $e) {
            $error = 'Invalid JSON: '.$e->getMessage();

            return ['success' => false, 'error' => $error];
        }

        return DataTypes::dottedHeadings($array);
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
