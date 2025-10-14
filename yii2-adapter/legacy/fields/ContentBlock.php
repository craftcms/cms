<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\fields;

/** @phpstan-ignore-next-line **/
if (false) {
    /**
     * @since 5.8.0
     * @deprecated 6.0.0 use {@see \CraftCms\Cms\Field\ContentBlock} instead.
     */
    class ContentBlock
    {
    }
}

class_alias(\CraftCms\Cms\Field\ContentBlock::class, ContentBlock::class);
