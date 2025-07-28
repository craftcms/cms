<?php

namespace CraftCms\Cms\Config;

use Illuminate\Support\ServiceProvider;
class ConfigServiceProvider extends ServiceProvider
{
    private array $configFiles = [
        'general',
    ];

    public function register(): void
    {
    }
    public function boot(): void
    {
        $this->bootPublishables();
    }

    private function bootPublishables(): void
    {
        if (! $this->app->runningInConsole()) {
            return;
        }

        collect($this->configFiles)->each(function ($file) {
            $this->publishes([__DIR__ . "/../../config/$file.php" => config_path("craft/$file.php")], 'craftcms');
        });
    }

}
