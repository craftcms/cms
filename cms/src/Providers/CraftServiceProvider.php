<?php

namespace Craft\Cms\Providers;

use Craft\Cms\Console\ConsoleServiceProvider;
use Illuminate\Support\AggregateServiceProvider;

class CraftServiceProvider extends AggregateServiceProvider
{
    protected $providers = [
        AppServiceProvider::class,
        ConsoleServiceProvider::class,
    ];
}
