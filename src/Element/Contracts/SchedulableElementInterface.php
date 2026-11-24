<?php

declare(strict_types=1);

namespace CraftCms\Cms\Element\Contracts;

use CraftCms\Cms\Element\Queries\Contracts\ElementQueryInterface;
use DateTimeInterface;

/**
 * An element whose status changes on its own at set dates, without being saved, such as an entry
 * going live at its post date or expiring at its expiry date.
 *
 * Craft invalidates these elements’ caches when their dates pass, from the `craft:invalidate-scheduled-element-caches`
 * scheduled task.
 *
 * @since 6.0.0
 */
interface SchedulableElementInterface
{
    /**
     * Returns a query for live elements with a scheduled date after `$after`, up to and including `$until`.
     */
    public static function findWithScheduledDatesBetween(DateTimeInterface $after, DateTimeInterface $until): ElementQueryInterface;
}
