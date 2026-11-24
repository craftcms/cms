<?php

declare(strict_types=1);

namespace CraftCms\Cms\Element\Contracts;

use CraftCms\Cms\Element\Events\ElementStatusChanged;

/**
 * An element whose status changes on its own at set dates, without being saved, such as an entry going live at its
 * post date or expiring at its expiry date.
 *
 * Its element type stores each element’s date-based status, and the `craft:update-element-statuses` command, which
 * Craft schedules every minute, brings the stored statuses up to date. Craft dispatches {@see ElementStatusChanged}
 * whenever an element’s status changes in a site, from a save or from that command.
 *
 * @internal This may change before Craft 7, as more element types adopt it.
 *
 * @since 6.0.0
 */
interface SchedulableElementInterface extends ElementInterface
{
    /**
     * Returns the IDs of canonical elements whose stored status no longer matches their dates.
     *
     * @return list<int>
     */
    public static function findIdsWithOutdatedStatus(): array;

    /**
     * Brings an element’s stored status up to date with its dates, unless something else changed it since it was
     * read, and returns whether it changed it.
     *
     * An element type may save the element instead, which dispatches {@see ElementStatusChanged} itself, and return
     * `false`.
     */
    public static function updateStoredStatus(int $elementId): bool;

    /**
     * Returns canonical elements’ statuses in each of their sites, as {@see ElementInterface::getStatus()} returns them
     * from their stored statuses, indexed by element ID and site ID.
     *
     * @param  list<int>  $elementIds
     * @return array<int, array<int, string>>
     */
    public static function storedStatuses(array $elementIds): array;

    /**
     * Returns the stored status that the element’s last save replaced, read under a row lock, or `null` if the save
     * didn’t replace one, such as when the element is new.
     *
     * Craft compares it with the stored status after the save, rather than one read before the save started, so a
     * status the `craft:update-element-statuses` command updated during the save isn’t dispatched twice.
     */
    public function getReplacedStoredStatus(): ?string;
}
