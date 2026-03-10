<?php

declare(strict_types=1);

namespace CraftCms\Cms\Asset\Events;

use CraftCms\Cms\Asset\Contracts\AssetPreviewHandlerInterface;
use CraftCms\Cms\Asset\Elements\Asset;
use CraftCms\Cms\Shared\Concerns\HandleableEvent;

/**
 * @event RegisterPreviewHandler The event that is triggered when determining the preview handler for an asset.
 */
final class RegisterPreviewHandler
{
    use HandleableEvent;

    public ?AssetPreviewHandlerInterface $previewHandler = null;

    public function __construct(
        public Asset $asset,
    ) {}
}
