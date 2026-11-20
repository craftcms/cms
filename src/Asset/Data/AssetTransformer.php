<?php

declare(strict_types=1);

namespace CraftCms\Cms\Asset\Data;

/**
 * The named transformer a volume uses, from the `assetTransformers` general config setting.
 *
 * @since 6.0.0
 */
readonly class AssetTransformer
{
    /**
     * @param  string  $name  The transformer’s name in the `assetTransformers` general config setting.
     */
    public function __construct(
        public string $name,
    ) {}
}
