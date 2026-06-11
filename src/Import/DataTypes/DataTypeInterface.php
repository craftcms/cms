<?php

declare(strict_types=1);

namespace CraftCms\Cms\Import\DataTypes;

interface DataTypeInterface
{
    /**
     * Formats the data string according to the Data Type rules.
     */
    public static function format(string $data): array;

    /**
     * Returns a list of unique column names/headings/properties present in the data.
     */
    public static function getHeadings(string $data): array;
}
