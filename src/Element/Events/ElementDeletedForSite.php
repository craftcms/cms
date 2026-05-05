<?php

declare(strict_types=1);

namespace CraftCms\Cms\Element\Events;

use CraftCms\Cms\Element\Contracts\ElementInterface;

class ElementDeletedForSite
{
    public function __construct(
        public ElementInterface $element,
    ) {}
}
