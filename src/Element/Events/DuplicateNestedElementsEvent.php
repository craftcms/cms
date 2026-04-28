<?php

declare(strict_types=1);

namespace CraftCms\Cms\Element\Events;

use CraftCms\Cms\Element\Contracts\ElementInterface;
use CraftCms\Cms\Element\NestedElementManager;

class DuplicateNestedElementsEvent
{
    public function __construct(
        public NestedElementManager $manager,
        public ElementInterface $source,
        public ElementInterface $target,

        /** @param  list<int>  $newElementIds */
        public array $newElementIds = [],
    ) {}
}
