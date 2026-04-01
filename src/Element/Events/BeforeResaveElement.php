<?php

declare(strict_types=1);

namespace CraftCms\Cms\Element\Events;

use craft\base\ElementInterface;
use CraftCms\Cms\Element\Queries\Contracts\ElementQueryInterface;

class BeforeResaveElement
{
    public function __construct(
        public ElementQueryInterface $query,
        public ElementInterface $element,
        public int $position,
    ) {}
}
