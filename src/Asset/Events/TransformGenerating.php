<?php

declare(strict_types=1);

namespace CraftCms\Cms\Asset\Events;

use CraftCms\Cms\Asset\Elements\Asset;
use CraftCms\Cms\Image\Data\ImageTransform;

/**
 * @event TransformGenerating The event that is triggered before a transform is generated for an asset.
 */
class TransformGenerating
{
    /** @param ImageTransform|string|array<string, bool|float|int|string|null>|null $transform */
    public function __construct(
        public Asset $asset,
        public ImageTransform|string|array|null $transform,
        public ?string $url = null,
    ) {}
}
