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
     * @deprecated 6.0.0 use {@see \CraftCms\Cms\Field\BaseOptionsField} instead.
     */
    abstract class BaseOptionsField
    {
    }
}

class_alias(\CraftCms\Cms\Field\BaseOptionsField::class, BaseOptionsField::class);
