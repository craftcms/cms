<?php

declare(strict_types=1);

namespace CraftCms\Cms\Import\DataTypes;

use InvalidArgumentException;
use PhpOffice\PhpSpreadsheet\Reader\Csv as CsvReader;

class Csv implements DataTypeInterface
{
    /**
     * {@inheritdoc}
     */
    public static function format(string $data): array
    {
        try {
            $reader = new CsvReader;
            $reader->setDelimiter(',');
            $reader->setReadDataOnly(true);
            // $reader->setSheetIndex(0);

            $spreadsheet = $reader->loadSpreadsheetFromString($data);
            $sheet = $spreadsheet->getSheet(0);
            $data = $sheet->toArray();
            $headings = array_shift($data);
            $array = array_map(fn ($row) => array_combine($headings, $row), $data);
        } catch (InvalidArgumentException $e) {
            $error = 'Invalid CSV: '.$e->getMessage();

            return ['success' => false, 'error' => $error];
        }

        return ['success' => true, 'data' => $array];
    }
}
