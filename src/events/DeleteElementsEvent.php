<?php
/**
 * @link      https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license   https://craftcms.com/license
 */

namespace craft\app\events;

/**
 * DeleteElementsEvent class.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since  3.0
 */
class DeleteElementsEvent extends Event
{
    // Properties
    // =========================================================================

    /**
     * @var array The element IDs associated with this event.
     */
    public $elementIds;
}
