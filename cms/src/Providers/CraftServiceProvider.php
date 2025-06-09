<?php

namespace Craft\Cms\Providers;

use Illuminate\Support\AggregateServiceProvider;

class CraftServiceProvider extends AggregateServiceProvider
{
    protected $providers = [
        AppServiceProvider::class,
    ];
}
