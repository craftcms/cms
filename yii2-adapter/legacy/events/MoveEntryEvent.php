<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\events;

use craft\base\Event;
use CraftCms\Cms\Entry\Elements\Entry;
use CraftCms\Cms\Section\Data\Section;

/**
 * Move entry event class.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 5.3.0
 * @deprecated 6.0.0 use {@see \CraftCms\Cms\Entry\Events\MovingEntryToSection} or {@see \CraftCms\Cms\Entry\Events\EntryMovedToSection} instead.
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
