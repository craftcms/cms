<?php

declare(strict_types=1);

namespace Workbench\Database\Seeders;

use CraftCms\Cms\Import\ImportPlans;
use CraftCms\Cms\Support\Json;
use Illuminate\Database\Seeder;
use RuntimeException;
use Workbench\App\Import\GuidePlans;

/**
 * Saves the workbench's editable import plans, unless one with the same handle is already there.
 */
class ImportPlansSeeder extends Seeder
{
    public function run(): void
    {
        $plans = app(ImportPlans::class);

        foreach (GuidePlans::editable() as $plan) {
            if ($plans->getImportPlanByHandle($plan->handle) !== null) {
                continue;
            }

            if (! $plans->saveImportPlan($plan)) {
                throw new RuntimeException("Failed to create the {$plan->name} import plan: ".Json::encode($plan->errors()->all()));
            }
        }
    }
}
