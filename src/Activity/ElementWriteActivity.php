<?php

declare(strict_types=1);

namespace CraftCms\Cms\Activity;

use CraftCms\Cms\Activity\Data\ElementWriteActivityState;
use CraftCms\Cms\Activity\EventTypes\ElementSiteAdded;
use CraftCms\Cms\Asset\Elements\Asset;
use CraftCms\Cms\Element\Contracts\ElementInterface;
use CraftCms\Cms\Entry\Elements\Entry;
use CraftCms\Cms\Site\Sites;
use CraftCms\Cms\Support\Facades\Activities;
use Illuminate\Container\Attributes\Singleton;

/** @internal */
#[Singleton]
readonly class ElementWriteActivity
{
    public function __construct(
        private Sites $sites,
        private DraftActivity $drafts,
    ) {}

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

        return new ElementWriteActivityState(
            $recordActivity,
            $recordEntry,
            $originalEntry,
            $originalAsset,
            $recordActivity ? $this->drafts->capture($element) : null,
        );
    }

    /** @param string[] $dirtyFields */
    public function captureContentChanges(
        ElementWriteActivityState $state,
        ElementInterface $element,
        array $dirtyFields,
    ): void {
        $this->drafts->captureContentChanges($state->draft, $element, $dirtyFields);
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
                Activities::record(new ElementSiteAdded(
                    subject: $siteElement,
                    site: $this->sites->getSiteById($siteElement->siteId),
                ));
            }
        }

        $this->drafts->recordWrite($state->draft, $element);
    }
}
