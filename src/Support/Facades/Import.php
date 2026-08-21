<?php

declare(strict_types=1);

namespace CraftCms\Cms\Support\Facades;

use CraftCms\Cms\Import\Data\ImportRun;
use CraftCms\Cms\Import\Importers\BaseImporter;
use Illuminate\Support\Facades\Facade;

/**
 * @method static array getAllDataTypes()
 * @method static array getAllImporterTypes()
 * @method static bool dispatchImport(ImportRun $run)
 * @method static void importItem(BaseImporter $importer, array $data)
 * @method static void import(BaseImporter $importer)
 * @method static string getRawData(string $filePath)
 * @method static array getFormattedData(string $filePath)
 * @method static ?array getDataHeadings(string $filePath)
 * @method static array processData(BaseImporter $importer, array $data, mixed $element)
 *
 * @see \CraftCms\Cms\Import\Import
 */
class Import extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return \CraftCms\Cms\Import\Import::class;
    }
}
