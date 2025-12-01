<?php

declare(strict_types=1);

namespace CraftCms\Cms\Plugin\Concerns;

use CraftCms\Cms\Plugin\Contracts\PluginInterface;
use CraftCms\Cms\Plugin\Events\DisablingPlugin;
use CraftCms\Cms\Plugin\Events\EnablingPlugin;
use CraftCms\Cms\Plugin\Events\PluginEvent;
use CraftCms\Cms\Plugin\Events\UninstallingPlugin;
use CraftCms\Cms\Plugin\Plugin;
use CraftCms\Cms\Support\Arr;
use CraftCms\Cms\Support\Str;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\File;

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

    protected array $styles = [];

    protected array $scripts = [];

    public function registerHasFrontendAssets(): void
    {
        Event::listen(EnablingPlugin::class, function (EnablingPlugin $event) {
            if (! $event->plugin instanceof static) {
                return;
            }

            if (! $config = $event->plugin->vite) {
                return;
            }

            [$source, $target] = $this->getSourceAndTarget($event->plugin, $config);

            File::copyDirectory($source, $this->app->publicPath($target));

            foreach (array_merge($event->plugin->styles, $event->plugin->scripts) as $asset) {
                $destination = Str::afterLast($asset, '/');

                File::copy($asset, public_path("vendor/{$event->plugin->packageName}/{$destination}"));
            }
        });

        Event::listen([DisablingPlugin::class, UninstallingPlugin::class], function (PluginEvent $event) {
            if (! $event->plugin instanceof static) {
                return;
            }

            File::deleteDirectory(public_path("vendor/{$event->plugin->packageName}"));
        });
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

        $assets = collect(array_merge($this->styles, $this->scripts))->mapWithKeys(fn ($public, $resource) => [$resource => $this->app->publicPath("vendor/{$name}/{$public}")])->all();

        foreach ($this->styles as $public) {
            $public = "$public?v=$version";

            $this->pluginsService->addStyle($name, asset("vendor/{$name}/{$public}"));
        }

        foreach ($this->scripts as $public) {
            $public = "$public?v=$version";

            $this->pluginsService->addScript($name, asset("vendor/{$name}/{$public}"));
        }

        $this->publishes($assets, static::getInstance()->handle);
    }

    private function getSourceAndTarget(PluginInterface $plugin, array $config): array
    {
        $name = $plugin->packageName;
        $directory = Str::finish(dirname($plugin->getBasePath()), '/');
        $publicDirectory = Str::finish($config['publicDirectory'] ?? 'public', '/');
        $buildDirectory = Str::finish($config['buildDirectory'] ?? 'build', '/');

        $source = "{$directory}{$publicDirectory}{$buildDirectory}";
        $target = "vendor/{$name}/{$buildDirectory}";

        return [$source, $target];
    }
}
