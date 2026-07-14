<?php

declare(strict_types=1);

namespace CraftCms\Cms\Plugin\Concerns;

use CraftCms\Cms\Support\File;

trait HasConfig
{
    /** @var bool Whether the plugin's override-only Craft settings config should be publishable */
    public bool $config = true;

    /**
     * Registers `config/craft/{handle}.php` for publishing.
     *
     * Published values override stored plugin settings; they are not merged as Laravel package defaults.
     */
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
