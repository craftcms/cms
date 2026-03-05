<?php

declare(strict_types=1);

namespace CraftCms\Cms\Image\Events;

use CraftCms\Cms\Asset\Elements\Asset;

final class DeletingTransformedImage
{
    public function __construct(
        public Asset $asset,
        public string $path,
    ) {}
}
