<?php

declare(strict_types=1);

namespace CraftCms\Cms\Element\Queries\Events;

use CraftCms\Cms\Element\Contracts\ElementInterface;

class HydratingElement
{
    public function __construct(
        public array $row,
        public ?ElementInterface $element = null,
    ) {}
}
