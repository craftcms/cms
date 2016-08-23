<?php
/**
 * @link      https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license   https://craftcms.com/license
 */

namespace craft\app\events;

use craft\app\elements\Entry;

/**
 * Revert entry event class.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since  3.0
 */
class RevertEntryEvent extends Event
{
    // Properties
    // =========================================================================

    /**
     * @var Entry The entry model associated with the event.
     */
    public $entry;
}
