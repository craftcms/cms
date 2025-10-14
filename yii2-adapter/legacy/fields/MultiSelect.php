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
     * @since 3.0.0
     * @deprecated 6.0.0 use {@see \CraftCms\Cms\Field\MultiSelect} instead.
     */
    class MultiSelect
    {
    }
}

class_alias(\CraftCms\Cms\Field\MultiSelect::class, MultiSelect::class);
