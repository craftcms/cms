<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\events;

use craft\base\ElementInterface;

/**
 * Element event class.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0.0
 * @deprecated 6.0.0 Use {@see \CraftCms\Cms\Element\Events\BeforeSaveElement} or {@see \CraftCms\Cms\Element\Events\AfterSaveElement} instead.
 */
class ElementEvent extends CancelableEvent
{
    /**
     * @var ElementInterface The element model associated with the event.
     */
    public ElementInterface $element;

    /**
     * @var bool Whether the element is brand new
     */
    public bool $isNew = false;
}
