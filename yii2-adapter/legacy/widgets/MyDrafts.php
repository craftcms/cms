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
     * @since 3.6.5
     * @deprecated in 6.0.0. Use `\CraftCms\Cms\Dashboard\Widgets\MyDrafts` instead.
     */
    class MyDrafts extends Widget
    {
    }
}

class_alias(\CraftCms\Cms\Dashboard\Widgets\MyDrafts::class, MyDrafts::class);
