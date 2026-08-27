<?php

declare(strict_types=1);

namespace CraftCms\Cms\Activity;

use CraftCms\Cms\Activity\Data\ElementWriteActivityState;
use CraftCms\Cms\Asset\Elements\Asset;
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
readonly class ElementWriteActivity
{
    public function __construct(private Sites $sites) {}

    public function capture(
        ElementInterface $element,
        bool $recordActivity,
        bool $isNewElement,
    ): ElementWriteActivityState {
        $recordEntry = $element instanceof Entry && EntryActivity::shouldRecord($element, $recordActivity);
        $originalEntry = $recordEntry && ! $isNewElement ? EntryActivity::original($element) : null;
        $originalAsset = $element instanceof Asset && AssetActivity::shouldRecord($element, $recordActivity)
            ? AssetActivity::original($element)
            : null;
        $recordDraft = $recordActivity &&
            $element->getIsDraft() &&
            $element->markDraftAsSaved &&
            ! $element->isProvisionalDraft &&
            ! $element->applyingDraft &&
            ! $element->propagating &&
            ! $element->resaving &&
            ! $element->mergingCanonicalChanges;
        $isNewDraft = false;
        $draftMetadataChanged = false;

        if ($recordDraft) {
            $draftState = DB::table(Table::DRAFTS)
                ->where('id', $element->draftId)
                ->first(['provisional', 'name', 'notes', 'saved'])
                ?? throw new LogicException("Could not load draft $element->draftId before saving it.");
            $wasDraft = $element->id && DB::table(Table::ELEMENTS)
                ->where('id', $element->id)
                ->whereNotNull('draftId')
                ->exists();
            $isNewDraft = ! $wasDraft || (bool) $draftState->provisional || ! (bool) $draftState->saved;
            $draftMetadataChanged =
                (bool) $draftState->provisional !== $element->isProvisionalDraft ||
                $draftState->name !== $element->draftName ||
                $draftState->notes !== $element->draftNotes ||
                (bool) $draftState->saved !== $element->markDraftAsSaved;
        }

        return new ElementWriteActivityState(
            $recordActivity,
            $recordEntry,
            $originalEntry,
            $originalAsset,
            $recordDraft,
            $isNewDraft,
            $draftMetadataChanged,
        );
    }

    /** @param string[] $dirtyFields */
    public function captureContentChanges(
        ElementWriteActivityState $state,
        ElementInterface $element,
        array $dirtyFields,
    ): void {
        $state->draftContentChanged = $state->recordDraft &&
            ($element->getDirtyAttributes() !== [] || $dirtyFields !== []);
    }

    /**
     * @param  string[]  $dirtyAttributes
     * @param  string[]  $dirtyFields
     * @param  array<int, ElementInterface>  $siteElements
     */
    public function record(
        ElementWriteActivityState $state,
        ElementInterface $element,
        bool $isNewElement,
        array $dirtyAttributes,
        array $dirtyFields,
        array $siteElements,
    ): void {
        if ($state->recordEntry && $element instanceof Entry) {
            if ($isNewElement) {
                EntryActivity::recordCreated($element);

                foreach ($siteElements as $siteElement) {
                    if ($siteElement instanceof Entry) {
                        EntryActivity::recordCreated($siteElement);
                    }
                }
            } elseif ($state->originalEntry !== null) {
                EntryActivity::recordUpdated($element, $state->originalEntry, $dirtyAttributes, $dirtyFields);
            }
        }

        if ($element instanceof Asset && $state->originalAsset !== null) {
            AssetActivity::recordReplaced($element, $state->originalAsset);
        }

        if (
            $state->recordActivity &&
            ! $isNewElement &&
            ElementActivity::shouldRecordWrite($element)
        ) {
            $addedSiteElements = $element->isNewForSite ? [$element] : [];

            foreach ($siteElements as $siteElement) {
                if (in_array($siteElement->siteId, $element->newSiteIds, true)) {
                    $addedSiteElements[] = $siteElement;
                }
            }

            foreach ($addedSiteElements as $siteElement) {
                Activities::record(
                    'craft.element.site-added',
                    subject: $siteElement,
                    site: $this->sites->getSiteById($siteElement->siteId),
                );
            }
        }

        if ($state->recordDraft && ($state->isNewDraft || $state->draftMetadataChanged || $state->draftContentChanged)) {
            Activities::record(
                $state->isNewDraft ? 'craft.draft.created' : 'craft.draft.saved',
                subject: $element,
                site: $this->sites->getSiteById($element->siteId),
            );
        }
    }
}
