<?php

namespace Craft\Yii2Adapter\Tests;

use Craft\Aliases\AliasesServiceProvider;
use Craft\Cms\Providers\CraftServiceProvider;
use Craft\Yii2Adapter\Yii2ServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;

class TestCase extends Orchestra
{
    protected function getPackageProviders($app): array
    {
        return [
            AliasesServiceProvider::class,
            CraftServiceProvider::class,
            Yii2ServiceProvider::class,
        ];
    }
}
