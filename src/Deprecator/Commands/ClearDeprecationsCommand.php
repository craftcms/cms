<?php

declare(strict_types=1);

namespace CraftCms\Cms\Deprecator\Commands;

use CraftCms\Cms\Console\CraftCommand;
use CraftCms\Cms\Deprecator\Deprecator;
use Illuminate\Console\Command;

final class ClearDeprecationsCommand extends Command
{
    use CraftCommand;

    #[\Override]
    protected $signature = 'craft:clear-deprecations';

    #[\Override]
    protected $description = 'Clears all deprecation warnings.';

    public function handle(Deprecator $deprecator): void
    {
        $this->components->task(
            'Clearing all deprecation logs',
            fn () => $deprecator->deleteAllLogs(),
        );
    }
}
