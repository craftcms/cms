<?php

declare(strict_types=1);

namespace CraftCms\Cms\Element\Events;

use CraftCms\Cms\Element\Concerns\HasControlPanelUI;
use CraftCms\Cms\Element\Contracts\ElementInterface;

/**
 * @event ElementActionMenuItemsResolving The event that is triggered when defining action menu items.
 *
 * {@see HasControlPanelUI::getActionMenuItems()}
 */
class ElementActionMenuItemsResolving
{
    /** @param list<array<string, mixed>> $items */
    public function __construct(
        public ElementInterface $element,
        public array $items = [],
    ) {}
}
