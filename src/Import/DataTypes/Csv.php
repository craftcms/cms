<?php

declare(strict_types=1);

namespace CraftCms\Cms\Import\DataTypes;

use InvalidArgumentException;
use Override;
use PhpOffice\PhpSpreadsheet\Reader\Csv as CsvReader;

class Csv implements DataTypeInterface
{
    #[Override]
    public static function format(string $data): array
    {
        try {
            $array = self::getData($data);
        } catch (InvalidArgumentException $e) {
            $error = 'Invalid CSV: '.$e->getMessage();

            return ['success' => false, 'error' => $error];
        }

        $headings = array_shift($array);
        $body = array_map(fn ($row) => array_combine($headings, $row), $array);

        return ['success' => true, 'data' => $body];
    }

    #[Override]
    public static function getHeadings(string $data): array
    {
        try {
            $data = self::getData($data);
        } catch (InvalidArgumentException $e) {
            $error = 'Invalid CSV: '.$e->getMessage();

            return ['success' => false, 'error' => $error];
        }

        return array_shift($data);
    }

    private static function getData(string $data): array
    {
        $reader = new CsvReader;
        $reader->setDelimiter(',');
        $reader->setReadDataOnly(true);
        // $reader->setSheetIndex(0);

        $spreadsheet = $reader->loadSpreadsheetFromString($data);
        $sheet = $spreadsheet->getSheet(0);

        return $sheet->toArray();
    }
}
