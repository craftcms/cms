<?php

declare(strict_types=1);

namespace CraftCms\Cms\Asset\Events;

use CraftCms\Cms\Asset\Elements\Asset;
use CraftCms\Cms\Image\Data\ImageTransform;

/**
 * @event TransformGenerated The event that is triggered after an asset’s transform driver returns the transform URL.
 *
 * It fires for every transform driver. The transformed file may not exist yet, such as when the driver generates it
 * on the first request to {@see $url}. It doesn’t fire when a {@see TransformGenerating} listener provides the URL.
 *
 * @since 6.0.0
 */
readonly class TransformGenerated
{
    /** @param ImageTransform|string|array<string, bool|float|int|string|null>|null $transform */
    public function __construct(
        public Asset $asset,
        public ImageTransform|string|array|null $transform,
        public string $url,
    ) {}
}
