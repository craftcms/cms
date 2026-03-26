<?php

declare(strict_types=1);

namespace CraftCms\Cms\Element\Queries\Events;

use craft\base\ElementInterface;

class HydratingElement
{
    public function __construct(
        public array $row,
        public ?ElementInterface $element = null,
    ) {}
}
