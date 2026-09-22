<?php

declare(strict_types=1);

namespace CraftCms\Cms\Import;

use CraftCms\Cms\Asset\Commands\ImportAsset;
use CraftCms\Cms\Entry\Commands\ImportEntry;
use CraftCms\Cms\SystemMessage\Commands\ImportSystemMessage;
use CraftCms\Cms\User\Commands\ImportUser;
use Illuminate\Support\ServiceProvider;
use Override;

class ImportServiceProvider extends ServiceProvider
{
    #[Override]
    public function register(): void
    {
        $this->registerLogChannel();
    }

    /**
     * Registers the import-related artisan command.
     */
    public function boot(): void
    {
        $this->commands([
            ImportAsset::class,
            ImportEntry::class,
            ImportUser::class,
            ImportSystemMessage::class,
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
