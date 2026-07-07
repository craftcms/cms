<?php

declare(strict_types=1);

namespace CraftCms\Cms\Plugin\Concerns;

use CraftCms\Cms\Plugin\Events\PluginDisabling;
use CraftCms\Cms\Plugin\Events\PluginEnabling;
use CraftCms\Cms\Plugin\Events\PluginEvent;
use CraftCms\Cms\Plugin\Events\PluginInstalling;
use CraftCms\Cms\Plugin\Events\PluginUninstalling;
use CraftCms\Cms\Plugin\Plugin;
use CraftCms\Cms\Support\File;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Event;

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
        Event::listen([PluginEnabling::class, PluginInstalling::class], function (PluginEvent $event) {
            if (! $event->plugin instanceof static) {
                return;
            }

            $event->plugin->copyPublishableFiles($event->plugin->publishableFilePaths());
        });

        Event::listen([PluginDisabling::class, PluginUninstalling::class], function (PluginEvent $event) {
            if (! $event->plugin instanceof static) {
                return;
            }

            File::deleteDirectory(public_path("vendor/{$event->plugin->packageName}"));
        });
    }

    public function bootPublishesFiles(): void
    {
        $handle = self::getInstance()->handle;

        $publishes = Collection::make($this->publishableFilePaths());

        if ($publishes->isNotEmpty()) {
            $this->publishes($publishes->all(), $handle);
        }
    }

    public function asset(string $path): string
    {
        return asset("vendor/$this->packageName/$path");
    }

    private function publishableFilePaths(): array
    {
        return Collection::make($this->publishables)
            ->map(fn (string $to) => public_path("vendor/{$this->packageName}/{$to}"))
            ->all();
    }
}
