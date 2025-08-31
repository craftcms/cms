<?php

namespace CraftCms\Cms\Deprecator\Commands;

use CraftCms\Cms\Console\CraftCommand;
use CraftCms\Cms\Deprecator\Deprecator;
use Illuminate\Console\Command;

/**
 * @since 6.0.0
 */
final class ClearDeprecations extends Command
{
    use CraftCommand;

    protected $signature = 'craft:clear-deprecations';

    protected $description = 'Clears all deprecation warnings.';

    public function handle(Deprecator $deprecator): void
    {
        $this->components->task('Clearing all deprecation logs', function () use ($deprecator) {
            $deprecator->deleteAllLogs();
        });
    }
}
