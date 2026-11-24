<?php

declare(strict_types=1);

namespace CraftCms\Cms\Asset\Events;

use CraftCms\Cms\Asset\Elements\Asset;
use CraftCms\Cms\Image\Data\ImageTransform;

/**
 * @event TransformGenerating The event that is triggered before an asset’s transform URL is determined.
 *
 * It fires for every transform driver, including drivers that only build a URL and never generate a file.
 * Set {@see $url} to use that URL instead of calling the driver.
 *
 * It can fire for files that aren’t images, such as PDFs, when a driver supports them. A listener that sets
 * {@see $url} should check {@see Asset::$kind} first.
 *
 * {@see $transform} holds the transform’s parameters as an array when the driver gives a core parameter a type that
 * {@see ImageTransform} doesn’t support.
 *
 * @since 6.0.0
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
