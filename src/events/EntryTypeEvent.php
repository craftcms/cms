<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\events;

use yii\base\Event;

/**
 * Entry type event class.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0
 */
class EntryTypeEvent extends Event
{
    // Properties
    // =========================================================================

    /**
     * @var \craft\models\EntryType|null The entry type model associated with the event.
     */
    public $entryType;

    /**
     * @var bool Whether the entry type is brand new
     */
    public $isNew = false;
}
