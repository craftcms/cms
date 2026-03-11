<?php

declare(strict_types=1);

namespace CraftCms\Yii2Adapter\Config;

use CraftCms\Cms\Config\BaseConfig;
use CraftCms\Cms\Support\Arr;
use CraftCms\Cms\Support\Facades\Deprecator;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Support\Facades\Config;
use Symfony\Component\Finder\Finder;

final readonly class MultiEnvironmentConfigCompatibility
{
    public function register(Application $app): void
    {
        if (!is_dir(config_path('craft'))) {
            return;
        }

        $files = new Finder()->files()->in(config_path('craft'))->name('*.php');
        $environment = $app->environment();

        foreach ($files as $file) {
            $key = "craft.{$file->getFilenameWithoutExtension()}";
            $config = Config::get($key);

            if ($config instanceof BaseConfig) {
                continue;
            }

            if (!is_array($config)) {
                Config::set($key, []);

                continue;
            }

            if (!array_key_exists('*', $config)) {
                continue;
            }

            Deprecator::log("config-{$file}", 'Using multi-environment config files is deprecated.', $file->getPathname());

            $merged = Arr::merge($config['*'], $config[$environment] ?? []);

            Config::set($key, $merged);
        }
    }
}
