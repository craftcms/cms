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
     * @since 3.0.0
     * @deprecated in 6.0.0 {@see \CraftCms\Cms\Dashboard\Contracts\WidgetInterface} should be used instead.
     */
    interface WidgetInterface
    {
    }
}

class_alias(\CraftCms\Cms\Dashboard\Contracts\WidgetInterface::class, WidgetInterface::class);
