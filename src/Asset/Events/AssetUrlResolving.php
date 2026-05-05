<?php

declare(strict_types=1);

namespace CraftCms\Cms\Asset\Events;

use CraftCms\Cms\Asset\Elements\Asset;
use CraftCms\Cms\Element\Events\BeforeDefineUrl;
use CraftCms\Cms\Image\Data\ImageTransform;

/**
 * @event AssetUrlResolving The event that is triggered before defining the asset’s URL.
 *
 * @see getUrl()
 */
class AssetUrlResolving extends BeforeDefineUrl
{
    public function __construct(
        public Asset $asset,
        public ImageTransform|string|array|null $transform,
        public ?string $url = null,
    ) {
        parent::__construct($asset, $url);
    }
}
