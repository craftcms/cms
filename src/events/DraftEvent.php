<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\events;

use craft\base\ElementInterface;
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
     * @var ElementInterface The canonical element
     */
    public ElementInterface $canonical;

    /**
     * @var int|null The creator ID
     */
    public ?int $creatorId = null;

    /**
     * @var bool Whether this is a provisional draft
     */
    public bool $provisional = false;

    /**
     * @var string|null The draft name
     */
    public ?string $draftName = null;

    /**
     * @var string|null The draft notes
     */
    public ?string $draftNotes = null;

    /**
     * @var ElementInterface|null The draft associated with the event (if it exists yet)
     */
    public ?ElementInterface $draft = null;
}
