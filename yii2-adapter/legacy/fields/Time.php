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
     * @since 3.5.12
     * @deprecated 6.0.0 use {@see \CraftCms\Cms\Field\Time} instead.
     */
    class Time
    {
    }
}

class_alias(\CraftCms\Cms\Field\Time::class, Time::class);
