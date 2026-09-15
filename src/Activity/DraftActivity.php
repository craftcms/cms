<?php

declare(strict_types=1);

namespace CraftCms\Cms\Activity;

use Closure;
use CraftCms\Cms\Activity\EventTypes\DraftApplied as DraftAppliedActivityEvent;
use CraftCms\Cms\Activity\EventTypes\DraftCreated as DraftCreatedActivityEvent;
use CraftCms\Cms\Activity\EventTypes\DraftSaved;
use CraftCms\Cms\Database\Table;
use CraftCms\Cms\Element\Contracts\ElementInterface;
use CraftCms\Cms\Element\Events\DraftApplied;
use CraftCms\Cms\Element\Events\DraftCreated;
use CraftCms\Cms\Element\Events\ElementPersisted;
use CraftCms\Cms\Element\Events\ElementSaving;
use CraftCms\Cms\Element\Queries\Contracts\ElementQueryInterface;
use CraftCms\Cms\Entry\Elements\Entry;
use CraftCms\Cms\Field\BaseRelationField;
use CraftCms\Cms\Site\Sites;
use CraftCms\Cms\Support\Facades\Activities;
use CraftCms\Cms\Support\Facades\Structures;
use Illuminate\Container\Attributes\Singleton;
use Illuminate\Support\Facades\DB;
use LogicException;
use WeakMap;

/** @internal */
#[Singleton]
readonly class DraftActivity
{
    /** @var WeakMap<ElementInterface, array{isNew: bool, metadataChanged: bool}> */
    private WeakMap $writes;

    public function __construct(private Sites $sites)
    {
        $this->writes = new WeakMap;
    }

    public function handleElementSaving(ElementSaving $event): void
    {
        $element = $event->element;

        if (
            ! $element->getIsDraft() ||
            ! $element->markDraftAsSaved ||
            $element->duplicateOf !== null ||
            $element->isProvisionalDraft ||
            $element->applyingDraft ||
            $element->propagating ||
            $element->resaving ||
            $element->mergingCanonicalChanges
        ) {
            unset($this->writes[$element]);

            return;
        }

        $draft = DB::table(Table::DRAFTS)
            ->where('id', $element->draftId)
            ->first(['provisional', 'name', 'notes', 'saved'])
            ?? throw new LogicException("Could not load draft $element->draftId before saving it.");
        $wasDraft = $element->id && DB::table(Table::ELEMENTS)
            ->where('id', $element->id)
            ->whereNotNull('draftId')
            ->exists();

        $this->writes[$element] = [
            'isNew' => ! $wasDraft || (bool) $draft->provisional || ! (bool) $draft->saved,
            'metadataChanged' => (bool) $draft->provisional !== $element->isProvisionalDraft ||
                $draft->name !== $element->draftName ||
                $draft->notes !== $element->draftNotes ||
                (bool) $draft->saved !== $element->markDraftAsSaved,
        ];
    }

    public function handleElementPersisted(ElementPersisted $event): void
    {
        $write = $this->writes[$event->element] ?? null;
        unset($this->writes[$event->element]);

        $contentChanged = $event->element->getDirtyAttributes() !== []
            || $event->element->getDirtyFields() !== [];

        if (
            $write === null ||
            (! $write['isNew'] && ! $write['metadataChanged'] && ! $contentChanged)
        ) {
            return;
        }

        $activity = $write['isNew']
            ? new DraftCreatedActivityEvent(subject: $event->element, site: $this->sites->getSiteById($event->element->siteId))
            : new DraftSaved(subject: $event->element, site: $this->sites->getSiteById($event->element->siteId));

        Activities::record($activity);
    }

    public function handleDraftCreated(DraftCreated $event): void
    {
        if ($event->provisional) {
            return;
        }

        Activities::record(new DraftCreatedActivityEvent(
            subject: $event->canonical,
            site: $this->sites->getSiteById($event->canonical->siteId),
        ));
    }

    public function handleDraftApplied(DraftApplied $event): void
    {
        if ($event->provisional) {
            return;
        }

        Activities::record(new DraftAppliedActivityEvent(
            subject: $event->canonical,
            site: $this->sites->getSiteById($event->canonical->siteId),
        ));
    }

    /** @return array<class-string, string> */
    public function subscribe(): array
    {
        return [
            ElementSaving::class => 'handleElementSaving',
            ElementPersisted::class => 'handleElementPersisted',
            DraftCreated::class => 'handleDraftCreated',
            DraftApplied::class => 'handleDraftApplied',
        ];
    }

    /** @return Closure(Entry): void|null */
    public function captureProvisionalApply(ElementInterface $canonical, ElementInterface $draft): ?Closure
    {
        if (
            ! $draft instanceof Entry ||
            ! $draft->isProvisionalDraft ||
            $draft->getPrimaryOwnerId() !== null ||
            ! $canonical instanceof Entry
        ) {
            return null;
        }

        $original = clone $canonical;
        $dirtyAttributes = $draft->getModifiedAttributes();
        $dirtyFields = $draft->getModifiedFields();

        if (in_array('authorIds', $dirtyAttributes, true)) {
            $original->getAuthorIds();
        }

        foreach ($original->getFieldLayout()?->getCustomFields() ?? [] as $field) {
            if ($field instanceof BaseRelationField && in_array($field->handle, $dirtyFields, true)) {
                $value = $original->getFieldValue($field->handle);

                if ($value instanceof ElementQueryInterface) {
                    $original->setFieldValue($field->handle, (clone $value)->status(null)->collect());
                }
            }
        }

        $moveOrigin = $original->structureId !== null && $original->getParentId() !== $draft->getParentId()
            ? StructuralElementActivity::position(
                Structures::getStructureById($original->structureId)->uid,
                $original,
            )
            : null;

        return function (Entry $entry) use ($original, $dirtyAttributes, $dirtyFields, $moveOrigin): void {
            if ($moveOrigin !== null) {
                StructuralElementActivity::recordMoved(
                    $entry,
                    $moveOrigin,
                    StructuralElementActivity::position($moveOrigin['structure'], $entry),
                );
            }

            EntryActivity::recordUpdated($entry, $original, $dirtyAttributes, $dirtyFields);
        };
    }
}
