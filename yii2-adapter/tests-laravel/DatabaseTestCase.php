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

    /**
     * Leaves out the main suite’s `tests/.env`.
     *
     * Its variables would stay in the process environment and change how the adapter boots in every later test, such
     * as applying `CRAFT_SECURITY_KEY` as a deprecated config override.
     */
    #[Override]
    protected function loadEnvironmentVariables(Application $app): void
    {
    }
}
