<?php
/**
 * @link      https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license   https://craftcms.com/license
 */

namespace craft\app\events;

use craft\app\base\WidgetInterface;

/**
 * WidgetEvent class.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since  3.0
 */
class WidgetEvent extends Event
{
    // Properties
    // =========================================================================

    /**
     * @var WidgetInterface The widget associated with this event.
     */
    public $widget;

    /**
     * @var boolean Whether the widget is brand new
     */
    public $isNew = false;
}
