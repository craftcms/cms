<?php

declare(strict_types=1);

namespace CraftCms\Cms\Asset\Events;

use craft\models\ImageTransform;
use CraftCms\Cms\Asset\Elements\Asset;

/**
 * @event BeforeGenerateTransform The event that is triggered before a transform is generated for an asset.
 */
final class BeforeGenerateTransform
{
    public function __construct(
        public Asset $asset,
        public ImageTransform|string|array|null $transform,
        public ?string $url = null,
    ) {}
}
