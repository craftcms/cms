<?php

declare(strict_types=1);

namespace CraftCms\Cms\Element\Queries\Events;

use CraftCms\Cms\Element\Contracts\ElementInterface;

class ElementHydrated
{
    public function __construct(
        public ElementInterface $element,
        public array $row,
    ) {}
}
