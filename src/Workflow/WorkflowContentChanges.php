<?php

declare(strict_types=1);

namespace CraftCms\Cms\Workflow;

use CraftCms\Cms\Element\Contracts\ElementInterface;
use CraftCms\Cms\Element\Drafts;
use CraftCms\Cms\Element\Events\ElementLifecycleDeleted;
use CraftCms\Cms\Element\Events\ElementLifecycleDeleting;
use CraftCms\Cms\Element\Events\ElementPersisted;
use CraftCms\Cms\Element\Events\ElementSaving;
use CraftCms\Cms\Workflow\Enums\WorkflowStatus;
use CraftCms\Cms\Workflow\Models\WorkflowRun;
use Illuminate\Container\Attributes\Singleton;
use WeakMap;

/**
 * @internal
 *
 * @since 6.0.0
 */
#[Singleton]
readonly class WorkflowContentChanges
{
    /** @var WeakMap<ElementInterface, list<int>> */
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
        $draftIds = $event->element->mergingCanonicalChanges
            ? []
            : $this->drafts->getDraftIdsForElement($event->element);

        if ($draftIds === []) {
            unset($this->writes[$event->element]);

            return;
        }

        $this->writes[$event->element] = $draftIds;
    }

    public function handleElementPersisted(ElementPersisted $event): void
    {
        $draftIds = $this->writes[$event->element] ?? [];
        unset($this->writes[$event->element]);

        if (
            $draftIds === [] ||
            ($event->element->getDirtyAttributes() === [] && $event->element->getDirtyFields() === [])
        ) {
            return;
        }

        if (($event->element->applyingDraft || $event->element->propagatingFrom?->applyingDraft) && WorkflowRun::query()
            ->whereIn('draftId', $draftIds)
            ->where('status', WorkflowStatus::Approved)
            ->exists()) {
            return;
        }

        $this->workflows->contentChangedByDraftIds($draftIds);
    }

    public function handleElementLifecycleDeleting(ElementLifecycleDeleting $event): void
    {
        $this->deletions[$event->element] = $event->element->mergingCanonicalChanges
            ? []
            : $this->drafts->getDraftIdsForElement($event->element);
    }

    public function handleElementLifecycleDeleted(ElementLifecycleDeleted $event): void
    {
        $draftIds = $this->deletions[$event->element] ?? [];
        unset($this->deletions[$event->element]);

        if ($draftIds !== []) {
            $this->workflows->contentChangedByDraftIds($draftIds);
        }
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
