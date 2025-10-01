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
     * @since 5.3.0
     * @deprecated 6.0.0 use {@see \CraftCms\Cms\Field\Link} instead.
     */
    class Link
    {
    }
}

class_alias(\CraftCms\Cms\Field\Link::class, Link::class);
