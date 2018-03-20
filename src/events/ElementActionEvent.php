<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\events;

use craft\base\ElementActionInterface;
use craft\elements\db\ElementQueryInterface;

/**
 * Element action event class.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0
 */
class ElementActionEvent extends CancelableEvent
{
    // Properties
    // =========================================================================

    /**
     * @var ElementActionInterface|null The element action associated with the event
     */
    public $action;

    /**
     * @var ElementQueryInterface|null The element query associated with the event
     */
    public $criteria;

    /**
     * @var string|null The message that should be displayed in the Control Panel if [[$isValid]] is false
     */
    public $message;
}
