<?php

declare(strict_types=1);

namespace CraftCms\Cms\Asset\Data;

readonly class AssetTransformResult
{
    public function __construct(
        public string $url,
        public string $mimeType,
        public ?string $filename = null,
        public ?int $width = null,
        public ?int $height = null,
        public ?int $size = null,
    ) {}
}
