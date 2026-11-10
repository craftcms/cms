<?php

declare(strict_types=1);

namespace CraftCms\Cms\Tests\Support\Import;

use CraftCms\Cms\Import\DataTypes\DataTypeInterface;
use CraftCms\Cms\Ui\Controls\Text;
use CraftCms\Cms\Ui\Nodes\Field;
use Override;

/**
 * A data type for `.txt` files that reads each line as a row with a single `line` column.
 *
 * It claims `.txt` files whose first line has no commas, so a comma-separated `.txt` file is only read as CSV.
 * Its `column` setting names the column.
 */
class TestDataType implements DataTypeInterface
{
    #[Override]
    public static function displayName(): string
    {
        return 'Lines';
    }

    #[Override]
    public static function extensions(): array
    {
        return ['txt'];
    }

    #[Override]
    public static function matches(string $sample): bool
    {
        return ! str_contains(strtok($sample, "\r\n") ?: '', ',');
    }

    #[Override]
    public static function settingsUi(array $settings, ?string $extension): array
    {
        return [
            Field::make('Column', Text::make('column')->value($settings['column'] ?? 'line')),
        ];
    }

    #[Override]
    public static function getSettingsRules(): array
    {
        return ['column' => ['nullable', 'string', 'max:20']];
    }

    #[Override]
    public static function splitList(string $value, array $settings): ?array
    {
        return null;
    }

    #[Override]
    public static function format(string $data, array $settings = [], ?string $extension = null): array
    {
        $column = $settings['column'] ?? 'line';
        $lines = array_values(array_filter(preg_split('/\R/', $data), fn (string $line) => $line !== ''));

        return ['success' => true, 'data' => array_map(fn (string $line) => [$column => $line], $lines)];
    }

    #[Override]
    public static function getHeadings(string $data, array $settings = [], ?string $extension = null): array
    {
        $column = $settings['column'] ?? 'line';

        return [['label' => $column, 'value' => $column]];
    }
}
