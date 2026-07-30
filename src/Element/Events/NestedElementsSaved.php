<?php

declare(strict_types=1);

namespace CraftCms\Cms\Element\Events;

use CraftCms\Cms\Element\Contracts\ElementInterface;
use CraftCms\Cms\Element\NestedElementManager;

class NestedElementsSaved
{
    public function __construct(
        public NestedElementManager $manager,
        /** @var list<ElementInterface> $elements */
        public array $elements = [],
    ) {}
}
