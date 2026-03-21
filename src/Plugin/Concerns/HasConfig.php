<?php

declare(strict_types=1);

namespace CraftCms\Cms\Plugin\Concerns;

trait HasConfig
{
    /** @var bool Whether the config file should be automatically registered */
    public bool $config = true;

    public function bootHasConfig(): void
    {
        $handle = self::getInstance()->handle;
        $source = sprintf('%s/config/%s.php', $this->getBasePath(), $handle);

        if (! $this->config) {
            return;
        }

        if (! file_exists($source)) {
            return;
        }

        $this->publishes([
            $source => config_path("craft/$handle.php"),
        ], $handle);

        $this->mergeConfigFrom($source, "craft.$handle");
    }
}
