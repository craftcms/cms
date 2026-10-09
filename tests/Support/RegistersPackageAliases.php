<?php

declare(strict_types=1);

namespace CraftCms\Cms\Tests\Support;

use CraftCms\Cms\Support\Json;

trait RegistersPackageAliases
{
    protected function getPackageAliases($app): array
    {
        $composer = file_get_contents(dirname(__DIR__, 2).'/composer.json');

        if ($composer === false) {
            return [];
        }

        return Json::decode($composer)['extra']['laravel']['aliases'] ?? [];
    }
}
