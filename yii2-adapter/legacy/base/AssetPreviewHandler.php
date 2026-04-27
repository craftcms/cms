<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\base;

/** @phpstan-ignore-next-line */
if (false) {
    /**
     * AssetPreviewHandler is the base class for classes that provide asset previewing functionality.
     *
     * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
     * @since 3.4.0
     * @deprecated in 6.0.0. Use {@see \CraftCms\Cms\Asset\PreviewHandlers\AssetPreviewHandler} instead.
     */
    abstract class AssetPreviewHandler extends Component implements \CraftCms\Cms\Asset\Contracts\AssetPreviewHandlerInterface
    {
    }
}

class_alias(\CraftCms\Cms\Asset\PreviewHandlers\AssetPreviewHandler::class, AssetPreviewHandler::class);
