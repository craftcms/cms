<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\events;

use craft\base\ElementInterface;
use craft\behaviors\RevisionBehavior;
use yii\base\Event;

/**
 * Revision event class.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.2.0
 */
class RevisionEvent extends Event
{
    /**
     * @var ElementInterface The canonical element
     * @todo rename to canonical in v4
     */
    public ElementInterface $source;

    /**
     * @var int|null The creator ID
     */
    public ?int $creatorId;

    /**
     * @var int The revision number
     */
    public int $revisionNum;

    /**
     * @var string|null The revision notes
     */
    public ?string $revisionNotes;

    /**
     * @var ElementInterface|RevisionBehavior|null The revision associated with the event (if it exists yet)
     */
    public $revision;
}
