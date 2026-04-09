<?php

declare(strict_types=1);

namespace CraftCms\Cms\Support\Facades;

use CraftCms\Cms\Import\Data\ImportRun;
use CraftCms\Cms\Import\Importers\BaseImporter;
use Illuminate\Support\Collection as LaravelCollection;
use Illuminate\Support\Facades\Facade;

/**
 * @method static LaravelCollection getAllConfigs()
 * @method static LaravelCollection getEditableConfigs()
 * @method static LaravelCollection getNonEditableConfigs()
 * @method static ?BaseImporter getConfigByHandle(?string $handle, bool $editableOnly = false)
 * @method static ?BaseImporter getConfigByUid(string $uid, bool $editableOnly = false)
 * @method static bool saveConfig(BaseImporter $import)
 * @method static void deleteConfig(BaseImporter $import)
 * @method static LaravelCollection getImportRuns()
 * @method static ?ImportRun getImportRunByHandle(?string $handle)
 * @method static ?ImportRun getImportRunByUid(string $uid)
 * @method static bool saveRun(ImportRun $run)
 * @method static void deleteRun(ImportRun $run)
 * @method static void import(BaseImporter $config)
 * @method static array getTypes()
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
