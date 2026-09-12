<?php

declare(strict_types=1);

namespace CraftCms\Cms\Support\Facades;

use CraftCms\Cms\Import\Importers\BaseImporter;
use Illuminate\Support\Collection as LaravelCollection;
use Illuminate\Support\Facades\Facade;

/**
 * @method static BaseImporter createImporter(array $config)
 * @method static LaravelCollection getAllConfigs()
 * @method static LaravelCollection getEditableConfigs()
 * @method static LaravelCollection getNonEditableConfigs()
 * @method static ?BaseImporter getConfigByHandle(?string $handle, bool $editableOnly = false)
 * @method static ?BaseImporter getConfigByUid(string $uid, bool $editableOnly = false)
 * @method static bool saveConfig(BaseImporter $importer)
 * @method static void duplicateConfig(BaseImporter $importer)
 * @method static void deleteConfig(BaseImporter $importer)
 *
 * @see \CraftCms\Cms\Import\ImportConfig
 */
class ImportConfig extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return \CraftCms\Cms\Import\ImportConfig::class;
    }
}
