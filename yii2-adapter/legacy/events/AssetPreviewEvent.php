<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\events;

use craft\base\Event;
use CraftCms\Cms\Asset\Contracts\AssetPreviewHandlerInterface;
use CraftCms\Cms\Asset\Elements\Asset;

/**
 * Asset preview event class.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.4.0
 * @deprecated 6.0.0 use {@see \CraftCms\Cms\Asset\Events\PreviewHandlerResolving} instead.
 */
class AssetPreviewEvent extends Event
{
    /**
     * @var Asset The asset associated with the event.
     */
    public Asset $asset;

    /**
     * An AssetPreview handler
     *
     * @var AssetPreviewHandlerInterface|null $previewHandler
     */
    public ?AssetPreviewHandlerInterface $previewHandler = null;
}
