<?php

declare(strict_types=1);

namespace CraftCms\Cms\Element\Concerns;

use craft\elements\User as UserElement;
use CraftCms\Cms\Database\Table;
use CraftCms\Cms\Element\Events\ElementAuthorizationCheck;
use CraftCms\Cms\User\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;

/** @phpstan-ignore trait.unused */
trait Draftable
{
    /**
     * @var int|null The ID of the draft’s row in the `drafts` table
     */
    public ?int $draftId = null;

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

    /**
     * {@inheritdoc}
     */
    public function canCreateDrafts(User $user): bool
    {
        if (Event::hasListeners(ElementAuthorizationCheck::class)) {
            Event::dispatch($event = new ElementAuthorizationCheck($this, $user));

            return $event->authorized;
        }

        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function canDuplicateAsDraft(UserElement $user): bool
    {
        // if anything, this will be more lenient than canDuplicate()
        return \Craft::$app->getElements()->canDuplicate($this, $user);
    }

    /**
     * {@inheritdoc}
     */
    public function getIsDraft(): bool
    {
        return ! empty($this->draftId);
    }

    /**
     * {@inheritdoc}
     */
    public static function hasDrafts(): bool
    {
        return false;
    }
}
