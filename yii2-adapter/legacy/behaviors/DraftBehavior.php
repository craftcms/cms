<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\behaviors;

use CraftCms\Cms\Element\Element;
use craft\helpers\ElementHelper;
use CraftCms\Cms\Database\Table;
use DateTime;
use Illuminate\Support\Facades\DB;

/**
 * DraftBehavior is applied to element drafts.
 *
 * @property-read Datetime|null $dateLastMerged The date that the canonical element was last merged into this one
 * @property-read bool $mergingChanges Whether recent changes to the canonical element are being merged into this element
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.2.0
 * @deprecated 6.0.0 use {@see \CraftCms\Cms\Element\Concerns\Draftable} instead.
 */
class DraftBehavior extends BaseRevisionBehavior
{
    /**
     * @var string|null The draft name
     */
    public ?string $draftName {
        get => $this->owner->draftName;
        set(?string $value) => $this->owner->draftName = $value;
    }

    /**
     * @var string|null The draft notes
     */
    public ?string $draftNotes {
        get => $this->owner->draftNotes;
        set(?string $value) => $this->owner->draftNotes = $value;
    }

    /**
     * @var bool Whether to track changes in this draft
     */
    public bool $trackChanges {
        get => $this->owner->trackDraftChanges;
        set(bool $value) => $this->owner->trackDraftChanges = $value;
    }

    /**
     * @var bool Whether the draft should be marked as saved (if unpublished).
     * @since 3.6.6
     */
    public bool $markAsSaved {
        get => $this->owner->markDraftAsSaved;
        set(bool $value) => $this->owner->markDraftAsSaved = $value;
    }

    /**
     * Returns the draft’s name.
     *
     * @return string
     * @since 3.3.17
     */
    public function getDraftName(): string
    {
        return $this->owner->getDraftName();
    }

    /**
     * Returns whether the source element has been saved since the time this draft was
     * created or last merged.
     *
     * @return bool
     * @since 3.4.0
     * @deprecated in 3.7.12. Use [[ElementHelper::isOutdated()]] instead.
     */
    public function getIsOutdated(): bool
    {
        return ElementHelper::isOutdated($this->owner);
    }
}
