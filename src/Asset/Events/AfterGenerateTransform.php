<?php

declare(strict_types=1);

namespace CraftCms\Cms\Asset\Events;

use CraftCms\Cms\Asset\Elements\Asset;
use CraftCms\Cms\Image\Data\ImageTransform;

/**
 * @event AfterGenerateTransform The event that is triggered after a transform is generated for an asset.
 */
final readonly class AfterGenerateTransform
{
    public function __construct(
        public Asset $asset,
        public ImageTransform|string|array|null $transform,
        public string $url,
    ) {}
}
