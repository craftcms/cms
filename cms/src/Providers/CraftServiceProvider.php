<?php

namespace CraftCms\Cms\Providers;

use CraftCms\Cms\Config\ConfigServiceProvider;
use CraftCms\Cms\Console\ConsoleServiceProvider;
use CraftCms\Cms\Database\DatabaseServiceProvider;
use CraftCms\Cms\License\LicenseServiceProvider;
use CraftCms\Cms\Plugin\PluginsServiceProvider;
use Illuminate\Support\AggregateServiceProvider;

/** @since 6.0.0 */
final class CraftServiceProvider extends AggregateServiceProvider
{
    protected $providers = [
        ConfigServiceProvider::class,
        DatabaseServiceProvider::class,
        LicenseServiceProvider::class,
        AppServiceProvider::class,
        IconServiceProvider::class,
        ConsoleServiceProvider::class,
        PluginsServiceProvider::class,
    ];
}
