<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\events;

use craft\base\Event;
use CraftCms\Cms\Element\Contracts\ElementInterface;

/**
 * Revision event class.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.2.0
 * @deprecated 6.0.0 use one of the events extending {@see \CraftCms\Cms\Element\Events\RevisionEvent} instead.
 */
class RevisionEvent extends Event
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
     * @var int The revision number
     */
    public int $revisionNum;

    /**
     * @var string|null The revision notes
     */
    public ?string $revisionNotes = null;

    /**
     * @var ElementInterface|null The revision associated with the event (if it exists yet)
     */
    public ?ElementInterface $revision = null;
}
