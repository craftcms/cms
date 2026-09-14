<?php

declare(strict_types=1);

namespace CraftCms\Cms\Asset\Events;

use CraftCms\Cms\Asset\Data\AssetTransformer;

readonly class AssetTransformerDeleting
{
    public function __construct(public AssetTransformer $transformer) {}
}
