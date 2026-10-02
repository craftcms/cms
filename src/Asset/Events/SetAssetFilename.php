<?php

declare(strict_types=1);

namespace CraftCms\Cms\Asset\Events;

/**
 * @since 6.0.0
 */
class SetAssetFilename
{
    public function __construct(
        public string $filename,
        public readonly string $originalBaseName,
        public string $extension,
    ) {}
}
