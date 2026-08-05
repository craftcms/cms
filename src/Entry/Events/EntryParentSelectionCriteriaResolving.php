<?php

declare(strict_types=1);

namespace CraftCms\Cms\Entry\Events;

use CraftCms\Cms\Entry\Elements\Entry;

/**
 * @event EntryParentSelectionCriteriaResolving The event that is triggered when defining the parent selection criteria.
 *
 * @see Entry::_parentOptionCriteria()
 */
class EntryParentSelectionCriteriaResolving
{
    /** @param array<string, array<array-key, int|string>|bool|int|string|null> $criteria */
    public function __construct(
        public Entry $entry,
        public array $criteria,
    ) {}
}
