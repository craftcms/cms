<?php

declare(strict_types=1);

namespace CraftCms\Cms\Element\Concerns;

use CraftCms\Cms\Database\Table;
use CraftCms\Cms\Element\Events\AuthorizeCreateDrafts;
use CraftCms\Cms\User\Elements\User as UserElement;
use Illuminate\Support\Facades\DB;

/**
 * Draftable provides draft functionality for elements.
 *
 * This trait contains properties and methods for managing element drafts, including
 * draft metadata (name, notes, creator), provisional draft handling, and lifecycle
 * methods for saving and deleting drafts.
 *
 * @internal
 */
trait Draftable
{
    /**
     * @var int|null The ID of the draft’s row in the `drafts` table
     */
    public ?int $draftId = null;

    /**
     * @var bool Whether the element is a draft that is about to be applied to the canonical element.
     *
     * @since 5.9.0
     */
    public bool $applyingDraft = false;

    /**
     * @var bool Whether this is a provisional draft.
     */
    public bool $isProvisionalDraft = false;

    /**
     * @var bool Whether provisional changes have been loaded onto this element.
     */
    public bool $hasProvisionalChanges = false;

    /**
     * @var int|null The creator’s ID
     */
    public ?int $draftCreatorId = null;

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
    public bool $trackDraftChanges = true;

    /**
     * @var bool Whether the draft should be marked as saved (if unpublished).
     */
    public bool $markDraftAsSaved = true;

    /**
     * @var UserElement|null|false The creator
     */
    private UserElement|false|null $draftCreator = null;

    /**
     * Returns the draft’s creator.
     */
    public function getDraftCreator(): ?UserElement
    {
        if (! isset($this->draftCreator)) {
            if (! $this->draftCreatorId) {
                return null;
            }

            /** @var UserElement|null $creator */
            $creator = UserElement::find()
                ->id($this->draftCreatorId)
                ->status(null)
                ->one();

            $this->draftCreator = $creator ?? false;
        }

        return $this->draftCreator ?: null;
    }

    /**
     * Sets the draft's creator.
     */
    public function setDraftCreator(?UserElement $creator = null): void
    {
        $this->draftCreator = $creator ?? false;
    }

    public function getDraftName(): string
    {
        return $this->draftName;
    }

    public function handleDraftSave(): void
    {
        if (! $this->getIsDraft()) {
            return;
        }

        DB::table(Table::DRAFTS)
            ->where('id', $this->draftId)
            ->update([
                'provisional' => $this->isProvisionalDraft,
                'name' => $this->draftName,
                'notes' => $this->draftNotes,
                'dateLastMerged' => $this->dateLastMerged,
                'saved' => $this->markDraftAsSaved,
            ]);
    }

    public function handleDraftDelete(): void
    {
        if (! $this->getIsDraft()) {
            return;
        }

        if (! $this->hardDelete) {
            return;
        }

        DB::table(Table::DRAFTS)->delete($this->draftId);
    }

    public function canCreateDrafts(UserElement $user): bool
    {
        event($event = new AuthorizeCreateDrafts($this, $user));

        return $event->authorized;
    }

    public function canDuplicateAsDraft(UserElement $user): bool
    {
        // if anything, this will be more lenient than canDuplicate()
        return $user->can('duplicate', $this);
    }

    public function getIsDraft(): bool
    {
        return ! empty($this->draftId);
    }

    public static function hasDrafts(): bool
    {
        return false;
    }
}
