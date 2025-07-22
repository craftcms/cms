<?php

namespace Craft\Yii2Adapter\Tests;

use Craft\Cms\Providers\CraftServiceProvider;
use Craft\Yii2Adapter\Yii2ServiceProvider;
use CraftCms\Aliases\AliasesServiceProvider;
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
