<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\widgets;

use craft\base\Widget;

/** @phpstan-ignore-next-line */
if (false) {
    /**
     * @since 3.0.0
     * @deprecated in 6.0.0. Use `\CraftCms\Cms\Dashboard\Widgets\Feed` instead.
     */
    class Feed extends Widget
    {
    }
}

class_alias(\CraftCms\Cms\Dashboard\Widgets\Feed::class, Feed::class);
