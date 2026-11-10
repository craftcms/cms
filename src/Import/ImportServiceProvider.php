<?php

declare(strict_types=1);

namespace CraftCms\Cms\Import;

use CraftCms\Cms\Import\Commands\RunImportPlanCommand;
use CraftCms\Cms\Import\Commands\UnlockImportPlanCommand;
use CraftCms\Cms\Import\Events\ImportFinished;
use CraftCms\Cms\Import\Events\ImportPlanSaved;
use CraftCms\Cms\SystemMessage\Commands\ImportSystemMessagesCommand;
use Illuminate\Support\Facades\Event;
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
     * Forgets steps' preview results once they're out of date.
     */
    public function boot(): void
    {
        $this->commands([
            RunImportPlanCommand::class,
            UnlockImportPlanCommand::class,
            ImportSystemMessagesCommand::class,
        ]);

        // a plan's steps can change, and a run changes the content they're previewed against
        Event::listen(fn (ImportPlanSaved $event) => app(ImportPreviews::class)->forgetSteps($event->importPlan->steps ?? []));
        Event::listen(fn (ImportFinished $event) => app(ImportPreviews::class)->forgetSteps($event->steps));
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
