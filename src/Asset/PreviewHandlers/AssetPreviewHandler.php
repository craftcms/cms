<?php

declare(strict_types=1);

namespace CraftCms\Cms\Asset\PreviewHandlers;

use CraftCms\Cms\Asset\Contracts\AssetPreviewHandlerInterface;
use CraftCms\Cms\Asset\Elements\Asset;

abstract class AssetPreviewHandler implements AssetPreviewHandlerInterface
{
    public function __construct(protected Asset $asset) {}
}
