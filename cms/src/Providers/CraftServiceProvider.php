<?php

namespace CraftCms\Cms\Providers;

use CraftCms\Cms\Config\ConfigServiceProvider;
use CraftCms\Cms\Console\ConsoleServiceProvider;
use CraftCms\Cms\License\LicenseServiceProvider;
use Illuminate\Support\AggregateServiceProvider;

class CraftServiceProvider extends AggregateServiceProvider
{
    protected $providers = [
        ConfigServiceProvider::class,
        LicenseServiceProvider::class,
        AppServiceProvider::class,
        IconServiceProvider::class,
        ConsoleServiceProvider::class,
    ];
}
