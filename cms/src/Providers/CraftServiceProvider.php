<?php

namespace CraftCms\Cms\Providers;

use CraftCms\Cms\Console\ConsoleServiceProvider;
use Illuminate\Support\AggregateServiceProvider;

/** @since 6.0.0 */
final class CraftServiceProvider extends AggregateServiceProvider
{
    protected $providers = [
        AppServiceProvider::class,
        IconServiceProvider::class,
        ConsoleServiceProvider::class,
    ];
}
