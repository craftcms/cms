<?php

declare(strict_types=1);

namespace CraftCms\Cms\Plugin\Concerns;

use CraftCms\Cms\Plugin\Events\DisablingPlugin;
use CraftCms\Cms\Plugin\Events\EnablingPlugin;
use CraftCms\Cms\Plugin\Events\PluginEvent;
use CraftCms\Cms\Plugin\Events\UninstallingPlugin;
use CraftCms\Cms\Plugin\Plugin;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\File;

/**
 * @mixin Plugin
 *
 * @internal
 */
trait PublishesFiles
{
    /**
     * Map of path on disk to name in the public directory. The file will be published
     * as `vendor/{package/name}/{value}`.
     *
     * @var array<string, string>
     */
    protected array $publishables = [];

    public function registerPublishesFiles(): void
    {
        Event::listen(EnablingPlugin::class, function (EnablingPlugin $event) {
            if (! $event->plugin instanceof static) {
                return;
            }

            foreach ($event->plugin->publishables as $from => $to) {
                if (File::isDirectory($from)) {
                    File::copyDirectory($from, public_path("vendor/{$event->plugin->packageName}/{$to}"));

                    continue;
                }

                File::copy($from, public_path("vendor/{$event->plugin->packageName}/{$to}/"));
            }
        });

        Event::listen([DisablingPlugin::class, UninstallingPlugin::class], function (PluginEvent $event) {
            if (! $event->plugin instanceof static) {
                return;
            }

            File::deleteDirectory(public_path("vendor/{$event->plugin->packageName}"));
        });
    }

    public function bootPublishesFiles(): void
    {
        $handle = self::getInstance()->handle;
        $name = self::getInstance()->packageName;

        $publishes = Collection::make($this->publishables)
            ->map(fn (string $to) => public_path("vendor/{$name}/{$to}"));

        if ($publishes->isNotEmpty()) {
            $this->publishes($publishes->all(), $handle);
        }
    }

    public function asset(string $path): string
    {
        return asset("vendor/$this->packageName/$path");
    }
}
