<?php

declare(strict_types=1);

namespace CraftCms\Cms\Image\Events;

use CraftCms\Cms\Asset\Elements\Asset;
use CraftCms\Cms\Shared\Concerns\ValidatableEvent;

final class InvalidatingAssetTransforms
{
    use ValidatableEvent;

    public function __construct(
        public Asset $asset,
    ) {}
}
