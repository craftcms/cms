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
 * @since 3.0.0
 */
class ElementActionEvent extends CancelableEvent
{
    /**
     * @var ElementActionInterface The element action associated with the event
     */
    public ElementActionInterface $action;

    /**
     * @var ElementQueryInterface The element query associated with the event
     */
    public ElementQueryInterface $criteria;

    /**
     * @var string|null The message that should be displayed in the control panel if [[$isValid]] is false
     */
    public ?string $message = null;
}
