<?php

declare(strict_types=1);

namespace CraftCms\Cms\Asset\Events;

use CraftCms\Cms\Asset\Elements\Asset;
use CraftCms\Cms\Shared\Concerns\ValidatableEvent;

/**
 * @event BeforeReplaceAsset The event that is triggered before an asset's file is replaced.
 */
class BeforeReplaceAsset
{
    use ValidatableEvent;

    public function __construct(
        public Asset $asset,
        public string $replaceWith,
        public string $filename,
    ) {}
}
