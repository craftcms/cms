<?php

declare(strict_types=1);

namespace CraftCms\Cms\Asset\Data;

use CraftCms\Cms\Asset\Elements\Asset;
use CraftCms\Cms\Asset\Enums\AssetIngestStatus;

/**
 * @since 6.0.0
 */
readonly class AssetIngestResult
{
    public function __construct(
        public AssetIngestStatus $status,
        public Asset $asset,
        public ?Asset $conflictingAsset = null,
        public ?string $message = null,
    ) {}
}
