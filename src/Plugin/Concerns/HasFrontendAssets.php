<?php

declare(strict_types=1);

namespace CraftCms\Cms\Plugin\Concerns;

use CraftCms\Cms\Plugin\Contracts\PluginInterface;
use CraftCms\Cms\Plugin\Plugin;
use CraftCms\Cms\Support\Arr;
use CraftCms\Cms\Support\File;
use CraftCms\Cms\Support\Str;

/**
 * @mixin Plugin
 *
 * @internal
 */
trait HasFrontendAssets
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

    /** @var array<string, string> */
    protected array $styles = [];

    /** @var array<string, string> */
    protected array $scripts = [];

    public function publishFrontendAssets(): void
    {
        if ($this->vite) {
            [$source, $target] = $this->getSourceAndTarget($this, $this->vite);

            File::copyDirectory($source, $this->app->publicPath($target));
        }

        $this->copyPublishableFiles($this->frontendAssetPublishPaths());
    }

    public function bootHasFrontendAssets(): void
    {
        $this->bootVite();
        $this->bootAssets();
    }

    protected function bootVite(): void
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
        $hotFile = $config['hotFile'] ?? "{$directory}{$publicDirectory}hot";

        [$source, $target] = $this->getSourceAndTarget(static::getInstance(), $config);

        $this->publishes([$source => $this->app->publicPath($target)], self::getInstance()->handle);

        $this->pluginsService->addViteConfig($name, [
            'hotFile' => $hotFile,
            'buildDirectory' => $target,
            'input' => $config['input'],
        ]);
    }

    protected function bootAssets(): void
    {
        if (! $this->styles && ! $this->scripts) {
            return;
        }

        $name = static::getInstance()->packageName;
        $version = md5(static::getInstance()->version);

        $assets = $this->frontendAssetPublishPaths();

        foreach ($this->styles as $public) {
            $public = "$public?v=$version";

            $this->pluginsService->addStyle($name, asset($this->getPublishablePath($public)));
        }

        foreach ($this->scripts as $public) {
            $public = "$public?v=$version";

            $this->pluginsService->addScript($name, asset($this->getPublishablePath($public)));
        }

        $this->publishes($assets, static::getInstance()->handle);
    }

    /**
     * @param  array{publicDirectory?: string, buildDirectory?: string}  $config
     * @return array{string, string}
     */
    private function getSourceAndTarget(PluginInterface $plugin, array $config): array
    {
        $directory = Str::finish(dirname($plugin->getBasePath()), '/');
        $publicDirectory = Str::finish($config['publicDirectory'] ?? 'public', '/');
        $buildDirectory = Str::finish($config['buildDirectory'] ?? 'build', '/');

        $source = "{$directory}{$publicDirectory}{$buildDirectory}";
        $target = $this->getPublishablePath($buildDirectory);

        return [$source, $target];
    }

    public function getPublishablePath(string $path): string
    {
        $ns = static::getInstance()->packageName;

        return "vendor/{$ns}/$path";
    }

    /** @return array<string, string> */
    private function frontendAssetPublishPaths(): array
    {
        return collect(array_merge($this->styles, $this->scripts))
            ->mapWithKeys(fn ($public, $resource) => [$resource => $this->app->publicPath($this->getPublishablePath($public))])
            ->all();
    }
}
