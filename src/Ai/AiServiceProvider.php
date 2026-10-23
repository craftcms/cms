<?php

declare(strict_types=1);

namespace CraftCms\Cms\Ai;

use CraftCms\Cms\ProjectConfig\ProjectConfig;
use Illuminate\Support\ServiceProvider;

/**
 * @since 6.0.0
 */
class AiServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->scoped(Settings::class, fn (): Settings => new Settings(
            $this->app->make(ProjectConfig::class)->get(ProjectConfig::PATH_AI) ?? [],
        ));
    }
}
