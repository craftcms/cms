<?php

declare(strict_types=1);

namespace CraftCms\Cms\Import\DataTypes;

interface DataTypeInterface
{
    /**
     * Formats the data string according to the Data Type rules.
     *
     * @param string $data The raw file contents to parse.
     * @return array
     */
    public static function format(string $data): array;

    /**
     * Returns a list of unique column names/headings/properties present in the data.
     *
     * @param string $data  he raw file contents to parse.
     * @return array
     */
    public static function getHeadings(string $data): array;
}
