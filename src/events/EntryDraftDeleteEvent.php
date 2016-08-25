<?php
/**
 * @link      https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license   https://craftcms.com/license
 */

namespace craft\app\events;

/**
 * Entry draft delete event class.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since  3.0
 */
class EntryDraftDeleteEvent extends Event
{
    // Properties
    // =========================================================================

    /**
     * @var \craft\app\models\EntryDraft The draft model associated with the event.
     */
    public $draft;
}
