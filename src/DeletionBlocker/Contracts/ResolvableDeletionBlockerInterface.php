<?php

declare(strict_types=1);

namespace CraftCms\Cms\DeletionBlocker\Contracts;

use CraftCms\Cms\Element\DeletionBlockers\Contracts\DeletionBlockerInterface;

/**
 * A deletion blocker that describes what it affects as plain data, so code
 * other than the control panel’s JavaScript can show and resolve it.
 *
 * Resolving it acknowledges what the deletion does to the items it lists. No
 * separate action runs before the deletion.
 *
 * @since 6.0.0
 */
interface ResolvableDeletionBlockerInterface extends DeletionBlockerInterface
{
    /**
     * Returns a key that identifies the blocker, such as `usages` or `elements`.
     */
    public function getKey(): string;

    /**
     * Returns the items that the deletion will affect.
     *
     * Each item has the following keys:
     *
     * - `label` – The item’s name, such as an entry type’s name
     * - `count` – The number of elements the deletion affects there, or `null` if it couldn’t be counted or doesn’t apply
     * - `description` – A sentence that describes the count, such as “42 entries have a value.”
     *
     * @return list<array{label: string, count: int|null, description: string}>
     */
    public function getItems(): array;

    /**
     * Returns the resolution button’s label, such as “Remove from field layouts”.
     */
    public function getResolutionLabel(): string;
}
