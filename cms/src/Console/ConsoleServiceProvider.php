<?php

namespace CraftCms\Cms\Console;

use CraftCms\Cms\Console\Commands\TwigCacheCommand;
use CraftCms\Cms\Console\Commands\TwigClearCommand;
use Illuminate\Support\ServiceProvider;

/**
 * @since 6.0.0
 *
 * @internal
 */
final class ConsoleServiceProvider extends ServiceProvider
{
    protected array $commands = [
        TwigCacheCommand::class,
        TwigClearCommand::class,
    ];

    public function boot(): void
    {
        if (! $this->app->runningInConsole()) {
            return;
        }

        $this->commands($this->commands);

        $this->optimizes(
            optimize: 'craft:twig:cache',
            clear: 'craft:twig:clear',
            key: 'twig'
        );

        $this->publishes([
            __DIR__.'/craft.stub' => base_path('craft'),
        ], 'craftcms');
    }
}
