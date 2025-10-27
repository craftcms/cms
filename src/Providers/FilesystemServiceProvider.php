<?php

declare(strict_types=1);

namespace CraftCms\Cms\Providers;

use craft\helpers\App;
use Illuminate\Support\ServiceProvider;

final class FilesystemServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->app['config']->set('filesystems.disks.craft-tmp', [
            'driver' => 'local',
            'root' => App::isEphemeral() ? '/tmp' : storage_path('app/temp'),
        ]);
    }
}
