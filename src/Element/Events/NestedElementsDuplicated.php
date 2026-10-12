<?php

declare(strict_types=1);

namespace CraftCms\Cms\Element\Events;

use CraftCms\Cms\Element\Contracts\ElementInterface;
use CraftCms\Cms\Element\NestedElementManager;

/**
 * @since 6.0.0
 */
class NestedElementsDuplicated
{
    public function __construct(
        public NestedElementManager $manager,
        public ElementInterface $source,
        public ElementInterface $target,

        /** @var array<int, int> $newElementIds Mapping from source element IDs to new element IDs. */
        public array $newElementIds = [],
    ) {}
}
