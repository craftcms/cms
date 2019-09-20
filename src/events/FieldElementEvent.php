<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\events;

use craft\base\ElementInterface;

/**
 * FieldElementEvent class.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0
 */
class FieldElementEvent extends ModelEvent
{
    // Properties
    // =========================================================================

    /**
     * @var ElementInterface|null The element associated with this event
     */
    public $element;
}
