<?php

declare(strict_types=1);

namespace CraftCms\Cms\Asset\Contracts;

use CraftCms\Cms\Asset\Data\AssetTransformDriverDefinition;
use CraftCms\Cms\Asset\Data\AssetTransformRequest;
use CraftCms\Cms\Asset\Data\AssetTransformResult;

interface AssetTransformDriver
{
    public function definition(): AssetTransformDriverDefinition;

    public function transform(AssetTransformRequest $request): AssetTransformResult;
}
