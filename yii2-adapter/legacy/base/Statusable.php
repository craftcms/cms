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
     * @since 5.0.0
     * @deprecated 6.0.0 use {@see \CraftCms\Cms\Component\Contracts\Statusable} instead.
     */
    interface Statusable
    {
    }
}

class_alias(\CraftCms\Cms\Component\Contracts\Statusable::class, Statusable::class);
