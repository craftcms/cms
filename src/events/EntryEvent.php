<?php
/**
 * @link      https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license   https://craftcms.com/license
 */

namespace craft\app\events;

use craft\app\elements\Entry;

/**
 * Entry event class.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since  3.0
 */
class EntryEvent extends Event
{
    // Properties
    // =========================================================================

    /**
     * @var Entry The entry model associated with the event.
     */
    public $entry;

    /**
     * @var boolean Whether the entry is brand new
     */
    public $isNew = false;
}
