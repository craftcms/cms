<?php

declare(strict_types=1);

namespace CraftCms\Yii2Adapter\Config;

use CraftCms\Cms\Config\BaseConfig;
use CraftCms\Cms\Support\Arr;
use CraftCms\Cms\Support\Str;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Support\Facades\Config;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo;

readonly class MultiEnvironmentConfigCompatibility
{
    public function register(Application $app): void
    {
        if (!is_dir(config_path('craft'))) {
            return;
        }

        $files = new Finder()->files()->in(config_path('craft'))->name('*.php');
        $environment = $app->environment();

        $names = collect($files)->map(fn(SplFileInfo $file) => $file->getFilenameWithoutExtension());

        foreach ($files as $file) {
            $name = $file->getFilenameWithoutExtension();
            $key = "craft.$name";
            $config = Config::get($key);

            if ($config instanceof BaseConfig) {
                continue;
            }

            if (!is_array($config)) {
                continue;
            }

            if (!array_key_exists('*', $config)) {
                continue;
            }

            $merged = Arr::merge($config['*'], $config[$environment] ?? []);

            // Keep files Laravel nested inside this one, such as `general.web.php` inside `general`
            $nested = $names
                ->filter(fn(string $fileName) => str_starts_with($fileName, "$name."))
                ->map(fn(string $fileName) => Str::before(Str::after($fileName, "$name."), '.'))
                ->all();

            Config::set($key, Arr::only($config, $nested) + $merged);
        }
    }
}
