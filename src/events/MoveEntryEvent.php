<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\events;

use craft\base\Event;
use craft\elements\Entry;
use craft\models\Section;

/**
 * Move entry event class.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 5.3.0
 */
class MoveEntryEvent extends Event
{
    /**
     * @var Entry The Entry that we're about to move to a different section.
     */
    public Entry $entry;

    /**
     * @var Section The section we're moving the entry to
     */
    public Section $section;
}
