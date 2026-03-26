<?php

declare(strict_types=1);

namespace CraftCms\Cms\Deprecator;

use CraftCms\Cms\Deprecator\Commands\ClearDeprecationsCommand;
use Illuminate\Support\ServiceProvider;

class DeprecatorServiceProvider extends ServiceProvider
{
    public function boot(Deprecator $deprecator): void
    {
        $this->app->terminating(fn () => $deprecator->storeLogs());

        $this->commands([
            ClearDeprecationsCommand::class,
        ]);
    }
}
