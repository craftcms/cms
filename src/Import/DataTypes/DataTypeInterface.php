<?php

declare(strict_types=1);

namespace CraftCms\Cms\Import\DataTypes;

interface DataTypeInterface
{
    /**
     * Formats the data string according to the Data Type rules.
     *
     * @param  string  $data  The raw file contents to parse.
     * @return array{success: true, data: array<mixed>}|array{success: false, error: string}
     */
    public static function format(string $data): array;

    /**
     * Returns a list of unique column names/headings/properties present in the data.
     *
     * @param  string  $data  The raw file contents to parse.
     * @return list<array{label: string, value: string, data?: array{hint: string}}>|array{success: false, error: string}
     */
    public static function getHeadings(string $data): array;
}
