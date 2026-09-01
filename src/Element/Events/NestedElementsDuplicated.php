<?php

declare(strict_types=1);

namespace CraftCms\Cms\Element\Events;

use CraftCms\Cms\Element\Contracts\ElementInterface;
use CraftCms\Cms\Element\NestedElementManager;

class NestedElementsDuplicated
{
    public function __construct(
        public NestedElementManager $manager,
        public ElementInterface $source,
        public ElementInterface $target,

        /** @var list<int> $newElementIds */
        public array $newElementIds = [],
    ) {}
}
