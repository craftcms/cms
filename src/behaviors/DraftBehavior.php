<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\behaviors;

use craft\base\Element;
use craft\db\Table;
use craft\helpers\Db;
use craft\helpers\ElementHelper;
use DateTime;

/**
 * DraftBehavior is applied to element drafts.
 *
 * @property-read Datetime|null $dateLastMerged The date that the canonical element was last merged into this one
 * @property-read bool $mergingChanges Whether recent changes to the canonical element are being merged into this element
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.2.0
 */
class DraftBehavior extends BaseRevisionBehavior
{
    /**
     * @var string|null The draft name
     */
    public ?string $draftName = null;

    /**
     * @var string|null The draft notes
     */
    public ?string $draftNotes = null;

    /**
     * @var bool Whether to track changes in this draft
     */
    public bool $trackChanges = true;

    /**
     * @var bool Whether the draft should be marked as saved (if unpublished).
     * @since 3.6.6
     */
    public bool $markAsSaved = true;

    /**
     * @inheritdoc
     */
    public function events(): array
    {
        return [
            Element::EVENT_AFTER_PROPAGATE => [$this, 'handleSave'],
            Element::EVENT_AFTER_DELETE => [$this, 'handleDelete'],
        ];
    }

    /**
     * Updates the row in the `drafts` table after the draft element is saved.
     */
    public function handleSave(): void
    {
        Db::update(Table::DRAFTS, [
            'provisional' => $this->owner->isProvisionalDraft,
            'name' => $this->draftName,
            'notes' => $this->draftNotes,
            'dateLastMerged' => Db::prepareDateForDb($this->owner->dateLastMerged),
            'saved' => $this->markAsSaved,
        ], [
            'id' => $this->owner->draftId,
        ], [], false);
    }

    /**
     * Deletes the row in the `drafts` table after the draft element is deleted.
     */
    public function handleDelete(): void
    {
        if ($this->owner->hardDelete) {
            Db::delete(Table::DRAFTS, [
                'id' => $this->owner->draftId,
            ]);
        }
    }

    /**
     * Returns the draft’s name.
     *
     * @return string
     * @since 3.3.17
     */
    public function getDraftName(): string
    {
        return $this->draftName;
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
