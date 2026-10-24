<?php

declare(strict_types=1);

namespace CraftCms\Cms\Import\Commands\Concerns;

use Closure;
use CraftCms\Cms\Import\Data\ImportPlan as ImportPlanData;
use CraftCms\Cms\Support\Facades\ImportPlans;
use Illuminate\Console\Command;

use function Laravel\Prompts\select;

/**
 * @mixin Command
 */
trait ResolvesImportPlan
{
    /**
     * Returns the import plan named by the `plan` argument, or outputs an error and returns null if it doesn't exist.
     */
    private function resolveImportPlan(): ?ImportPlanData
    {
        $importPlan = ImportPlans::getImportPlanByHandleOrUid($this->argument('plan'));

        if ($importPlan === null) {
            $this->components->error("Import plan “{$this->argument('plan')}” not found.");
        }

        return $importPlan;
    }

    /** @return array<string, Closure> */
    protected function promptForMissingArgumentsUsing(): array
    {
        return [
            'plan' => fn () => select(
                label: 'Which import plan?',
                options: ImportPlans::getAllImportPlans()
                    ->mapWithKeys(fn (ImportPlanData $importPlan) => [$importPlan->handle => $importPlan->name])
                    ->all(),
            ),
        ];
    }
}
