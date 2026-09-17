<?php

declare(strict_types=1);

namespace CraftCms\Cms\Workflow;

use CraftCms\Cms\Element\Contracts\ElementInterface;
use CraftCms\Cms\Element\Drafts;
use CraftCms\Cms\Element\Events\ElementLifecycleDeleted;
use CraftCms\Cms\Element\Events\ElementLifecycleDeleting;
use CraftCms\Cms\Element\Events\ElementPersisted;
use CraftCms\Cms\Element\Events\ElementSaving;
use Illuminate\Container\Attributes\Singleton;
use WeakMap;

/** @internal */
#[Singleton]
readonly class WorkflowContentChanges
{
    /** @var WeakMap<ElementInterface, array{draftIds: list<int>, mergingCanonicalChanges: bool}> */
    private WeakMap $writes;

    /** @var WeakMap<ElementInterface, list<int>> */
    private WeakMap $deletions;

    public function __construct(
        private Drafts $drafts,
        private Workflows $workflows,
    ) {
        $this->writes = new WeakMap;
        $this->deletions = new WeakMap;
    }

    public function handleElementSaving(ElementSaving $event): void
    {
        $draftIds = $this->drafts->getDraftIdsForElement($event->element);

        if ($draftIds === []) {
            unset($this->writes[$event->element]);

            return;
        }

        $this->writes[$event->element] = [
            'draftIds' => $draftIds,
            'mergingCanonicalChanges' => $event->element->mergingCanonicalChanges,
        ];
    }

    public function handleElementPersisted(ElementPersisted $event): void
    {
        $write = $this->writes[$event->element] ?? null;
        unset($this->writes[$event->element]);

        if (
            $write === null ||
            (! $write['mergingCanonicalChanges'] &&
                $event->element->getDirtyAttributes() === [] &&
                $event->element->getDirtyFields() === [])
        ) {
            return;
        }

        $this->workflows->contentChangedByDraftIds($write['draftIds']);
    }

    public function handleElementLifecycleDeleting(ElementLifecycleDeleting $event): void
    {
        $this->deletions[$event->element] = $this->drafts->getDraftIdsForElement($event->element);
    }

    public function handleElementLifecycleDeleted(ElementLifecycleDeleted $event): void
    {
        $draftIds = $this->deletions[$event->element] ?? [];
        unset($this->deletions[$event->element]);

        $this->workflows->contentChangedByDraftIds($draftIds);
    }

    /** @return array<class-string, string> */
    public function subscribe(): array
    {
        return [
            ElementSaving::class => 'handleElementSaving',
            ElementPersisted::class => 'handleElementPersisted',
            ElementLifecycleDeleting::class => 'handleElementLifecycleDeleting',
            ElementLifecycleDeleted::class => 'handleElementLifecycleDeleted',
        ];
    }
}
