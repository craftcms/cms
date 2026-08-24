<?php

declare(strict_types=1);

namespace CraftCms\Cms\Asset\Contracts;

use CraftCms\Cms\Asset\Data\AssetProcessorDriverDefinition;
use CraftCms\Cms\Asset\Data\AssetTransformRequest;
use CraftCms\Cms\Asset\Data\AssetTransformResult;

interface AssetProcessorDriver
{
    public function definition(): AssetProcessorDriverDefinition;

    public function transform(AssetTransformRequest $request): AssetTransformResult;
}
