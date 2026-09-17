<?php

declare(strict_types=1);

namespace CraftCms\Cms\Providers;

use CraftCms\Cms\Support\Facades\Path;
use Illuminate\Contracts\Config\Repository as ConfigRepository;
use Illuminate\Support\ServiceProvider;

class FilesystemServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $config = $this->app->make(ConfigRepository::class);

        $config->set('filesystems.disks.craft-tmp', [
            'driver' => 'local',
            'root' => app()->isEphemeral() ? '/tmp' : storage_path('app/temp'),
        ]);

        $config->set('filesystems.disks.craft-asset-temp', [
            'driver' => 'local',
            'root' => Path::tempAssetUploads(),
        ]);
    }
}
