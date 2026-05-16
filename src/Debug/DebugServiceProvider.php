<?php

declare(strict_types=1);

namespace CraftCms\Cms\Debug;

use CraftCms\Cms\Debug\Element\ElementCollectorProvider;
use Fruitcake\LaravelDebugbar\CollectorProviders\AbstractCollectorProvider;
use Fruitcake\LaravelDebugbar\LaravelDebugbar;
use Illuminate\Support\ServiceProvider;

class DebugServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        if (
            ! $this->app->bound('debugbar') ||
            ! $this->app->bound(LaravelDebugbar::class) ||
            ! class_exists(AbstractCollectorProvider::class)
        ) {
            return;
        }

        $this->app->afterResolving('debugbar', fn () => $this->registerElementCollectorProvider());

        if ($this->app->resolved('debugbar')) {
            $this->registerElementCollectorProvider();
        }
    }

    private function registerElementCollectorProvider(): void
    {
        $this->app->call(ElementCollectorProvider::class);
    }
}
