<?php

declare(strict_types=1);

namespace CraftCms\Cms\Asset\Events;

class SetAssetFilename
{
    public function __construct(
        public string $filename,
        public readonly string $originalBaseName,
        public string $extension,
    ) {}
}
