<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\events;

use craft\elements\GlobalSet;
use craft\models\EntryType;
use craft\models\Section;
use yii\base\Event;

/**
 * Entrify global set event class.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 4.4.16
 */
class EntrifyGlobalSetEvent extends Event
{
    /**
     * @var GlobalSet Global set being entrified
     */
    public GlobalSet $globalSet;

    /**
     * @var Section Section used for entrification
     */
    public Section $section;

    /**
     * @var EntryType Entry type used for entrification
     */
    public EntryType $entryType;
}
