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
     * @since 5.5.0
     * @deprecated 6.0.0 use {@see \CraftCms\Cms\Field\Range} instead.
     */
    class Range
    {
    }
}

class_alias(\CraftCms\Cms\Field\Range::class, Range::class);
