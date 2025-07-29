<?php

namespace CraftCms\Cms\Providers;

use CraftCms\Cms\Config\ConfigServiceProvider;
use CraftCms\Cms\Console\ConsoleServiceProvider;
use Illuminate\Support\AggregateServiceProvider;

class CraftServiceProvider extends AggregateServiceProvider
{
    protected $providers = [
        ConfigServiceProvider::class,
        AppServiceProvider::class,
        IconServiceProvider::class,
        ConsoleServiceProvider::class,
    ];
}
