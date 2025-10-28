<?php

declare(strict_types=1);

namespace CraftCms\Cms\Element\Concerns;

use craft\elements\User;
use CraftCms\Cms\Database\Table;
use Illuminate\Support\Facades\DB;

/** @phpstan-ignore trait.unused */
trait Draftable
{
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
     * @var User|null|false The creator
     */
    private User|false|null $draftCreator = null;

    /**
     * Returns the draft’s creator.
     */
    public function getDraftCreator(): ?User
    {
        if (! isset($this->draftCreator)) {
            if (! $this->draftCreatorId) {
                return null;
            }

            /** @var User|null $creator */
            $creator = User::find()
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
    public function setDraftCreator(?User $creator = null): void
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
}
