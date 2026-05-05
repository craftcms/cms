<?php

/**
 * @link https://craftcms.com/
 *
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\elements;

/** @phpstan-ignore-next-line */
if (false) {
    /**
     * @since 3.0.0
     * @deprecated 6.0.0 use {@see \CraftCms\Cms\Entry\Elements\Entry} instead.
     */
    class Entry extends \CraftCms\Cms\Entry\Elements\Entry
    {
    }
}

class_alias(\CraftCms\Cms\Entry\Elements\Entry::class, Entry::class);
