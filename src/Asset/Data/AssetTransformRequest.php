<?php

declare(strict_types=1);

namespace CraftCms\Cms\Asset\Data;

use CraftCms\Cms\Asset\Elements\Asset;

readonly class AssetTransformRequest
{
    /**
     * @param  array<string, mixed>  $operations
     */
    public function __construct(
        public Asset $asset,
        public AssetTransformer $transformer,
        public array $operations,
        public bool $immediately,
    ) {}
}
