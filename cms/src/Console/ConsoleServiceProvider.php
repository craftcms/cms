<?php

namespace CraftCms\Cms\Console;

use Illuminate\Support\ServiceProvider;

/**
 * @since 6.0.0
 *
 * @internal
 */
final class ConsoleServiceProvider extends ServiceProvider
{
    protected array $commands = [

    ];

    public function boot(): void
    {
        if (! $this->app->runningInConsole()) {
            return;
        }

        $this->commands($this->commands);

        $this->publishes([
            __DIR__.'/craft.stub' => base_path('craft'),
        ], 'craftcms');
    }
}
