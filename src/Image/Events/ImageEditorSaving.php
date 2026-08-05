<?php

declare(strict_types=1);

namespace CraftCms\Cms\Image\Events;

use CraftCms\Cms\Asset\Elements\Asset;
use CraftCms\Cms\Shared\Concerns\HandleableEvent;

class ImageEditorSaving
{
    use HandleableEvent;

    public ?int $newAssetId = null;

    /**
     * @param  array<string,mixed>  $cropData
     * @param  array<string,mixed>|null  $focalPoint
     * @param  array<string,mixed>  $imageDimensions
     * @param  array<string,mixed>|null  $flipData
     */
    public function __construct(
        public readonly Asset $asset,
        public readonly bool $replace,
        public readonly int $viewportRotation,
        public readonly float $imageRotation,
        public readonly array $cropData,
        public readonly ?array $focalPoint,
        public readonly array $imageDimensions,
        public readonly ?array $flipData,
        public readonly float $zoom,
    ) {}
}
