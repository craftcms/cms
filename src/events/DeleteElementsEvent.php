<?php
/**
 * @link      http://craftcms.com/
 * @copyright Copyright (c) 2015 Pixel & Tonic, Inc.
 * @license   http://craftcms.com/license
 */

namespace craft\app\events;

/**
 * Delete Elements event class.
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
