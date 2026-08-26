<?php

declare(strict_types=1);

namespace CraftCms\Cms\Asset\Data;

use CraftCms\Cms\Asset\Elements\Asset;

readonly class AssetTransformRequest
{
    /**
     * @param  array<string, mixed>  $parameters
     */
    public function __construct(
        public Asset $asset,
        public AssetTransformer $transformer,
        public array $parameters,
        public bool $immediately,
    ) {}
}
