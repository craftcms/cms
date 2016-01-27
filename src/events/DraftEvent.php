<?php
/**
 * @link      http://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license   http://craftcms.com/license
 */

namespace craft\app\events;

/**
 * Draft event class.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since  3.0
 */
class DraftEvent extends Event
{
    // Properties
    // =========================================================================

    /**
     * @var \craft\app\models\EntryDraft The draft model associated with the event.
     */
    public $draft;
}
