<?php

declare(strict_types=1);

namespace CraftCms\Cms\Element\Data;

use Closure;
use CraftCms\Cms\Asset\Elements\Asset;
use CraftCms\Cms\Image\Enums\ImageTransformMode;

/**
 * An element’s thumbnail, which can be rendered at any size, and may show an asset’s image.
 *
 * @since 6.0.0
 */
readonly class Thumb
{
    /**
     * @param  Closure(int, ImageTransformMode): ?string  $html  Renders the thumbnail at a size and mode.
     * @param  Asset|null  $asset  The asset whose image the thumbnail shows, if any.
     */
    public function __construct(
        private Closure $html,
        private ?Asset $asset = null,
    ) {}

    /**
     * Returns the thumbnail’s HTML.
     *
     * @param  int  $size  The maximum width and height the thumbnail should have.
     */
    public function getHtml(int $size, ImageTransformMode $mode = ImageTransformMode::Fit): ?string
    {
        return ($this->html)($size, $mode);
    }

    /**
     * Returns the asset whose image the thumbnail shows, if any.
     */
    public function getAsset(): ?Asset
    {
        return $this->asset;
    }

    /**
     * Returns whether the thumbnail’s image can cover an element card.
     */
    public function supportsCover(): bool
    {
        return $this->asset?->canCoverCard() ?? false;
    }
}
