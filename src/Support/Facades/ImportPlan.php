<?php

declare(strict_types=1);

namespace CraftCms\Cms\Support\Facades;

use CraftCms\Cms\Import\Data\ImportPlan as ImportPlanData;
use CraftCms\Cms\Import\Importers\BaseImporter;
use Illuminate\Support\Collection as LaravelCollection;
use Illuminate\Support\Facades\Facade;
use Override;

/**
 * @method static ?BaseImporter createImporter(array $step)
 * @method static LaravelCollection getAllImportPlans()
 * @method static LaravelCollection getEditableImportPlans()
 * @method static LaravelCollection getNonEditableImportPlans()
 * @method static ?ImportPlanData getImportPlanByHandle(?string $handle, bool $editableOnly = false)
 * @method static ?ImportPlanData getImportPlanByUid(string $uid, bool $editableOnly = false)
 * @method static bool saveImportPlan(ImportPlanData $importPlan)
 * @method static bool duplicateImportPlan(ImportPlanData $importPlan)
 * @method static void deleteImportPlan(ImportPlanData $importPlan)
 *
 * @see \CraftCms\Cms\Import\ImportPlan
 */
class ImportPlan extends Facade
{
    #[Override]
    protected static function getFacadeAccessor(): string
    {
        return \CraftCms\Cms\Import\ImportPlan::class;
    }
}
