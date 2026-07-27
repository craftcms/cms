<?php

declare(strict_types=1);

namespace CraftCms\Yii2Adapter\Tests;

use CraftCms\Cms\Tests\TestCase;
use Illuminate\Contracts\Config\Repository as ConfigRepository;
use Illuminate\Foundation\Application;
use Override;

class DatabaseTestCase extends TestCase
{
    /** @param Application $app */
    #[Override]
    protected function defineEnvironment($app): void
    {
        parent::defineEnvironment($app);

        $config = $app->make(ConfigRepository::class);
        $connection = $config->get('database.default');

        $config->set("database.connections.$connection.database", ':memory:');
    }
}
