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
     * Returns the IDs of canonical elements whose dates passed since their stored statuses were last updated.
     *
     * @return list<int>
     */
    public static function findIdsWithOutdatedStatus(): array;

    /**
     * Brings an element’s stored status up to date with its dates, unless something else already did, and returns
     * the stored status it wrote with the element’s statuses by site ID before the change, read under the same row
     * lock as a save’s. Returns `null` if it didn’t change it.
     *
     * An element type may save the element instead, which dispatches {@see ElementStatusChanged} itself, and return
     * `null` for `before`.
     *
     * @return array{before: array<int, string>|null, status: string}|null
     */
    public static function updateStoredStatus(int $elementId): ?array;

    /**
     * Returns canonical elements’ statuses in each of their sites, as {@see ElementInterface::getStatus()} returns them
     * from their stored statuses, indexed by element ID and site ID.
     *
     * @param  list<int>  $elementIds
     * @return array<int, array<int, string>>
     */
    public static function storedStatuses(array $elementIds): array;

    /**
     * Returns the stored status the element’s last save replaced, read under a row lock, and the one it wrote, or
     * `null` if the save didn’t write one, such as when it propagated the element to another site.
     *
     * Craft compares these rather than statuses read before and after the save, so a status that the
     * `craft:update-element-statuses` command or another save changed during or right after the save is dispatched
     * once, by the one that changed it.
     *
     * @return array{replaced: string|null, written: string}|null
     */
    public function getStoredStatusWrite(): ?array;
}
