<?php

declare(strict_types=1);

namespace CraftCms\Cms\Import\DataTypes;

use CraftCms\Cms\Ui\Contracts\Node;

interface DataTypeInterface
{
    /**
     * Returns the data type's name, shown when a file could be read as more than one data type.
     */
    public static function displayName(): string;

    /**
     * Returns the file extensions this data type parses, without leading dots.
     *
     * Other data types may claim the same extensions; a file whose extension is claimed by more than one
     * is read as the one whose `matches()` accepts its contents, or the one chosen for the import step.
     *
     * @return non-empty-list<string>
     */
    public static function extensions(): array;

    /**
     * Returns whether the start of a file looks like this data type.
     *
     * Only called for files whose extension is claimed by more than one data type, so it can be a quick check.
     *
     * @param  string  $sample  The first few kilobytes of the file.
     */
    public static function matches(string $sample): bool;

    /**
     * Returns the fields for the import step settings this data type reads files with, such as a delimiter.
     *
     * They're only shown once the step's source is known to be read as this data type, and are scoped under the
     * step's `settings.dataTypeSettings`, so each field's path is the name of the setting it holds.
     *
     * @param  array<string, mixed>  $settings  The step's current settings for this data type.
     * @param  string|null  $extension  The source's file extension, if it has one.
     * @return list<Node>
     */
    public static function settingsUi(array $settings, ?string $extension): array;

    /**
     * Returns the validation rules for this data type's settings, keyed by setting name.
     *
     * Only the settings named here are passed to `format()` and `getHeadings()`.
     *
     * @return array<string, list<mixed>|string>
     */
    public static function getSettingsRules(): array;

    /**
     * Splits a value into the list of values it holds, for a field or attribute that takes more than one value.
     *
     * Only called with a string; data types whose data can hold lists itself (e.g. JSON arrays) return null.
     *
     * @param  string  $value  The incoming value.
     * @param  array<string, mixed>  $settings  The import step's settings for this data type.
     * @return list<string>|null The values, or null if the value isn't to be split.
     */
    public static function splitList(string $value, array $settings): ?array;

    /**
     * Formats the data string according to the Data Type rules.
     *
     * @param  string  $data  The raw file contents to parse.
     * @param  array<string, mixed>  $settings  The import step's settings for this data type.
     * @param  string|null  $extension  The file's extension.
     * @return array{success: true, data: array<mixed>}|array{success: false, error: string}
     */
    public static function format(string $data, array $settings = [], ?string $extension = null): array;

    /**
     * Returns a list of unique column names/headings/properties present in the data.
     *
     * @param  string  $data  The raw file contents to parse.
     * @param  array<string, mixed>  $settings  The import step's settings for this data type.
     * @param  string|null  $extension  The file's extension.
     * @return list<array{label: string, value: string, data?: array{hint: string}}>|array{success: false, error: string}
     */
    public static function getHeadings(string $data, array $settings = [], ?string $extension = null): array;
}
