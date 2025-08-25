<?php

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

    public function registerHasViteAssets(): void
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
        });

        Event::listen([DisablingPlugin::class, UninstallingPlugin::class], function (PluginEvent $event) {
            if (! $event->plugin instanceof static) {
                return;
            }

            File::deleteDirectory(public_path("vendor/{$event->plugin->packageName}"));
        });
    }

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
        $hotFile = $config['hotFile'] ?? "{$directory}{$publicDirectory}hot";

        [$source, $target] = $this->getSourceAndTarget(static::getInstance(), $config);

        $this->publishes([$source => $this->app->publicPath($target)], self::getInstance()->handle);

        $this->pluginsService->addViteConfig($name, [
            'hotFile' => $hotFile,
            'buildDirectory' => $target,
            'input' => $config['input'],
        ]);
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
