<?php

declare(strict_types=1);

namespace CraftCms\Cms\Asset\Events;

use CraftCms\Cms\Asset\Elements\Asset;

/**
 * @event BeforeHandleFile The event that is triggered before an asset is uploaded to volume.
 */
class BeforeHandleFile
{
    public function __construct(
        public Asset $asset,
        public bool $isNew,
    ) {}
}
