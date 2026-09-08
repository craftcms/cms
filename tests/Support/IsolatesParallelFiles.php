<?php

declare(strict_types=1);

namespace CraftCms\Cms\Tests\Support;

use Illuminate\Filesystem\Filesystem;
use Illuminate\Foundation\Application;

trait IsolatesParallelFiles
{
    protected function resolveApplication(): Application
    {
        $app = parent::resolveApplication();

        if (($token = getenv('TEST_TOKEN')) !== false) {
            $app->useBootstrapPath($app->bootstrapPath("parallel_$token"));
            $app->useConfigPath($app->storagePath("parallel_$token/config"));

            $files = new Filesystem;
            $files->ensureDirectoryExists($app->bootstrapPath('cache'));
            $files->ensureDirectoryExists($app->configPath());
        }

        return $app;
    }
}
