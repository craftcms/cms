<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\events;

use craft\elements\Entry;
use craft\models\Section;
use yii\base\Event;

/**
 * Entry move event class.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 5.0.0
 */
class EntryMoveEvent extends Event
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
