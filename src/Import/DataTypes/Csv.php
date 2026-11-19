<?php

declare(strict_types=1);

namespace CraftCms\Cms\Import\DataTypes;

use CraftCms\Cms\Ui\Controls\Choice;
use CraftCms\Cms\Ui\Controls\Text;
use CraftCms\Cms\Ui\Nodes\Field;
use InvalidArgumentException;
use Override;
use PhpOffice\PhpSpreadsheet\Reader\Csv as CsvReader;

use function CraftCms\Cms\t;

/**
 * @since 6.0.0
 */
class Csv implements DataTypeInterface
{
    /**
     * The delimiters a file can be read with, keyed by the name the import step's `delimiter` setting holds.
     */
    public const array DELIMITERS = [
        'comma' => ',',
        'tab' => "\t",
        'semicolon' => ';',
        'pipe' => '|',
    ];

    #[Override]
    public static function displayName(): string
    {
        return t('CSV');
    }

    #[Override]
    public static function extensions(): array
    {
        return ['csv', 'tsv', 'txt'];
    }

    #[Override]
    public static function matches(string $sample): bool
    {
        $start = DataTypes::sampleStart($sample);

        // anything that isn't binary or opened like JSON or XML could be a heading row
        return $start !== ''
            && ! str_contains($sample, "\0")
            && ! in_array($start[0], ['[', '{', '<'], true);
    }

    #[Override]
    public static function settingsUi(array $settings, ?string $extension): array
    {
        return [
            Field::make(t('Column Delimiter'), Choice::make('delimiter')
                ->options([
                    ['label' => t('Comma'), 'value' => 'comma'],
                    ['label' => t('Tab'), 'value' => 'tab'],
                    ['label' => t('Semicolon'), 'value' => 'semicolon'],
                    ['label' => t('Pipe'), 'value' => 'pipe'],
                ])
                ->value($settings['delimiter'] ?? self::defaultDelimiter($extension)))
                ->instructions(t('The character that separates the values in each row of CSV data.')),
            Field::make(t('List Separator'), Text::make('listSeparator')
                ->value($settings['listSeparator'] ?? null))
                ->instructions(t('The character that separates multiple values within one cell, such as several author IDs. Only used for fields and attributes that take more than one value.')),
        ];
    }

    #[Override]
    public static function getSettingsRules(): array
    {
        return [
            'delimiter' => ['nullable', 'string', 'in:'.implode(',', array_keys(self::DELIMITERS))],
            'listSeparator' => ['nullable', 'string', 'max:16'],
        ];
    }

    #[Override]
    public static function splitList(string $value, array $settings): ?array
    {
        $separator = $settings['listSeparator'] ?? null;

        if (! is_string($separator) || $separator === '') {
            return null;
        }

        return array_values(array_filter(
            array_map(trim(...), explode($separator, $value)),
            fn (string $item) => $item !== '',
        ));
    }

    /**
     * Returns the name of the delimiter a file is read with when its import step doesn't set one.
     *
     * @param  string|null  $extension  The file's extension.
     */
    public static function defaultDelimiter(?string $extension): string
    {
        return strtolower((string) $extension) === 'tsv' ? 'tab' : 'comma';
    }

    #[Override]
    public static function format(string $data, array $settings = [], ?string $extension = null): array
    {
        try {
            $array = self::getData($data, $settings, $extension);
        } catch (InvalidArgumentException $e) {
            $error = 'Invalid CSV: '.$e->getMessage();

            return ['success' => false, 'error' => $error];
        }

        $headings = array_shift($array);
        $body = array_map(fn ($row) => array_combine($headings, $row), $array);

        return ['success' => true, 'data' => $body];
    }

    #[Override]
    public static function getHeadings(string $data, array $settings = [], ?string $extension = null): array
    {
        try {
            $data = self::getData($data, $settings, $extension);
        } catch (InvalidArgumentException $e) {
            $error = 'Invalid CSV: '.$e->getMessage();

            return ['success' => false, 'error' => $error];
        }

        $keys = array_shift($data);
        $firstRow = $data[0] ?? null;

        $headings = [];
        foreach ($keys as $index => $key) {
            $hint = $firstRow[$index] ?? null;
            $headings[] = array_filter([
                'label' => $key,
                'value' => $key,
                'data' => $hint !== null && $hint !== '' ? ['hint' => (string) $hint] : null,
            ], fn ($value) => $value !== null);
        }

        usort($headings, fn ($a, $b) => $a['label'] <=> $b['label']);

        return $headings;
    }

    /**
     * Uses PhpSpreadsheet's CSV reader to load the string into a 2D array.
     *
     * @param  array<string, mixed>  $settings
     * @return array<int, array<int, mixed>>
     */
    private static function getData(string $data, array $settings, ?string $extension): array
    {
        $delimiter = $settings['delimiter'] ?? self::defaultDelimiter($extension);

        $reader = new CsvReader;
        $reader->setDelimiter(self::DELIMITERS[$delimiter] ?? throw new InvalidArgumentException("Unknown delimiter: $delimiter"));
        $reader->setReadDataOnly(true);

        $spreadsheet = $reader->loadSpreadsheetFromString($data);
        $rows = $spreadsheet->getSheet(0)->toArray();

        // the spreadsheet and its worksheets reference each other, so PHP wouldn't free them otherwise
        $spreadsheet->disconnectWorksheets();

        return $rows;
    }
}
