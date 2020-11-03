<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\events;

use craft\base\WidgetInterface;
use yii\base\Event;

/**
 * WidgetEvent class.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0.0
 */
class WidgetEvent extends Event
{
    /**
     * @var WidgetInterface|null The widget associated with this event.
     */
    public $widget;

    /**
     * @var bool Whether the widget is brand new
     */
    public $isNew = false;
}
