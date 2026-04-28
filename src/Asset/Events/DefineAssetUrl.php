<?php

declare(strict_types=1);

namespace CraftCms\Cms\Asset\Events;

use CraftCms\Cms\Asset\Elements\Asset;
use CraftCms\Cms\Element\Events\DefineUrl;
use CraftCms\Cms\Image\Data\ImageTransform;

/**
 * @event DefineAssetUrl The event that is triggered when defining the asset’s URL.
 *
 * @see getUrl()
 */
class DefineAssetUrl extends DefineUrl
{
    public function __construct(
        public Asset $asset,
        public ImageTransform|string|array|null $transform,
        public ?string $url = null,
    ) {
        parent::__construct($asset, $url);
    }
}
