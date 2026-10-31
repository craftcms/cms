<?php

declare(strict_types=1);

namespace CraftCms\Cms\Import;

use CraftCms\Cms\Import\Commands\RunImportPlanCommand;
use CraftCms\Cms\Import\Commands\UnlockImportPlanCommand;
use CraftCms\Cms\SystemMessage\Commands\ImportSystemMessagesCommand;
use Illuminate\Support\ServiceProvider;
use Override;

/**
 * @since 6.0.0
 */
class ImportServiceProvider extends ServiceProvider
{
    #[Override]
    public function register(): void
    {
        $this->registerLogChannel();
    }

    /**
     * Registers the run/unlock import plan and system messages import commands; the other import commands are registered by their domains.
     */
    public function boot(): void
    {
        $this->commands([
            RunImportPlanCommand::class,
            UnlockImportPlanCommand::class,
            ImportSystemMessagesCommand::class,
        ]);
    }

    /**
     * Adds a daily `import` log channel to the logging config if not already defined.
     */
    private function registerLogChannel(): void
    {
        $channels = config('logging.channels', []);

        if (! isset($channels['import'])) {
            $channels['import'] = [
                'driver' => 'daily',
                'path' => storage_path('logs/import.log'),
                'level' => 'debug',
                'days' => 14,
            ];

            config()->set('logging.channels', $channels);
        }
    }
}
