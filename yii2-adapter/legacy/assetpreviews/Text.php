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
     * Provides functionality to preview text files as HTML.
     *
     * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
     * @since 3.4.0
     * @deprecated in 6.0.0. Use {@see \CraftCms\Cms\Asset\PreviewHandlers\Text} instead.
     */
    class Text extends \CraftCms\Cms\Asset\PreviewHandlers\Text
    {
    }
}

class_alias(\CraftCms\Cms\Asset\PreviewHandlers\Text::class, Text::class);
