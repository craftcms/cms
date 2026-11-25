<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\elements\deletionblockers;

/**
 * ResolvableDeletionBlockerInterface defines a deletion blocker that describes what it affects as plain data, so it
 * can be shown and resolved without the control panel’s JavaScript.
 *
 * Resolving it acknowledges what the deletion does to the items it lists. No separate action runs before the deletion.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 5.13.0
 */
interface ResolvableDeletionBlockerInterface extends DeletionBlockerInterface
{
    /**
     * Returns a key that identifies the blocker, such as `usages` or `entries`.
     */
    public function getKey(): string;

    /**
     * Returns the items that the deletion will affect.
     *
     * Each item has the following keys:
     *
     * - `label` – The item’s name, such as an entry type’s name
     * - `count` – The number of elements the deletion affects there, or `null` if it couldn’t be counted
     * - `description` – A sentence that describes the count, such as “42 entries have a value.”
     *
     * @return array{label: string, count: int|null, description: string}[]
     */
    public function getItems(): array;

    /**
     * Returns the resolution button’s label, such as “Remove from field layouts”.
     */
    public function getResolutionLabel(): string;
}
