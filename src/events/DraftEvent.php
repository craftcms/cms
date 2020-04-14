<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\events;

use craft\base\ElementInterface;
use craft\behaviors\DraftBehavior;
use yii\base\Event;

/**
 * Draft event class.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.2.0
 */
class DraftEvent extends Event
{
    /**
     * @var ElementInterface|null The source element
     */
    public $source;

    /**
     * @var int The creator ID
     */
    public $creatorId;

    /**
     * @var string|null The draft name
     */
    public $draftName;

    /**
     * @var string|null The draft notes
     */
    public $draftNotes;

    /**
     * @var ElementInterface|DraftBehavior|null The draft associated with the event (if it exists yet)
     */
    public $draft;
}
