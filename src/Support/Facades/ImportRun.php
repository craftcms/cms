<?php

declare(strict_types=1);

namespace CraftCms\Cms\Support\Facades;

use CraftCms\Cms\Import\Data\ImportRun as ImportRunData;
use Illuminate\Support\Collection as LaravelCollection;
use Illuminate\Support\Facades\Facade;

/**
 * @method static LaravelCollection getImportRuns()
 * @method static ?ImportRun getImportRunByHandle(?string $handle)
 * @method static ?ImportRun getImportRunByUid(string $uid)
 * @method static bool saveRun(ImportRunData $run)
 * @method static void deleteRun(ImportRunData $run)
 *
 * @see \CraftCms\Cms\Import\ImportRun
 */
class ImportRun extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return \CraftCms\Cms\Import\ImportRun::class;
    }
}
