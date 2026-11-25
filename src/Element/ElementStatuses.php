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
 * Updates the stored statuses of {@see SchedulableElementInterface} elements when their dates pass, and dispatches
 * {@see ElementStatusChanged} when an element’s status changes in a site, from a save or a passed date.
 *
 * @since 6.0.0
 */
#[Singleton]
readonly class ElementStatuses
{
    /**
     * The statuses by site ID of elements being saved, read before each save. `siteId` is set when the save only
     * propagates the element to that site, so only that site is compared.
     *
     * @var WeakMap<ElementInterface, array{statuses: array<int, string>, siteId: int|null}>
     */
    private WeakMap $statusesBeforeSave;

    /**
     * Saved elements whose statuses will be compared when their transaction commits
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
     * Updates the stored statuses of elements whose dates passed, invalidates their caches and dispatches
     * {@see ElementStatusChanged} for each site whose status changed. Returns the number of elements it updated.
     *
     * The `craft:update-element-statuses` command calls this, and the scheduler calls it every minute. If a run is
     * late or skipped, the next run updates every element whose dates passed in the meantime.
     */
    public function updateScheduled(): int
    {
        $count = 0;

        foreach (Elements::getAllElementTypes() as $elementType) {
            if (! is_subclass_of($elementType, SchedulableElementInterface::class)) {
                continue;
            }

            foreach ($elementType::findIdsWithOutdatedStatus() as $elementId) {
                // Report a failure or a listener exception, and move on to the next element
                try {
                    $update = $elementType::updateStoredStatus($elementId);

                    if ($update === null) {
                        continue;
                    }

                    $count++;

                    $this->dispatchChanges(
                        $elementType,
                        $elementId,
                        $update['before'],
                        self::withDateStatus($update['before'], $update['status']),
                        invalidateCaches: true,
                    );
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
            // This includes nested elements added or edited in drafts, which have no draft ID of their own
            ElementHelper::isDraftOrRevision($element)
        ) {
            return;
        }

        if ($element->propagating) {
            // The save that propagates the element compares every site when it finishes. Only compare this site if
            // the element is being propagated on its own, such as to a newly added site.
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

        // Compare the stored status that the save replaced with the one it wrote. Both were read under the row lock,
        // so a change that a scheduled run makes before or after the save is left for that run to dispatch.
        $write = $element->getStoredStatusWrite();

        if ($write !== null && $write['replaced'] !== null) {
            $before = self::withDateStatus($before, $write['replaced']);
        }

        // Wait until every site is written. Applying a draft, for example, saves the other sites after this one.
        // The save already invalidated the element’s caches.
        $this->comparingAfterCommit[$element] = true;

        DB::afterCommit(function () use ($element, $before, $siteId, $write): void {
            unset($this->comparingAfterCommit[$element]);

            $after = $element::storedStatuses([$element->id])[$element->id] ?? [];

            if ($siteId !== null) {
                $after = array_intersect_key($after, [$siteId => true]);
            }

            if ($write !== null) {
                $after = self::withDateStatus($after, $write['written']);
            }

            $this->dispatchChanges(
                $element::class,
                $element->id,
                $before,
                $after,
                invalidateCaches: false,
                element: $element,
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
     * @param  array<int, string>  $after  The element’s statuses by site ID, after the change
     */
    private function dispatchChanges(
        string $elementType,
        int $elementId,
        array $before,
        array $after,
        bool $invalidateCaches,
        ?ElementInterface $element = null,
    ): void {
        if ($after === []) {
            return;
        }

        $find = fn (int $siteId): ?ElementInterface => $element?->siteId === $siteId
            ? $element
            : $elementType::find()->id($elementId)->siteId($siteId)->status(null)->one();
        $firstSiteElement = $find(array_key_first($after));

        // Scheduled runs find nested elements added or edited in drafts, because their rows have no draft ID
        if ($firstSiteElement === null || ElementHelper::isDraftOrRevision($firstSiteElement)) {
            return;
        }

        if ($invalidateCaches) {
            // The element’s cache tags cover all of its sites
            ElementCaches::invalidateForElement($firstSiteElement);
        }

        foreach ($after as $siteId => $status) {
            $oldStatus = $before[$siteId] ?? null;

            if ($oldStatus !== $status && ($siteElement = $find($siteId)) !== null) {
                event(new ElementStatusChanged($siteElement, $oldStatus, $status));
            }
        }
    }

    /**
     * Sets each site’s status to the given date-based status, except in sites where the element is disabled or
     * archived.
     *
     * @param  array<int, string>  $statuses
     * @return array<int, string>
     */
    private static function withDateStatus(array $statuses, string $dateStatus): array
    {
        return array_map(
            fn (string $status): string => in_array($status, [Element::STATUS_DISABLED, Element::STATUS_ARCHIVED], true) ? $status : $dateStatus,
            $statuses,
        );
    }
}
