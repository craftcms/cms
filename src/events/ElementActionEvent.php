<?php
/**
 * @link      https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license   https://craftcms.com/license
 */

namespace craft\app\events;

use craft\app\base\ElementActionInterface;
use craft\app\elements\db\ElementQueryInterface;

/**
 * Element action event class.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since  3.0
 */
class ElementActionEvent extends Event
{
    // Properties
    // =========================================================================

    /**
     * @var ElementActionInterface The element action associated with the event
     */
    public $action;

    /**
     * @var ElementQueryInterface The element query associated with the event
     */
    public $criteria;

    /**
     * @var string The message that should be displayed in the Control Panel if [[$isValid]] is false
     */
    public $message;
}
