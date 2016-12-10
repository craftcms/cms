<?php
/**
 * @link      https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license   https://craftcms.com/license
 */

namespace craft\events;

/**
 * Entry type event class.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since  3.0
 */
class EntryTypeEvent extends \yii\base\Event
{
    // Properties
    // =========================================================================

    /**
     * @var \craft\models\EntryType The entry type model associated with the event.
     */
    public $entryType;

    /**
     * @var boolean Whether the entry type is brand new
     */
    public $isNew = false;
}
