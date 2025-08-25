<?php

namespace CraftCms\Cms\Plugin\Concerns;

use CraftCms\Cms\Plugin\Plugin;
use CraftCms\Cms\Support\Arr;
use CraftCms\Cms\Support\Str;

/**
 * @mixin Plugin
 *
 * @internal
 *
 * @since 6.0.0
 */
trait HasViteAssets
{
    /**
     * Vite configuration.
     *
     * @var array{
     *     input: string[],
     *     publicDirectory?: string,
     *     buildDirectory?: string,
     *     hotFile?: string,
     * }|string[]
     */
    protected array $vite = [];

    public function bootHasViteAssets(): void
    {
        if (! $config = $this->vite) {
            return;
        }

        $name = static::getInstance()->packageName;
        $directory = Str::finish(dirname(static::getInstance()->getBasePath()), '/');

        if (! Arr::isAssoc($config)) {
            $config = ['input' => $config];
        }

        $publicDirectory = Str::finish($config['publicDirectory'] ?? 'public', '/');
        $buildDirectory = Str::finish($config['buildDirectory'] ?? 'build', '/');
        $hotFile = $config['hotFile'] ?? "{$directory}{$publicDirectory}hot";

        $source = "{$directory}{$publicDirectory}{$buildDirectory}";
        $target = $this->app->publicPath("vendor/{$name}/{$buildDirectory}");

        $this->publishes([$source => $target], self::getInstance()->handle);

        $this->pluginsService->addViteConfig($name, [
            'hotFile' => $hotFile,
            'buildDirectory' => "vendor/{$name}/{$buildDirectory}",
            'input' => $config['input'],
        ]);
    }
}
