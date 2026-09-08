<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\events;

use craft\models\EntryType;
use craft\models\Section;
use craft\models\TagGroup;
use yii\base\Event;

/**
 * Entrify tags event class.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 4.4.16
 */
class EntrifyTagsEvent extends Event
{
    /**
     * @var TagGroup Tag group being entrified
     */
    public TagGroup $tagGroup;

    /**
     * @var Section Section used for entrification
     */
    public Section $section;

    /**
     * @var EntryType Entry type used for entrification
     */
    public EntryType $entryType;

    /**
     * @var bool Whether fields were entrified
     */
    public bool $fieldsConverted;

    /**
     * @var array The array of fields that were entrified
     */
    public array $fields = [];
}
