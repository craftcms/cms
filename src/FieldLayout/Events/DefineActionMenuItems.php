<?php

declare(strict_types=1);

namespace CraftCms\Cms\FieldLayout\Events;

use CraftCms\Cms\Element\Contracts\ElementInterface;
use CraftCms\Cms\Element\Events\ElementActionMenuItemsResolving;

class DefineActionMenuItems extends ElementActionMenuItemsResolving
{
    public function __construct(
        ElementInterface $element,
        array $items = [],
        public bool $static = false,
    ) {
        parent::__construct($element, $items);
    }
}
