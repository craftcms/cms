<?php

declare(strict_types=1);

namespace CraftCms\Cms\Asset\Events;

use craft\models\ImageTransform;
use CraftCms\Cms\Asset\Elements\Asset;
use CraftCms\Cms\Element\Events\BeforeDefineUrl;

/**
 * @event BeforeDefineAssetUrl The event that is triggered before defining the asset’s URL.
 *
 * @see getUrl()
 */
final class BeforeDefineAssetUrl extends BeforeDefineUrl
{
    public function __construct(
        public Asset $asset,
        public ImageTransform|string|array|null $transform,
        public ?string $url = null,
    ) {
        parent::__construct($asset, $url);
    }
}
