<?php

declare(strict_types=1);

namespace CraftCms\Cms\Asset\Contracts;

use CraftCms\Cms\Asset\Data\AssetTransformRequest;

interface PreloadsAssetTransforms
{
    /** @param list<AssetTransformRequest> $requests */
    public function preloadAssetTransforms(array $requests): void;
}
