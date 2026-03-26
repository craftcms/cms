<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\assetpreviews;

/** @phpstan-ignore-next-line */
if (false) {
    /**
     * Provides functionality to preview videos.
     *
     * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
     * @since 3.4.3
     * @deprecated in 6.0.0. Use {@see \CraftCms\Cms\Asset\PreviewHandlers\Video} instead.
     */
    class Video extends \CraftCms\Cms\Asset\PreviewHandlers\Video
    {
    }
}

class_alias(\CraftCms\Cms\Asset\PreviewHandlers\Video::class, Video::class);
