<?php

declare(strict_types=1);

namespace CraftCms\Cms\Asset\Events;

use CraftCms\Cms\Asset\Elements\Asset;
use CraftCms\Cms\Shared\Concerns\HandleableEvent;

/**
 * @event ThumbUrlResolving The event that is triggered when a thumbnail is being requested for an asset.
 */
class ThumbUrlResolving
{
    use HandleableEvent;

    public ?string $url = null;

    public function __construct(
        public Asset $asset,
        public int $width,
        public int $height,
    ) {}
}
