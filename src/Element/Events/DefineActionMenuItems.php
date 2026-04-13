<?php

declare(strict_types=1);

namespace CraftCms\Cms\Element\Events;

use CraftCms\Cms\Element\Concerns\HasControlPanelUI;
use CraftCms\Cms\Element\Contracts\ElementInterface;

/**
 * @event DefineActionMenuItems The event that is triggered when defining action menu items.
 *
 * {@see HasControlPanelUI::getActionMenuItems()}
 */
class DefineActionMenuItems
{
    public function __construct(
        public ElementInterface $element,
        public array $items = [],
    ) {}
}
