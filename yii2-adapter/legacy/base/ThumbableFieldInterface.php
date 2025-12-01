<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\base;

use CraftCms\Cms\Field\Contracts\FieldInterface;

/** @phpstan-ignore-next-line */
if (false) {
    /**
     * @since 5.0.0
     * @deprecated 6.0.0 use {@see \CraftCms\Cms\Field\Contracts\ThumbableFieldInterface} instead.
     */
    interface ThumbableFieldInterface extends FieldInterface
    {
    }
}

class_alias(\CraftCms\Cms\Field\Contracts\ThumbableFieldInterface::class, ThumbableFieldInterface::class);
