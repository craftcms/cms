<?php

declare(strict_types=1);

namespace CraftCms\Cms\Asset\Data;

use CraftCms\Cms\Asset\Elements\Asset;
use CraftCms\Cms\Image\Data\ImageTransform;

/**
 * @since 6.0.0
 */
readonly class AssetTransformRequest
{
    /**
     * @param  array<string, mixed>  $parameters
     * @param  ImageTransform|null  $namedTransform  The named Image Transform the definition used, if it didn’t override any of its parameters.
     */
    public function __construct(
        public Asset $asset,
        public AssetTransformer $transformer,
        public array $parameters,
        public ?ImageTransform $namedTransform = null,
    ) {}
}
