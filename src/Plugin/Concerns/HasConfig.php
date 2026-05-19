<?php

declare(strict_types=1);

namespace CraftCms\Cms\Plugin\Concerns;

use CraftCms\Cms\Support\File;

trait HasConfig
{
    /** @var bool Whether the config file should be automatically registered */
    public bool $config = true;

    public function bootHasConfig(): void
    {
        if (! $this->config) {
            return;
        }

        $handle = self::getInstance()->handle;
        $source = File::normalizePath(sprintf('%s/config/%s.php', dirname($this->getBasePath()), $handle));

        if (! file_exists($source)) {
            return;
        }

        $this->publishes([
            $source => config_path("craft/$handle.php"),
        ], $handle);
    }
}
