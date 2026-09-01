<?php

declare(strict_types=1);

namespace CraftCms\Cms\Plugin\Concerns;

use CraftCms\Cms\Plugin\Plugin;
use Illuminate\Support\Collection;

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

    public function publishConfiguredFiles(): void
    {
        $this->copyPublishableFiles($this->publishableFilePaths());
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

    /** @return array<string, string> */
    private function publishableFilePaths(): array
    {
        return Collection::make($this->publishables)
            ->map(fn (string $to) => public_path("vendor/{$this->packageName}/{$to}"))
            ->all();
    }
}
