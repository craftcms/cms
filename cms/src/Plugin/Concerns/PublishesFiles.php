<?php

namespace CraftCms\Cms\Plugin\Concerns;

use CraftCms\Cms\Plugin\Plugin;
use Illuminate\Support\Collection;

/**
 * @mixin Plugin
 *
 * @internal
 *
 * @since 6.0.0
 */
trait PublishesFiles
{
    /**
     * Map of path on disk to name in the public directory. The file will be published
     * as `vendor/{pluginHandle}/{value}`.
     *
     * @var array<string, string>
     */
    protected array $publishables = [];

    public function bootPlublishesFiles(): void
    {
        if (! $this->app->runningInConsole()) {
            return;
        }

        $handle = self::getInstance()->handle;
        $name = self::getInstance()->packageName;

        $publishes = Collection::make($this->publishables)
            ->map(fn (string $to) => public_path("vendor/{$name}/{$to}"));

        if ($publishes->isNotEmpty()) {
            $this->publishes($publishes->all(), $handle);
        }
    }
}
