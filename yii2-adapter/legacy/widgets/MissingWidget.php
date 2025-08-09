<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\widgets;

use craft\base\MissingComponentInterface;
use craft\base\Widget;

/** @phpstan-ignore-next-line */
if (false) {
    /**
     * @since 3.0.0
     * @deprecated in 6.0.0. Use `\CraftCms\Cms\Dashboard\Widgets\MissingWidget` instead.
     */
    class MissingWidget extends Widget implements MissingComponentInterface
    {
    }
}

class_alias(\CraftCms\Cms\Dashboard\Widgets\MissingWidget::class, MissingWidget::class);
