<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\events;

use craft\models\EntryType;
use yii\base\Event;

/**
 * Entry type event class.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0.0
 */
class EntryTypeEvent extends Event
{
    /**
     * @var EntryType The entry type model associated with the event.
     */
    public EntryType $entryType;

    /**
     * @var bool Whether the entry type is brand new
     */
    public bool $isNew = false;
}
