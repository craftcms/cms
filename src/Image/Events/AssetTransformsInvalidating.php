<?php

declare(strict_types=1);

namespace CraftCms\Cms\Image\Events;

use CraftCms\Cms\Asset\Elements\Asset;
use CraftCms\Cms\Shared\Concerns\ValidatableEvent;

/**
 * @since 6.0.0
 */
class AssetTransformsInvalidating
{
    use ValidatableEvent;

    public function __construct(
        public Asset $asset,
    ) {}
}
