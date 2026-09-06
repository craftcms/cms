<?php

declare(strict_types=1);

namespace CraftCms\Cms\Console\Commands;

use CraftCms\Cms\Http\Middleware\PreventRequestsDuringMaintenance;
use Illuminate\Foundation\Console\RouteCacheCommand as LaravelRouteCacheCommand;
use Illuminate\Routing\RouteCollection;
use Override;

class RouteCacheCommand extends LaravelRouteCacheCommand
{
    #[Override]
    protected function buildRouteCacheFile(RouteCollection $routes): string
    {
        $templates = PreventRequestsDuringMaintenance::routeExceptionTemplates($routes);

        return parent::buildRouteCacheFile($routes)."\nreturn ".var_export($templates, true).";\n";
    }
}
