<?php

declare(strict_types=1);

namespace CraftCms\Cms\Asset\Events;

use CraftCms\Cms\Asset\Data\AssetProcessor;

readonly class AssetProcessorDeleting
{
    public function __construct(public AssetProcessor $processor) {}
}
