<?php

declare(strict_types=1);

namespace CraftCms\Cms\Asset\Events;

use CraftCms\Cms\Asset\Elements\Asset;

/**
 * @event AfterReplaceAsset The event that is triggered after an asset's file is replaced.
 */
final class AfterReplaceAsset
{
    public function __construct(
        public Asset $asset,
        public string $filename,
    ) {}
}
