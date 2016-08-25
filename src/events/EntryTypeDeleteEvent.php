<?php
/**
 * @link      https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license   https://craftcms.com/license
 */

namespace craft\app\events;

/**
 * Entry type delete event class.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since  3.0
 */
class EntryTypeDeleteEvent extends Event
{
    // Properties
    // =========================================================================

    /**
     * @var \craft\app\models\EntryType The entry type model associated with the event.
     */
    public $entryType;
}
