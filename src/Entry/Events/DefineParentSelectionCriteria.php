<?php

declare(strict_types=1);

namespace CraftCms\Cms\Entry\Events;

use CraftCms\Cms\Entry\Elements\Entry;

/**
 * @event DefineParentSelectionCriteria The event that is triggered when defining the parent selection criteria.
 *
 * @see Entry::_parentOptionCriteria()
 */
class DefineParentSelectionCriteria
{
    public function __construct(
        public Entry $entry,
        public array $criteria,
    ) {}
}
