<?php
/**
 * @link      https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license   https://craftcms.com/license
 */

namespace craft\app\events;

use craft\app\base\ElementInterface;

/**
 * Element event class.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since  3.0
 */
class ElementEvent extends Event
{
    // Properties
    // =========================================================================

    /**
     * @var ElementInterface The element model associated with the event.
     */
    public $element;

    /**
     * @var boolean Whether the element is brand new
     */
    public $isNew = false;
}
