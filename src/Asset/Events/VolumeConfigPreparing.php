<?php

declare(strict_types=1);

namespace CraftCms\Cms\Asset\Events;

use CraftCms\Cms\Asset\Data\Volume;

class VolumeConfigPreparing
{
    /** @param array<string, mixed> $config */
    public function __construct(
        public Volume $volume,
        public array $config,
    ) {}
}
