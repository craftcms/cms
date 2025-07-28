<?php

namespace CraftCms\Yii2Adapter\Tests;

use CraftCms\Aliases\AliasesServiceProvider;
use CraftCms\Cms\Providers\CraftServiceProvider;
use CraftCms\Yii2Adapter\Yii2ServiceProvider;
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
