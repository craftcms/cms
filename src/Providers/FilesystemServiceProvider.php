<?php

declare(strict_types=1);

namespace CraftCms\Cms\Providers;

use craft\helpers\App;
use CraftCms\Cms\Filesystem\Filesystems;
use Illuminate\Contracts\Config\Repository as ConfigRepository;
use Illuminate\Support\ServiceProvider;

final class FilesystemServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $config = $this->app->make(ConfigRepository::class);

        $config->set('filesystems.disks.craft-tmp', [
            'driver' => 'local',
            'root' => App::isEphemeral() ? '/tmp' : storage_path('app/temp'),
        ]);

        $this->app->booted(fn () => app(Filesystems::class)->syncDisks());
    }
}
