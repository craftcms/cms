<?php

declare(strict_types=1);

namespace CraftCms\Cms\Activity;

use CraftCms\Cms\Activity\Data\DraftWriteActivityState;
use CraftCms\Cms\Activity\EventTypes\DraftCreated;
use CraftCms\Cms\Activity\EventTypes\DraftSaved;
use CraftCms\Cms\Database\Table;
use CraftCms\Cms\Element\Contracts\ElementInterface;
use CraftCms\Cms\Entry\Elements\Entry;
use CraftCms\Cms\Site\Sites;
use CraftCms\Cms\Support\Facades\Activities;
use Illuminate\Container\Attributes\Singleton;
use Illuminate\Support\Facades\DB;
use LogicException;

/** @internal */
#[Singleton]
readonly class DraftActivity
{
    public function __construct(private Sites $sites) {}

    public function capture(ElementInterface $element): ?DraftWriteActivityState
    {
        if (
            ! $element->getIsDraft() ||
            ! $element->markDraftAsSaved ||
            $element->isProvisionalDraft ||
            $element->applyingDraft ||
            $element->propagating ||
            $element->resaving ||
            $element->mergingCanonicalChanges
        ) {
            return null;
        }

        $draft = DB::table(Table::DRAFTS)
            ->where('id', $element->draftId)
            ->first(['provisional', 'name', 'notes', 'saved'])
            ?? throw new LogicException("Could not load draft $element->draftId before saving it.");
        $wasDraft = $element->id && DB::table(Table::ELEMENTS)
            ->where('id', $element->id)
            ->whereNotNull('draftId')
            ->exists();

        return new DraftWriteActivityState(
            isNew: ! $wasDraft || (bool) $draft->provisional || ! (bool) $draft->saved,
            metadataChanged: (bool) $draft->provisional !== $element->isProvisionalDraft ||
                $draft->name !== $element->draftName ||
                $draft->notes !== $element->draftNotes ||
                (bool) $draft->saved !== $element->markDraftAsSaved,
        );
    }

    /** @param string[] $dirtyFields */
    public function captureContentChanges(
        ?DraftWriteActivityState $state,
        ElementInterface $element,
        array $dirtyFields,
    ): void {
        if ($state !== null) {
            $state->contentChanged = $element->getDirtyAttributes() !== [] || $dirtyFields !== [];
        }
    }

    public function recordWrite(?DraftWriteActivityState $state, ElementInterface $element): void
    {
        if ($state !== null && ($state->isNew || $state->metadataChanged || $state->contentChanged)) {
            $event = $state->isNew
                ? new DraftCreated(subject: $element, site: $this->sites->getSiteById($element->siteId))
                : new DraftSaved(subject: $element, site: $this->sites->getSiteById($element->siteId));

            Activities::record($event);
        }
    }

    /**
     * @param  string[]  $dirtyAttributes
     * @param  string[]  $dirtyFields
     */
    public function recordProvisionalApplied(
        Entry $entry,
        Entry $original,
        array $dirtyAttributes,
        array $dirtyFields,
    ): void {
        EntryActivity::recordUpdated($entry, $original, $dirtyAttributes, $dirtyFields);
    }
}
