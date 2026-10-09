<?php

declare(strict_types=1);

namespace CraftCms\Cms\Tests\Support\Import;

use CraftCms\Cms\Import\DataTypes\DataTypeInterface;
use Override;

/**
 * A data type for `.txt` files that reads each line as a row with a single `line` column.
 */
class TestDataType implements DataTypeInterface
{
    #[Override]
    public static function extension(): string
    {
        return 'txt';
    }

    #[Override]
    public static function format(string $data): array
    {
        $lines = array_values(array_filter(explode("\n", $data), fn (string $line) => $line !== ''));

        return ['success' => true, 'data' => array_map(fn (string $line) => ['line' => $line], $lines)];
    }

    #[Override]
    public static function getHeadings(string $data): array
    {
        return [['label' => 'line', 'value' => 'line']];
    }
}
