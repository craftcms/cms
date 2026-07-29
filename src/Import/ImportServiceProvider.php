<?php

declare(strict_types=1);

namespace CraftCms\Cms\Import;

use CraftCms\Cms\Import\Commands\Element;
use Illuminate\Support\ServiceProvider;
use Override;

class ImportServiceProvider extends ServiceProvider
{
    #[Override]
    public function register(): void
    {
        $this->registerLogChannel();
    }

    public function boot(): void
    {
        $this->commands([
            Element::class,
        ]);
    }

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
