<?php

declare(strict_types=1);

namespace CraftCms\Cms\Import\Commands;

use CraftCms\Cms\Console\CraftCommand;
use CraftCms\Cms\Import\Commands\Concerns\ResolvesImportPlan;
use CraftCms\Cms\Support\Facades\Import;
use Illuminate\Console\Command;
use Illuminate\Contracts\Console\PromptsForMissingInput;
use Override;
use Symfony\Component\Console\Input\InputArgument;

/**
 * Releases an import plan's run lock, for when a run stopped without finishing.
 *
 * @since 6.0.0
 */
class UnlockImportPlanCommand extends Command implements PromptsForMissingInput
{
    use CraftCommand;
    use ResolvesImportPlan;

    #[Override]
    protected $name = 'craft:import:unlock-plan';

    #[Override]
    protected $description = 'Releases an import plan’s lock so it can run again.';

    #[Override]
    protected $aliases = ['import/unlock-plan'];

    #[Override]
    protected function configure(): void
    {
        $this->addArgument('plan', InputArgument::REQUIRED, 'The handle or UID of the import plan to unlock.');
    }

    /**
     * Releases the import plan's lock if it's marked as running.
     */
    public function handle(): int
    {
        $importPlan = $this->resolveImportPlan();

        if ($importPlan === null) {
            return self::FAILURE;
        }

        if (! Import::isRunning($importPlan)) {
            $this->components->info('This import plan isn’t running.');

            return self::SUCCESS;
        }

        Import::unlock($importPlan);
        $this->components->info('Import plan unlocked.');

        return self::SUCCESS;
    }
}
