<?php

declare(strict_types=1);

namespace CraftCms\Cms\Image\Data;

use CraftCms\Cms\Image\ImageHash;
use CraftCms\Cms\Image\Images;

/**
 * What was sampled from an image’s pixels in one pass: its colors, and a fingerprint for finding copies of it.
 *
 * @see Images::sample()
 * @since 6.0.0
 */
readonly class ImageSample
{
    /**
     * @param  ImageColors  $colors  The image’s colors, which are empty if it couldn’t be sampled
     * @param  string|null  $hash  The image’s difference hash, as 16 hexadecimal digits, or `null` if it couldn’t be
     *                             sampled
     *
     * @see ImageHash
     */
    public function __construct(
        public ImageColors $colors = new ImageColors,
        public ?string $hash = null,
    ) {}
}
