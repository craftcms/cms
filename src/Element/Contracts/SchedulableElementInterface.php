<?php

declare(strict_types=1);

namespace CraftCms\Cms\Element\Contracts;

use CraftCms\Cms\Element\Events\ElementStatusChanged;

/**
 * An element whose status changes at set dates without a save, such as an entry that goes live at its post date and
 * expires at its expiry date.
 *
 * The element type stores each element’s date-based status. The `craft:update-element-statuses` command, which Craft
 * schedules every minute, updates stored statuses whose dates passed. Craft dispatches {@see ElementStatusChanged}
 * when an element’s status changes in a site, from a save or from that command.
 *
 * @internal This may change before Craft 7, as more element types adopt it.
 *
 * @since 6.0.0
 */
interface SchedulableElementInterface extends ElementInterface
{
    /**
     * Returns the IDs of canonical elements with a date that passed after their stored status was last written.
     *
     * @return list<int>
     */
    public static function findIdsWithOutdatedStatus(): array;

    /**
     * Brings an element’s stored status up to date with its dates, and returns the status it stored along with the
     * element’s statuses by site ID before the change. Returns `null` if the stored status was already up to date,
     * such as when a save or another run updated it first.
     *
     * It should lock the element’s row as saves do, so a change that a save and a run both see is dispatched once.
     *
     * @return array{before: array<int, string>, status: string}|null
     */
    public static function updateStoredStatus(int $elementId): ?array;

    /**
     * Returns canonical elements’ statuses in each of their sites, based on their stored statuses, as
     * {@see ElementInterface::getStatus()} would return them. The array is indexed by element ID and then site ID.
     *
     * @param  list<int>  $elementIds
     * @return array<int, array<int, string>>
     */
    public static function storedStatuses(array $elementIds): array;

    /**
     * Returns the stored status that the element’s last save replaced and the one it wrote, both read under the row
     * lock. Returns `null` if the save didn’t write a stored status, such as when it propagated the element to
     * another site.
     *
     * Craft compares these two statuses, rather than statuses read before and after the save. If the
     * `craft:update-element-statuses` command or another save changes the status during the save or just after
     * it, that one dispatches the change, and this save doesn’t dispatch it again.
     *
     * @return array{replaced: string|null, written: string}|null
     */
    public function getStoredStatusWrite(): ?array;
}
