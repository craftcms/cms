<?php

declare(strict_types=1);

namespace CraftCms\Cms\Element\Contracts;

use CraftCms\Cms\Element\Queries\Contracts\ElementQueryInterface;
use DateTimeInterface;

/**
 * An element whose status changes on its own at set dates, without being saved, such as an entry
 * going live at its post date or expiring at its expiry date.
 *
 * @since 6.0.0
 */
interface SchedulableElementInterface
{
    /**
     * Returns the dates when the element’s status changes on its own, keyed by attribute name.
     *
     * Past dates are included, so callers can compare a previous set with a new one.
     *
     * @return array<string, DateTimeInterface|null>
     */
    public function getScheduledDates(): array;

    /**
     * Returns a query for live elements with a scheduled date after `$after`, up to and including `$until`.
     */
    public static function findWithScheduledDatesBetween(DateTimeInterface $after, DateTimeInterface $until): ElementQueryInterface;
}
