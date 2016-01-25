<?php
/**
 * @link      http://craftcms.com/
 * @copyright Copyright (c) 2015 Pixel & Tonic, Inc.
 * @license   http://craftcms.com/license
 */

namespace craft\app\events;

/**
 * Entry type event class.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since  3.0
 */
class EntryTypeEvent extends Event
{
    // Properties
    // =========================================================================

    /**
     * @var \craft\app\models\EntryType The entry type model associated with the event.
     */
    public $entryType;
}
