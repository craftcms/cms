<?php

namespace CraftCms\Cms\Providers;

use CraftCms\Cms\Console\ConsoleServiceProvider;
use Illuminate\Support\AggregateServiceProvider;

class CraftServiceProvider extends AggregateServiceProvider
{
    protected $providers = [
        AppServiceProvider::class,
        IconServiceProvider::class,
        ConsoleServiceProvider::class,
    ];
}
