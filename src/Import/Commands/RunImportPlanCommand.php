<?php

declare(strict_types=1);

namespace CraftCms\Cms\Import\Commands;

use CraftCms\Cms\Console\CraftCommand;
use CraftCms\Cms\Import\Commands\Concerns\ResolvesImportPlan;
use CraftCms\Cms\Import\Data\ImportPlan as ImportPlanData;
use CraftCms\Cms\Import\Events\ImportFinished;
use CraftCms\Cms\Support\Facades\Import;
use Illuminate\Console\Command;
use Illuminate\Contracts\Console\PromptsForMissingInput;
use Illuminate\Support\Facades\Event;
use Override;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;

/**
 * Runs a saved or registered import plan, so it can be triggered from cron or Laravel's scheduler.
 *
 * @since 6.0.0
 */
class RunImportPlanCommand extends Command implements PromptsForMissingInput
{
    use CraftCommand;
    use ResolvesImportPlan;

    #[Override]
    protected $name = 'craft:import:run-plan';

    #[Override]
    protected $description = 'Runs an import plan.';

    #[Override]
    protected $aliases = ['import/run-plan'];

    #[Override]
    protected function configure(): void
    {
        $this->addArgument('plan', InputArgument::REQUIRED, 'The handle or UID of the import plan to run.')
            ->addOption('sync', null, InputOption::VALUE_NONE, 'Run the import in this process instead of queueing it.')
            ->addOption('force', null, InputOption::VALUE_NONE, 'Run the import plan even if it’s marked as running, e.g. after a run that didn’t finish.');
    }

    /**
     * Dispatches the import plan, or runs it in this process with `--sync`, failing if it can't be started or any of its items fail.
     */
    public function handle(): int
    {
        $importPlan = $this->resolveImportPlan();

        if ($importPlan === null) {
            return self::FAILURE;
        }

        if (Import::isRunning($importPlan)) {
            if (! $this->option('force')) {
                $this->components->error('This import plan is already running. Use --force if its last run didn’t finish.');

                return self::FAILURE;
            }

            $this->components->warn('Releasing the import plan’s lock from a previous run.');
            Import::unlock($importPlan);
        }

        $sync = (bool) $this->option('sync');
        $hasFailures = false;

        if ($sync) {
            Event::listen(function (ImportFinished $event) use ($importPlan, &$hasFailures) {
                if ($event->importPlan !== null && self::isSamePlan($event->importPlan, $importPlan)) {
                    $hasFailures = $event->hasFailures;
                }
            });
        }

        if (! Import::dispatchImport($importPlan, $sync ? 'sync' : null)) {
            $this->components->error('Import could not be started. See the import log for details.');

            return self::FAILURE;
        }

        if (! $sync) {
            $this->components->info('Import queued.');

            return self::SUCCESS;
        }

        if ($hasFailures) {
            $this->components->error('Import finished with failures. See the import log for details.');

            return self::FAILURE;
        }

        $this->components->info('Import finished.');

        return self::SUCCESS;
    }

    /**
     * Returns whether two import plan instances represent the same import plan.
     */
    private static function isSamePlan(ImportPlanData $a, ImportPlanData $b): bool
    {
        return $a->uid !== null && $b->uid !== null ? $a->uid === $b->uid : $a->handle === $b->handle;
    }
}
