<?php

namespace CraftCms\Cms\Console;

use CraftCms\Cms\Console\Commands\Env\EnvRemoveCommand;
use CraftCms\Cms\Console\Commands\Env\EnvSetCommand;
use CraftCms\Cms\Console\Commands\Env\EnvShowCommand;
use CraftCms\Cms\Console\Commands\Twig\TwigCacheCommand;
use CraftCms\Cms\Console\Commands\Twig\TwigClearCommand;
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

        EnvShowCommand::class,
        EnvSetCommand::class,
        EnvRemoveCommand::class,
    ];

    public function boot(): void
    {
        if (! $this->app->runningInConsole()) {
            return;
        }

        $this->app->terminating(function () {
            app('Craft')->getProjectConfig()->flush();
        });

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
