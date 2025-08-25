<?php

namespace CraftCms\Cms\Plugin\Concerns;

use CraftCms\Cms\Plugin\Plugin;
use CraftCms\Cms\Support\Arr;

/**
 * @mixin Plugin
 *
 * @internal
 *
 * @since 6.0.0
 */
trait HasViteAssets
{
    /** @var array - URLs of Vite entry points */
    protected array $vite = [];

    public function bootHasViteAssets(): void
    {
        if (! $this->vite) {
            return;
        }

        $config = $this->vite;
        $name = static::getInstance()->packageName;
        $directory = dirname(static::getInstance()->getBasePath());

        if (! Arr::isAssoc($config)) {
            $config = ['input' => $config];
        }

        $publicDirectory = $config['publicDirectory'] ?? 'public';
        $buildDirectory = $config['buildDirectory'] ?? 'build';
        $hotFile = $config['hotFile'] ?? "{$directory}{$publicDirectory}/hot";
        $input = $config['input'];

        $publishSource = "{$directory}/{$publicDirectory}/{$buildDirectory}/";
        $publishTarget = public_path("vendor/{$name}/{$buildDirectory}/");

        $this->publishes([
            $publishSource => $publishTarget,
        ], self::getInstance()->handle);

        $this->pluginsService->addViteConfig($name, [
            'hotFile' => $hotFile,
            'buildDirectory' => "vendor/{$name}/{$buildDirectory}",
            'input' => $input,
        ]);
    }
}
