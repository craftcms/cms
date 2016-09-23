<?php
/**
 * @link      https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license   https://craftcms.com/license
 */

namespace craft\app\events;

/**
 * Element event class.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since  3.0
 */
class PopulateElementEvent extends ElementEvent
{
    // Properties
    // =========================================================================

    /**
     * @var array The element query’s result for this element.
     */
    public $row;
}
