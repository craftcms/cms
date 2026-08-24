<?php

declare(strict_types=1);

namespace CraftCms\Cms\Gql;

use CraftCms\Cms\Asset\Elements\Asset;
use Illuminate\Container\Attributes\Scoped;
use WeakMap;

#[Scoped]
class AssetTransformContext
{
    /** @var WeakMap<Asset, AssetTransformState> */
    private WeakMap $transforms;

    public function __construct()
    {
        /** @var WeakMap<Asset, AssetTransformState> $transforms */
        $transforms = new WeakMap;
        $this->transforms = $transforms;
    }

    /** @param array<string, mixed>|string $definition */
    public function set(Asset $asset, array|string $definition, ?bool $immediately): Asset
    {
        $this->transforms[$asset] = new AssetTransformState($definition, $immediately);

        return $asset;
    }

    public function get(Asset $asset): ?AssetTransformState
    {
        return $this->transforms[$asset] ?? null;
    }
}
