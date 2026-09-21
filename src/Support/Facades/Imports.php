<?php

declare(strict_types=1);

namespace CraftCms\Cms\Support\Facades;

use CraftCms\Cms\Import\Data\Import as ImportData;
use CraftCms\Cms\Import\Importers\BaseImporter;
use Illuminate\Support\Collection as LaravelCollection;
use Illuminate\Support\Facades\Facade;

/**
 * @method static BaseImporter createImporter(array $step)
 * @method static LaravelCollection getAllImports()
 * @method static LaravelCollection getEditableImports()
 * @method static LaravelCollection getNonEditableImports()
 * @method static ?ImportData getImportByHandle(?string $handle, bool $editableOnly = false)
 * @method static ?ImportData getImportByUid(string $uid, bool $editableOnly = false)
 * @method static bool saveImport(ImportData $import)
 * @method static void duplicateImport(ImportData $import)
 * @method static void deleteImport(ImportData $import)
 *
 * @see \CraftCms\Cms\Import\Imports
 */
class Imports extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return \CraftCms\Cms\Import\Imports::class;
    }
}
