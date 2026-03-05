<?php

declare(strict_types=1);

namespace CraftCms\Cms\Image\Contracts;

use CraftCms\Cms\Asset\Elements\Asset;
use CraftCms\Cms\Image\Data\ImageTransform;

interface EagerImageTransformerInterface
{
    /**
     * Eager-loads the given transforms for the given assets.
     *
     * @param  ImageTransform[]  $transforms
     * @param  Asset[]  $assets
     */
    public function eagerLoadTransforms(array $transforms, array $assets): void;
}
