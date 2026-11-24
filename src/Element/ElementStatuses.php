<?php

declare(strict_types=1);

namespace CraftCms\Cms\Element;

use CraftCms\Cms\Element\Contracts\ElementInterface;
use CraftCms\Cms\Element\Contracts\SchedulableElementInterface;
use CraftCms\Cms\Element\Events\ElementSaved;
use CraftCms\Cms\Element\Events\ElementSaving;
use CraftCms\Cms\Element\Events\ElementStatusChanged;
use CraftCms\Cms\Support\Facades\ElementCaches;
use CraftCms\Cms\Support\Facades\Elements;
use Illuminate\Container\Attributes\Singleton;
use Illuminate\Support\Facades\DB;
use Throwable;
use WeakMap;

/**
 * Keeps {@see SchedulableElementInterface} elements’ stored statuses up to date, and dispatches
 * {@see ElementStatusChanged} when an element’s status changes in a site.
 *
 * @since 6.0.0
 */
#[Singleton]
readonly class ElementStatuses
{
    /**
     * The statuses of elements being saved, by site ID, read before their saves, and the site to compare for saves
     * that propagate an element to one site on their own.
     *
     * @var WeakMap<ElementInterface, array{statuses: array<int, string>, siteId: int|null}>
     */
    private WeakMap $statusesBeforeSave;

    /**
     * Saved elements whose sites are compared once their transaction commits
     *
     * @var WeakMap<ElementInterface, true>
     */
    private WeakMap $comparingAfterCommit;

    public function __construct()
    {
        $this->statusesBeforeSave = new WeakMap;
        $this->comparingAfterCommit = new WeakMap;
    }

    /**
     * Brings stored statuses up to date for elements whose dates passed, invalidates their caches and dispatches
     * {@see ElementStatusChanged} for each site whose status changed. Returns how many elements’ stored statuses it
     * updated.
     *
     * Run by the `craft:update-element-statuses` command, and every minute by the scheduler. A late or missed run
     * is caught up by the next one, since it compares each element’s stored status with its dates.
     */
    public function updateScheduled(): int
    {
        $count = 0;

        foreach (Elements::getAllElementTypes() as $elementType) {
            if (! is_subclass_of($elementType, SchedulableElementInterface::class)) {
                continue;
            }

            foreach ($elementType::findIdsWithOutdatedStatus() as $elementId) {
                // One element failing, or a listener throwing, mustn't hold up the rest
                try {
                    $before = $elementType::storedStatuses([$elementId])[$elementId] ?? [];

                    if (! $elementType::updateStoredStatus($elementId)) {
                        continue;
                    }

                    $count++;
                    $this->dispatchChanges($elementType, $elementId, $before, invalidateCaches: true);
                } catch (Throwable $exception) {
                    report($exception);
                }
            }
        }

        return $count;
    }

    public function handleElementSaving(ElementSaving $event): void
    {
        $element = $event->element;

        if (
            ! $element instanceof SchedulableElementInterface ||
            $element->getIsDraft() ||
            $element->getIsRevision()
        ) {
            return;
        }

        if ($element->propagating) {
            // The save it’s propagated from compares all sites once it’s saved, unless it isn’t being saved, as when
            // an element is propagated to new sites on its own
            $from = $element->propagatingFrom;

            if ($from !== null && (isset($this->statusesBeforeSave[$from]) || isset($this->comparingAfterCommit[$from]))) {
                return;
            }

            $siteId = $element->siteId;
        }

        $this->statusesBeforeSave[$element] = [
            'statuses' => $element->id ? ($element::storedStatuses([$element->id])[$element->id] ?? []) : [],
            'siteId' => $siteId ?? null,
        ];
    }

    public function handleElementSaved(ElementSaved $event): void
    {
        $element = $event->element;

        if (! $element instanceof SchedulableElementInterface || ! isset($this->statusesBeforeSave[$element])) {
            return;
        }

        ['statuses' => $before, 'siteId' => $siteId] = $this->statusesBeforeSave[$element];
        unset($this->statusesBeforeSave[$element]);

        // The stored status may have been updated by a scheduled run after it was read above. The one the save
        // replaced was read under a lock, so it’s the one to compare.
        $replacedStatus = $element->getReplacedStoredStatus();

        if ($replacedStatus !== null) {
            foreach ($before as $beforeSiteId => $status) {
                if (! in_array($status, [Element::STATUS_DISABLED, Element::STATUS_ARCHIVED], true)) {
                    $before[$beforeSiteId] = $replacedStatus;
                }
            }
        }

        // Once every site is written, such as when a draft is applied, which saves its other sites after this one.
        // The save already invalidated the element’s caches.
        $this->comparingAfterCommit[$element] = true;

        DB::afterCommit(function () use ($element, $before, $siteId): void {
            unset($this->comparingAfterCommit[$element]);

            $this->dispatchChanges(
                $element::class,
                $element->id,
                $before,
                invalidateCaches: false,
                element: $element,
                siteId: $siteId,
            );
        });
    }

    /**
     * @return array<class-string, string>
     */
    public function subscribe(): array
    {
        return [
            ElementSaving::class => 'handleElementSaving',
            ElementSaved::class => 'handleElementSaved',
        ];
    }

    /**
     * @param  class-string<SchedulableElementInterface>  $elementType
     * @param  array<int, string>  $before  The element’s statuses by site ID, before the change
     * @param  int|null  $siteId  The only site to compare, if not all of them
     */
    private function dispatchChanges(
        string $elementType,
        int $elementId,
        array $before,
        bool $invalidateCaches,
        ?ElementInterface $element = null,
        ?int $siteId = null,
    ): void {
        $after = $elementType::storedStatuses([$elementId])[$elementId] ?? [];

        if ($siteId !== null) {
            $after = array_intersect_key($after, [$siteId => true]);
        }

        if ($after === []) {
            return;
        }
        $find = fn (int $siteId): ?ElementInterface => $element?->siteId === $siteId
            ? $element
            : $elementType::find()->id($elementId)->siteId($siteId)->status(null)->one();

        if ($invalidateCaches && ($siteElement = $find(array_key_first($after))) !== null) {
            // The element’s tags cover all of its sites
            ElementCaches::invalidateForElement($siteElement);
        }

        foreach ($after as $siteId => $status) {
            $oldStatus = $before[$siteId] ?? null;

            if ($oldStatus !== $status && ($siteElement = $find($siteId)) !== null) {
                event(new ElementStatusChanged($siteElement, $oldStatus, $status));
            }
        }
    }
}
