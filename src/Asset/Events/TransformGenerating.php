<?php

declare(strict_types=1);

namespace CraftCms\Cms\Asset\Events;

use CraftCms\Cms\Asset\Elements\Asset;
use CraftCms\Cms\Image\Data\ImageTransform;
use CraftCms\Cms\Image\ImageHelper;

/**
 * @event TransformGenerating The event that is triggered before an asset’s transform URL is determined.
 *
 * It fires for every transform driver, including drivers that only build a URL and never generate a file.
 * Set {@see $url} to use that URL instead of calling the driver.
 *
 * It fires before the driver checks whether it can transform the file, so it fires for every file, including files
 * that aren’t images and GIFs or SVGs when the `transformGifs` or `transformSvgs` config setting is off. A listener
 * that sets {@see $url} should check the file first, such as with
 * {@see ImageHelper::canManipulateAsImage()}.
 *
 * Changes to {@see $transform} have no effect.
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
