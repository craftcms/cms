<?php

declare(strict_types=1);

namespace CraftCms\Cms\FieldLayout\Events;

use craft\base\ElementInterface;

final class DefineActionMenuItems extends \CraftCms\Cms\Element\Events\DefineActionMenuItems
{
    public function __construct(
        ElementInterface $element,
        array $items = [],
        public bool $static = false,
    ) {
        parent::__construct($element, $items);
    }
}
