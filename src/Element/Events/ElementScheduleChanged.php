<?php

declare(strict_types=1);

namespace CraftCms\Cms\Element\Events;

use CraftCms\Cms\Element\Contracts\ElementInterface;
use CraftCms\Cms\Element\Contracts\SchedulableElementInterface;
use DateTimeInterface;
use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;

/**
 * Dispatched after a live element is saved with an upcoming scheduled date added, changed or removed.
 *
 * Drafts and revisions don’t dispatch it. A draft’s dates take effect when it’s applied, which saves
 * the canonical element and dispatches this event then.
 *
 * @see SchedulableElementInterface
 * @since 6.0.0
 */
class ElementScheduleChanged implements ShouldDispatchAfterCommit
{
    public function __construct(
        public ElementInterface&SchedulableElementInterface $element,
        /** @var array<string, DateTimeInterface|null> The scheduled dates before the save, keyed by attribute name */
        public array $oldDates,
        /** @var array<string, DateTimeInterface|null> The scheduled dates after the save, keyed by attribute name */
        public array $newDates,
    ) {}

    /**
     * Dispatches the event when a date changed and either its old or new value is in the future.
     *
     * @param  array<string, DateTimeInterface|null>  $oldDates
     */
    public static function dispatchIfChanged(ElementInterface&SchedulableElementInterface $element, array $oldDates): void
    {
        $newDates = $element->getScheduledDates();
        $now = now()->getTimestamp();

        foreach (array_keys($oldDates + $newDates) as $key) {
            $old = ($oldDates[$key] ?? null)?->getTimestamp();
            $new = ($newDates[$key] ?? null)?->getTimestamp();

            if ($old !== $new && max($old ?? 0, $new ?? 0) > $now) {
                event(new self($element, $oldDates, $newDates));

                return;
            }
        }
    }

    /**
     * Returns the future dates the element now has, earliest first.
     *
     * @return list<DateTimeInterface>
     */
    public function upcomingDates(): array
    {
        return self::futureDates($this->newDates);
    }

    /**
     * Returns the future dates the element had before the save and no longer has, earliest first.
     *
     * A listener can cancel anything it scheduled for these. Not cancelling only costs an extra invalidation.
     *
     * @return list<DateTimeInterface>
     */
    public function cancelledDates(): array
    {
        $upcoming = array_map(fn (DateTimeInterface $date) => $date->getTimestamp(), $this->upcomingDates());

        return array_values(array_filter(
            self::futureDates($this->oldDates),
            fn (DateTimeInterface $date) => ! in_array($date->getTimestamp(), $upcoming, true),
        ));
    }

    /**
     * @param  array<string, DateTimeInterface|null>  $dates
     * @return list<DateTimeInterface>
     */
    private static function futureDates(array $dates): array
    {
        $now = now()->getTimestamp();
        $future = array_filter($dates, fn (?DateTimeInterface $date) => $date !== null && $date->getTimestamp() > $now);
        usort($future, fn (DateTimeInterface $a, DateTimeInterface $b) => $a->getTimestamp() <=> $b->getTimestamp());

        return $future;
    }
}
