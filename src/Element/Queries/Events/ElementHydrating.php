<?php

declare(strict_types=1);

namespace CraftCms\Cms\Element\Queries\Events;

use CraftCms\Cms\Element\Contracts\ElementInterface;

class ElementHydrating
{
    public function __construct(
        public array $row,
        public array $content,
        public ?ElementInterface $element = null,
    ) {}
}
