<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\events;

use craft\base\Event;
use CraftCms\Cms\Dashboard\Contracts\WidgetInterface;

/**
 * WidgetEvent class.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0.0
 * @deprecated in 6.0.0. Use `\CraftCms\Cms\Dashboard\Events\WidgetSaving`, `\CraftCms\Cms\Dashboard\Events\WidgetSaved`, `\CraftCms\Cms\Dashboard\Events\WidgetDeleting`, `\CraftCms\Cms\Dashboard\Events\WidgetDeleted` instead.
 */
class WidgetEvent extends Event
{
    /**
     * @var WidgetInterface The widget associated with this event.
     */
    public WidgetInterface $widget;

    /**
     * @var bool Whether the widget is brand new
     */
    public bool $isNew = false;
}
