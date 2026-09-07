<?php

declare(strict_types=1);

namespace CraftCms\Cms\Activity;

use CraftCms\Cms\Activity\EventTypes\ElementSiteAdded;
use CraftCms\Cms\Asset\Elements\Asset;
use CraftCms\Cms\Element\Contracts\ElementInterface;
use CraftCms\Cms\Element\Events\ElementPersisted;
use CraftCms\Cms\Element\Events\ElementSaving;
use CraftCms\Cms\Entry\Elements\Entry;
use CraftCms\Cms\Site\Sites;
use CraftCms\Cms\Support\Facades\Activities;
use Illuminate\Container\Attributes\Singleton;
use WeakMap;

/** @internal */
#[Singleton]
readonly class ElementWriteActivity
{
    /** @var WeakMap<ElementInterface, bool> */
    private WeakMap $writes;

    /** @var WeakMap<ElementInterface, array{entry: Entry, authorChanged: bool}> */
    private WeakMap $originalEntries;

    /** @var WeakMap<ElementInterface, Asset> */
    private WeakMap $originalAssets;

    public function __construct(
        private Sites $sites,
    ) {
        $this->writes = new WeakMap;
        $this->originalEntries = new WeakMap;
        $this->originalAssets = new WeakMap;
    }

    public function handleElementSaving(ElementSaving $event): void
    {
        $element = $event->element;
        $this->forgetWrite($element);

        if ($element->duplicateOf !== null) {
            return;
        }

        $recordEntry = $element instanceof Entry && ElementActivity::shouldRecordWrite($element);
        $recordAsset = $element instanceof Asset && AssetActivity::shouldRecord($element);
        $recordElement = ElementActivity::shouldRecordWrite($element);

        if (! $recordEntry && ! $recordAsset && ! $recordElement) {
            return;
        }

        $this->writes[$element] = $event->isNew;

        if ($recordEntry && ! $event->isNew) {
            $original = EntryActivity::original($element);

            if ($original !== null) {
                $this->originalEntries[$element] = [
                    'entry' => $original,
                    'authorChanged' => $element->getAuthorIds() !== $original->getAuthorIds(),
                ];
            }
        }

        if ($recordAsset) {
            $this->originalAssets[$element] = AssetActivity::original($element);
        }
    }

    public function handleElementPersisted(ElementPersisted $event): void
    {
        $element = $event->element;

        if ($element->propagatingFrom !== null) {
            $isNew = $this->writes[$element->propagatingFrom] ?? null;

            if ($isNew === true && $element instanceof Entry) {
                EntryActivity::recordCreated($element);
            } elseif ($isNew === false && $element->isNewForSite) {
                $this->recordSiteAdded($element);
            }

            return;
        }

        $isNew = $this->writes[$element] ?? null;
        $originalEntry = $this->originalEntries[$element] ?? null;
        $originalAsset = $this->originalAssets[$element] ?? null;
        $this->forgetWrite($element);

        if ($isNew === null) {
            return;
        }

        if ($element instanceof Entry) {
            if ($isNew) {
                EntryActivity::recordCreated($element);
            } elseif ($originalEntry !== null) {
                EntryActivity::recordUpdated(
                    $element,
                    $originalEntry['entry'],
                    $element->getDirtyAttributes(),
                    $element->getDirtyFields(),
                    $originalEntry['authorChanged'],
                );
            }
        }

        if ($element instanceof Asset && $originalAsset !== null) {
            AssetActivity::recordReplaced($element, $originalAsset);
        }

        if (! $isNew && $element->isNewForSite) {
            $this->recordSiteAdded($element);
        }
    }

    private function recordSiteAdded(ElementInterface $element): void
    {
        Activities::record(new ElementSiteAdded(
            subject: $element,
            site: $this->sites->getSiteById($element->siteId),
        ));
    }

    private function forgetWrite(ElementInterface $element): void
    {
        unset(
            $this->writes[$element],
            $this->originalEntries[$element],
            $this->originalAssets[$element],
        );
    }

    /** @return array<class-string, string> */
    public function subscribe(): array
    {
        return [
            ElementSaving::class => 'handleElementSaving',
            ElementPersisted::class => 'handleElementPersisted',
        ];
    }
}
